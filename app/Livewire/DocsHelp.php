<?php

namespace App\Livewire;

use Livewire\Component;

class DocsHelp extends Component
{
    public string $activeSection = 'getting-started';
    
    public array $sections = [
        'getting-started' => 'Getting Started',
        'purchase-request' => 'Purchase Request',
        'stock-request' => 'Stock Request',
        'approvals' => 'Approvals',
        'dashboard' => 'Dashboard',
        'faq' => 'FAQ',
    ];

    public function setActiveSection(string $section): void
    {
        $this->activeSection = $section;
    }

    public function render()
    {
        return view('livewire.docs-help')
            ->layout('layouts.app', ['title' => 'Docs & Help']);
    }
}
