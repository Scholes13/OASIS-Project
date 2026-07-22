<?php

namespace App\Services\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\Purchasing\Shared\RequestFormDataProvider;
use Illuminate\Support\Collection;

class PurchaseRequestFormDataService
{
    public function create(User $user, int $businessUnitId, int $departmentId, RequestFormDataProvider $provider): array
    {
        return [
            'categories' => $provider->getPrCategories($businessUnitId),
            'departments' => $provider->getAccessibleDepartments($user, $businessUnitId),
            'businessUnits' => $this->businessUnits($user),
            'availableApprovers' => $provider->getAvailableApprovers(
                $user,
                $businessUnitId,
                excludeSuperAdmin: true,
            ),
            'currentBusinessUnitId' => $businessUnitId,
            'currentDepartmentId' => $departmentId,
        ];
    }

    public function edit(User $user, PurchaseRequest $purchaseRequest, RequestFormDataProvider $provider): array
    {
        $businessUnitId = $purchaseRequest->business_unit_id;
        $purchaseRequest->load([
            'items.expenseDepartment:id,name,code',
            'category:id,name,code,color',
            'approvals.approver:id,name,email',
        ]);

        $approvalWorkflow = $purchaseRequest->approvals->map(fn ($approval) => [
            'approver_id' => $approval->approver_id,
            'task_type' => $approval->approval_type ?? 'approval',
        ])->toArray();

        return [
            'mode' => 'edit',
            'purchaseRequest' => array_merge($purchaseRequest->toArray(), [
                'approval_workflow' => $approvalWorkflow,
            ]),
            'categories' => $provider->getPrCategories($businessUnitId),
            'departments' => $provider->getAccessibleDepartments($user, $businessUnitId),
            'businessUnits' => $this->businessUnits($user),
            'availableApprovers' => $provider->getAvailableApprovers($user, $businessUnitId),
            'currentBusinessUnitId' => $businessUnitId,
            'currentDepartmentId' => $purchaseRequest->department_id,
        ];
    }

    private function businessUnits(User $user): Collection
    {
        return $user->activeBusinessUnits()
            ->with('businessUnit:id,name,code')
            ->get()
            ->pluck('businessUnit')
            ->filter();
    }
}
