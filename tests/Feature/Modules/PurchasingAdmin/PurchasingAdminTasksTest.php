<?php

namespace Tests\Feature\Modules\PurchasingAdmin;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Modules\Purchasing\Admin\AdminTaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchasingAdminTasksTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function tasks_page_filters_stock_request_tasks_using_the_stock_request_namespace(): void
    {
        config(['inertia.testing.ensure_pages_exist' => false]);

        [$user, $businessUnit, $department] = $this->createPurchasingAdminContext();
        $this->actingAs($user);
        $this->setPurchasingAdminSession($businessUnit, $department);

        AdminTask::create([
            'taskable_type' => PurchaseRequest::class,
            'taskable_id' => 101,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'status' => 'pending_followup',
            'entered_at' => now()->subDay(),
            'estimated_total_price' => 100000,
        ]);

        AdminTask::create([
            'taskable_type' => StockRequest::class,
            'taskable_id' => 202,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'status' => 'pending_followup',
            'entered_at' => now()->subHours(2),
            'estimated_total_price' => 150000,
        ]);

        $response = $this->get(route('purchasing.admin.tasks', ['type' => 'stock_request']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('PurchasingAdmin/Tasks')
            ->has('tasks.data', 1)
            ->has('allTasks', 1)
            ->where('tasks.data.0.taskable_type', StockRequest::class)
            ->where('allTasks.0.taskable_type', StockRequest::class)
        );
    }

    #[Test]
    public function task_actions_delegate_to_admin_task_service(): void
    {
        config(['inertia.testing.ensure_pages_exist' => false]);

        [$user, $businessUnit, $department] = $this->createPurchasingAdminContext();
        $this->actingAs($user);
        $this->setPurchasingAdminSession($businessUnit, $department);

        $claimTask = AdminTask::create([
            'taskable_type' => PurchaseRequest::class,
            'taskable_id' => 301,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'status' => 'pending_followup',
            'entered_at' => now()->subHours(3),
            'estimated_total_price' => 200000,
        ]);

        $startTask = AdminTask::create([
            'taskable_type' => PurchaseRequest::class,
            'taskable_id' => 302,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'assigned_admin_id' => $user->id,
            'status' => 'pending_followup',
            'entered_at' => now()->subHours(2),
            'estimated_total_price' => 250000,
        ]);

        $completeTask = AdminTask::create([
            'taskable_type' => PurchaseRequest::class,
            'taskable_id' => 303,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'assigned_admin_id' => $user->id,
            'status' => 'in_progress',
            'entered_at' => now()->subHours(4),
            'started_at' => now()->subHours(1),
            'estimated_total_price' => 300000,
        ]);

        $this->mock(AdminTaskService::class, function (MockInterface $mock) use ($claimTask, $startTask, $completeTask, $user): void {
            $mock->shouldReceive('claimTask')
                ->once()
                ->withArgs(fn (AdminTask $task, int $adminId): bool => $task->id === $claimTask->id && $adminId === $user->id)
                ->andReturn($claimTask);

            $mock->shouldReceive('startTask')
                ->once()
                ->withArgs(fn (AdminTask $task): bool => $task->id === $startTask->id)
                ->andReturn($startTask);

            $mock->shouldReceive('completeTask')
                ->once()
                ->withArgs(fn (AdminTask $task, float $realizedTotalPrice, ?string $notes): bool => $task->id === $completeTask->id && $realizedTotalPrice === 275000.0 && $notes === 'Task completed')
                ->andReturn($completeTask);
        });

        $this->post(route('purchasing.admin.tasks.claim', $claimTask))
            ->assertRedirect();

        $this->post(route('purchasing.admin.tasks.start', $startTask))
            ->assertRedirect();

        $this->post(route('purchasing.admin.tasks.complete', $completeTask), [
            'realized_total_price' => 275000,
            'vendor_name' => 'Vendor A',
            'notes' => 'Task completed',
        ])->assertRedirect();
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
