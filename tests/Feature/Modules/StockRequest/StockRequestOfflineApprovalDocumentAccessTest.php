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
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StockRequestOfflineApprovalDocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    #[Test]
    public function owner_can_open_offline_approval_document_inline(): void
    {
        [$owner, , $stockRequest] = $this->createApprovedFixture();

        $response = $this->actingAs($owner)
            ->withSession([
                'current_business_unit_id' => $stockRequest->business_unit_id,
                'current_department_id' => $stockRequest->department_id,
            ])
            ->get(route('stock-requests.offline-approval-document', $stockRequest));

        $response->assertOk();
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    #[Test]
    public function assigned_approver_can_open_offline_approval_document_inline(): void
    {
        [, $approver, $stockRequest] = $this->createApprovedFixture();

        $response = $this->actingAs($approver)
            ->withSession([
                'current_business_unit_id' => $stockRequest->business_unit_id,
                'current_department_id' => $stockRequest->department_id,
            ])
            ->get(route('stock-requests.offline-approval-document', $stockRequest));

        $response->assertOk();
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    #[Test]
    public function unrelated_user_is_forbidden_from_opening_offline_approval_document(): void
    {
        [$owner, , $stockRequest] = $this->createApprovedFixture();
        [$otherUser] = $this->createUserContext('other.stock@example.com', $stockRequest->businessUnit, $stockRequest->department);

        $response = $this->actingAs($otherUser)
            ->withSession([
                'current_business_unit_id' => $stockRequest->business_unit_id,
                'current_department_id' => $stockRequest->department_id,
            ])
            ->get(route('stock-requests.offline-approval-document', $stockRequest));

        $response->assertForbidden();
    }

    #[Test]
    public function offline_approval_document_route_returns_404_when_file_is_missing(): void
    {
        [$owner, , $stockRequest] = $this->createApprovedFixture();

        Storage::disk('public')->delete($stockRequest->offline_approval_document_path);

        $response = $this->actingAs($owner)
            ->withSession([
                'current_business_unit_id' => $stockRequest->business_unit_id,
                'current_department_id' => $stockRequest->department_id,
            ])
            ->get(route('stock-requests.offline-approval-document', $stockRequest));

        $response->assertNotFound();
    }

    /**
     * @return array{0: User, 1: User, 2: StockRequest}
     */
    private function createApprovedFixture(): array
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

        $documentPath = 'offline-approvals/stock-requests/'.$owner->id.'/proof.pdf';
        Storage::disk('public')->put($documentPath, 'offline-proof');

        $stockRequest = StockRequest::create([
            'st_number' => 'ST/'.$businessUnit->code.'/'.now()->format('Ym').'/001',
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'user_id' => $owner->id,
            'sequence_id' => $numberSequence->id,
            'purpose' => 'Offline document parity test',
            'date_of_request' => now()->toDateString(),
            'expected_date' => now()->addDay()->toDateString(),
            'status' => 'approved',
            'submitted_at' => now()->subDay(),
            'approved_at' => now(),
            'offline_approved_at' => now(),
            'offline_approval_document_path' => $documentPath,
            'offline_approval_document_name' => 'proof.pdf',
        ]);

        StockApproval::create([
            'stock_request_id' => $stockRequest->id,
            'approver_id' => $approver->id,
            'step_order' => 1,
            'approval_type' => 'approval',
            'task_type' => 'approval',
            'status' => 'approved',
            'assigned_at' => now()->subDay(),
            'responded_at' => now(),
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
