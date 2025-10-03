<?php

namespace Tests\Feature;

use App\Models\BusinessUnit;
use App\Models\Department;
use App\Models\NumberingModule;
use App\Models\Position;
use App\Models\User;
use App\Models\UserBusinessUnit;
use App\Services\Modules\Wns\PRNumberingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class WNSPRNumberingTest extends TestCase
{
    use RefreshDatabase;

    protected PRNumberingService $prNumberingService;

    protected BusinessUnit $wnsBusinessUnit;

    protected Department $basDepartment;

    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Redis for testing
        Redis::shouldReceive('connection')
            ->andReturnSelf();
        Redis::shouldReceive('set')
            ->andReturn('OK');
        Redis::shouldReceive('del')
            ->andReturn(1);
        Redis::shouldReceive('keys')
            ->andReturn([]);

        // Create WNS business unit
        $this->wnsBusinessUnit = BusinessUnit::create([
            'code' => 'WNS',
            'name' => 'Werkudara Nirwana Sakti',
            'numbering_config' => [],
            'is_active' => true,
        ]);

        // Create BAS department
        $this->basDepartment = Department::create([
            'business_unit_id' => $this->wnsBusinessUnit->id,
            'code' => 'BAS',
            'name' => 'Business & Administrative Services',
            'is_active' => true,
        ]);

        // Create position
        $position = Position::create([
            'department_id' => $this->basDepartment->id,
            'name' => 'Staff',
            'code' => 'STAFF',
            'level' => 'staff',
            'hierarchy_level' => 1,
            'is_active' => true,
        ]);

        // Create test user
        $this->testUser = User::create([
            'name' => 'Test User BAS',
            'email' => 'test.bas@wns.com',
            'password' => bcrypt('password'),
            'phone_number' => '+628123456789',
            'primary_department_id' => $this->basDepartment->id,
            'primary_position_id' => $position->id,
            'global_role' => 'user',
            'is_active' => true,
        ]);

        // Assign user to WNS business unit
        UserBusinessUnit::create([
            'user_id' => $this->testUser->id,
            'business_unit_id' => $this->wnsBusinessUnit->id,
            'department_id' => $this->basDepartment->id,
            'position_id' => $position->id,
            'role' => 'staff',
            'is_primary' => true,
            'is_active' => true,
        ]);

        // Create numbering module
        NumberingModule::create([
            'business_unit_id' => $this->wnsBusinessUnit->id,
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

        $this->prNumberingService = app(PRNumberingService::class);
    }

    /**
     * Test BAS department PR number generation
     */
    public function test_bas_department_pr_number_generation(): void
    {
        // Test date: today
        $testDate = Carbon::now();

        // Generate PR number for BAS department
        $result = $this->prNumberingService->generatePRNumber($this->testUser, $testDate);

        // Expected format: PR.BAS/YYYY/MM/001
        $expectedFormat = sprintf(
            'PR.BAS/%d/%02d/001',
            $testDate->year,
            $testDate->month
        );

        $this->assertEquals($expectedFormat, $result['formatted_number']);
        $this->assertEquals('BAS', $result['department_code']);
        $this->assertEquals('WNS', $result['business_unit_code']);
        $this->assertEquals(1, $result['sequence_number']);
        $this->assertEquals($testDate->year, $result['year']);
        $this->assertEquals($testDate->month, $result['month']);
    }

    /**
     * Test cross-department sequential numbering
     */
    public function test_cross_department_sequential_numbering(): void
    {
        // Create GA department
        $gaDepartment = Department::create([
            'business_unit_id' => $this->wnsBusinessUnit->id,
            'code' => 'GA',
            'name' => 'General Affair',
            'is_active' => true,
        ]);

        $gaPosition = Position::create([
            'department_id' => $gaDepartment->id,
            'name' => 'Staff',
            'code' => 'STAFF',
            'level' => 'staff',
            'hierarchy_level' => 1,
            'is_active' => true,
        ]);

        $gaUser = User::create([
            'name' => 'Test User GA',
            'email' => 'test.ga@wns.com',
            'password' => bcrypt('password'),
            'phone_number' => '+628123456790',
            'primary_department_id' => $gaDepartment->id,
            'primary_position_id' => $gaPosition->id,
            'global_role' => 'user',
            'is_active' => true,
        ]);

        UserBusinessUnit::create([
            'user_id' => $gaUser->id,
            'business_unit_id' => $this->wnsBusinessUnit->id,
            'department_id' => $gaDepartment->id,
            'position_id' => $gaPosition->id,
            'role' => 'staff',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $testDate = Carbon::now();

        // Generate first PR for GA department
        $gaResult1 = $this->prNumberingService->generatePRNumber($gaUser, $testDate);
        $this->assertEquals(sprintf('PR.GA/%d/%02d/001', $testDate->year, $testDate->month), $gaResult1['formatted_number']);
        $this->assertEquals(1, $gaResult1['sequence_number']);

        // Generate first PR for BAS department - should get sequence 2
        $basResult1 = $this->prNumberingService->generatePRNumber($this->testUser, $testDate);
        $this->assertEquals(sprintf('PR.BAS/%d/%02d/002', $testDate->year, $testDate->month), $basResult1['formatted_number']);
        $this->assertEquals(2, $basResult1['sequence_number']);

        // Generate second PR for GA department - should get sequence 3
        $gaResult2 = $this->prNumberingService->generatePRNumber($gaUser, $testDate);
        $this->assertEquals(sprintf('PR.GA/%d/%02d/003', $testDate->year, $testDate->month), $gaResult2['formatted_number']);
        $this->assertEquals(3, $gaResult2['sequence_number']);

        // Generate second PR for BAS department - should get sequence 4
        $basResult2 = $this->prNumberingService->generatePRNumber($this->testUser, $testDate);
        $this->assertEquals(sprintf('PR.BAS/%d/%02d/004', $testDate->year, $testDate->month), $basResult2['formatted_number']);
        $this->assertEquals(4, $basResult2['sequence_number']);
    }

    /**
     * Test month change doesn't reset sequence (only year change resets)
     */
    public function test_month_change_does_not_reset_sequence(): void
    {
        $january = Carbon::create(2025, 1, 15);
        $february = Carbon::create(2025, 2, 15);

        // Generate PR in January
        $januaryResult = $this->prNumberingService->generatePRNumber($this->testUser, $january);
        $this->assertEquals('PR.BAS/2025/01/001', $januaryResult['formatted_number']);
        $this->assertEquals(1, $januaryResult['sequence_number']);

        // Generate PR in February - should continue sequence
        $februaryResult = $this->prNumberingService->generatePRNumber($this->testUser, $february);
        $this->assertEquals('PR.BAS/2025/02/002', $februaryResult['formatted_number']);
        $this->assertEquals(2, $februaryResult['sequence_number']);
    }

    /**
     * Test year change resets sequence
     */
    public function test_year_change_resets_sequence(): void
    {
        $december2024 = Carbon::create(2024, 12, 31);
        $january2025 = Carbon::create(2025, 1, 1);

        // Generate PR in December 2024
        $result2024 = $this->prNumberingService->generatePRNumber($this->testUser, $december2024);
        $this->assertEquals('PR.BAS/2024/12/001', $result2024['formatted_number']);
        $this->assertEquals(1, $result2024['sequence_number']);

        // Generate PR in January 2025 - should reset to 001
        $result2025 = $this->prNumberingService->generatePRNumber($this->testUser, $january2025);
        $this->assertEquals('PR.BAS/2025/01/001', $result2025['formatted_number']);
        $this->assertEquals(1, $result2025['sequence_number']);
    }

    /**
     * Test PR creation with number
     */
    public function test_create_pr_with_number(): void
    {
        $testDate = Carbon::now();

        $prData = [
            'used_for' => 'Office Supplies',
            'keperluan' => 'Stationery and office equipment',
            'date_of_request' => $testDate->toDateString(),
        ];

        $pr = $this->prNumberingService->createPRWithNumber($this->testUser, $prData);

        $expectedNumber = sprintf('PR.BAS/%d/%02d/001', $testDate->year, $testDate->month);

        $this->assertEquals($expectedNumber, $pr->pr_number);
        $this->assertEquals('Office Supplies', $pr->used_for);
        $this->assertEquals('Stationery and office equipment', $pr->keperluan);
        $this->assertEquals($this->testUser->id, $pr->user_id);
        $this->assertEquals($this->basDepartment->id, $pr->department_id);
        $this->assertEquals($this->wnsBusinessUnit->id, $pr->business_unit_id);
        $this->assertEquals('draft', $pr->status);
    }

    /**
     * Test getting next PR number preview
     */
    public function test_get_next_pr_number_preview(): void
    {
        $testDate = Carbon::now();

        $preview = $this->prNumberingService->getNextPRNumberPreview($this->testUser, $testDate);

        $expectedPreview = sprintf('PR.BAS/%d/%02d/001', $testDate->year, $testDate->month);

        $this->assertEquals($expectedPreview, $preview['preview_number']);
        $this->assertEquals(1, $preview['next_sequence']);
        $this->assertEquals('BAS', $preview['department_code']);
        $this->assertEquals($testDate->year, $preview['year']);
        $this->assertEquals($testDate->month, $preview['month']);
        $this->assertStringContainsString('Cross-department sequential numbering', $preview['note']);
    }
}
