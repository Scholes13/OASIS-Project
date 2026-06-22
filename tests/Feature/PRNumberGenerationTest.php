<?php

namespace Tests\Feature;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Services\Modules\Purchasing\PurchaseRequest\UniversalPRNumberingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PRNumberGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected BusinessUnit $businessUnit;

    protected Department $department;

    protected Position $position;

    protected UniversalPRNumberingService $prNumberingService;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->position = Position::where('department_id', $this->department->id)
            ->where('code', 'HOD_'.strtoupper($this->department->code))
            ->firstOrFail();

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

        UserBusinessUnit::create([
            'user_id' => $this->user->id,
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'role' => 'hod',
            'is_primary' => true,
            'is_active' => true,
        ]);

        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_department_id' => $this->department->id,
        ]);

        $this->prNumberingService = app(UniversalPRNumberingService::class);
    }

    #[Test]
    public function it_can_generate_pr_number_for_valid_user()
    {
        $result = $this->prNumberingService->generatePRNumber($this->user);

        $this->assertIsArray($result);
        $this->assertMatchesRegularExpression('/^PR\.WNS\/\d{6}\/\d{3}$/', $result['formatted_number']);
        $this->assertSame('WNS', $result['business_unit_code']);
        $this->assertSame('GA', $result['department_code']);
        $this->assertSame(1, $result['sequence_number']);
    }

    #[Test]
    public function it_generates_sequential_numbers()
    {
        $first = $this->prNumberingService->generatePRNumber($this->user);
        $second = $this->prNumberingService->generatePRNumber($this->user);

        $this->assertSame(1, $first['sequence_number']);
        $this->assertSame(2, $second['sequence_number']);
    }

    #[Test]
    public function it_generates_number_for_specific_date()
    {
        $date = Carbon::create(2026, 3, 15);

        $result = $this->prNumberingService->generatePRNumber($this->user, null, null, $date);

        $this->assertStringContainsString('/202603/', $result['formatted_number']);
        $this->assertSame(2026, $result['year']);
        $this->assertSame(3, $result['month']);
    }

    #[Test]
    public function it_provides_next_number_preview()
    {
        $preview = $this->prNumberingService->getNextPRNumberPreview($this->user);

        $this->assertIsArray($preview);
        $this->assertMatchesRegularExpression('/^PR\.WNS\/\d{6}\/\d{3}$/', $preview['preview_number']);
        $this->assertSame(1, $preview['next_sequence']);
        $this->assertSame('WNS', $preview['business_unit']['code']);
        $this->assertSame('GA', $preview['department']['code']);
    }

    #[Test]
    public function it_validates_and_parses_pr_number_format()
    {
        $validNumber = 'PR.WNS/202602/001';
        $invalidNumber = 'PR-GA-2026-02-001';

        $this->assertTrue($this->prNumberingService->validatePRNumber($validNumber));
        $this->assertFalse($this->prNumberingService->validatePRNumber($invalidNumber));

        $parsed = $this->prNumberingService->parseUniversalPRNumber($validNumber);
        $this->assertTrue($parsed['valid']);
        $this->assertSame('WNS', $parsed['business_unit_code']);
        $this->assertSame(2026, $parsed['year']);
        $this->assertSame(2, $parsed['month']);
        $this->assertSame(1, $parsed['sequence']);
    }

    #[Test]
    public function it_gets_user_available_business_units()
    {
        $list = $this->prNumberingService->getUserAvailableBusinessUnits($this->user);

        $this->assertCount(1, $list);
        $this->assertSame('WNS', $list[0]['code']);
        $this->assertNotEmpty($list[0]['departments']);
    }

    #[Test]
    public function it_throws_exception_when_user_has_no_business_unit_context()
    {
        $otherUser = User::create([
            'name' => 'No Access User',
            'email' => 'no-access@example.com',
            'phone_number' => '081200000099',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        session()->forget('current_business_unit_id');
        session()->forget('current_department_id');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Business unit not found or user has no access');

        $this->prNumberingService->generatePRNumber($otherUser);
    }
}
