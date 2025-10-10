<?php

namespace Tests\Feature\Livewire\Traits;

use App\Livewire\Traits\HasLazyLoading;
use Livewire\Livewire;
use Tests\TestCase;

class HasLazyLoadingTest extends TestCase
{
    public function test_renders_successfully()
    {
        Livewire::test(HasLazyLoading::class)
            ->assertStatus(200);
    }
}
