<?php

namespace App\Services\Modules\Purchasing\StockRequest;

use App\Models\Core\Department;
use App\Models\Core\User;
use App\Services\Core\UserAccessResolver;
use Illuminate\Support\Collection;

class StockRequestApprovalWorkflowResolver
{
    public function __construct(
        private UserAccessResolver $userAccessResolver,
    ) {}

    /**
     * @return array<int, array{approver_id: int, task_type: string}>
     */
    public function resolve(
        User $user,
        int $businessUnitId,
        int $departmentId,
        ?bool $routesDirectlyToPurchasing = null,
    ): array {
        $accessLevel = $this->userAccessResolver
            ->getAccessLevelInDepartment($user, $businessUnitId, $departmentId);

        if ($accessLevel === null) {
            throw new \DomainException('Active department assignment is required for stock requests.');
        }

        $routesDirectlyToPurchasing ??= $this->routesDirectlyToPurchasing($businessUnitId, $departmentId);

        if ($routesDirectlyToPurchasing) {
            return [];
        }

        if ($accessLevel !== 'staff') {
            return [];
        }

        $approvers = $this->resolveStaffApprovers($user, $businessUnitId, $departmentId);

        if ($approvers->isEmpty()) {
            throw new \DomainException('HOD or Leader approver is required for staff stock requests.');
        }

        return $approvers
            ->map(fn (User $approver) => [
                'approver_id' => $approver->id,
                'task_type' => 'department_lead',
            ])
            ->values()
            ->all();
    }

    public function routesDirectlyToPurchasing(int $businessUnitId, int $departmentId): bool
    {
        return Department::query()
            ->whereKey($departmentId)
            ->where('business_unit_id', $businessUnitId)
            ->where('is_ga_stock_review_department', true)
            ->exists();
    }

    /** @return Collection<int, User> */
    private function resolveStaffApprovers(User $user, int $businessUnitId, int $departmentId): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->whereHas('activeBusinessUnits', function ($query) use ($businessUnitId, $departmentId) {
                $query->where('business_unit_id', $businessUnitId)
                    ->where('department_id', $departmentId)
                    ->whereHas('position', function ($positionQuery) {
                        $positionQuery->whereIn('level', ['leader', 'hod'])
                            ->orWhereIn('access_level', ['team_leader', 'department_head']);
                    });
            })
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$user->supervisor_id ?? 0])
            ->orderBy('name')
            ->get();
    }
}
