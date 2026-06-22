<?php

namespace Tests\Feature\Modules\StockRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\NumberingModule;
use App\Models\Core\NumberSequence;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Purchasing\StockRequest\StockApproval;
use App\Models\Modules\Purchasing\StockRequest\StockItem;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StockApprovalViewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function approver_can_open_stock_approval_detail_page(): void
    {
        config(['inertia.testing.ensure_pages_exist' => false]);

        [$approver, $stockApproval, $stockRequest] = $this->createStockApprovalFixture();
        /** @var \Illuminate\Contracts\Auth\Authenticatable $approver */
        $response = $this->actingAs($approver)
            ->get(route('stock-approvals.show', $stockApproval));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/StockRequest/Show')
            ->where('stockRequest.st_number', $stockRequest->st_number)
            ->where('approvalContext.approvalId', $stockApproval->id)
            ->where('approvalContext.canApprove', true)
            ->where('approvalContext.approvalStatus', 'pending')
            ->where('can.approve', true)
            ->where('can.reject', true)
            ->where('can.resendApprovalEmail', false)
        );
    }

    #[Test]
    public function non_approver_cannot_open_stock_approval_detail_page(): void
    {
        config(['inertia.testing.ensure_pages_exist' => false]);

        [, $stockApproval] = $this->createStockApprovalFixture();
        [$anotherUser] = $this->createUserContext();
        /** @var \Illuminate\Contracts\Auth\Authenticatable $anotherUser */
        $response = $this->actingAs($anotherUser)
            ->get(route('stock-approvals.show', $stockApproval));

        $response->assertForbidden();
    }

    #[Test]
    public function approver_cannot_open_stock_approval_detail_page_from_another_business_unit_context(): void
    {
        config(['inertia.testing.ensure_pages_exist' => false]);

        [$approver, $stockApproval] = $this->createStockApprovalFixture();
        $otherBusinessUnit = BusinessUnit::factory()->create();
        $otherDepartment = Department::factory()->create([
            'business_unit_id' => $otherBusinessUnit->id,
        ]);
        $otherPosition = Position::query()
            ->where('department_id', $otherDepartment->id)
            ->where('code', 'STAFF_'.strtoupper($otherDepartment->code))
            ->firstOrFail();

        UserBusinessUnit::create([
            'user_id' => $approver->id,
            'business_unit_id' => $otherBusinessUnit->id,
            'department_id' => $otherDepartment->id,
            'position_id' => $otherPosition->id,
            'is_primary' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($approver)
            ->withSession([
                'current_business_unit_id' => $otherBusinessUnit->id,
                'current_department_id' => $otherDepartment->id,
            ])
            ->get(route('stock-approvals.show', $stockApproval));

        $response->assertForbidden();
    }

    /**
     * @return array{0: User, 1: StockApproval, 2: StockRequest}
     */
    protected function createStockApprovalFixture(): array
    {
        [$approver, $businessUnit, $department] = $this->createUserContext('approver.test@example.com');

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

        $stockRequest = StockRequest::create([
            'st_number' => 'ST/'.$businessUnit->code.'/'.now()->format('Ym').'/001',
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'user_id' => $approver->id,
            'sequence_id' => $numberSequence->id,
            'purpose' => 'Stock item for operational needs',
            'date_of_request' => now()->toDateString(),
            'expected_date' => now()->addDay()->toDateString(),
            'status' => 'in_approval',
            'submitted_at' => now(),
        ]);

        StockItem::create([
            'stock_request_id' => $stockRequest->id,
            'item_order' => 1,
            'item_name' => 'Test Stock Item',
            'quantity' => 2,
            'unit' => 'pcs',
            'price' => 50000,
            'total' => 100000,
            'specifications' => 'Spec A',
            'item_code' => 'ITEM-001',
        ]);

        $stockApproval = StockApproval::create([
            'stock_request_id' => $stockRequest->id,
            'approver_id' => $approver->id,
            'step_order' => 1,
            'approval_type' => 'approval',
            'task_type' => 'approval',
            'status' => 'pending',
            'assigned_at' => now(),
        ]);

        return [$approver, $stockApproval, $stockRequest];
    }

    /**
     * @return array{0: User, 1: BusinessUnit, 2: Department}
     */
    protected function createUserContext(?string $email = null): array
    {
        $businessUnit = BusinessUnit::factory()->create();
        $department = Department::factory()->create([
            'business_unit_id' => $businessUnit->id,
        ]);

        $position = Position::query()
            ->where('department_id', $department->id)
            ->where('code', 'STAFF_'.strtoupper($department->code))
            ->firstOrFail();

        $user = User::factory()->create([
            'email' => $email ?? fake()->unique()->safeEmail(),
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
        ]);

        return [$user, $businessUnit, $department];
    }
}
