<?php

namespace Tests\Feature\Activity;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\BackdatePermission;
use App\Services\Modules\Activity\BackdatePermissionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackdateValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected BusinessUnit $businessUnit;

    protected Department $department;

    protected ActivityType $activityType;

    protected BackdatePermissionService $backdateService;

    protected function setUp(): void
    {
        parent::setUp();

        config(['features.backdate_approval' => true]);

        // Create test data
        $this->businessUnit = BusinessUnit::factory()->create();
        $this->department = Department::factory()->create([
            'business_unit_id' => $this->businessUnit->id,
        ]);
        $this->user = User::factory()->create([
            'primary_department_id' => $this->department->id,
        ]);
        $this->activityType = ActivityType::create([
            'code' => 'TEST',
            'name' => 'Test Activity',
            'color' => 'blue',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Set session
        session(['current_business_unit_id' => $this->businessUnit->id]);

        $this->backdateService = app(BackdatePermissionService::class);
    }

    #[Test]
    public function user_can_create_task_with_today_date()
    {
        $today = Carbon::today();

        $canCreate = $this->backdateService->canCreateTaskWithDate($this->user, $today);

        $this->assertTrue($canCreate);
    }

    #[Test]
    public function user_can_create_task_with_yesterday_date()
    {
        $yesterday = Carbon::yesterday();

        $canCreate = $this->backdateService->canCreateTaskWithDate($this->user, $yesterday);

        $this->assertTrue($canCreate);
    }

    #[Test]
    public function user_cannot_create_task_with_date_older_than_yesterday_without_permission()
    {
        $twoDaysAgo = Carbon::today()->subDays(2);

        $canCreate = $this->backdateService->canCreateTaskWithDate($this->user, $twoDaysAgo);

        $this->assertFalse($canCreate);
    }

    #[Test]
    public function user_can_create_task_with_old_date_when_has_active_permission()
    {
        $tenDaysAgo = Carbon::today()->subDays(10);

        // Create active backdate permission
        BackdatePermission::create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'business_unit_id' => $this->businessUnit->id,
            'requested_date' => $tenDaysAgo,
            'reason' => 'Test reason',
            'status' => 'approved',
            'approved_by' => $this->user->id,
            'approved_at' => now(),
            'granted_until' => now()->endOfDay(),
        ]);

        $canCreate = $this->backdateService->canCreateTaskWithDate($this->user, $tenDaysAgo);

        $this->assertTrue($canCreate);
    }

    #[Test]
    public function user_cannot_create_task_with_date_before_granted_permission()
    {
        $tenDaysAgo = Carbon::today()->subDays(10);
        $fifteenDaysAgo = Carbon::today()->subDays(15);

        // Create active backdate permission for 10 days ago
        BackdatePermission::create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'business_unit_id' => $this->businessUnit->id,
            'requested_date' => $tenDaysAgo,
            'reason' => 'Test reason',
            'status' => 'approved',
            'approved_by' => $this->user->id,
            'approved_at' => now(),
            'granted_until' => now()->endOfDay(),
        ]);

        // Try to create task with date older than permission
        $canCreate = $this->backdateService->canCreateTaskWithDate($this->user, $fifteenDaysAgo);

        $this->assertFalse($canCreate);
    }

    #[Test]
    public function allowed_date_range_is_yesterday_to_today_by_default()
    {
        $range = $this->backdateService->getAllowedDateRange($this->user);

        $this->assertEquals(Carbon::yesterday()->startOfDay()->toDateString(), $range['from']->toDateString());
        $this->assertEquals(Carbon::today()->addYear()->toDateString(), $range['to']->toDateString());
    }

    #[Test]
    public function allowed_date_range_extends_with_active_permission()
    {
        $tenDaysAgo = Carbon::today()->subDays(10);

        // Create active backdate permission
        BackdatePermission::create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'business_unit_id' => $this->businessUnit->id,
            'requested_date' => $tenDaysAgo,
            'reason' => 'Test reason',
            'status' => 'approved',
            'approved_by' => $this->user->id,
            'approved_at' => now(),
            'granted_until' => now()->endOfDay(),
        ]);

        $range = $this->backdateService->getAllowedDateRange($this->user);

        $this->assertEquals($tenDaysAgo->toDateString(), $range['from']->toDateString());
        $this->assertEquals(Carbon::today()->addYear()->toDateString(), $range['to']->toDateString());
    }
}
