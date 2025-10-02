<?php

namespace Tests\Feature\Livewire\PurchaseRequests;

use App\Livewire\PurchaseRequests\RequestNumber;
use App\Models\BusinessUnit;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RequestNumberUITest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $department;

    protected $businessUnit;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->businessUnit = BusinessUnit::factory()->create([
            'code' => 'IT',
            'name' => 'Information Technology',
            'is_active' => true,
        ]);

        $this->department = Department::factory()->create([
            'business_unit_id' => $this->businessUnit->id,
            'code' => 'IT',
            'name' => 'Information Technology',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'primary_department_id' => $this->department->id,
            'is_active' => true,
        ]);

        // Set session data
        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_name' => $this->businessUnit->name,
            'current_department_id' => $this->department->id,
        ]);
    }

    public function test_shows_specific_validation_messages_for_short_fields()
    {
        $this->actingAs($this->user);

        Livewire::test(RequestNumber::class)
            ->set('purpose', 'ab') // 2 characters - too short
            ->set('description', 'makan') // 5 characters - too short
            ->assertSee('Keperluan minimal 3 karakter (saat ini: 2)')
            ->assertSee('Deskripsi minimal 10 karakter (saat ini: 5)')
            ->assertSee('Lengkapi persyaratan berikut');
    }

    public function test_shows_valid_status_when_fields_meet_requirements()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(RequestNumber::class)
            ->set('purpose', 'Valid purpose text') // 18 characters - valid
            ->set('description', 'This is a valid description with enough characters'); // 50+ characters - valid

        // Check that the form is valid
        $this->assertTrue($component->instance()->getIsFormValidProperty());

        $component->assertSee('Semua persyaratan terpenuhi');
    }

    public function test_shows_mixed_validation_status()
    {
        $this->actingAs($this->user);

        Livewire::test(RequestNumber::class)
            ->set('purpose', 'Valid purpose') // Valid
            ->set('description', 'short') // Invalid
            ->assertSee('Keperluan sudah valid')
            ->assertSee('Deskripsi minimal 10 karakter (saat ini: 5)');
    }

    public function test_button_disabled_with_clear_feedback_for_screenshot_scenario()
    {
        $this->actingAs($this->user);

        // Test the exact scenario from the screenshot
        Livewire::test(RequestNumber::class)
            ->set('purpose', 'dasdadasdad') // 11 characters - valid
            ->set('description', 'makan') // 5 characters - invalid
            ->assertSee('Keperluan sudah valid')
            ->assertSee('Deskripsi minimal 10 karakter (saat ini: 5)')
            ->assertSee('disabled') // Button should be disabled
            ->assertDontSee('Semua persyaratan terpenuhi');
    }

    public function test_real_time_character_count_updates()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(RequestNumber::class);

        // Test purpose character count
        $component->set('purpose', 'a')
            ->assertSee('1/500 karakter');

        $component->set('purpose', 'abc')
            ->assertSee('3/500 karakter');

        // Test description character count
        $component->set('description', 'hello')
            ->assertSee('5/1000 karakter');

        $component->set('description', 'hello world test')
            ->assertSee('16/1000 karakter');
    }

    public function test_visual_indicators_change_based_on_validity()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(RequestNumber::class);

        // Initially invalid - should show red indicators
        $component->set('purpose', 'ab')
            ->set('description', 'short')
            ->assertSee('text-red-500')
            ->assertSee('(minimal 3 karakter)')
            ->assertSee('(minimal 10 karakter)');

        // Make valid - should show green indicators
        $component->set('purpose', 'Valid purpose text')
            ->set('description', 'This is a valid description with enough characters')
            ->assertSee('text-green-600')
            ->assertSee('Valid');
    }
}
