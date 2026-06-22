<?php

namespace App\Services\Modules\Activity;

use App\Models\Core\DepartmentActivityType;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\EmployeeTask;
use App\Models\Modules\Activity\SubActivity;
use App\Services\Modules\Activity\Migration\ActivityTypeConsolidator;
use App\Services\Modules\Activity\Migration\SubActivityConsolidator;
use App\Services\Modules\Activity\Migration\TaskReferenceUpdater;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates migration of Activity Types from per-department architecture
 * to a global master architecture.
 *
 * Heavy lifting is delegated to focused helpers under the
 * {@see \App\Services\Modules\Activity\Migration} namespace; this class
 * keeps transactional control, result aggregation and the public API
 * consumed by {@see \App\Console\Commands\MigrateActivityTypesCommand}.
 */
class ActivityTypeMigrationService
{
    /**
     * Aggregated migration results.
     *
     * @var array<string, mixed>
     */
    protected array $results = [
        'activity_types' => [
            'consolidated' => 0,
            'created' => 0,
            'skipped' => 0,
            'errors' => [],
        ],
        'sub_activities' => [
            'consolidated' => 0,
            'created' => 0,
            'skipped' => 0,
            'errors' => [],
        ],
        'tasks_updated' => 0,
        'department_assignments' => 0,
        'orphans_removed' => 0,
    ];

    /** @var array<int, int> Mapping of old activity type IDs to new master IDs */
    protected array $activityTypeMapping = [];

    /** @var array<int, int> Mapping of old sub activity IDs to new master IDs */
    protected array $subActivityMapping = [];

    public function __construct(
        protected ActivityTypeConsolidator $activityTypeConsolidator,
        protected SubActivityConsolidator $subActivityConsolidator,
        protected TaskReferenceUpdater $taskReferenceUpdater,
    ) {}

    /**
     * Run the full migration process.
     *
     * @param  bool  $dryRun  If true, rollback the transaction at the end.
     * @return array<string, mixed> Migration results.
     */
    public function migrate(bool $dryRun = false): array
    {
        Log::info('Starting Activity Type Migration', ['dry_run' => $dryRun]);

        try {
            DB::beginTransaction();

            $this->consolidateActivityTypes();
            $this->consolidateSubActivities();
            $this->updateTaskReferences($this->activityTypeMapping, $this->subActivityMapping);
            $this->cleanupOrphans();

            if ($dryRun) {
                DB::rollBack();
                Log::info('Activity Type Migration completed (dry run)', $this->results);
            } else {
                DB::commit();
                Log::info('Activity Type Migration completed successfully', $this->results);
            }

            return $this->results;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Activity Type Migration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Consolidate prefixed activity types into master records.
     *
     * @return array<int, int> Mapping of old IDs to new master IDs.
     */
    public function consolidateActivityTypes(): array
    {
        $outcome = $this->activityTypeConsolidator->consolidate();

        $this->activityTypeMapping = $outcome['mapping'];
        $stats = $outcome['stats'];

        $this->results['activity_types']['created'] = $stats['created'];
        $this->results['activity_types']['consolidated'] = $stats['consolidated'];
        $this->results['activity_types']['skipped'] = $stats['skipped'];
        $this->results['activity_types']['errors'] = $stats['errors'];
        $this->results['department_assignments'] += $stats['department_assignments'];

        return $this->activityTypeMapping;
    }

    /**
     * Consolidate prefixed sub activities into master records.
     *
     * @return array<int, int> Mapping of old IDs to new master IDs.
     */
    public function consolidateSubActivities(): array
    {
        $outcome = $this->subActivityConsolidator->consolidate();

        $this->subActivityMapping = $outcome['mapping'];
        $stats = $outcome['stats'];

        $this->results['sub_activities']['created'] = $stats['created'];
        $this->results['sub_activities']['consolidated'] = $stats['consolidated'];
        $this->results['sub_activities']['skipped'] = $stats['skipped'];
        $this->results['sub_activities']['errors'] = $stats['errors'];

        return $this->subActivityMapping;
    }

    /**
     * Repoint employee tasks at the new master ids.
     *
     * @param  array<int, int>  $activityTypeMapping
     * @param  array<int, int>  $subActivityMapping
     */
    public function updateTaskReferences(array $activityTypeMapping, array $subActivityMapping): int
    {
        $tasksUpdated = $this->taskReferenceUpdater->updateTaskReferences(
            $activityTypeMapping,
            $subActivityMapping
        );

        $this->results['tasks_updated'] = $tasksUpdated;

        return $tasksUpdated;
    }

    /**
     * Clean up orphaned activity types and sub activities after consolidation.
     */
    public function cleanupOrphans(): int
    {
        $orphansRemoved = $this->taskReferenceUpdater->cleanupOrphans(
            $this->activityTypeMapping,
            $this->subActivityMapping
        );

        $this->results['orphans_removed'] = $orphansRemoved;

        return $orphansRemoved;
    }

    /**
     * @return array<int, int>
     */
    public function getActivityTypeMapping(): array
    {
        return $this->activityTypeMapping;
    }

    /**
     * @return array<int, int>
     */
    public function getSubActivityMapping(): array
    {
        return $this->subActivityMapping;
    }

    /**
     * @return array<string, mixed>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Preview what the migration would do without making changes.
     *
     * @return array<string, mixed>
     */
    public function preview(): array
    {
        $activityTypesByName = ActivityType::all()->groupBy('name');

        $preview = [
            'activity_types' => [],
            'sub_activities' => [],
            'affected_tasks' => 0,
            'affected_departments' => 0,
        ];

        foreach ($activityTypesByName as $name => $types) {
            if ($types->count() > 1) {
                $preview['activity_types'][] = [
                    'name' => $name,
                    'current_codes' => $types->pluck('code')->toArray(),
                    'new_code' => $this->activityTypeConsolidator->generateMasterCode($name),
                    'count' => $types->count(),
                ];

                $typeIds = $types->pluck('id')->toArray();
                $preview['affected_tasks'] += EmployeeTask::whereIn('activity_type_id', $typeIds)->count();
                $preview['affected_departments'] += DepartmentActivityType::whereIn('activity_type_id', $typeIds)->count();
            }
        }

        $subActivities = SubActivity::with('activityType')->get();
        $grouped = $subActivities->groupBy(function ($sub): string {
            return ($sub->activityType->name ?? 'Unknown').'::'.$sub->name;
        });

        foreach ($grouped as $key => $subs) {
            if ($subs->count() > 1) {
                [$activityTypeName, $subName] = explode('::', $key, 2);
                $preview['sub_activities'][] = [
                    'activity_type' => $activityTypeName,
                    'name' => $subName,
                    'current_codes' => $subs->pluck('code')->toArray(),
                    'count' => $subs->count(),
                ];
            }
        }

        return $preview;
    }
}
