<?php

namespace App\Livewire\Layout;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Actions\Logout;

class UserMenu extends Component
{
    // Logout method removed - now using standard form POST to logout route
    
    public function render()
    {
        return view('livewire.layout.user-menu');
    }
}