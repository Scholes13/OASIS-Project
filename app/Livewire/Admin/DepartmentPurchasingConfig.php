<?php

namespace App\Livewire\Admin;

use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DepartmentPurchasingConfig extends Component
{
    public Department $department;
    public $isPurchasingDepartment;
    public $defaultPurchasingAdminId;
    public $availableUsers = [];
    public $purchasingAdmins = [];

    public function mount(Department $department): void
    {
        $this->department = $department;
        $this->isPurchasingDepartment = $department->is_purchasing_department;
        $this->defaultPurchasingAdminId = $department->default_purchasing_admin_id;
        
        $this->loadAvailableUsers();
        $this->loadPurchasingAdmins();
    }

    public function loadAvailableUsers(): void
    {
        // Get all active users in this department's business unit
        $this->availableUsers = UserBusinessUnit::where('business_unit_id', $this->department->business_unit_id)
            ->where('department_id', $this->department->id)
            ->where('is_active', true)
            ->with(['user' => function ($query) {
                $query->where('is_active', true);
            }, 'position'])
            ->get()
            ->filter(fn($ubu) => $ubu->user !== null)
            ->map(function ($ubu) {
                return [
                    'id' => $ubu->user->id,
                    'name' => $ubu->user->name,
                    'email' => $ubu->user->email,
                    'position' => $ubu->position?->name ?? 'No Position',
                    'is_purchasing_admin' => $ubu->is_purchasing_admin,
                    'ubu_id' => $ubu->id,
                ];
            })
            ->values()
            ->toArray();
    }

    public function loadPurchasingAdmins(): void
    {
        $this->purchasingAdmins = UserBusinessUnit::where('business_unit_id', $this->department->business_unit_id)
            ->where('department_id', $this->department->id)
            ->where('is_purchasing_admin', true)
            ->where('is_active', true)
            ->with(['user', 'position'])
            ->get()
            ->filter(fn($ubu) => $ubu->user !== null && $ubu->user->is_active)
            ->map(function ($ubu) {
                return [
                    'id' => $ubu->user->id,
                    'name' => $ubu->user->name,
                    'email' => $ubu->user->email,
                    'position' => $ubu->position?->name ?? 'No Position',
                    'ubu_id' => $ubu->id,
                ];
            })
            ->values()
            ->toArray();
    }

    public function togglePurchasingDepartment(): void
    {
        try {
            DB::beginTransaction();

            $this->department->update([
                'is_purchasing_department' => $this->isPurchasingDepartment,
            ]);

            // If disabling purchasing department, clear default admin and remove all admin flags
            if (!$this->isPurchasingDepartment) {
                $this->department->update(['default_purchasing_admin_id' => null]);
                $this->defaultPurchasingAdminId = null;

                // Remove purchasing admin flag from all users in this department
                UserBusinessUnit::where('business_unit_id', $this->department->business_unit_id)
                    ->where('department_id', $this->department->id)
                    ->update(['is_purchasing_admin' => false]);

                $this->loadAvailableUsers();
                $this->loadPurchasingAdmins();
            }

            DB::commit();

            $this->dispatch('toast', [
                'type' => 'success',
                'message' => $this->isPurchasingDepartment 
                    ? 'Department enabled as purchasing department' 
                    : 'Department disabled as purchasing department'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Failed to update department: ' . $e->getMessage()
            ]);
        }
    }

    public function updateDefaultAdmin(): void
    {
        try {
            // Validate that the selected admin is actually a purchasing admin
            if ($this->defaultPurchasingAdminId) {
                $isValidAdmin = UserBusinessUnit::where('business_unit_id', $this->department->business_unit_id)
                    ->where('department_id', $this->department->id)
                    ->where('user_id', $this->defaultPurchasingAdminId)
                    ->where('is_purchasing_admin', true)
                    ->where('is_active', true)
                    ->exists();

                if (!$isValidAdmin) {
                    $this->dispatch('toast', [
                        'type' => 'error',
                        'message' => 'Selected user is not a purchasing admin in this department'
                    ]);
                    return;
                }
            }

            $this->department->update([
                'default_purchasing_admin_id' => $this->defaultPurchasingAdminId,
            ]);

            $this->dispatch('toast', [
                'type' => 'success',
                'message' => 'Default purchasing admin updated successfully'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Failed to update default admin: ' . $e->getMessage()
            ]);
        }
    }

    public function assignPurchasingAdmin($userId): void
    {
        try {
            DB::beginTransaction();

            // Find the user business unit record
            $userBusinessUnit = UserBusinessUnit::where('business_unit_id', $this->department->business_unit_id)
                ->where('department_id', $this->department->id)
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->first();

            if (!$userBusinessUnit) {
                $this->dispatch('toast', [
                    'type' => 'error',
                    'message' => 'User not found in this department'
                ]);
                return;
            }

            $userBusinessUnit->update(['is_purchasing_admin' => true]);

            $this->loadAvailableUsers();
            $this->loadPurchasingAdmins();

            DB::commit();

            $this->dispatch('toast', [
                'type' => 'success',
                'message' => 'User assigned as purchasing admin successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Failed to assign purchasing admin: ' . $e->getMessage()
            ]);
        }
    }

    public function removePurchasingAdmin($userId): void
    {
        try {
            DB::beginTransaction();

            // Find the user business unit record
            $userBusinessUnit = UserBusinessUnit::where('business_unit_id', $this->department->business_unit_id)
                ->where('department_id', $this->department->id)
                ->where('user_id', $userId)
                ->first();

            if (!$userBusinessUnit) {
                $this->dispatch('toast', [
                    'type' => 'error',
                    'message' => 'User not found in this department'
                ]);
                return;
            }

            $userBusinessUnit->update(['is_purchasing_admin' => false]);

            // If this was the default admin, clear it
            if ($this->department->default_purchasing_admin_id == $userId) {
                $this->department->update(['default_purchasing_admin_id' => null]);
                $this->defaultPurchasingAdminId = null;
            }

            $this->loadAvailableUsers();
            $this->loadPurchasingAdmins();

            DB::commit();

            $this->dispatch('toast', [
                'type' => 'success',
                'message' => 'Purchasing admin removed successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Failed to remove purchasing admin: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.admin.department-purchasing-config');
    }
}
