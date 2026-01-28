<?php

namespace App\Livewire\Modules\Activity;

use App\Models\Modules\Activity\BackdatePermission;
use App\Services\Modules\Activity\BackdatePermissionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class BackdateRequests extends Component
{
    use WithPagination;

    public $businessUnitId;
    public $showRequestModal = false;
    public $showDetailModal = false;
    public $selectedRequest = null;
    
    // Form fields
    public $reason;

    protected $listeners = [
        'business-unit-switched' => 'handleBusinessUnitSwitch',
    ];

    public function mount(): void
    {
        $this->businessUnitId = session('current_business_unit_id');
    }

    public function hydrate(): void
    {
        $sessionBuId = session('current_business_unit_id');
        if ($this->businessUnitId != $sessionBuId) {
            $this->businessUnitId = $sessionBuId;
            $this->resetPage();
        }
    }

    #[On('business-unit-switched')]
    public function handleBusinessUnitSwitch($businessUnitId): void
    {
        // Update session FIRST (single source of truth)
        session(['current_business_unit_id' => $businessUnitId]);
        
        $this->businessUnitId = $businessUnitId;
        $this->resetPage();
        
        // ✅ ORCHESTRATOR: Acknowledge completion
        $this->dispatch('bu-switch-acknowledge', component: 'backdate-requests');
        
        $buName = session('current_business_unit_name', 'new business unit');
        $this->dispatch('notify', message: "Switched to {$buName}", type: 'success');
    }

    public function openRequestModal(): void
    {
        $this->reset(['reason']);
        $this->showRequestModal = true;
    }

    public function closeRequestModal(): void
    {
        $this->showRequestModal = false;
        $this->reset(['reason']);
        $this->resetValidation();
    }

    public function submitRequest(): void
    {
        $this->validate([
            'reason' => 'required|string|min:10|max:500',
        ], [
            'reason.required' => 'Please provide a reason for backdate access',
            'reason.min' => 'Reason must be at least 10 characters',
            'reason.max' => 'Reason cannot exceed 500 characters',
        ]);

        try {
            $service = app(BackdatePermissionService::class);
            $user = Auth::user();

            // System automatically records the submission date
            $permission = $service->requestPermission([
                'reason' => $this->reason,
            ], $user);

            // TODO: Notify department head
            // This will be implemented in task 15.1

            $this->closeRequestModal();
            $this->dispatch('notify', message: 'Backdate request submitted successfully', type: 'success');
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function viewDetail($requestId): void
    {
        $this->selectedRequest = BackdatePermission::with(['approver', 'rejector'])
            ->findOrFail($requestId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedRequest = null;
    }

    public function getRequestsProperty()
    {
        $user = Auth::user();
        
        return BackdatePermission::forUser($user->id)
            ->with(['approver', 'rejector', 'department'])
            ->latest()
            ->paginate(10);
    }

    public function getActivePermissionProperty()
    {
        $service = app(BackdatePermissionService::class);
        return $service->checkUserPermission(Auth::id());
    }

    public function getHasPendingRequestProperty()
    {
        return BackdatePermission::forUser(Auth::id())
            ->pending()
            ->exists();
    }

    public function render()
    {
        return view('livewire.modules.activity.backdate-requests', [
            'requests' => $this->requests,
            'activePermission' => $this->activePermission,
            'hasPendingRequest' => $this->hasPendingRequest,
        ])->layout('layouts.app');
    }
}
