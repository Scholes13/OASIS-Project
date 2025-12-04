<?php

namespace App\Livewire\Modules\PurchaseRequest;

use App\Livewire\Traits\HasLazyLoading;
use App\Models\Modules\PurchaseRequest\PrNumberReservation;
use App\Services\Modules\PurchaseRequest\PurchaseRequestService;
use Illuminate\Support\Facades\Auth;
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

        // Reset pagination (data will auto-refresh on next render)
        $this->resetPage();

        // ✅ ORCHESTRATOR: Acknowledge completion
        $this->dispatch('bu-switch-acknowledge', component: 'pr-history');
        $this->dispatch('notify',
            message: "Switched to {$this->businessUnitName}",
            type: 'success'
        );
    }

    /**
     * Get purchase requests query based on user hierarchy
     */
    protected function getPurchaseRequests()
    {
        $purchaseRequestService = app(PurchaseRequestService::class);
        $query = $purchaseRequestService->getPurchaseRequestsQuery();

        return $query->latest('created_at')->paginate(10, ['*'], 'pr_page');
    }

    /**
     * Get reservations query based on user hierarchy
     */
    protected function getReservations()
    {
        $user = Auth::user();
        $accessLevel = $user->getAccessLevel();
        $businessUnitId = session('current_business_unit_id') ?? $this->activeBusinessUnitId;

        $query = PrNumberReservation::with(['businessUnit', 'department', 'user', 'purchaseRequest'])
            ->where('business_unit_id', $businessUnitId);

        // Apply hierarchy filtering
        switch ($accessLevel) {
            case 'super_admin':
            case 'executive':
            case 'general_manager':
                break;
            case 'department_head':
                $query->where('department_id', $user->primary_department_id);
                break;
            case 'team_leader':
                $subordinateIds = $user->activeSubordinates()->pluck('id')->toArray();
                $subordinateIds[] = $user->id;
                $query->whereIn('user_id', $subordinateIds);
                break;
            case 'staff':
            default:
                $query->byUser($user->id);
                break;
        }

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
        // Lazy loading: return empty data until component is ready
        if (! $this->readyToLoad) {
            return view('livewire.modules.purchase-request.my-history', [
                'allItems' => collect(),
                'purchaseRequests' => collect(),
                'reservations' => collect(),
            ]);
        }

        $purchaseRequests = $this->getPurchaseRequests();
        $reservations = $this->getReservations();

        // Combine items for display
        $allItems = collect();

        foreach ($purchaseRequests as $pr) {
            $allItems->push([
                'type' => 'purchase_request',
                'data' => $pr,
                'sort_date' => $pr->created_at,
                'pr_number' => $pr->pr_number,
                'status' => $pr->status,
                'purpose' => $pr->used_for,
                'description' => $pr->used_for,
                'department' => $pr->department,
                'user' => $pr->user,
                'date' => $pr->date_of_request,
                'created_at' => $pr->created_at,
            ]);
        }

        foreach ($reservations as $reservation) {
            $allItems->push([
                'type' => 'reservation',
                'data' => $reservation,
                'sort_date' => $reservation->reserved_at,
                'pr_number' => $reservation->pr_number,
                'status' => $reservation->status,
                'purpose' => $reservation->purpose,
                'description' => $reservation->description,
                'department' => $reservation->department,
                'user' => $reservation->user,
                'date' => $reservation->reserved_at->toDateString(),
                'created_at' => $reservation->reserved_at,
            ]);
        }

        // Sort by date (newest first)
        $allItems = $allItems->sortByDesc('sort_date');

        return view('livewire.modules.purchase-request.my-history', [
            'allItems' => $allItems,
            'purchaseRequests' => $purchaseRequests,
            'reservations' => $reservations,
        ]);
    }
}
