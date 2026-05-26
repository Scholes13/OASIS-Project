<?php

namespace App\Services\Modules\Activity;

use App\Models\Core\Department;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Database\Eloquent\Builder;

class ActivityMemberFocusService
{
    /**
     * Get valid team members from active user_business_units assignments
     * for the given BU + department context.
     *
     * When the dept is a root department with active children, members from
     * the descendant departments are included so a manager at a root dept
     * (e.g. GM at S&M) can drill into any sub-dept member without leaving
     * the dashboard.
     *
     * @return array<int, array{id: int, name: string, department_id: int}>
     */
    public function resolveDepartmentMembers(?int $businessUnitId, ?int $departmentId): array
    {
        if ($businessUnitId === null || $departmentId === null) {
            return [];
        }

        $scopeIds = Department::scopeIdsForId($departmentId);

        return UserBusinessUnit::query()
            ->where('business_unit_id', $businessUnitId)
            ->whereIn('department_id', $scopeIds)
            ->where('is_active', true)
            ->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
            ->with('user:id,name,is_active')
            ->get()
            ->filter(fn ($ubu) => $ubu->user !== null)
            ->unique(fn ($ubu) => $ubu->user->id)
            ->sortBy(fn ($ubu) => $ubu->user->name)
            ->map(fn ($ubu) => [
                'id' => $ubu->user->id,
                'name' => $ubu->user->name,
                'department_id' => (int) $ubu->department_id,
            ])
            ->values()
            ->all();
    }

    /**
     * Sanitize requested member_user_id against valid team members.
     * Returns null if invalid or not provided.
     *
     * @param  array<int, array{id: int, name: string}>  $validMembers
     */
    public function sanitizeRequestedMember(mixed $requestedMemberId, array $validMembers): ?int
    {
        if ($requestedMemberId === null || $requestedMemberId === '') {
            return null;
        }

        if (! is_scalar($requestedMemberId)) {
            return null;
        }

        $memberId = filter_var((string) $requestedMemberId, FILTER_VALIDATE_INT);
        if ($memberId === false) {
            return null;
        }

        $validMemberIds = array_column($validMembers, 'id');

        return in_array($memberId, $validMemberIds, true) ? $memberId : null;
    }

    /**
     * Apply member focus predicate to a task query.
     * This is an additive filter on top of existing base scope.
     *
     * Matches tasks where:
     * - created_by = memberUserId
     * - OR task has a participant with user_id = memberUserId
     *
     * If memberUserId is null, returns the query unchanged.
     */
    public function applyMemberFocus(Builder $query, ?int $memberUserId): Builder
    {
        if ($memberUserId === null) {
            return $query;
        }

        return $query->where(function ($q) use ($memberUserId) {
            $q->where('created_by', $memberUserId)
                ->orWhereHas('participants', fn ($participantQuery) => $participantQuery->where('user_id', $memberUserId)
                );
        });
    }
}
