<?php

namespace App\Services\Modules\Activity\Migration;

use App\Models\Core\DepartmentActivityType;
use App\Models\Modules\Activity\ActivityType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Consolidate prefixed activity types (ACC_LEAVE, BAS_LEAVE) into global
 * master records (LEAVE) and preserve the department assignments that
 * pointed at the prefixed records.
 */
class ActivityTypeConsolidator
{
    /**
     * Consolidate prefixed activity types into master records.
     *
     * @return array{
     *     mapping: array<int, int>,
     *     stats: array{
     *         created: int,
     *         consolidated: int,
     *         skipped: int,
     *         errors: array<int, array<string, mixed>>,
     *         department_assignments: int
     *     }
     * }
     */
    public function consolidate(): array
    {
        Log::info('Consolidating activity types...');

        $mapping = [];
        $stats = [
            'created' => 0,
            'consolidated' => 0,
            'skipped' => 0,
            'errors' => [],
            'department_assignments' => 0,
        ];

        $activityTypesByName = ActivityType::all()->groupBy('name');

        foreach ($activityTypesByName as $name => $activityTypes) {
            try {
                $masterCode = $this->generateMasterCode($name);
                $existingMaster = ActivityType::where('code', $masterCode)->first();

                if ($existingMaster) {
                    $masterId = $existingMaster->id;
                    $stats['skipped']++;
                } else {
                    $template = $activityTypes->first();
                    $master = ActivityType::create([
                        'code' => $masterCode,
                        'name' => $name,
                        'color' => $template->color,
                        'is_active' => true,
                        'sort_order' => $template->sort_order,
                    ]);
                    $masterId = $master->id;
                    $stats['created']++;
                }

                foreach ($activityTypes as $activityType) {
                    if ($activityType->id !== $masterId) {
                        $mapping[$activityType->id] = $masterId;
                        $stats['consolidated']++;
                        $stats['department_assignments'] += $this->preserveDepartmentAssignments(
                            $activityType->id,
                            $masterId
                        );
                    }
                }
            } catch (\Exception $e) {
                $stats['errors'][] = [
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
            'created' => $stats['created'],
            'consolidated' => $stats['consolidated'],
            'skipped' => $stats['skipped'],
        ]);

        return ['mapping' => $mapping, 'stats' => $stats];
    }

    /**
     * Generate a master code from activity type name.
     *
     * Converts name to uppercase snake_case without department prefix.
     * Example: "Leave" => "LEAVE", "Action Plan" => "ACTION_PLAN"
     */
    public function generateMasterCode(string $name): string
    {
        $cleanName = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
        $cleanName = preg_replace('/\s+/', ' ', trim($cleanName));

        return Str::upper(Str::snake($cleanName));
    }

    /**
     * Copy department assignments from old activity type to master,
     * avoiding duplicates. Returns the number of assignments created.
     */
    protected function preserveDepartmentAssignments(int $oldActivityTypeId, int $masterActivityTypeId): int
    {
        $count = 0;
        $oldAssignments = DepartmentActivityType::where('activity_type_id', $oldActivityTypeId)->get();

        foreach ($oldAssignments as $assignment) {
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
                $count++;
            }
        }

        return $count;
    }
}
