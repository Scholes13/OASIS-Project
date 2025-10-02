<?php

namespace Tests\Feature\Livewire\PurchaseRequests;

use App\Livewire\PurchaseRequests\RequestNumber;
use App\Models\BusinessUnit;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RequestNumberTest extends TestCase
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

    /** @test */
    public function it_can_render_the_component()
    {
        $this->actingAs($this->user);

        Livewire::test(RequestNumber::class)
            ->assertStatus(200)
            ->assertSee('Request PR Number')
            ->assertSee('Keperluan')
            ->assertSee('Deskripsi');
    }

    /** @test */
    public function it_initializes_with_empty_form_fields()
    {
        $this->actingAs($this->user);

        Livewire::test(RequestNumber::class)
            ->assertSet('purpose', '')
            ->assertSet('description', '')
            ->assertSet('generatedNumber', null);
    }

    /** @test */
    public function it_shows_form_as_invalid_when_fields_are_empty()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(RequestNumber::class);

        // Test computed property
        $this->assertFalse($component->instance()->getIsFormValidProperty());

        // Test UI reflects invalid state
        $component->assertSee('Lengkapi semua field yang diperlukan');
    }

    /** @test */
    public function it_shows_form_as_invalid_when_purpose_is_too_short()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(RequestNumber::class)
            ->set('purpose', 'ab') // Only 2 characters
            ->set('description', 'This is a valid description with more than 10 characters');

        // Test computed property
        $this->assertFalse($component->instance()->getIsFormValidProperty());

        // Test UI reflects invalid state
        $component->assertSee('Lengkapi semua field yang diperlukan');
    }

    /** @test */
    public function it_shows_form_as_invalid_when_description_is_too_short()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(RequestNumber::class)
            ->set('purpose', 'Valid purpose')
            ->set('description', 'short'); // Only 5 characters

        // Test computed property
        $this->assertFalse($component->instance()->getIsFormValidProperty());

        // Test UI reflects invalid state
        $component->assertSee('Lengkapi semua field yang diperlukan');
    }

    /** @test */
    public function it_shows_form_as_valid_when_both_fields_meet_minimum_requirements()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(RequestNumber::class)
            ->set('purpose', 'Valid purpose for testing')
            ->set('description', 'This is a valid description with more than 10 characters for testing');

        // Test computed property
        $this->assertTrue($component->instance()->getIsFormValidProperty());

        // Test UI reflects valid state
        $component->assertSee('Klik generate untuk mereservasi nomor PR');
    }

    /** @test */
    public function it_handles_whitespace_correctly_in_validation()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(RequestNumber::class)
            ->set('purpose', '   abc   ') // 3 chars with whitespace
            ->set('description', '   This is valid   '); // Valid with whitespace

        // Test computed property handles trimming
        $this->assertTrue($component->instance()->getIsFormValidProperty());
    }

    /** @test */
    public function it_shows_form_as_invalid_when_fields_are_only_whitespace()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(RequestNumber::class)
            ->set('purpose', '   ') // Only whitespace
            ->set('description', '          '); // Only whitespace

        // Test computed property
        $this->assertFalse($component->instance()->getIsFormValidProperty());
    }

    /** @test */
    public function it_updates_form_validity_when_purpose_is_updated()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(RequestNumber::class);

        // Initially invalid
        $this->assertFalse($component->instance()->getIsFormValidProperty());

        // Set valid description first
        $component->set('description', 'This is a valid description with more than 10 characters');
        $this->assertFalse($component->instance()->getIsFormValidProperty()); // Still invalid due to purpose

        // Now set valid purpose
        $component->set('purpose', 'Valid purpose');
        $this->assertTrue($component->instance()->getIsFormValidProperty()); // Now valid
    }

    /** @test */
    public function it_updates_form_validity_when_description_is_updated()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(RequestNumber::class);

        // Set valid purpose first
        $component->set('purpose', 'Valid purpose');
        $this->assertFalse($component->instance()->getIsFormValidProperty()); // Still invalid due to description

        // Now set valid description
        $component->set('description', 'This is a valid description with more than 10 characters');
        $this->assertTrue($component->instance()->getIsFormValidProperty()); // Now valid
    }

    /** @test */
    public function button_is_disabled_when_form_is_invalid()
    {
        $this->actingAs($this->user);

        Livewire::test(RequestNumber::class)
            ->set('purpose', 'ab') // Too short
            ->set('description', 'short') // Too short
            ->assertSee('disabled')
            ->assertSee('Lengkapi semua field yang diperlukan');
    }

    /** @test */
    public function button_is_enabled_when_form_is_valid()
    {
        $this->actingAs($this->user);

        Livewire::test(RequestNumber::class)
            ->set('purpose', 'Valid purpose for testing')
            ->set('description', 'This is a valid description with more than 10 characters')
            ->assertDontSee('disabled')
            ->assertSee('Klik generate untuk mereservasi nomor PR');
    }

    /** @test */
    public function it_validates_fields_correctly_on_submit()
    {
        $this->actingAs($this->user);

        Livewire::test(RequestNumber::class)
            ->set('purpose', 'ab') // Too short
            ->set('description', 'short') // Too short
            ->call('submitRequest')
            ->assertHasErrors(['purpose', 'description']);
    }

    /** @test */
    public function debug_current_field_values()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(RequestNumber::class)
            ->set('purpose', 'dasdadasdad') // From screenshot
            ->set('description', 'makan'); // From screenshot

        // Debug output
        $purpose = $component->instance()->purpose;
        $description = $component->instance()->description;
        $isValid = $component->instance()->getIsFormValidProperty();

        echo "\n=== DEBUG INFO ===\n";
        echo "Purpose: '{$purpose}' (length: ".strlen(trim($purpose)).")\n";
        echo "Description: '{$description}' (length: ".strlen(trim($description)).")\n";
        echo 'Is Valid: '.($isValid ? 'TRUE' : 'FALSE')."\n";
        echo 'Purpose >= 3: '.(strlen(trim($purpose)) >= 3 ? 'TRUE' : 'FALSE')."\n";
        echo 'Description >= 10: '.(strlen(trim($description)) >= 10 ? 'TRUE' : 'FALSE')."\n";
        echo "==================\n";

        // The issue: description "makan" is only 5 characters, needs 10
        $this->assertFalse($isValid);
        $this->assertEquals(5, strlen(trim($description)));
    }
}
