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
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StockRequestShowAuthorizationParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    #[Test]
    public function owner_show_page_exposes_phase_two_parity_permissions(): void
    {
        [$owner, $approver, $stockRequest] = $this->createInApprovalFixture(withOfflineDocument: true);

        $response = $this->actingAs($owner)
            ->withSession([
                'current_business_unit_id' => $stockRequest->business_unit_id,
                'current_department_id' => $stockRequest->department_id,
            ])
            ->get(route('stock-requests.show', $stockRequest));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/StockRequest/Show')
            ->where('can.edit', false)
            ->where('can.delete', false)
            ->where('can.void', true)
            ->where('can.resubmit', false)
            ->where('can.resendApprovalEmail', true)
            ->where('can.approve', false)
            ->where('can.reject', false)
            ->where('can.downloadPdf', true)
            ->where('can.markOfflineApproved', true)
            ->where('can.offlineApprovalDocument', true)
        );
    }

    #[Test]
    public function active_approver_show_page_can_approve_and_reject_without_owner_actions(): void
    {
        [, $approver, $stockRequest] = $this->createInApprovalFixture();

        $response = $this->actingAs($approver)
            ->withSession([
                'current_business_unit_id' => $stockRequest->business_unit_id,
                'current_department_id' => $stockRequest->department_id,
            ])
            ->get(route('stock-requests.show', $stockRequest));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/StockRequest/Show')
            ->where('can.approve', true)
            ->where('can.reject', true)
            ->where('can.resendApprovalEmail', false)
            ->where('can.markOfflineApproved', false)
        );
    }

    /**
     * @return array{0: User, 1: User, 2: StockRequest}
     */
    private function createInApprovalFixture(bool $withOfflineDocument = false): array
    {
        [$owner, $businessUnit, $department] = $this->createUserContext('owner.stock@example.com');
        [$approver] = $this->createUserContext('approver.stock@example.com', $businessUnit, $department);

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
            'user_id' => $owner->id,
            'sequence_id' => $numberSequence->id,
            'purpose' => 'Parity contract test',
            'date_of_request' => now()->toDateString(),
            'expected_date' => now()->addDay()->toDateString(),
            'status' => 'in_approval',
            'submitted_at' => now(),
            'offline_approval_document_path' => $withOfflineDocument ? 'offline-approvals/stock-requests/'.$owner->id.'/evidence.pdf' : null,
            'offline_approval_document_name' => $withOfflineDocument ? 'evidence.pdf' : null,
        ]);

        StockApproval::create([
            'stock_request_id' => $stockRequest->id,
            'approver_id' => $approver->id,
            'step_order' => 1,
            'approval_type' => 'approval',
            'task_type' => 'approval',
            'status' => 'pending',
            'assigned_at' => now(),
        ]);

        return [$owner, $approver, $stockRequest->fresh()];
    }

    /**
     * @return array{0: User, 1: BusinessUnit, 2: Department}
     */
    private function createUserContext(?string $email = null, ?BusinessUnit $businessUnit = null, ?Department $department = null): array
    {
        $businessUnit ??= BusinessUnit::factory()->create();
        $department ??= Department::factory()->create([
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
