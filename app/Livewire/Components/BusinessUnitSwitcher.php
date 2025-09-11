<?php

namespace App\Livewire\Components;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class BusinessUnitSwitcher extends Component
{
    public $currentBusinessUnit;
    public $availableBusinessUnits;
    public $isLoaded = false;
    
    public function mount()
    {
        $this->loadBusinessUnits();
        $this->isLoaded = true;
    }
    
    public function hydrate()
    {
        // Only load if not already loaded or if session changed
        $sessionBuId = session('current_business_unit_id');
        $currentBuId = $this->currentBusinessUnit['id'] ?? null;
        
        if (!$this->isLoaded || $sessionBuId !== $currentBuId) {
            $this->loadBusinessUnits();
            $this->isLoaded = true;
        }
    }
    
    public function loadBusinessUnits()
    {
        $user = Auth::user();
        $this->currentBusinessUnit = [
            'id' => session('current_business_unit_id'),
            'code' => session('current_business_unit_code'),
            'name' => session('current_business_unit_name'),
        ];
        
        // Super admins don't have business unit assignments
        if ($user->global_role === 'super_admin') {
            $this->availableBusinessUnits = collect([
                [
                    'id' => null,
                    'code' => 'WG',
                    'name' => 'Werkudara Group',
                    'role' => 'super_admin',
                    'department_id' => null,
                ]
            ]);
            return;
        }
        
        $this->availableBusinessUnits = $user->businessUnits()
            ->with('businessUnit')
            ->where('is_active', true)
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->businessUnit->id,
                    'code' => $assignment->businessUnit->code,
                    'name' => $assignment->businessUnit->name,
                    'role' => $assignment->role,
                    'department_id' => $assignment->department_id,
                ];
            });
    }
    
    public function switchBusinessUnit($businessUnitId)
    {
        $user = Auth::user();
        
        // Super admins can't switch business units
        if ($user->global_role === 'super_admin') {
            session()->flash('info', 'Super administrators have system-wide access.');
            return;
        }
        
        // Find the business unit assignment
        $assignment = $user->businessUnits()
            ->with('businessUnit')
            ->where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->first();
            
        if ($assignment) {
            $businessUnit = $assignment->businessUnit;
            
            // Update session context
            session([
                'current_business_unit_id' => $businessUnit->id,
                'current_business_unit_code' => $businessUnit->code,
                'current_business_unit_name' => $businessUnit->name,
                'current_user_role' => $assignment->role,
                'current_department_id' => $assignment->department_id,
            ]);
            
            // Mark as not loaded to force refresh
            $this->isLoaded = false;
            
            // Flash success message
            session()->flash('success', "Switched to {$businessUnit->name} ({$businessUnit->code})");
            
            // Redirect to dashboard to refresh context
            return $this->redirect(route('dashboard'), navigate: true);
        }
        
        session()->flash('error', 'Unable to switch to selected business unit.');
    }
    
    public function render()
    {
        return view('livewire.components.business-unit-switcher');
    }
}