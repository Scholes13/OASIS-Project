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
use App\Notifications\Purchasing\StockRequest\ApprovalRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StockRequestResendApprovalEmailTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owner_can_resend_approval_email_to_current_pending_approver(): void
    {
        Notification::fake();

        [$owner, $approver, $stockRequest, $approval] = $this->createInApprovalFixture();

        $response = $this->actingAs($owner)
            ->withSession([
                'current_business_unit_id' => $stockRequest->business_unit_id,
                'current_department_id' => $stockRequest->department_id,
            ])
            ->from(route('stock-requests.show', $stockRequest))
            ->post(route('stock-requests.resend-approval-email', $stockRequest));

        $response->assertRedirect(route('stock-requests.show', $stockRequest));
        $response->assertSessionHas('success');

        Notification::assertSentTo($approver, ApprovalRequested::class);
        $this->assertTrue((bool) $approval->fresh()?->email_sent);
        $this->assertNotNull($approval->fresh()?->email_sent_at);
    }

    #[Test]
    public function non_owner_cannot_resend_approval_email(): void
    {
        Notification::fake();

        [$owner, , $stockRequest] = $this->createInApprovalFixture();
        [$otherUser] = $this->createUserContext('other.stock@example.com', $stockRequest->businessUnit, $stockRequest->department);

        $response = $this->actingAs($otherUser)
            ->withSession([
                'current_business_unit_id' => $stockRequest->business_unit_id,
                'current_department_id' => $stockRequest->department_id,
            ])
            ->post(route('stock-requests.resend-approval-email', $stockRequest));

        $response->assertForbidden();
        Notification::assertNothingSent();
    }

    /**
     * @return array{0: User, 1: User, 2: StockRequest, 3: StockApproval}
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
            'purpose' => 'Resend approval parity test',
            'date_of_request' => now()->toDateString(),
            'expected_date' => now()->addDay()->toDateString(),
            'status' => 'in_approval',
            'submitted_at' => now(),
        ]);

        $approval = StockApproval::create([
            'stock_request_id' => $stockRequest->id,
            'approver_id' => $approver->id,
            'step_order' => 1,
            'approval_type' => 'approval',
            'task_type' => 'approval',
            'status' => 'pending',
            'assigned_at' => now(),
            'email_sent' => false,
        ]);

        return [$owner, $approver, $stockRequest->fresh(), $approval];
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
