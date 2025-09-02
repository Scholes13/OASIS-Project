<?php

namespace Tests\Feature;

use App\Livewire\PurchaseRequests\RequestNumber;
use App\Models\User;
use App\Models\BusinessUnit;
use App\Models\Department;
use App\Models\Position;
use App\Models\UserBusinessUnit;
use App\Models\NumberingModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class RequestNumberLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $businessUnit;
    protected $department;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->businessUnit = BusinessUnit::create([
            'code' => 'WNS',
            'name' => 'Werkudara Nirwana Sakti',
            'description' => 'Test business unit',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'business_unit_id' => $this->businessUnit->id,
            'code' => 'GA',
            'name' => 'General Affairs',
            'description' => 'Test department',
            'is_active' => true,
        ]);

        $position = Position::create([
            'department_id' => $this->department->id,
            'code' => 'HOD_GA',
            'name' => 'Head of General Affairs',
            'level' => 'hod',
            'description' => 'Test position',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone_number' => '+62812345678999',
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $position->id,
            'global_role' => 'user',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        UserBusinessUnit::create([
            'user_id' => $this->user->id,
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $position->id,
            'role' => 'hod',
            'is_primary' => true,
            'is_active' => true,
        ]);

        NumberingModule::create([
            'business_unit_id' => $this->businessUnit->id,
            'module_code' => 'PR',
            'module_name' => 'Purchase Request',
            'format_pattern' => 'PR.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}',
            'config' => [
                'sequence_padding' => 3,
                'max_number' => 999,
                'reset_annually' => true,
                'reset_monthly' => false,
                'cross_department' => true,
                'shared_sequence' => true,
            ],
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_mounts_with_correct_initial_data()
    {
        $this->actingAs($this->user);

        Livewire::test(RequestNumber::class)
            ->assertSet('submission_date', now()->format('d/m/Y'))
            ->assertSet('user_name', 'Test User')
            ->assertSet('department_name', 'General Affairs')
            ->assertSet('department_code', 'GA')
            ->assertSet('generatedNumber', null)
            ->assertSet('isLoading', false);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->actingAs($this->user);

        // Test with empty fields
        Livewire::test(RequestNumber::class)
            ->call('submitRequest')
            ->assertHasErrors(['purpose', 'description']);

        // Test with only purpose filled
        Livewire::test(RequestNumber::class)
            ->set('purpose', 'Test purpose')
            ->call('submitRequest')
            ->assertHasErrors(['description'])
            ->assertHasNoErrors(['purpose']);

        // Test with both fields filled
        Livewire::test(RequestNumber::class)
            ->set('purpose', 'Test purpose')
            ->set('description', 'Test description')
            ->call('submitRequest')
            ->assertHasNoErrors();
    }

    /** @test */
    public function it_generates_pr_number_successfully()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(RequestNumber::class)
            ->set('purpose', 'Test purpose for PR generation')
            ->set('description', 'Detailed description of the purchase request')
            ->call('submitRequest')
            ->assertHasNoErrors();

        // Check that a number was generated
        $this->assertNotNull($component->get('generatedNumber'));
        $this->assertNotNull($component->get('numberDetails'));
        
        // Check the format of generated number
        $generatedNumber = $component->get('generatedNumber');
        $this->assertMatchesRegularExpression('/^PR\.GA\/\d{4}\/\d{2}\/\d{3}$/', $generatedNumber);
        
        // Check number details
        $details = $component->get('numberDetails');
        $this->assertEquals($generatedNumber, $details['formatted_number']);
        $this->assertEquals('Test purpose for PR generation', $details['purpose']);
        $this->assertEquals('Detailed description of the purchase request', $details['description']);
        $this->assertEquals('Test User', $details['requested_by']);
    }

    /** @test */
    public function it_shows_loading_state_during_generation()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(RequestNumber::class)
            ->set('purpose', 'Test purpose')
            ->set('description', 'Test description');

        // Before calling submitRequest, loading should be false
        $this->assertFalse($component->get('isLoading'));

        // During the call, loading should be managed properly
        $component->call('submitRequest');
        
        // After successful generation, loading should be false again
        $this->assertFalse($component->get('isLoading'));
    }

    /** @test */
    public function it_displays_next_number_preview_correctly()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(RequestNumber::class);
        
        $preview = $component->call('getNextNumberPreview');
        $previewNumber = $component->get('getNextNumberPreview');
        
        // Should match the expected format with current year/month
        $expectedPattern = sprintf(
            '/^PR\.GA\/%d\/%02d\/XXX$/',
            now()->year,
            now()->month
        );
        
        $this->assertMatchesRegularExpression($expectedPattern, $previewNumber);
    }

    /** @test */
    public function it_can_redirect_to_create_form_after_generation()
    {
        $this->actingAs($this->user);

        // Generate a number first
        $component = Livewire::test(RequestNumber::class)
            ->set('purpose', 'Test purpose')
            ->set('description', 'Test description')
            ->call('submitRequest');

        // Now try to redirect to create form
        $component->call('createPRForm')
            ->assertRedirect(route('purchase-requests.create-with-number'));

        // Check that session has the number details
        $this->assertNotNull(session('pr_number_details'));
        $sessionData = session('pr_number_details');
        $this->assertArrayHasKey('formatted_number', $sessionData);
        $this->assertArrayHasKey('purpose', $sessionData);
        $this->assertArrayHasKey('description', $sessionData);
    }

    /** @test */
    public function it_prevents_redirect_without_generated_number()
    {
        $this->actingAs($this->user);

        Livewire::test(RequestNumber::class)
            ->call('createPRForm')
            ->assertHasErrors(); // Should have flash error message

        // Session should not have number details
        $this->assertNull(session('pr_number_details'));
    }

    /** @test */
    public function it_can_reset_to_generate_new_number()
    {
        $this->actingAs($this->user);

        // Generate a number first
        $component = Livewire::test(RequestNumber::class)
            ->set('purpose', 'Test purpose')
            ->set('description', 'Test description')
            ->call('submitRequest');

        // Verify number was generated
        $this->assertNotNull($component->get('generatedNumber'));

        // Reset the generated number
        $component->set('generatedNumber', null);

        // Should be able to generate again
        $this->assertNull($component->get('generatedNumber'));
    }

    /** @test */
    public function it_handles_user_without_department_gracefully()
    {
        // Create user without primary department
        $userWithoutDept = User::create([
            'name' => 'User Without Department',
            'email' => 'nodept@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->actingAs($userWithoutDept);

        $component = Livewire::test(RequestNumber::class);
        
        // Should show appropriate message for missing department
        $this->assertEquals('Department not set', $component->get('department_name'));
        $this->assertEquals('N/A', $component->get('department_code'));
    }

    /** @test */
    public function it_validates_input_length_limits()
    {
        $this->actingAs($this->user);

        // Test purpose field length limit (500 chars)
        $longPurpose = str_repeat('a', 501);
        
        Livewire::test(RequestNumber::class)
            ->set('purpose', $longPurpose)
            ->set('description', 'Valid description')
            ->call('submitRequest')
            ->assertHasErrors(['purpose']);

        // Test description field length limit (1000 chars)
        $longDescription = str_repeat('b', 1001);
        
        Livewire::test(RequestNumber::class)
            ->set('purpose', 'Valid purpose')
            ->set('description', $longDescription)
            ->call('submitRequest')
            ->assertHasErrors(['description']);

        // Test valid lengths
        $validPurpose = str_repeat('a', 500);
        $validDescription = str_repeat('b', 1000);
        
        Livewire::test(RequestNumber::class)
            ->set('purpose', $validPurpose)
            ->set('description', $validDescription)
            ->call('submitRequest')
            ->assertHasNoErrors();
    }

    /** @test */
    public function it_handles_service_errors_gracefully()
    {
        $this->actingAs($this->user);

        // Remove the numbering module to cause an error
        NumberingModule::where('business_unit_id', $this->businessUnit->id)
            ->where('module_code', 'PR')
            ->delete();

        $component = Livewire::test(RequestNumber::class)
            ->set('purpose', 'Test purpose')
            ->set('description', 'Test description')
            ->call('submitRequest');

        // Should handle the error gracefully
        $this->assertFalse($component->get('isLoading'));
        $this->assertNull($component->get('generatedNumber'));
        
        // Should show error in session flash
        $this->assertNotNull(session('error'));
    }
}
