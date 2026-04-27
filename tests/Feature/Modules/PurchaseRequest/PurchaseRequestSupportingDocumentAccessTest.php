<?php

namespace Tests\Feature\Modules\PurchaseRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\Purchasing\PurchaseRequest\UniversalPRNumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseRequestSupportingDocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    private BusinessUnit $businessUnit;

    private Department $department;

    private Position $staffPosition;

    private Position $approverPosition;

    private User $creator;

    private User $approver;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->businessUnit = BusinessUnit::create([
            'name' => 'Test Business Unit',
            'code' => 'TBU',
            'description' => 'Test business unit',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'name' => 'Purchasing',
            'code' => 'PUR',
            'business_unit_id' => $this->businessUnit->id,
            'is_active' => true,
        ]);

        $this->staffPosition = Position::where('department_id', $this->department->id)
            ->where('code', 'STAFF_'.strtoupper($this->department->code))
            ->firstOrFail();

        $this->approverPosition = Position::where('department_id', $this->department->id)
            ->where('code', 'HOD_'.strtoupper($this->department->code))
            ->firstOrFail();

        $this->creator = User::create([
            'name' => 'PR Creator',
            'email' => 'creator@example.com',
            'phone_number' => '081234567890',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->staffPosition->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->approver = User::create([
            'name' => 'PR Approver',
            'email' => 'approver@example.com',
            'phone_number' => '081234567891',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->approverPosition->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'phone_number' => '081234567892',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->staffPosition->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        foreach ([$this->creator, $this->approver, $this->otherUser] as $user) {
            $user->businessUnits()->create([
                'business_unit_id' => $this->businessUnit->id,
                'department_id' => $this->department->id,
                'position_id' => $this->staffPosition->id,
                'role' => 'staff',
                'is_primary' => true,
                'is_active' => true,
            ]);
        }

        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_department_id' => $this->department->id,
        ]);
    }

    #[Test]
    public function creator_can_open_supporting_document_inline(): void
    {
        $purchaseRequest = $this->createPurchaseRequestWithSupportingDocument($this->creator);

        $response = $this->actingAs($this->creator)
            ->get("/purchase-requests/{$purchaseRequest->id}/supporting-document");

        $response->assertOk();
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    #[Test]
    #[DataProvider('approverAccessProvider')]
    public function assigned_approver_can_access_supporting_document(string $approvalStatus, string $pathSuffix, string $expectedDisposition): void
    {
        $purchaseRequest = $this->createPurchaseRequestWithSupportingDocument($this->creator, $approvalStatus);

        $response = $this->actingAs($this->approver)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->get(route(
                $pathSuffix === '/download'
                    ? 'purchase-requests.supporting-document.download'
                    : 'purchase-requests.supporting-document',
                $purchaseRequest
            ));

        $response->assertOk();
        $this->assertStringContainsString($expectedDisposition, (string) $response->headers->get('Content-Disposition'));
    }

    #[Test]
    public function assigned_parent_business_unit_approver_can_access_supporting_document(): void
    {
        $parentBusinessUnit = BusinessUnit::create([
            'name' => 'Parent Business Unit',
            'code' => 'PBU',
            'description' => 'Parent business unit',
            'is_active' => true,
        ]);

        $this->businessUnit->update([
            'parent_id' => $parentBusinessUnit->id,
        ]);

        $parentDepartment = Department::create([
            'name' => 'Executive',
            'code' => 'EXE',
            'business_unit_id' => $parentBusinessUnit->id,
            'is_active' => true,
        ]);

        $parentApproverPosition = Position::where('department_id', $parentDepartment->id)
            ->where('code', 'HOD_'.strtoupper($parentDepartment->code))
            ->firstOrFail();

        $parentApprover = User::create([
            'name' => 'Parent Approver',
            'email' => 'parent.approver@example.com',
            'phone_number' => '081234567893',
            'password' => bcrypt('password'),
            'primary_department_id' => $parentDepartment->id,
            'primary_position_id' => $parentApproverPosition->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $parentApprover->businessUnits()->create([
            'business_unit_id' => $parentBusinessUnit->id,
            'department_id' => $parentDepartment->id,
            'position_id' => $parentApproverPosition->id,
            'role' => 'department_head',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $purchaseRequest = $this->createPurchaseRequestWithSupportingDocument($this->creator, 'pending', $parentApprover);

        $response = $this->actingAs($parentApprover)
            ->withSession([
                'current_business_unit_id' => $parentBusinessUnit->id,
                'current_department_id' => $parentDepartment->id,
            ])
            ->get(route('purchase-requests.supporting-document', $purchaseRequest));

        $response->assertOk();
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    #[Test]
    public function unrelated_user_in_same_business_unit_is_forbidden(): void
    {
        $purchaseRequest = $this->createPurchaseRequestWithSupportingDocument($this->creator);

        $response = $this->actingAs($this->otherUser)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->get(route('purchase-requests.supporting-document', $purchaseRequest));

        $response->assertForbidden();
    }

    #[Test]
    public function supporting_document_route_returns_404_when_file_is_missing(): void
    {
        $purchaseRequest = $this->createPurchaseRequestWithSupportingDocument($this->creator);

        Storage::disk('public')->delete($purchaseRequest->supporting_document_path);

        $response = $this->actingAs($this->creator)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->get(route('purchase-requests.supporting-document', $purchaseRequest));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public static function approverAccessProvider(): array
    {
        return [
            'pending approval view' => ['pending', '', 'inline'],
            'completed approval download' => ['approved', '/download', 'attachment'],
        ];
    }

    private function createPurchaseRequestWithSupportingDocument(User $creator, string $approvalStatus = 'pending', ?User $approver = null): PurchaseRequest
    {
        $numberingService = app(UniversalPRNumberingService::class);
        $numbering = $numberingService->generatePRNumber($creator, $this->businessUnit->id, $this->department->id);

        $documentPath = 'purchase-requests/supporting-documents/'.$numbering['formatted_number'].'.pdf';
        Storage::disk('public')->put($documentPath, 'supporting-document');

        $purchaseRequest = PurchaseRequest::create([
            'pr_number' => $numbering['formatted_number'],
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'user_id' => $creator->id,
            'sequence_id' => $numbering['sequence_id'],
            'used_for' => 'Test purchase request',
            'date_of_request' => now(),
            'status' => 'in_approval',
            'currency' => 'IDR',
            'supporting_document_path' => $documentPath,
            'supporting_document_name' => 'supporting-document.pdf',
        ]);

        PrApproval::create([
            'purchase_request_id' => $purchaseRequest->id,
            'approver_id' => ($approver ?? $this->approver)->id,
            'step_order' => 1,
            'status' => $approvalStatus,
            'assigned_at' => now(),
            'responded_at' => $approvalStatus === 'pending' ? null : now(),
        ]);

        return $purchaseRequest->fresh();
    }
}
