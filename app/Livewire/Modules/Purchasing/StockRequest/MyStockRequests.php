<?php

namespace App\Livewire\Modules\Purchasing\StockRequest;

use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MyStockRequests extends Component
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
        $this->dispatch('bu-switch-acknowledge', component: 'sr-history');

        $this->dispatch('notify',
            message: "Switched to {$this->businessUnitName}",
            type: 'success'
        );
    }

    public function performSearch(): void
    {
        $this->resetPage();
    }

    protected function getStockRequests()
    {
        $user = Auth::user();

        $query = StockRequest::with(['department', 'user', 'items', 'approvals'])
            ->where('business_unit_id', $this->activeBusinessUnitId)
            ->where('user_id', $user->id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('st_number', 'like', '%' . $this->search . '%')
                  ->orWhere('used_for', 'like', '%' . $this->search . '%');
            });
        }

        return $query->latest('created_at')->paginate(10);
    }

    public function render()
    {
        return view('livewire.modules.purchasing.stock-request.my-stock-requests', [
            'stockRequests' => $this->getStockRequests(),
        ]);
    }
}
