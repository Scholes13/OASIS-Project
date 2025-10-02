<?php

namespace Tests\Feature\Modules\WNS;

use App\Livewire\Modules\WNS\PurchaseRequests\Create;
use App\Models\BusinessUnit;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseRequestUserDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected $businessUnit;

    protected $department;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create role first
        \Spatie\Permission\Models\Role::create(['name' => 'user']);

        // Create business unit
        $this->businessUnit = BusinessUnit::create([
            'name' => 'Test Business Unit',
            'code' => 'TBU',
            'is_active' => true,
        ]);

        // Create department
        $this->department = Department::create([
            'business_unit_id' => $this->businessUnit->id,
            'name' => 'Test Department',
            'code' => 'TD',
            'is_active' => true,
        ]);

        // Create user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'primary_department_id' => $this->department->id,
        ]);

        // Assign role
        $this->user->assignRole('user');

        // Set session context
        session(['current_business_unit_id' => $this->businessUnit->id]);
    }

    /** @test */
    public function user_properties_are_initialized_correctly_in_component()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(Create::class);

        // Check properties are set correctly
        $this->assertEquals('Test User', $component->get('user_name'));
        $this->assertEquals('Test Department', $component->get('department_name'));
        $this->assertEquals('TD', $component->get('department_code'));
    }

    /** @test */
    public function user_information_displays_correctly_in_blade_view()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(Create::class);

        // Check HTML output contains user information
        $component
            ->assertSee('Test User')
            ->assertSee('Test Department')
            ->assertSee('TD')
            ->assertDontSee('Unknown User')
            ->assertDontSee('Department not set');
    }

    /** @test */
    public function properties_are_accessible_without_this_in_blade()
    {
        $this->actingAs($this->user);

        // Get the rendered HTML
        $component = Livewire::test(Create::class);

        // Verify that user_name is accessible directly (without $this->)
        $component
            ->assertSee('Test User')
            ->assertSee('Test Department')
            ->assertSee('TD');
    }
}
