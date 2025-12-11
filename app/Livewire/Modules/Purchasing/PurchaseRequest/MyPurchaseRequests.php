<?php

namespace App\Livewire\Modules\Purchasing\PurchaseRequest;

use App\Models\Modules\Purchasing\PurchaseRequest\PrNumberReservation;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MyPurchaseRequests extends Component
{
    use WithPagination;

    public $activeBusinessUnitId;
    public $businessUnitName;
    public $search = '';

    public function mount(): void
    {
        $this->activeBusinessUnitId = session('current_business_unit_id');
        $this->businessUnitName = session('current_business_unit_name', 'Current Business Unit');
    }

    #[On('business-unit-switched')]
    public function handleBusinessUnitSwitch($businessUnitId = null): void
    {
        if ($businessUnitId) {
            $this->activeBusinessUnitId = (int) $businessUnitId;
        } else {
            $this->activeBusinessUnitId = (int) session('current_business_unit_id');
        }
        $this->businessUnitName = session('current_business_unit_name', 'Business Unit');
        $this->resetPage();

        // ✅ ORCHESTRATOR: Acknowledge completion
        $this->dispatch('bu-switch-acknowledge', component: 'pr-history');

        $this->dispatch('notify',
            message: "Switched to {$this->businessUnitName}",
            type: 'success'
        );
    }

    public function performSearch(): void
    {
        $this->resetPage();
    }

    protected function getPurchaseRequests()
    {
        $user = Auth::user();

        $query = PurchaseRequest::with(['department', 'user', 'items', 'approvals'])
            ->where('business_unit_id', $this->activeBusinessUnitId)
            ->where('user_id', $user->id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('pr_number', 'like', '%' . $this->search . '%')
                  ->orWhere('used_for', 'like', '%' . $this->search . '%');
            });
        }

        return $query->latest('created_at')->paginate(10);
    }

    protected function getReservations()
    {
        $user = Auth::user();

        $query = PrNumberReservation::with(['businessUnit', 'department', 'user', 'purchaseRequest'])
            ->where('business_unit_id', $this->activeBusinessUnitId)
            ->where('user_id', $user->id)
            ->where('status', 'reserved');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('pr_number', 'like', '%' . $this->search . '%')
                  ->orWhere('purpose', 'like', '%' . $this->search . '%');
            });
        }

        return $query->latest('reserved_at')->paginate(10);
    }

    public function render()
    {
        return view('livewire.modules.purchasing.purchase-request.my-purchase-requests', [
            'purchaseRequests' => $this->getPurchaseRequests(),
            'reservations' => $this->getReservations(),
        ]);
    }
}
