<?php

namespace App\Livewire\Modules\Activity;

use App\Models\Modules\Activity\BackdatePermission;
use App\Services\Modules\Activity\BackdatePermissionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class BackdateApprovals extends Component
{
    use WithPagination;

    public $businessUnitId;
    public $showRejectModal = false;
    public $selectedRequest = null;
    public $rejection_reason = '';
    
    // Filters
    public $statusFilter = 'pending'; // pending, approved, rejected, all

    protected $listeners = [
        'business-unit-switched' => 'handleBusinessUnitSwitch',
    ];

    public function mount(): void
    {
        $this->businessUnitId = session('current_business_unit_id');
        
        // Check if user is department head
        $user = Auth::user();
        $accessLevel = $user->getAccessLevel();
        
        if (!in_array($accessLevel, ['department_head', 'super_admin'])) {
            abort(403, 'Only department heads can access this page');
        }
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
        $this->dispatch('bu-switch-acknowledge', component: 'backdate-approvals');
        
        $buName = session('current_business_unit_name', 'new business unit');
        $this->dispatch('notify', message: "Switched to {$buName}", type: 'success');
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function approveRequest($requestId): void
    {
        try {
            $request = BackdatePermission::findOrFail($requestId);
            
            // Verify this request is from the user's department
            $user = Auth::user();
            if ($request->department_id !== $user->primary_department_id && !$user->isSuperAdmin()) {
                throw new \Exception('You can only approve requests from your department');
            }

            $service = app(BackdatePermissionService::class);
            $service->approveRequest($request, $user);

            // TODO: Notify requester
            // This will be implemented in task 15.2

            $this->dispatch('notify', message: 'Backdate request approved successfully', type: 'success');
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function openRejectModal($requestId): void
    {
        $this->selectedRequest = BackdatePermission::with('user')->findOrFail($requestId);
        $this->rejection_reason = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal(): void
    {
        $this->showRejectModal = false;
        $this->selectedRequest = null;
        $this->rejection_reason = '';
        $this->resetValidation();
    }

    public function rejectRequest(): void
    {
        $this->validate([
            'rejection_reason' => 'required|string|min:10|max:500',
        ], [
            'rejection_reason.required' => 'Please provide a reason for rejection',
            'rejection_reason.min' => 'Rejection reason must be at least 10 characters',
            'rejection_reason.max' => 'Rejection reason cannot exceed 500 characters',
        ]);

        try {
            // Verify this request is from the user's department
            $user = Auth::user();
            if ($this->selectedRequest->department_id !== $user->primary_department_id && !$user->isSuperAdmin()) {
                throw new \Exception('You can only reject requests from your department');
            }

            $service = app(BackdatePermissionService::class);
            $service->rejectRequest($this->selectedRequest, $user, $this->rejection_reason);

            // TODO: Notify requester with reason
            // This will be implemented in task 15.3

            $this->closeRejectModal();
            $this->dispatch('notify', message: 'Backdate request rejected', type: 'success');
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function getRequestsProperty()
    {
        $user = Auth::user();
        $departmentId = $user->primary_department_id;
        
        $query = BackdatePermission::query()
            ->with(['user', 'approver', 'rejector', 'department'])
            ->where('business_unit_id', $this->businessUnitId);

        // Filter by department (unless super admin)
        if (!$user->isSuperAdmin()) {
            $query->where('department_id', $departmentId);
        }

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            if ($this->statusFilter === 'pending') {
                $query->pending();
            } else {
                $query->where('status', $this->statusFilter);
            }
        }

        return $query->latest()->paginate(15);
    }

    public function getPendingCountProperty()
    {
        $user = Auth::user();
        $departmentId = $user->primary_department_id;
        
        $query = BackdatePermission::pending()
            ->where('business_unit_id', $this->businessUnitId);

        if (!$user->isSuperAdmin()) {
            $query->where('department_id', $departmentId);
        }

        return $query->count();
    }

    public function render()
    {
        return view('livewire.modules.activity.backdate-approvals', [
            'requests' => $this->requests,
            'pendingCount' => $this->pendingCount,
        ])->layout('layouts.app');
    }
}
