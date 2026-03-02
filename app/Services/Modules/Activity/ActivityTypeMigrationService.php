<?php

namespace App\Services\Modules\Activity;

use App\Models\Core\DepartmentActivityType;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\EmployeeTask;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service untuk migrasi Activity Types dari arsitektur per-department
 * ke arsitektur master global.
 *
 * Handles:
 * - Consolidating prefixed activity types (ACC_LEAVE, BAS_LEAVE) to global (LEAVE)
 * - Consolidating prefixed sub activities
 * - Updating task references to new master IDs
 * - Preserving department assignments in pivot table
 * - Cleaning up orphaned records
 */
class ActivityTypeMigrationService
{
    /**
     * Results tracking for migration operations
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

    /**
     * Mapping of old activity type IDs to new master IDs
     */
    protected array $activityTypeMapping = [];

    /**
     * Mapping of old sub activity IDs to new master IDs
     */
    protected array $subActivityMapping = [];

    /**
     * Run the full migration process
     *
     * @param  bool  $dryRun  If true, don't commit changes
     * @return array Migration results
     */
    public function migrate(bool $dryRun = false): array
    {
        Log::info('Starting Activity Type Migration', ['dry_run' => $dryRun]);

        try {
            DB::beginTransaction();

            // Step 1: Consolidate activity types
            $this->consolidateActivityTypes();

            // Step 2: Consolidate sub activities
            $this->consolidateSubActivities();

            // Step 3: Update task references
            $this->updateTaskReferences($this->activityTypeMapping, $this->subActivityMapping);

            // Step 4: Cleanup orphans
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
     * Consolidate prefixed activity types to global master records.
     *
     * Identifies unique activity types by name (ignoring prefix like ACC_, BAS_),
     * creates one master record per unique name, and builds a mapping of old IDs
     * to new master IDs.
     *
     * @return array Mapping of old IDs to new master IDs
     */
    public function consolidateActivityTypes(): array
    {
        Log::info('Consolidating activity types...');

        // Get all activity types grouped by name
        $activityTypesByName = ActivityType::all()
            ->groupBy('name');

        foreach ($activityTypesByName as $name => $activityTypes) {
            try {
                // Find or create the master record (without prefix)
                $masterCode = $this->generateMasterCode($name);

                // Check if a master record already exists (no prefix pattern)
                $existingMaster = ActivityType::where('code', $masterCode)->first();

                if ($existingMaster) {
                    // Use existing master
                    $masterId = $existingMaster->id;
                    $this->results['activity_types']['skipped']++;
                } else {
                    // Get the first record's properties as template
                    $template = $activityTypes->first();

                    // Create new master record
                    $master = ActivityType::create([
                        'code' => $masterCode,
                        'name' => $name,
                        'color' => $template->color,
                        'is_active' => true,
                        'sort_order' => $template->sort_order,
                    ]);

                    $masterId = $master->id;
                    $this->results['activity_types']['created']++;
                }

                // Map all old IDs to the master ID
                foreach ($activityTypes as $activityType) {
                    if ($activityType->id !== $masterId) {
                        $this->activityTypeMapping[$activityType->id] = $masterId;
                        $this->results['activity_types']['consolidated']++;

                        // Preserve department assignments
                        $this->preserveDepartmentAssignments($activityType->id, $masterId);
                    }
                }
            } catch (\Exception $e) {
                $this->results['activity_types']['errors'][] = [
                    'name' => $name,
                    'error' => $e->getMessage(),
                ];
                Log::warning('Failed to consolidate activity type', [
                    'name' => $name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Activity types consolidation complete', [
            'created' => $this->results['activity_types']['created'],
            'consolidated' => $this->results['activity_types']['consolidated'],
            'skipped' => $this->results['activity_types']['skipped'],
        ]);

        return $this->activityTypeMapping;
    }

    /**
     * Consolidate prefixed sub activities to global master records.
     *
     * Similar to activity types, identifies unique sub activities by name
     * within each activity type, creates master records, and builds mapping.
     *
     * @return array Mapping of old IDs to new master IDs
     */
    public function consolidateSubActivities(): array
    {
        Log::info('Consolidating sub activities...');

        // Get all sub activities with their activity type
        $subActivities = SubActivity::with('activityType')->get();

        // Group by activity type name + sub activity name
        $grouped = $subActivities->groupBy(function ($subActivity) {
            $activityTypeName = $subActivity->activityType->name ?? 'Unknown';

            return $activityTypeName.'::'.$subActivity->name;
        });

        foreach ($grouped as $key => $subActivityGroup) {
            try {
                [$activityTypeName, $subActivityName] = explode('::', $key, 2);

                // Find the master activity type for this sub activity
                $masterActivityType = ActivityType::where('name', $activityTypeName)
                    ->whereRaw("code NOT LIKE '%\\_%'") // No underscore prefix pattern
                    ->first();

                if (! $masterActivityType) {
                    // Try to find by generated master code
                    $masterCode = $this->generateMasterCode($activityTypeName);
                    $masterActivityType = ActivityType::where('code', $masterCode)->first();
                }

                if (! $masterActivityType) {
                    $this->results['sub_activities']['errors'][] = [
                        'name' => $subActivityName,
                        'error' => "Master activity type not found for: {$activityTypeName}",
                    ];

                    continue;
                }

                // Generate master code for sub activity
                $masterSubCode = $this->generateSubActivityMasterCode(
                    $masterActivityType->code,
                    $subActivityName
                );

                // Check if master sub activity exists
                $existingMaster = SubActivity::where('activity_type_id', $masterActivityType->id)
                    ->where('code', $masterSubCode)
                    ->first();

                if ($existingMaster) {
                    $masterId = $existingMaster->id;
                    $this->results['sub_activities']['skipped']++;
                } else {
                    // Get template from first record
                    $template = $subActivityGroup->first();

                    // Create master sub activity
                    $master = SubActivity::create([
                        'activity_type_id' => $masterActivityType->id,
                        'code' => $masterSubCode,
                        'name' => $subActivityName,
                        'is_active' => true,
                        'sort_order' => $template->sort_order,
                    ]);

                    $masterId = $master->id;
                    $this->results['sub_activities']['created']++;
                }

                // Map all old IDs to master ID
                foreach ($subActivityGroup as $subActivity) {
                    if ($subActivity->id !== $masterId) {
                        $this->subActivityMapping[$subActivity->id] = $masterId;
                        $this->results['sub_activities']['consolidated']++;
                    }
                }
            } catch (\Exception $e) {
                $this->results['sub_activities']['errors'][] = [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ];
                Log::warning('Failed to consolidate sub activity', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Sub activities consolidation complete', [
            'created' => $this->results['sub_activities']['created'],
            'consolidated' => $this->results['sub_activities']['consolidated'],
            'skipped' => $this->results['sub_activities']['skipped'],
        ]);

        return $this->subActivityMapping;
    }

    /**
     * Update all employee task references to point to new master IDs.
     *
     * @param  array  $activityTypeMapping  Old activity type ID => New master ID
     * @param  array  $subActivityMapping  Old sub activity ID => New master ID
     * @return int Number of tasks updated
     */
    public function updateTaskReferences(array $activityTypeMapping, array $subActivityMapping): int
    {
        Log::info('Updating task references...', [
            'activity_type_mappings' => count($activityTypeMapping),
            'sub_activity_mappings' => count($subActivityMapping),
        ]);

        $tasksUpdated = 0;

        // Update activity type references
        foreach ($activityTypeMapping as $oldId => $newId) {
            $updated = EmployeeTask::where('activity_type_id', $oldId)
                ->update(['activity_type_id' => $newId]);
            $tasksUpdated += $updated;
        }

        // Update sub activity references
        foreach ($subActivityMapping as $oldId => $newId) {
            $updated = EmployeeTask::where('sub_activity_id', $oldId)
                ->update(['sub_activity_id' => $newId]);
            // Don't double count if same task had both updated
        }

        $this->results['tasks_updated'] = $tasksUpdated;

        Log::info('Task references updated', ['count' => $tasksUpdated]);

        return $tasksUpdated;
    }

    /**
     * Clean up orphaned activity types and sub activities after consolidation.
     *
     * Removes old prefixed records that have been consolidated into master records.
     * Only removes records that:
     * - Have no remaining task references
     * - Have been mapped to a master record
     *
     * @return int Number of orphans removed
     */
    public function cleanupOrphans(): int
    {
        Log::info('Cleaning up orphaned records...');

        $orphansRemoved = 0;

        // Remove orphaned sub activities first (due to foreign key)
        foreach ($this->subActivityMapping as $oldId => $newId) {
            // Check if any tasks still reference this sub activity
            $taskCount = EmployeeTask::where('sub_activity_id', $oldId)->count();

            if ($taskCount === 0) {
                SubActivity::where('id', $oldId)->delete();
                $orphansRemoved++;
            } else {
                Log::warning('Cannot remove sub activity - still has tasks', [
                    'sub_activity_id' => $oldId,
                    'task_count' => $taskCount,
                ]);
            }
        }

        // Remove orphaned activity types
        foreach ($this->activityTypeMapping as $oldId => $newId) {
            // Check if any tasks still reference this activity type
            $taskCount = EmployeeTask::where('activity_type_id', $oldId)->count();

            // Check if any sub activities still reference this activity type
            $subActivityCount = SubActivity::where('activity_type_id', $oldId)->count();

            if ($taskCount === 0 && $subActivityCount === 0) {
                // Remove department assignments first
                DepartmentActivityType::where('activity_type_id', $oldId)->delete();

                // Remove the activity type
                ActivityType::where('id', $oldId)->delete();
                $orphansRemoved++;
            } else {
                Log::warning('Cannot remove activity type - still has references', [
                    'activity_type_id' => $oldId,
                    'task_count' => $taskCount,
                    'sub_activity_count' => $subActivityCount,
                ]);
            }
        }

        $this->results['orphans_removed'] = $orphansRemoved;

        Log::info('Orphan cleanup complete', ['removed' => $orphansRemoved]);

        return $orphansRemoved;
    }

    /**
     * Preserve department assignments when consolidating activity types.
     *
     * Copies department assignments from old activity type to master,
     * avoiding duplicates.
     */
    protected function preserveDepartmentAssignments(int $oldActivityTypeId, int $masterActivityTypeId): void
    {
        // Get existing assignments for the old activity type
        $oldAssignments = DepartmentActivityType::where('activity_type_id', $oldActivityTypeId)->get();

        foreach ($oldAssignments as $assignment) {
            // Check if assignment already exists for master
            $exists = DepartmentActivityType::where('department_id', $assignment->department_id)
                ->where('activity_type_id', $masterActivityTypeId)
                ->exists();

            if (! $exists) {
                DepartmentActivityType::create([
                    'department_id' => $assignment->department_id,
                    'activity_type_id' => $masterActivityTypeId,
                    'is_default' => $assignment->is_default,
                    'sort_order' => $assignment->sort_order,
                ]);
                $this->results['department_assignments']++;
            }
        }
    }

    /**
     * Generate a master code from activity type name.
     *
     * Converts name to uppercase snake_case without department prefix.
     * Example: "Leave" => "LEAVE", "Action Plan" => "ACTION_PLAN"
     */
    protected function generateMasterCode(string $name): string
    {
        // Remove special characters and normalize
        $cleanName = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
        $cleanName = preg_replace('/\s+/', ' ', trim($cleanName));

        return Str::upper(Str::snake($cleanName));
    }

    /**
     * Generate a master code for sub activity.
     *
     * Format: {ACTIVITY_TYPE_CODE}_{SUB_ACTIVITY_NAME}
     * Example: "LEAVE" + "Sick" => "LEAVE_SICK"
     */
    protected function generateSubActivityMasterCode(string $activityTypeCode, string $subActivityName): string
    {
        // Clean up the sub activity name
        $cleanName = $subActivityName;

        // Remove parent activity type name if it's a prefix (e.g., "Leave – Sick" => "Sick")
        if (Str::contains($cleanName, '–')) {
            $parts = explode('–', $cleanName);
            $cleanName = trim(end($parts));
        } elseif (Str::contains($cleanName, '-')) {
            $parts = explode('-', $cleanName);
            if (count($parts) > 1) {
                $cleanName = trim(end($parts));
            }
        }

        // Remove special characters and normalize
        $cleanName = preg_replace('/[^a-zA-Z0-9\s]/', '', $cleanName);
        $cleanName = preg_replace('/\s+/', ' ', trim($cleanName));

        $subCode = Str::upper(Str::snake($cleanName));

        // Truncate if too long (code column is varchar(50))
        $maxTotalLength = 50;
        $maxSubCodeLength = $maxTotalLength - strlen($activityTypeCode) - 1; // -1 for underscore

        if (strlen($subCode) > $maxSubCodeLength) {
            $subCode = substr($subCode, 0, $maxSubCodeLength);
            // Remove trailing underscore if any
            $subCode = rtrim($subCode, '_');
        }

        return $activityTypeCode.'_'.$subCode;
    }

    /**
     * Get the activity type mapping (old ID => new master ID)
     */
    public function getActivityTypeMapping(): array
    {
        return $this->activityTypeMapping;
    }

    /**
     * Get the sub activity mapping (old ID => new master ID)
     */
    public function getSubActivityMapping(): array
    {
        return $this->subActivityMapping;
    }

    /**
     * Get migration results
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Preview what the migration would do without making changes.
     *
     * @return array Preview data
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
                    'new_code' => $this->generateMasterCode($name),
                    'count' => $types->count(),
                ];

                // Count affected tasks
                $typeIds = $types->pluck('id')->toArray();
                $preview['affected_tasks'] += EmployeeTask::whereIn('activity_type_id', $typeIds)->count();

                // Count affected department assignments
                $preview['affected_departments'] += DepartmentActivityType::whereIn('activity_type_id', $typeIds)->count();
            }
        }

        // Preview sub activities
        $subActivities = SubActivity::with('activityType')->get();
        $grouped = $subActivities->groupBy(function ($sub) {
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
