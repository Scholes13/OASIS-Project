<?php

namespace Tests\Feature\Activity;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Activity\BackdatePermission;
use App\Notifications\Activity\BackdateRequestApproved;
use App\Notifications\Activity\BackdateRequestRejected;
use App\Notifications\Activity\BackdateRequestSubmitted;
use App\Services\Modules\Activity\BackdatePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackdateNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected BackdatePermissionService $service;

    protected User $employee;

    protected User $departmentHead;

    protected Department $department;

    protected BusinessUnit $businessUnit;

    protected Position $staffPosition;

    protected Position $headPosition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BackdatePermissionService;

        // Create business unit and department
        $this->businessUnit = BusinessUnit::factory()->create();
        $this->department = Department::factory()->create([
            'business_unit_id' => $this->businessUnit->id,
        ]);

        // Create positions
        $this->staffPosition = Position::create([
            'department_id' => $this->department->id,
            'name' => 'Staff',
            'code' => 'STAFF',
            'level' => 'staff',
            'access_level' => 'staff',
            'hierarchy_level' => 3,
            'is_active' => true,
        ]);

        $this->headPosition = Position::create([
            'department_id' => $this->department->id,
            'name' => 'Department Head',
            'code' => 'HOD',
            'level' => 'hod',
            'access_level' => 'department_head',
            'hierarchy_level' => 1,
            'is_active' => true,
        ]);

        // Create employee
        $this->employee = User::factory()->create([
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->staffPosition->id,
        ]);

        // Create department head
        $this->departmentHead = User::factory()->create([
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->headPosition->id,
        ]);

        // Create UserBusinessUnit assignments
        UserBusinessUnit::create([
            'user_id' => $this->employee->id,
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->staffPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        UserBusinessUnit::create([
            'user_id' => $this->departmentHead->id,
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->headPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        // Set session
        session(['current_business_unit_id' => $this->businessUnit->id]);
    }

    #[Test]
    public function it_sends_notification_when_backdate_request_is_submitted()
    {
        Notification::fake();

        $this->service->requestPermission([
            'reason' => 'Forgot to log tasks last week',
        ], $this->employee);

        Notification::assertSentTo(
            $this->departmentHead,
            BackdateRequestSubmitted::class
        );
    }

    #[Test]
    public function submitted_backdate_notification_uses_the_backdate_approvals_route(): void
    {
        $permission = BackdatePermission::create([
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'business_unit_id' => $this->businessUnit->id,
            'requested_date' => now()->subDays(3),
            'reason' => 'Need to log missed tasks',
            'status' => 'pending',
        ]);

        $notification = new BackdateRequestSubmitted($permission);

        $payload = $notification->toArray($this->departmentHead);

        $this->assertSame(route('activity.backdate.approvals'), $payload['action_url']);
    }

    #[Test]
    public function it_sends_notification_when_backdate_request_is_approved()
    {
        Notification::fake();

        $permission = BackdatePermission::create([
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'business_unit_id' => $this->businessUnit->id,
            'requested_date' => now()->subDays(5),
            'reason' => 'Forgot to log tasks',
            'status' => 'pending',
        ]);

        $this->service->approveRequest($permission, $this->departmentHead);

        Notification::assertSentTo(
            $this->employee,
            BackdateRequestApproved::class
        );
    }

    #[Test]
    public function it_sends_notification_when_backdate_request_is_rejected()
    {
        Notification::fake();

        $permission = BackdatePermission::create([
            'user_id' => $this->employee->id,
            'department_id' => $this->department->id,
            'business_unit_id' => $this->businessUnit->id,
            'requested_date' => now()->subDays(5),
            'reason' => 'Forgot to log tasks',
            'status' => 'pending',
        ]);

        $this->service->rejectRequest($permission, $this->departmentHead, 'Insufficient reason provided');

        Notification::assertSentTo(
            $this->employee,
            BackdateRequestRejected::class
        );
    }
}
