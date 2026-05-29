<?php

namespace App\Services\Modules\Activity\Migration;

use App\Models\Core\DepartmentActivityType;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\EmployeeTask;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Support\Facades\Log;

/**
 * Update employee_task references to point at the new master activity type
 * and sub activity ids, then clean up the orphaned prefixed records when
 * they are no longer referenced anywhere.
 */
class TaskReferenceUpdater
{
    /**
     * Repoint employee tasks at the new master ids.
     *
     * @param  array<int, int>  $activityTypeMapping  old activity type id => new master id
     * @param  array<int, int>  $subActivityMapping  old sub activity id => new master id
     * @return int Number of tasks repointed for activity_type_id
     */
    public function updateTaskReferences(array $activityTypeMapping, array $subActivityMapping): int
    {
        Log::info('Updating task references...', [
            'activity_type_mappings' => count($activityTypeMapping),
            'sub_activity_mappings' => count($subActivityMapping),
        ]);

        $tasksUpdated = 0;

        foreach ($activityTypeMapping as $oldId => $newId) {
            $tasksUpdated += EmployeeTask::where('activity_type_id', $oldId)
                ->update(['activity_type_id' => $newId]);
        }

        foreach ($subActivityMapping as $oldId => $newId) {
            EmployeeTask::where('sub_activity_id', $oldId)
                ->update(['sub_activity_id' => $newId]);
            // Don't double count if same task had both updated.
        }

        Log::info('Task references updated', ['count' => $tasksUpdated]);

        return $tasksUpdated;
    }

    /**
     * Remove prefixed records that are no longer referenced anywhere.
     *
     * @param  array<int, int>  $activityTypeMapping  old activity type id => new master id
     * @param  array<int, int>  $subActivityMapping  old sub activity id => new master id
     */
    public function cleanupOrphans(array $activityTypeMapping, array $subActivityMapping): int
    {
        Log::info('Cleaning up orphaned records...');

        $orphansRemoved = 0;

        foreach ($subActivityMapping as $oldId => $newId) {
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

        foreach ($activityTypeMapping as $oldId => $newId) {
            $taskCount = EmployeeTask::where('activity_type_id', $oldId)->count();
            $subActivityCount = SubActivity::where('activity_type_id', $oldId)->count();

            if ($taskCount === 0 && $subActivityCount === 0) {
                DepartmentActivityType::where('activity_type_id', $oldId)->delete();
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

        Log::info('Orphan cleanup complete', ['removed' => $orphansRemoved]);

        return $orphansRemoved;
    }
}
