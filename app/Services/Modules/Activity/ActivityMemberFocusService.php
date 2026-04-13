<?php

namespace App\Services\Modules\Activity;

use App\Models\Core\UserBusinessUnit;
use Illuminate\Database\Eloquent\Builder;

class ActivityMemberFocusService
{
    /**
     * Get valid team members from active user_business_units assignments
     * for the given BU + department context.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function resolveDepartmentMembers(?int $businessUnitId, ?int $departmentId): array
    {
        if ($businessUnitId === null || $departmentId === null) {
            return [];
        }

        return UserBusinessUnit::query()
            ->where('business_unit_id', $businessUnitId)
            ->where('department_id', $departmentId)
            ->where('is_active', true)
            ->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
            ->with('user:id,name,is_active')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
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
