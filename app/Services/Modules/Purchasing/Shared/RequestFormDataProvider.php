<?php

namespace App\Services\Modules\Purchasing\Shared;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PrCategory;
use Illuminate\Support\Collection;

/**
 * Provides shared form data (available approvers, departments,
 * categories) for both Purchase Request and Stock Request create/edit
 * Inertia screens.
 *
 * Extracted from the duplicated builder logic that previously lived
 * inside `PurchaseRequestController::create` / `::editInertia` and
 * `StockRequestController::createInertia` / `::editInertia`.
 *
 * Behaviour parity:
 *  - approver pool walks the BU parent chain with cycle detection
 *    (matches the original loop)
 *  - approver list excludes the current user; super-admin exclusion is
 *    opt-in via `$excludeSuperAdmin` because only `PurchaseRequest::create`
 *    historically filtered them out — preserved exactly as-is
 *  - department list is BU-scoped, active-only, ordered by name, with
 *    the same `id, name, code` projection
 *  - category list returns active+ordered PR categories with the same
 *    `id, name, code, color, description` projection; for ST callers
 *    the helper is unused (kept for API symmetry)
 */
class RequestFormDataProvider
{
    /**
     * Get available approvers for the user's BU and ancestors.
     *
     * @return Collection<int, array{id:int,name:string,email:string,position:string,department:string}>
     */
    public function getAvailableApprovers(
        User $user,
        int $businessUnitId,
        bool $excludeSuperAdmin = false,
    ): Collection {
        $approverBusinessUnitIds = $this->collectBusinessUnitChain($businessUnitId);

        $query = User::whereHas('activeBusinessUnits', function ($query) use ($approverBusinessUnitIds) {
            $query->whereIn('business_unit_id', $approverBusinessUnitIds);
        })
            ->with(['primaryPosition:id,name', 'primaryDepartment:id,name'])
            ->where('id', '!=', $user->id)
            ->orderBy('name');

        if ($excludeSuperAdmin) {
            // Match the historical PR `create` behaviour:
            // exclude system admin accounts from approver picker.
            $query->where('global_role', '!=', 'super_admin');
        }

        return $query
            ->get(['id', 'name', 'email', 'primary_position_id', 'primary_department_id'])
            ->map(function ($approver) {
                return [
                    'id' => $approver->id,
                    'name' => $approver->name,
                    'email' => $approver->email,
                    'position' => $approver->primaryPosition?->name ?? 'N/A',
                    'department' => $approver->primaryDepartment?->name ?? 'N/A',
                ];
            });
    }

    /**
     * Get departments accessible to the user in this BU
     * (active + BU-scoped, sorted by name).
     *
     * @return Collection<int, Department>
     */
    public function getAccessibleDepartments(User $user, int $businessUnitId): Collection
    {
        return Department::where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    /**
     * Get PR categories.  Returns active+ordered categories regardless
     * of BU (preserving historical behaviour); the BU id is accepted
     * for API symmetry with future BU-scoped filtering.
     *
     * @return Collection<int, PrCategory>
     */
    public function getPrCategories(int $businessUnitId): Collection
    {
        return PrCategory::active()
            ->ordered()
            ->get(['id', 'name', 'code', 'color', 'description']);
    }

    /**
     * Walk the BU parent chain (with cycle detection) and return all
     * BU ids whose users may serve as approvers for the given BU.
     *
     * @return list<int>
     */
    private function collectBusinessUnitChain(int $businessUnitId): array
    {
        $chain = [$businessUnitId];
        $visited = [$businessUnitId];

        $current = BusinessUnit::find($businessUnitId);
        while ($current && $current->parent_id) {
            if (in_array($current->parent_id, $visited, true)) {
                break; // Prevent infinite loop from circular references
            }
            $chain[] = $current->parent_id;
            $visited[] = $current->parent_id;
            $current = BusinessUnit::find($current->parent_id);
        }

        return $chain;
    }
}
