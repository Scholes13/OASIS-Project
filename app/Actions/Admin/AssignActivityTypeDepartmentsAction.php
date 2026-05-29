<?php

namespace App\Actions\Admin;

use App\Models\Core\DepartmentActivityType;
use App\Models\Modules\Activity\ActivityType;
use Illuminate\Support\Facades\DB;

/**
 * Encapsulates the super-admin pivot-write workflow extracted from
 * {@see \App\Http\Controllers\Admin\ActivityTypeController::assignDepartments()}.
 *
 * For each department, skip if the assignment already exists. Otherwise the
 * sort_order is appended after the existing maximum and (when {@see $isDefault}
 * is true) any prior defaults for that department are unset before the new
 * pivot row is created.
 */
class AssignActivityTypeDepartmentsAction
{
    /**
     * @param  array<int, int>  $departmentIds
     * @return int number of departments processed (matches the legacy success message)
     */
    public function execute(ActivityType $activityType, array $departmentIds, bool $isDefault): int
    {
        DB::transaction(function () use ($activityType, $departmentIds, $isDefault): void {
            foreach ($departmentIds as $departmentId) {
                $exists = DepartmentActivityType::where('department_id', $departmentId)
                    ->where('activity_type_id', $activityType->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                if ($isDefault) {
                    DepartmentActivityType::where('department_id', $departmentId)
                        ->update(['is_default' => false]);
                }

                $maxSortOrder = DepartmentActivityType::where('department_id', $departmentId)
                    ->max('sort_order') ?? 0;

                DepartmentActivityType::create([
                    'department_id' => $departmentId,
                    'activity_type_id' => $activityType->id,
                    'is_default' => $isDefault,
                    'sort_order' => $maxSortOrder + 1,
                ]);
            }
        });

        return count($departmentIds);
    }
}
