<?php

namespace App\Livewire\Modules\Purchasing\PurchaseRequest;

use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ApprovalsIndex extends Component
{
    use WithPagination;

    #[Url]
    public ?string $filter = null;

    #[Url]
    public string $search = '';

    // Lazy loading flag
    public bool $readyToLoad = false;

    // Track if "Total documents" is actively selected (not just default state)
    public bool $showAllActive = false;

    // Statistics (calculated once, cached)
    public int $pendingCount = 0;
    public int $approvedCount = 0;
    public int $rejectedCount = 0;
    public int $totalCount = 0;

    /**
     * Lazy load data after component is mounted
     */
    public function loadData(): void
    {
        $this->readyToLoad = true;
        $this->loadStatistics();
    }

    #[On('business-unit-switched')]
    public function handleBusinessUnitSwitch($businessUnitId = null): void
    {
        // Reset pagination and reload
        $this->resetPage();
        $this->loadStatistics();

        // ✅ ORCHESTRATOR: Acknowledge completion
        $this->dispatch('bu-switch-acknowledge', component: 'approvals');
        $this->dispatch('notify',
            message: 'Business unit switched',
            type: 'success'
        );
    }

    /**
     * Load statistics using optimized aggregate queries
     * ✅ OPTIMIZED: Single query with conditional counts instead of multiple queries
     */
    protected function loadStatistics(): void
    {
        $userId = Auth::id();

        // ✅ OPTIMIZED: Get all counts in a single query using conditional aggregation
        $stats = PrApproval::where('approver_id', $userId)
            ->select([
                DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count"),
                DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count"),
                DB::raw("COUNT(*) as total_count"),
            ])
            ->first();

        $this->pendingCount = (int) ($stats->pending_count ?? 0);
        $this->approvedCount = (int) ($stats->approved_count ?? 0);
        $this->rejectedCount = (int) ($stats->rejected_count ?? 0);
        $this->totalCount = (int) ($stats->total_count ?? 0);
    }

    public function setFilter(?string $filter): void
    {
        $this->filter = $filter;
        $this->showAllActive = false;
        $this->resetPage();
    }

    /**
     * Show all documents (Total documents clicked)
     */
    public function showAll(): void
    {
        $this->filter = null;
        $this->search = '';
        $this->showAllActive = true;
        $this->resetPage();
    }

    public function clearFilter(): void
    {
        $this->filter = null;
        $this->search = '';
        $this->showAllActive = false;
        $this->resetPage();
    }

    /**
     * Go to a specific page
     */
    public function gotoPage(int $page): void
    {
        $this->setPage($page);
    }

    /**
     * Reset pagination when search changes
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        // Return empty paginator if not ready (lazy loading)
        if (!$this->readyToLoad) {
            return view('livewire.modules.purchasing.purchase-request.approvals-index', [
                'approvals' => new LengthAwarePaginator([], 0, 10, 1),
            ]);
        }

        $userId = Auth::id();

        // ✅ OPTIMIZED: Build query with minimal eager loading
        // Include approvals for calculating x/x done progress and approver names
        $query = PrApproval::with([
            'purchaseRequest:id,pr_number,user_id,department_id,total_amount,currency,status,updated_at',
            'purchaseRequest.user:id,name',
            'purchaseRequest.department:id,name,code',
            'purchaseRequest.approvals:id,purchase_request_id,approver_id,status', // For x/x done calculation
            'purchaseRequest.approvals.approver:id,name', // For approver names in "To:" field
            'approver:id,name',
        ])
        ->where('approver_id', $userId);

        // Apply filter
        $query = match ($this->filter) {
            'pending' => $query->where('status', 'pending'),
            'approved' => $query->where('status', 'approved'),
            'rejected' => $query->where('status', 'rejected'),
            default => $query,
        };

        // Apply search filter
        if ($this->search) {
            $query->whereHas('purchaseRequest', function ($q) {
                $q->where('pr_number', 'like', '%' . $this->search . '%');
            });
        }

        // ✅ OPTIMIZED: Order and paginate (10 items per page)
        $approvals = $query
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END") // Pending first
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        return view('livewire.modules.purchasing.purchase-request.approvals-index', [
            'approvals' => $approvals,
        ]);
    }
}
