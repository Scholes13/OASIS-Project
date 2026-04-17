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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StockRequestActionAuthorizationParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    #[Test]
    public function non_owner_cannot_mark_stock_request_as_offline_approved(): void
    {
        [$owner, , $stockRequest] = $this->createInApprovalFixture();
        [$otherUser] = $this->createUserContext('other.stock@example.com', $stockRequest->businessUnit, $stockRequest->department);

        $response = $this->actingAs($otherUser)
            ->withSession([
                'current_business_unit_id' => $stockRequest->business_unit_id,
                'current_department_id' => $stockRequest->department_id,
            ])
            ->post(route('stock-requests.mark-offline-approved', $stockRequest), [
                'offline_approval_document' => UploadedFile::fake()->create('offline-proof.pdf', 50, 'application/pdf'),
            ]);

        $response->assertForbidden();
    }

    #[Test]
    public function inactive_approver_cannot_process_stock_approval_action(): void
    {
        [$owner, $firstApprover, $stockRequest] = $this->createInApprovalFixture();
        [$secondApprover] = $this->createUserContext('second.approver@example.com', $stockRequest->businessUnit, $stockRequest->department);

        StockApproval::create([
            'stock_request_id' => $stockRequest->id,
            'approver_id' => $secondApprover->id,
            'step_order' => 2,
            'approval_type' => 'approval',
            'task_type' => 'approval',
            'status' => 'pending',
        ]);

        $inactiveApproval = StockApproval::query()
            ->where('stock_request_id', $stockRequest->id)
            ->where('approver_id', $secondApprover->id)
            ->firstOrFail();

        $response = $this->actingAs($secondApprover)
            ->withSession([
                'current_business_unit_id' => $stockRequest->business_unit_id,
                'current_department_id' => $stockRequest->department_id,
            ])
            ->from(route('stock-approvals.show', $inactiveApproval))
            ->post(route('stock-approvals.process', $inactiveApproval), [
                'action' => 'approve',
                'notes' => 'Trying to skip the active step',
            ]);

        $response->assertRedirect(route('stock-approvals.show', $inactiveApproval));
        $response->assertSessionHas('error', 'This approval is not currently active.');
        $this->assertSame('pending', $inactiveApproval->fresh()?->status);
        $this->assertSame('pending', StockApproval::query()->where('stock_request_id', $stockRequest->id)->where('approver_id', $firstApprover->id)->firstOrFail()->status);
    }

    #[Test]
    public function approver_cannot_process_stock_approval_from_another_business_unit_context(): void
    {
        [, $approver, $stockRequest] = $this->createInApprovalFixture();
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

        $approval = StockApproval::query()
            ->where('stock_request_id', $stockRequest->id)
            ->where('approver_id', $approver->id)
            ->firstOrFail();

        $response = $this->actingAs($approver)
            ->withSession([
                'current_business_unit_id' => $otherBusinessUnit->id,
                'current_department_id' => $otherDepartment->id,
            ])
            ->post(route('stock-approvals.process', $approval), [
                'action' => 'approve',
            ]);

        $response->assertForbidden();
    }

    /**
     * @return array{0: User, 1: User, 2: StockRequest}
     */
    private function createInApprovalFixture(): array
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
            'purpose' => 'Action authorization parity test',
            'date_of_request' => now()->toDateString(),
            'expected_date' => now()->addDay()->toDateString(),
            'status' => 'in_approval',
            'submitted_at' => now(),
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
