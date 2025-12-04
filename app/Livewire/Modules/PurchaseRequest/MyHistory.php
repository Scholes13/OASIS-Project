<?php

namespace App\Livewire\Modules\PurchaseRequest;

use App\Livewire\Traits\HasLazyLoading;
use App\Models\Modules\PurchaseRequest\PrNumberReservation;
use App\Models\Modules\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\PurchaseRequest\PurchaseRequestService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MyHistory extends Component
{
    use HasLazyLoading, WithPagination;

    protected $listeners = [
        'business-unit-switched' => 'handleBusinessUnitSwitch',
    ];

    public $activeBusinessUnitId;

    public $businessUnitName;

    #[Url]
    public string $search = '';

    #[Url]
    public ?string $filter = null;

    // Stats
    public int $totalPRs = 0;
    public int $pendingCount = 0;
    public int $approvedCount = 0;
    public int $rejectedCount = 0;
    public int $reservedCount = 0;

    // For void modal
    public $showVoidModal = false;

    public $voidReservationId;

    public $voidPrNumber;

    public $voidReason = '';

    public function mount(): void
    {
        $this->activeBusinessUnitId = session('current_business_unit_id');
        $this->businessUnitName = session('current_business_unit_name', 'Current Business Unit');
    }

    /**
     * Handle business unit switch event
     */
    public function handleBusinessUnitSwitch($businessUnitId): void
    {
        // Update session first (single source of truth)
        session(['current_business_unit_id' => $businessUnitId]);

        // Update local properties
        $this->activeBusinessUnitId = $businessUnitId;
        $this->businessUnitName = session('current_business_unit_name', 'Business Unit');

        // Reset filters and search
        $this->search = '';
        $this->filter = null;
        $this->resetPage();

        // ✅ ORCHESTRATOR: Acknowledge completion
        $this->dispatch('bu-switch-acknowledge', component: 'pr-history');
        $this->dispatch('notify',
            message: "Switched to {$this->businessUnitName}",
            type: 'success'
        );
    }

    /**
     * Go to specific page
     */
    public function gotoPage(int $page): void
    {
        $this->setPage($page, 'pr_page');
    }

    /**
     * Handle search update
     */
    public function updatedSearch(): void
    {
        $this->resetPage('pr_page');
    }

    /**
     * Set filter
     */
    public function setFilter(string $status): void
    {
        $this->filter = $status;
        $this->resetPage('pr_page');
    }

    /**
     * Clear filter
     */
    public function clearFilter(): void
    {
        $this->filter = null;
        $this->search = '';
        $this->resetPage('pr_page');
    }

    /**
     * Load stats for cards
     */
    protected function loadStats(): void
    {
        $user = Auth::user();
        $businessUnitId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;

        // Get PR stats for current user only
        $prStats = PurchaseRequest::where('business_unit_id', $businessUnitId)
            ->where('user_id', $user->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('submitted', 'in_approval') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            ")
            ->first();

        // Get reservation stats for current user only
        $reservedCount = PrNumberReservation::where('business_unit_id', $businessUnitId)
            ->where('user_id', $user->id)
            ->where('status', 'reserved')
            ->count();

        $this->totalPRs = $prStats->total ?? 0;
        $this->pendingCount = $prStats->pending ?? 0;
        $this->approvedCount = $prStats->approved ?? 0;
        $this->rejectedCount = $prStats->rejected ?? 0;
        $this->reservedCount = $reservedCount;
    }

    /**
     * Get purchase requests query based on user hierarchy
     */
    protected function getPurchaseRequests()
    {
        $user = Auth::user();
        $businessUnitId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;

        $query = PurchaseRequest::with(['department', 'user', 'approvals', 'items'])
            ->where('business_unit_id', $businessUnitId)
            ->where('user_id', $user->id); // Only current user's PRs

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('pr_number', 'like', '%' . $this->search . '%')
                  ->orWhere('used_for', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if ($this->filter) {
            if ($this->filter === 'pending') {
                $query->whereIn('status', ['submitted', 'in_approval']);
            } elseif ($this->filter === 'approved') {
                $query->where('status', 'approved');
            } elseif ($this->filter === 'rejected') {
                $query->where('status', 'rejected');
            }
        }

        return $query->latest('created_at')->paginate(10, ['*'], 'pr_page');
    }

    /**
     * Get reservations query based on user hierarchy
     */
    protected function getReservations()
    {
        $user = Auth::user();
        $businessUnitId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;

        $query = PrNumberReservation::with(['businessUnit', 'department', 'user', 'purchaseRequest'])
            ->where('business_unit_id', $businessUnitId)
            ->where('user_id', $user->id) // Only current user's reservations
            ->where('status', 'reserved'); // Only show active reservations

        return $query->latest('reserved_at')->paginate(10, ['*'], 'res_page');
    }

    /**
     * Open void modal
     */
    public function openVoidModal($reservationId, $prNumber): void
    {
        $this->voidReservationId = $reservationId;
        $this->voidPrNumber = $prNumber;
        $this->voidReason = '';
        $this->showVoidModal = true;
    }

    /**
     * Close void modal
     */
    public function closeVoidModal(): void
    {
        $this->showVoidModal = false;
        $this->voidReservationId = null;
        $this->voidPrNumber = null;
        $this->voidReason = '';
    }

    /**
     * Void a reservation
     */
    public function voidReservation(): void
    {
        $this->validate([
            'voidReason' => 'required|min:10',
        ], [
            'voidReason.required' => 'Please provide a reason for voiding.',
            'voidReason.min' => 'Reason must be at least 10 characters.',
        ]);

        $reservation = PrNumberReservation::find($this->voidReservationId);

        if ($reservation && $reservation->status === 'reserved') {
            $reservation->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => Auth::id(),
                'void_reason' => $this->voidReason,
            ]);

            $this->dispatch('notify',
                message: "PR Number {$this->voidPrNumber} has been voided.",
                type: 'success'
            );
        } else {
            $this->dispatch('notify',
                message: 'Unable to void this reservation.',
                type: 'error'
            );
        }

        $this->closeVoidModal();
    }

    public function render()
    {
        // Lazy loading: return empty paginator until component is ready
        if (! $this->readyToLoad) {
            return view('livewire.modules.purchase-request.my-history', [
                'purchaseRequests' => new LengthAwarePaginator([], 0, 10),
                'reservations' => collect(),
            ]);
        }

        // Load stats
        $this->loadStats();

        $purchaseRequests = $this->getPurchaseRequests();
        $reservations = $this->getReservations();


        return view('livewire.modules.purchase-request.my-history', [
            'purchaseRequests' => $purchaseRequests,
            'reservations' => $reservations,
        ]);
    }
}
