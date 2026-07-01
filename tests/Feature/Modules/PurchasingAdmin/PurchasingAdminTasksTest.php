<?php

namespace Tests\Feature\Modules\PurchasingAdmin;

use App\Actions\Modules\Purchasing\StockRequest\ProcessStockRequestGaReviewAction;
use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\NumberingModule;
use App\Models\Core\NumberSequence;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Models\Modules\Purchasing\StockRequest\StockItem;
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

    #[Test]
    public function approved_purchase_request_creates_one_unassigned_admin_task(): void
    {
        config(['inertia.testing.ensure_pages_exist' => false]);

        [$user, $businessUnit, $department] = $this->createPurchasingAdminContext();
        $this->actingAs($user);
        $this->setPurchasingAdminSession($businessUnit, $department);

        $purchaseRequest = $this->createPurchaseRequestInApproval($user, $businessUnit, $department);

        $purchaseRequest->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $purchaseRequest->forceFill(['status' => 'in_approval'])->saveQuietly();
        $purchaseRequest->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $this->assertSame(1, AdminTask::query()
            ->where('taskable_type', PurchaseRequest::class)
            ->where('taskable_id', $purchaseRequest->id)
            ->count());
        $this->assertDatabaseHas('admin_tasks', [
            'taskable_type' => PurchaseRequest::class,
            'taskable_id' => $purchaseRequest->id,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'assigned_admin_id' => null,
            'status' => 'pending_followup',
        ]);
    }

    #[Test]
    public function ga_review_procurement_items_create_stock_request_admin_task(): void
    {
        config(['inertia.testing.ensure_pages_exist' => false]);

        [$user, $businessUnit, $department] = $this->createPurchasingAdminContext();
        $this->actingAs($user);
        $this->setPurchasingAdminSession($businessUnit, $department);

        $stockRequest = $this->createStockRequestReadyForGaReview($user, $businessUnit, $department);
        $item = StockItem::create([
            'stock_request_id' => $stockRequest->id,
            'item_order' => 1,
            'item_name' => 'Printer toner',
            'quantity' => 3,
            'unit' => 'pcs',
            'price' => 100000,
            'total' => 300000,
        ]);

        app(ProcessStockRequestGaReviewAction::class)->approve($stockRequest->load('items'), $user, [
            'items' => [[
                'id' => $item->id,
                'ga_review_result' => 'need_procurement',
                'warehouse_available_qty' => 1,
                'procurement_quantity' => 2,
            ]],
        ]);

        $this->assertDatabaseHas('stock_requests', [
            'id' => $stockRequest->id,
            'status' => 'ready_for_purchasing',
        ]);
        $this->assertDatabaseHas('admin_tasks', [
            'taskable_type' => StockRequest::class,
            'taskable_id' => $stockRequest->id,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'assigned_admin_id' => null,
            'status' => 'pending_followup',
        ]);

        $response = $this->get(route('purchasing.admin.tasks', ['type' => 'stock_request']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('PurchasingAdmin/Tasks')
            ->has('tasks.data', 1)
            ->where('tasks.data.0.taskable_type', StockRequest::class)
            ->where('tasks.data.0.taskable_id', $stockRequest->id)
        );
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

    private function createStockRequestReadyForGaReview(User $user, BusinessUnit $businessUnit, Department $department): StockRequest
    {
        $numberingModule = NumberingModule::create([
            'business_unit_id' => $businessUnit->id,
            'module_code' => 'ST',
            'module_name' => 'Stock Request',
            'format_pattern' => 'ST/{BU}/{YYYYMM}/{SEQ}',
            'is_active' => true,
        ]);

        $numberSequence = NumberSequence::create([
            'business_unit_id' => $businessUnit->id,
            'numbering_module_id' => $numberingModule->id,
            'department_id' => $department->id,
            'year' => (int) now()->format('Y'),
            'month' => (int) now()->format('m'),
            'current_number' => 1,
            'max_number' => 999,
        ]);

        return StockRequest::create([
            'st_number' => 'ST/'.$businessUnit->code.'/'.now()->format('Ym').'/001',
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'user_id' => $user->id,
            'sequence_id' => $numberSequence->id,
            'purpose' => 'Stock item for operational needs',
            'date_of_request' => now()->toDateString(),
            'expected_date' => now()->addDay()->toDateString(),
            'status' => 'ga_review',
            'submitted_at' => now(),
            'approved_at' => now(),
            'ga_review_started_at' => now(),
        ]);
    }

    private function createPurchaseRequestInApproval(User $user, BusinessUnit $businessUnit, Department $department): PurchaseRequest
    {
        $numberingModule = NumberingModule::create([
            'business_unit_id' => $businessUnit->id,
            'module_code' => 'PR',
            'module_name' => 'Purchase Request',
            'format_pattern' => 'PR/{BU}/{YYYYMM}/{SEQ}',
            'is_active' => true,
        ]);

        $numberSequence = NumberSequence::create([
            'business_unit_id' => $businessUnit->id,
            'numbering_module_id' => $numberingModule->id,
            'department_id' => $department->id,
            'year' => (int) now()->format('Y'),
            'month' => (int) now()->format('m'),
            'current_number' => 1,
            'max_number' => 999,
        ]);

        return PurchaseRequest::create([
            'pr_number' => 'PR/'.$businessUnit->code.'/'.now()->format('Ym').'/001',
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'user_id' => $user->id,
            'sequence_id' => $numberSequence->id,
            'used_for' => 'Operational procurement',
            'date_of_request' => now()->toDateString(),
            'status' => 'in_approval',
            'submitted_at' => now(),
            'total_amount' => 500000,
            'currency' => 'IDR',
        ]);
    }
}
