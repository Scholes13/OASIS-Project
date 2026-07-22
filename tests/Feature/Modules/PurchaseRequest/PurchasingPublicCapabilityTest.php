<?php

namespace Tests\Feature\Modules\PurchaseRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\NumberingModule;
use App\Models\Core\NumberSequence;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchasingPublicCapabilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function public_pr_approval_post_requires_valid_unexpired_untampered_signature(): void
    {
        [$approval] = $this->createApprovalFixture();

        $this->post(route('approvals.public.process', $approval), ['action' => 'approved'])->assertForbidden();

        $expired = URL::temporarySignedRoute('approvals.public.process', now()->subMinute(), ['approval' => $approval->id]);
        $this->post($expired, ['action' => 'approved'])->assertForbidden();

        $valid = URL::temporarySignedRoute('approvals.public.process', now()->addHour(), ['approval' => $approval->id]);
        $this->post($valid.'&approval=999999', ['action' => 'approved'])->assertForbidden();
    }

    #[Test]
    public function signed_approval_page_embeds_signed_process_capability(): void
    {
        [$approval] = $this->createApprovalFixture();
        $url = URL::temporarySignedRoute('approvals.public.approve', now()->addHour(), ['approval' => $approval->id]);

        $this->get($url)
            ->assertOk()
            ->assertSee('/approvals/'.$approval->id.'/public/process?expires=', false)
            ->assertSee('signature=', false);
    }

    #[Test]
    public function public_pr_and_stock_pdf_routes_require_signed_capabilities(): void
    {
        [, $purchaseRequest, $stockRequest] = $this->createApprovalFixture();

        $this->get(route('purchase-requests.pdf-public', $purchaseRequest))->assertForbidden();
        $this->get(route('stock-requests.pdf-public', $stockRequest))->assertForbidden();

        $prUrl = URL::temporarySignedRoute('purchase-requests.pdf-public', now()->addHour(), ['purchaseRequest' => $purchaseRequest->id]);
        $stockUrl = URL::temporarySignedRoute('stock-requests.pdf-public', now()->addHour(), ['stockRequest' => $stockRequest->id]);
        $this->get($prUrl)->assertOk();
        $this->get($stockUrl)->assertOk();

        $expired = URL::temporarySignedRoute('purchase-requests.pdf-public', now()->subMinute(), ['purchaseRequest' => $purchaseRequest->id]);
        $this->get($expired)->assertForbidden();
        $this->get($prUrl.'&purchaseRequest=999999')->assertForbidden();

        $expiredStockUrl = URL::temporarySignedRoute('stock-requests.pdf-public', now()->subMinute(), ['stockRequest' => $stockRequest->id]);
        $this->get($expiredStockUrl)->assertForbidden();
        $this->get($stockUrl.'&stockRequest=999999')->assertForbidden();
    }

    #[Test]
    public function authenticated_stock_owner_can_use_plain_public_pdf_url_but_foreign_user_cannot(): void
    {
        [, , $stockRequest] = $this->createApprovalFixture();
        $owner = $stockRequest->user;
        $foreignBusinessUnit = BusinessUnit::factory()->create();
        $foreignDepartment = Department::factory()->create([
            'business_unit_id' => $foreignBusinessUnit->id,
        ]);
        $foreignUser = User::factory()->create([
            'global_role' => 'user',
            'primary_department_id' => $foreignDepartment->id,
            'last_active_business_unit_id' => $foreignBusinessUnit->id,
        ]);
        UserBusinessUnit::create([
            'user_id' => $foreignUser->id,
            'business_unit_id' => $foreignBusinessUnit->id,
            'department_id' => $foreignDepartment->id,
            'position_id' => $foreignDepartment->positions()->where('level', 'staff')->firstOrFail()->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('stock-requests.pdf-public', $stockRequest))
            ->assertOk();

        $owner->update(['is_active' => false]);
        $this->actingAs($owner->fresh())
            ->get(route('stock-requests.pdf-public', $stockRequest))
            ->assertForbidden();

        $this->actingAs($foreignUser)
            ->withSession([
                'current_business_unit_id' => $foreignBusinessUnit->id,
                'current_department_id' => $foreignDepartment->id,
            ])
            ->get(route('stock-requests.pdf-public', $stockRequest))
            ->assertForbidden();
    }

    #[Test]
    public function authenticated_purchase_request_owner_can_use_authenticated_pdf_route(): void
    {
        [, $purchaseRequest] = $this->createApprovalFixture();

        $this->actingAs($purchaseRequest->user)
            ->withSession([
                'current_business_unit_id' => $purchaseRequest->business_unit_id,
                'current_department_id' => $purchaseRequest->department_id,
            ])
            ->get(route('purchase-requests.pdf', $purchaseRequest))
            ->assertOk();
    }

    /** @return array{PrApproval, PurchaseRequest, StockRequest} */
    private function createApprovalFixture(): array
    {
        $businessUnit = BusinessUnit::factory()->create();
        $department = Department::factory()->create(['business_unit_id' => $businessUnit->id]);
        $user = User::factory()->create(['primary_department_id' => $department->id]);
        $approver = User::factory()->create(['primary_department_id' => $department->id]);
        foreach ([$user, $approver] as $assignedUser) {
            UserBusinessUnit::create([
                'user_id' => $assignedUser->id,
                'business_unit_id' => $businessUnit->id,
                'department_id' => $department->id,
                'position_id' => $department->positions()->firstOrFail()->id,
                'is_primary' => true,
                'is_active' => true,
            ]);
        }
        $prModule = NumberingModule::create([
            'business_unit_id' => $businessUnit->id,
            'module_code' => 'PR',
            'module_name' => 'Purchase Request',
            'format_pattern' => 'PR/{BU}/{YYYYMM}/{SEQ}',
            'is_active' => true,
        ]);
        $stockModule = NumberingModule::create([
            'business_unit_id' => $businessUnit->id,
            'module_code' => 'ST',
            'module_name' => 'Stock Request',
            'format_pattern' => 'ST/{BU}/{YYYYMM}/{SEQ}',
            'is_active' => true,
        ]);
        $prSequence = NumberSequence::create([
            'business_unit_id' => $businessUnit->id,
            'numbering_module_id' => $prModule->id,
            'department_id' => $department->id,
            'year' => (int) now()->format('Y'),
            'month' => (int) now()->format('m'),
            'current_number' => 1,
            'max_number' => 999,
        ]);
        $stockSequence = NumberSequence::create([
            'business_unit_id' => $businessUnit->id,
            'numbering_module_id' => $stockModule->id,
            'department_id' => $department->id,
            'year' => (int) now()->format('Y'),
            'month' => (int) now()->format('m'),
            'current_number' => 1,
            'max_number' => 999,
        ]);

        $purchaseRequest = PurchaseRequest::create([
            'pr_number' => 'PR/CAP/001', 'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id, 'user_id' => $user->id,
            'sequence_id' => $prSequence->id,
            'used_for' => 'Capability test', 'date_of_request' => now(), 'status' => 'in_approval',
            'submitted_at' => now(), 'total_amount' => 0, 'currency' => 'IDR',
        ]);
        $approval = PrApproval::create([
            'purchase_request_id' => $purchaseRequest->id, 'approver_id' => $approver->id,
            'step_order' => 1, 'approval_type' => 'approval', 'status' => 'pending', 'assigned_at' => now(),
        ]);
        $stockRequest = StockRequest::create([
            'st_number' => 'ST/CAP/001', 'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id, 'user_id' => $user->id,
            'sequence_id' => $stockSequence->id,
            'purpose' => 'Capability test', 'date_of_request' => now(), 'status' => 'draft',
        ]);

        return [$approval, $purchaseRequest, $stockRequest];
    }
}
