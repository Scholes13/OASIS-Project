<?php

namespace Tests\Unit\Services\Purchasing\Admin;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Notifications\Purchasing\Admin\TaskAssigned;
use App\Services\Modules\Purchasing\Admin\AdminTaskService;
use App\Services\Modules\Purchasing\Admin\PriceEfficiencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminTaskServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_claims_starts_and_completes_tasks_consistently(): void
    {
        [$user, $businessUnit, $department] = $this->createPurchasingAdminContext();
        $this->actingAs($user);
        $this->setPurchasingAdminSession($businessUnit, $department);
        $this->mock(PriceEfficiencyService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('calculateSavings')
                ->once()
                ->with(200000.0, 175000.0)
                ->andReturn([
                    'savings_amount' => 25000.0,
                    'savings_percentage' => 12.5,
                ]);
        });

        $service = app(AdminTaskService::class);

        $claimTask = AdminTask::create([
            'taskable_type' => PurchaseRequest::class,
            'taskable_id' => 401,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'status' => 'pending_followup',
            'entered_at' => now()->subHours(2),
            'estimated_total_price' => 100000,
        ]);

        $service = app(AdminTaskService::class);
        $claimedTask = $service->claimTask($claimTask, $user->id);

        $this->assertSame($user->id, $claimedTask->assigned_admin_id);
        $this->assertSame('pending_followup', $claimedTask->status);

        $startTask = AdminTask::create([
            'taskable_type' => PurchaseRequest::class,
            'taskable_id' => 402,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'assigned_admin_id' => $user->id,
            'status' => 'pending_followup',
            'entered_at' => now()->subHours(3),
            'estimated_total_price' => 150000,
        ]);

        $startedTask = $service->startTask($startTask);
        $this->assertSame('in_progress', $startedTask->status);
        $this->assertNotNull($startedTask->started_at);
        $this->assertNotNull($startedTask->followup_time_minutes);

        $completeTask = AdminTask::create([
            'taskable_type' => PurchaseRequest::class,
            'taskable_id' => 403,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'assigned_admin_id' => $user->id,
            'status' => 'in_progress',
            'entered_at' => now()->subHours(4),
            'started_at' => now()->subHour(),
            'estimated_total_price' => 200000,
        ]);

        $completedTask = $service->completeTask($completeTask, 175000.0, 'Finished');

        $this->assertSame('done', $completedTask->status);
        $this->assertSame(175000.0, (float) $completedTask->realized_total_price);
        $this->assertSame('Finished', $completedTask->notes);
        $this->assertSame(25000.0, (float) $completedTask->savings_amount);
        $this->assertSame(12.5, (float) $completedTask->savings_percentage);
        $this->assertNotNull($completedTask->completed_at);
    }

    #[Test]
    public function it_notifies_the_assigned_admin_when_a_task_is_claimed(): void
    {
        Notification::fake();

        [$user, $businessUnit, $department] = $this->createPurchasingAdminContext();
        $this->actingAs($user);
        $this->setPurchasingAdminSession($businessUnit, $department);

        $task = AdminTask::create([
            'taskable_type' => PurchaseRequest::class,
            'taskable_id' => 901,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'status' => 'pending_followup',
            'entered_at' => now()->subHour(),
            'estimated_total_price' => 100000,
        ]);

        app(AdminTaskService::class)->claimTask($task, $user->id);

        Notification::assertSentTo($user, TaskAssigned::class);
    }

    /**
     * @return array{0: User, 1: BusinessUnit, 2: Department}
     */
    private function createPurchasingAdminContext(): array
    {
        $businessUnit = BusinessUnit::factory()->create([
            'is_active' => true,
        ]);

        $department = Department::create([
            'business_unit_id' => $businessUnit->id,
            'code' => 'PRC',
            'name' => 'Purchasing',
            'is_active' => true,
            'is_purchasing_department' => true,
        ]);

        $position = Position::query()
            ->where('department_id', $department->id)
            ->where('code', 'STAFF_'.strtoupper($department->code))
            ->firstOrFail();

        $user = User::factory()->create([
            'primary_department_id' => $department->id,
            'primary_position_id' => $position->id,
            'email_verified_at' => now(),
        ]);

        UserBusinessUnit::create([
            'user_id' => $user->id,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'is_primary' => true,
            'is_active' => true,
            'is_purchasing_admin' => true,
        ]);

        return [$user, $businessUnit, $department];
    }

    private function setPurchasingAdminSession(BusinessUnit $businessUnit, Department $department): void
    {
        session([
            'current_business_unit_id' => $businessUnit->id,
            'current_business_unit_code' => $businessUnit->code,
            'current_business_unit_name' => $businessUnit->name,
            'current_department_id' => $department->id,
        ]);
    }
}
