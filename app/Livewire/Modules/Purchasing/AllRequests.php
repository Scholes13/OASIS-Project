<?php

namespace App\Livewire\Modules\Purchasing;

use App\Livewire\Traits\HasLazyLoading;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class AllRequests extends Component
{
    use HasLazyLoading, WithPagination;

    public $activeTab = 'all'; // all, purchase, stock
    public $statusFilter = '';
    public $searchTerm = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $activeBusinessUnitId;

    protected $queryString = [
        'activeTab' => ['except' => 'all'],
        'statusFilter' => ['except' => ''],
        'searchTerm' => ['except' => ''],
    ];

    public function mount()
    {
        $this->activeBusinessUnitId = session('current_business_unit_id');
        $this->dateFrom = now()->subMonths(3)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    #[On('business-unit-switched')]
    public function handleBusinessUnitSwitch($businessUnitId = null): void
    {
        // Use parameter directly if provided, then session as fallback
        if ($businessUnitId) {
            $this->activeBusinessUnitId = (int) $businessUnitId;
        } else {
            $this->activeBusinessUnitId = (int) session('current_business_unit_id');
        }

        // Reset filters and pagination
        $this->resetPage();

        // ✅ ORCHESTRATOR: Acknowledge completion
        $this->dispatch('bu-switch-acknowledge', component: 'purchasing-all-requests');

        $buName = session('current_business_unit_name', 'new business unit');
        $this->dispatch('notify',
            message: "Switched to {$buName}",
            type: 'success'
        );
    }

    public function updatingSearchTerm()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function gotoPage($page)
    {
        $this->setPage($page);
    }

    public function render()
    {
        $user = Auth::user();
        $accessLevel = $user->getAccessLevel();

        // Fallback to session if activeBusinessUnitId is not set
        if (! $this->activeBusinessUnitId) {
            $this->activeBusinessUnitId = session('current_business_unit_id');
        }

        $combinedRequests = collect();

        // Check if user is purchasing admin for current business unit
        $isPurchasingAdmin = $user->activeBusinessUnits()
            ->where('business_unit_id', $this->activeBusinessUnitId)
            ->where('is_purchasing_admin', true)
            ->exists();

        // Fetch Purchase Requests based on hierarchy
        if (in_array($this->activeTab, ['all', 'purchase'])) {
            $prQuery = PurchaseRequest::with(['businessUnit', 'department', 'user', 'items', 'approvals'])
                ->where('business_unit_id', $this->activeBusinessUnitId);

            // Apply hierarchy filtering (purchasing admin can see all)
            if (! $isPurchasingAdmin) {
                switch ($accessLevel) {
                    case 'super_admin':
                    case 'executive':
                    case 'general_manager':
                        // Can see all
                        break;
                    case 'department_head':
                        $prQuery->where('department_id', $user->primary_department_id);
                        break;
                    case 'team_leader':
                        $subordinateIds = $user->activeSubordinates()->pluck('id')->toArray();
                        $subordinateIds[] = $user->id;
                        $prQuery->whereIn('user_id', $subordinateIds);
                        break;
                    case 'staff':
                    default:
                        $prQuery->where('user_id', $user->id);
                        break;
                }
            }

            if ($this->statusFilter) {
                $prQuery->where('status', $this->statusFilter);
            }

            if ($this->searchTerm) {
                $prQuery->where(function ($q) {
                    $q->where('pr_number', 'like', '%'.$this->searchTerm.'%')
                        ->orWhere('purpose', 'like', '%'.$this->searchTerm.'%');
                });
            }

            if ($this->dateFrom && $this->dateTo) {
                $prQuery->whereBetween('created_at', [$this->dateFrom, $this->dateTo]);
            }

            $purchaseRequests = $prQuery->get()->map(function ($pr) {
                $totalApprovals = $pr->approvals->count();
                $approvedCount = $pr->approvals->where('status', 'approved')->count();
                $showProgress = in_array($pr->status, ['submitted', 'in_approval', 'approved', 'rejected']);

                return [
                    'type' => 'PR',
                    'id' => $pr->id,
                    'number' => $pr->pr_number,
                    'purpose' => $pr->purpose ?? $pr->used_for,
                    'status' => $pr->status,
                    'created_at' => $pr->created_at,
                    'business_unit' => $pr->businessUnit->name ?? 'N/A',
                    'department' => $pr->department->code ?? 'N/A',
                    'user' => $pr->user->name ?? 'N/A',
                    'items_count' => $pr->items->count(),
                    'approval_progress' => $showProgress && $totalApprovals > 0 ? "{$approvedCount}/{$totalApprovals}" : null,
                    'show_route' => route('purchase-requests.show', $pr),
                ];
            });

            $combinedRequests = $combinedRequests->merge($purchaseRequests);
        }

        // Fetch Stock Requests based on hierarchy
        if (in_array($this->activeTab, ['all', 'stock'])) {
            $srQuery = StockRequest::with(['businessUnit', 'department', 'user', 'items', 'approvals'])
                ->where('business_unit_id', $this->activeBusinessUnitId);

            // Apply hierarchy filtering (purchasing admin can see all)
            if (! $isPurchasingAdmin) {
                switch ($accessLevel) {
                    case 'super_admin':
                    case 'executive':
                    case 'general_manager':
                        // Can see all
                        break;
                    case 'department_head':
                        $srQuery->where('department_id', $user->primary_department_id);
                        break;
                    case 'team_leader':
                        $subordinateIds = $user->activeSubordinates()->pluck('id')->toArray();
                        $subordinateIds[] = $user->id;
                        $srQuery->whereIn('user_id', $subordinateIds);
                        break;
                    case 'staff':
                    default:
                        $srQuery->where('user_id', $user->id);
                        break;
                }
            }

            if ($this->statusFilter) {
                $srQuery->where('status', $this->statusFilter);
            }

            if ($this->searchTerm) {
                $srQuery->where(function ($q) {
                    $q->where('st_number', 'like', '%'.$this->searchTerm.'%')
                        ->orWhere('purpose', 'like', '%'.$this->searchTerm.'%');
                });
            }

            if ($this->dateFrom && $this->dateTo) {
                $srQuery->whereBetween('created_at', [$this->dateFrom, $this->dateTo]);
            }

            $stockRequests = $srQuery->get()->map(function ($sr) {
                $totalApprovals = $sr->approvals->count();
                $approvedCount = $sr->approvals->where('status', 'approved')->count();
                $showProgress = in_array($sr->status, ['submitted', 'in_approval', 'approved', 'rejected']);

                return [
                    'type' => 'ST',
                    'id' => $sr->id,
                    'number' => $sr->st_number,
                    'purpose' => $sr->purpose ?? $sr->used_for,
                    'status' => $sr->status,
                    'created_at' => $sr->created_at,
                    'business_unit' => $sr->businessUnit->name ?? 'N/A',
                    'department' => $sr->department->code ?? 'N/A',
                    'user' => $sr->user->name ?? 'N/A',
                    'items_count' => $sr->items->count(),
                    'approval_progress' => $showProgress && $totalApprovals > 0 ? "{$approvedCount}/{$totalApprovals}" : null,
                    'show_route' => route('stock-requests.show', $sr),
                ];
            });

            $combinedRequests = $combinedRequests->merge($stockRequests);
        }

        // Sort by created_at desc
        $combinedRequests = $combinedRequests->sortByDesc('created_at');

        // Manual pagination
        $perPage = 15;
        $currentPage = $this->getPage();
        $paginatedRequests = $combinedRequests->slice(($currentPage - 1) * $perPage, $perPage);

        $total = $combinedRequests->count();
        $lastPage = (int) ceil($total / $perPage);

        return view('livewire.modules.purchasing.all-requests', [
            'requests' => $paginatedRequests,
            'total' => $total,
            'currentPage' => $currentPage,
            'lastPage' => $lastPage ?: 1,
        ]);
    }
}
