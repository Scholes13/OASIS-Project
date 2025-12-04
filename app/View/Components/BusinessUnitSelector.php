<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class BusinessUnitSelector extends Component
{
    public $businessUnits;

    public $selectedBusinessUnit;

    public $showAll;

    /**
     * Create a new component instance.
     */
    public function __construct($selectedBusinessUnit = null, $showAll = false)
    {
        $user = auth()->user();

        if ($user) {
            if ($user->isSuperAdmin() && $showAll) {
                // Super admin can see all business units
                $this->businessUnits = \App\Models\Core\BusinessUnit::with('parent')
                    ->active()
                    ->orderBy('name')
                    ->get();
            } else {
                // Regular users see only accessible business units
                $this->businessUnits = $user->getAccessibleBusinessUnits();
            }
        } else {
            $this->businessUnits = collect();
        }

        $this->selectedBusinessUnit = $selectedBusinessUnit;
        $this->showAll = $showAll;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.business-unit-selector');
    }
}
