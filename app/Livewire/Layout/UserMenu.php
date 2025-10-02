<?php

namespace App\Livewire\Layout;

use App\Livewire\Actions\Logout;
use Livewire\Component;

class UserMenu extends Component
{
    // Logout method removed - now using standard form POST to logout route

    public function render()
    {
        return view('livewire.layout.user-menu');
    }
}
