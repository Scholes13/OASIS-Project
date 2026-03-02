<?php

namespace App\Services\Modules\Activity;

use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ActivityTypePrioritizationService
{
    /**
     * Get prioritized activity types for a user
     *
     * Priority order:
     * 1. User favorites (top N by usage history) - only from department's assigned types
     * 2. Department defaults (assigned to user's department, ordered by pivot sort_order)
     *
     * Note: Only shows activity types assigned to user's department (no "others" section)
     * Activity types are filtered via department_activity_types pivot table (Requirements 5.1)
     * and ordered by the department's configured sort_order (Requirements 5.3)
     *
     * @return array{favorites: Collection, department: Collection, others: Collection}
     */
    public function getForUser(User $user, int $favoritesLimit = 3): array
    {
        $departmentId = $user->getCurrentDepartmentId();

        // Get department's assigned activity types ordered by pivot sort_order
        $departmentTypes = $this->getDepartmentActivityTypes($departmentId);
        $departmentIds = $departmentTypes->pluck('id');

        // Get user's most used activity types (only from department's assigned types)
        $favoriteIds = $this->getUserFavoriteIds($user->id, $favoritesLimit)
            ->intersect($departmentIds);

        // Department types excluding favorites, preserving pivot sort_order
        $departmentOnlyTypes = $departmentTypes->filter(fn ($type) => ! $favoriteIds->contains($type->id));

        // Get favorites in usage order (most used first)
        $favoriteTypes = $favoriteIds->map(fn ($id) => $departmentTypes->firstWhere('id', $id))->filter();

        return [
            'favorites' => $favoriteTypes->values(),
            'department' => $departmentOnlyTypes->values(),
            'others' => collect(), // Empty - only show department's activity types
        ];
    }

    /**
     * Get flat list of activity types with priority flag
     * Useful for simpler dropdown implementations
     * Note: Only includes department's assigned activity types
     */
    public function getFlatListForUser(User $user, int $favoritesLimit = 3): Collection
    {
        $prioritized = $this->getForUser($user, $favoritesLimit);

        return collect()
            ->merge($prioritized['favorites']->map(fn ($t) => $this->addPriority($t, 'favorite')))
            ->merge($prioritized['department']->map(fn ($t) => $this->addPriority($t, 'department')));
    }

    /**
     * Get user's most frequently used activity type IDs
     */
    protected function getUserFavoriteIds(int $userId, int $limit): Collection
    {
        return EmployeeTask::where('created_by', $userId)
            ->whereNotNull('activity_type_id')
            ->select('activity_type_id', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('activity_type_id')
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->pluck('activity_type_id');
    }

    /**
     * Get activity types assigned to a department, ordered by pivot sort_order
     *
     * Queries via department_activity_types pivot table (Requirements 5.1)
     * Orders by department's configured sort_order (Requirements 5.3)
     */
    protected function getDepartmentActivityTypes(?int $departmentId): Collection
    {
        if (! $departmentId) {
            return collect();
        }

        $department = Department::find($departmentId);
        if (! $department) {
            return collect();
        }

        return $department->activeActivityTypes()
            ->with(['subActivities' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->get();
    }

    /**
     * Get activity type IDs assigned to a department (for backward compatibility)
     *
     * @deprecated Use getDepartmentActivityTypes() instead for proper ordering
     */
    protected function getDepartmentActivityTypeIds(?int $departmentId): Collection
    {
        return $this->getDepartmentActivityTypes($departmentId)->pluck('id');
    }

    /**
     * Add priority metadata to activity type
     */
    protected function addPriority(ActivityType $type, string $priority): ActivityType
    {
        $type->setAttribute('priority', $priority);

        return $type;
    }
}
