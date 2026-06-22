<?php

namespace App\Services\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Access-level aware query builders for Purchase Requests.
 *
 * Owns the hierarchy/business-unit filtering previously inlined in
 * PurchaseRequestService::getPurchaseRequestsQuery and
 * PurchaseRequestService::getAllPurchaseRequestsQuery. Behavior preserved
 * verbatim — pairs with PurchaseRequestQueryService which owns listing
 * pagination/transforms.
 */
class PrAccessibleQueryBuilder
{
    /**
     * Get Purchase Requests based on user hierarchy and filters.
     */
    public function forCurrentUser(array $filters = []): Builder
    {
        $user = Auth::user();
        $accessLevel = $user->getAccessLevel();

        // Get business unit context
        $currentBusinessUnitId = session('current_business_unit_id');
        $currentBusinessUnit = BusinessUnit::find($currentBusinessUnitId);

        // Base query with business unit hierarchy consideration
        $query = PurchaseRequest::with(['department', 'user', 'items', 'approvals']);

        // Apply business unit filtering based on hierarchy
        if ($currentBusinessUnit) {
            if (in_array($accessLevel, ['super_admin', 'executive'])) {
                $accessibleBusinessUnits = $currentBusinessUnit->getAccessibleBusinessUnits();
                $query->whereIn('business_unit_id', $accessibleBusinessUnits);
            } elseif ($accessLevel === 'general_manager') {
                $managedBusinessUnits = $user->generalManagerBusinessUnitIds();
                if (! empty($managedBusinessUnits)) {
                    $query->whereIn('business_unit_id', $managedBusinessUnits);
                } else {
                    $query->where('business_unit_id', $currentBusinessUnitId);
                }
            } else {
                $query->where('business_unit_id', $currentBusinessUnitId);
            }
        } else {
            // Fallback: only current business unit
            $query->where('business_unit_id', $currentBusinessUnitId);
        }

        $this->applyHierarchyFilter($query, $user, $accessLevel);
        $this->applyAdditionalFilters($query, $filters);
        $this->applySorting($query, $filters);

        return $query;
    }

    /**
     * Get ALL Purchase Requests in current business unit without hierarchy
     * filtering. Used for "All Requests" page where every user in a BU can
     * see every PR.
     *
     * @param  array  $filters  Optional filters (status, date_from, date_to, etc.)
     */
    public function forCurrentBusinessUnit(array $filters = []): Builder
    {
        $currentBusinessUnitId = session('current_business_unit_id');

        $query = PurchaseRequest::with(['department', 'user', 'items', 'approvals', 'category'])
            ->where('business_unit_id', $currentBusinessUnitId);

        $this->applyAdditionalFilters($query, $filters, includeCategory: true);
        $this->applySorting($query, $filters);

        return $query;
    }

    /**
     * Apply hierarchy-based row-level filtering.
     */
    private function applyHierarchyFilter(Builder $query, $user, string $accessLevel): void
    {
        switch ($accessLevel) {
            case 'super_admin':
            case 'executive':
            case 'general_manager':
                // Can see all PRs in accessible business units (already filtered above)
                break;

            case 'department_head':
                // Department head can see all PRs in their department
                $query->where('department_id', $user->primary_department_id);
                break;

            case 'team_leader':
                // Team leader can see their own + subordinates' PRs
                $subordinateIds = $user->activeSubordinates()->pluck('id')->toArray();
                $subordinateIds[] = $user->id; // Include own PRs
                $query->whereIn('user_id', $subordinateIds);
                break;

            case 'staff':
            default:
                // Staff can only see their own PRs
                $query->byUser($user->id);
                break;
        }
    }

    /**
     * Apply optional status / user / department / date filters.
     */
    private function applyAdditionalFilters(Builder $query, array $filters, bool $includeCategory = false): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if ($includeCategory && isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('date_of_request', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('date_of_request', '<=', $filters['date_to']);
        }
    }

    /**
     * Apply sort_by / sort_order from filters.
     */
    private function applySorting(Builder $query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
    }
}
