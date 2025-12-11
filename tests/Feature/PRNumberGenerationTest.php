<?php

namespace Tests\Feature;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\NumberingModule;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Services\Modules\Purchasing\PurchaseRequest\UniversalPRNumberingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PRNumberGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $businessUnit;

    protected $department;

    protected $position;

    protected $prNumberingService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test business unit
        $this->businessUnit = BusinessUnit::create([
            'code' => 'WNS',
            'name' => 'Werkudara Nirwana Sakti',
            'description' => 'Test business unit',
            'is_active' => true,
        ]);

        // Create test department
        $this->department = Department::create([
            'business_unit_id' => $this->businessUnit->id,
            'code' => 'GA',
            'name' => 'General Affairs',
            'description' => 'Test department',
            'is_active' => true,
        ]);

        // Create test position
        $this->position = Position::create([
            'department_id' => $this->department->id,
            'code' => 'HOD_GA',
            'name' => 'Head of General Affairs',
            'level' => 'hod',
            'description' => 'Test position',
            'is_active' => true,
        ]);

        // Create test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone_number' => '+62812345678999',
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->position->id,
            'global_role' => 'user',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Create business unit assignment
        UserBusinessUnit::create([
            'user_id' => $this->user->id,
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'role' => 'hod',
            'is_primary' => true,
            'is_active' => true,
        ]);

        // Create numbering module for PR
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

        // Initialize the service
        $this->prNumberingService = app(UniversalPRNumberingService::class);
    }

    /** @test */
    public function it_can_generate_pr_number_for_valid_user()
    {
        // Act
        $result = $this->prNumberingService->generatePRNumber($this->user);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('formatted_number', $result);
        $this->assertArrayHasKey('sequence_number', $result);
        $this->assertArrayHasKey('department_code', $result);

        // Check format: PR.GA/2025/09/001
        $expectedPattern = '/^PR\.GA\/\d{4}\/\d{2}\/\d{3}$/';
        $this->assertMatchesRegularExpression($expectedPattern, $result['formatted_number']);

        $this->assertEquals('GA', $result['department_code']);
        $this->assertEquals(1, $result['sequence_number']);
    }

    /** @test */
    public function it_generates_sequential_numbers()
    {
        // Generate first PR number
        $result1 = $this->prNumberingService->generatePRNumber($this->user);

        // Generate second PR number
        $result2 = $this->prNumberingService->generatePRNumber($this->user);

        // Assert they are sequential
        $this->assertEquals(1, $result1['sequence_number']);
        $this->assertEquals(2, $result2['sequence_number']);

        // Both should have same department and date format
        $this->assertEquals('GA', $result1['department_code']);
        $this->assertEquals('GA', $result2['department_code']);
    }

    /** @test */
    public function it_can_create_pr_with_generated_number()
    {
        // Arrange
        $prData = [
            'keperluan' => 'Test keperluan untuk PR testing',
            'description' => 'Test description untuk unit test',
            'currency' => 'IDR',
        ];

        // Act
        $pr = $this->prNumberingService->createPRWithNumber($this->user, $prData);

        // Assert
        $this->assertNotNull($pr);
        $this->assertEquals($this->user->id, $pr->user_id);
        $this->assertEquals($this->department->id, $pr->department_id);
        $this->assertEquals($this->businessUnit->id, $pr->business_unit_id);
        $this->assertEquals('Test keperluan untuk PR testing', $pr->keperluan);
        $this->assertEquals('IDR', $pr->currency);
        $this->assertEquals('draft', $pr->status);

        // Check PR number format
        $expectedPattern = '/^PR\.GA\/\d{4}\/\d{2}\/\d{3}$/';
        $this->assertMatchesRegularExpression($expectedPattern, $pr->pr_number);
    }

    /** @test */
    public function it_validates_user_can_create_pr()
    {
        // Valid user should pass validation
        $this->assertTrue($this->prNumberingService->validateUserCanCreatePR($this->user));

        // User without primary department should fail
        $userWithoutDept = User::create([
            'name' => 'User Without Department',
            'email' => 'nodept@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->assertFalse($this->prNumberingService->validateUserCanCreatePR($userWithoutDept));
    }

    /** @test */
    public function it_provides_next_number_preview()
    {
        // Act
        $preview = $this->prNumberingService->getNextPRNumberPreview($this->user);

        // Assert
        $this->assertIsArray($preview);
        $this->assertArrayHasKey('preview_number', $preview);
        $this->assertArrayHasKey('next_sequence', $preview);
        $this->assertArrayHasKey('department_code', $preview);

        $expectedPattern = '/^PR\.GA\/\d{4}\/\d{2}\/\d{3}$/';
        $this->assertMatchesRegularExpression($expectedPattern, $preview['preview_number']);

        $this->assertEquals('GA', $preview['department_code']);
        $this->assertEquals(1, $preview['next_sequence']);
    }

    /** @test */
    public function it_handles_different_dates_correctly()
    {
        // Generate PR for current month
        $currentDate = Carbon::now();
        $result1 = $this->prNumberingService->generatePRNumber($this->user, $currentDate);

        // Generate PR for next month
        $nextMonth = $currentDate->copy()->addMonth();
        $result2 = $this->prNumberingService->generatePRNumber($this->user, $nextMonth);

        // They should have different month in the format but sequential numbers
        $this->assertStringContainsString($currentDate->format('/Y/m/'), $result1['formatted_number']);
        $this->assertStringContainsString($nextMonth->format('/Y/m/'), $result2['formatted_number']);

        // Since it's cross-department shared sequence, both should be sequential
        $this->assertEquals(1, $result1['sequence_number']);
        $this->assertEquals(2, $result2['sequence_number']);
    }

    /** @test */
    public function it_gets_available_departments()
    {
        // Act
        $departments = $this->prNumberingService->getAvailableDepartments();

        // Assert
        $this->assertIsArray($departments);
        $this->assertCount(1, $departments);
        $this->assertEquals('GA', $departments[0]['code']);
        $this->assertEquals('General Affairs', $departments[0]['name']);
    }

    /** @test */
    public function it_gets_user_pr_history()
    {
        // Create some PRs first
        $pr1 = $this->prNumberingService->createPRWithNumber($this->user, [
            'keperluan' => 'First PR',
            'description' => 'First description',
        ]);

        $pr2 = $this->prNumberingService->createPRWithNumber($this->user, [
            'keperluan' => 'Second PR',
            'description' => 'Second description',
        ]);

        // Act
        $history = $this->prNumberingService->getUserPRHistory($this->user, 5);

        // Assert
        $this->assertIsArray($history);
        $this->assertCount(2, $history);

        // Should be ordered by created_at desc (newest first)
        $this->assertEquals($pr2->pr_number, $history[0]['pr_number']);
        $this->assertEquals($pr1->pr_number, $history[1]['pr_number']);
    }

    /** @test */
    public function it_throws_exception_for_user_without_wns_business_unit()
    {
        // Create a different business unit
        $otherBU = BusinessUnit::create([
            'code' => 'OTHER',
            'name' => 'Other Business Unit',
            'is_active' => true,
        ]);

        $otherDept = Department::create([
            'business_unit_id' => $otherBU->id,
            'code' => 'OTHER_DEPT',
            'name' => 'Other Department',
            'is_active' => true,
        ]);

        $userFromOtherBU = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'primary_department_id' => $otherDept->id,
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Should throw exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User must belong to WNS business unit');

        $this->prNumberingService->generatePRNumber($userFromOtherBU);
    }

    /** @test */
    public function it_validates_pr_number_format()
    {
        // Valid formats
        $this->assertTrue($this->prNumberingService->validatePRNumber('PR.GA/2025/09/001'));
        $this->assertTrue($this->prNumberingService->validatePRNumber('PR.IT/2024/12/999'));

        // Invalid formats
        $this->assertFalse($this->prNumberingService->validatePRNumber('PR.GA/25/09/001')); // Year too short
        $this->assertFalse($this->prNumberingService->validatePRNumber('PR.GA/2025/9/001')); // Month not padded
        $this->assertFalse($this->prNumberingService->validatePRNumber('PR.GA/2025/09/1')); // Sequence not padded
        $this->assertFalse($this->prNumberingService->validatePRNumber('GA/2025/09/001')); // Missing PR prefix
        $this->assertFalse($this->prNumberingService->validatePRNumber('PR-GA-2025-09-001')); // Wrong separators
    }

    /** @test */
    public function it_parses_pr_number_correctly()
    {
        // Valid PR number
        $result = $this->prNumberingService->parsePRNumber('PR.GA/2025/09/001');

        $this->assertTrue($result['valid']);
        $this->assertEquals('GA', $result['department_code']);
        $this->assertEquals(2025, $result['year']);
        $this->assertEquals(9, $result['month']);
        $this->assertEquals(1, $result['sequence']);

        // Invalid PR number
        $invalidResult = $this->prNumberingService->parsePRNumber('INVALID-FORMAT');
        $this->assertFalse($invalidResult['valid']);
    }
}
