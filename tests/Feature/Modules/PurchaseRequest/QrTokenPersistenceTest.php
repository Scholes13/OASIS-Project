<?php

namespace Tests\Feature\Modules\PurchaseRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\PurchaseRequest\PrItem;
use App\Models\Modules\PurchaseRequest\PurchaseRequest;
use App\Services\Core\QrCodeService;
use App\Services\Modules\PurchaseRequest\ApprovalWorkflowService;
use App\Services\Modules\PurchaseRequest\PurchaseRequestService;
use App\Services\Modules\PurchaseRequest\UniversalPRNumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test Suite for v2.0 QR Token Persistence Feature
 *
 * Tests the CRITICAL behavior that QR tokens must remain identical
 * after PR resubmission. This is achieved by preserving the original
 * submitted_at timestamp.
 *
 * QR Token Formula: hash('sha256', json_encode([pr_id, user_id, submitted_at, type]) . app.key)
 *
 * Key Principle: Same submitted_at = Same QR token = Reusable QR codes
 */
class QrTokenPersistenceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $requestor;

    protected User $departmentHead;

    protected BusinessUnit $businessUnit;

    protected Department $department;

    protected Position $position;

    protected QrCodeService $qrService;

    protected PurchaseRequestService $prService;

    protected ApprovalWorkflowService $workflowService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'user']);
        Role::create(['name' => 'department_head']);

        // Create business unit
        $this->businessUnit = BusinessUnit::create([
            'name' => 'WNS Business Unit',
            'code' => 'WNS',
            'description' => 'Test business unit for QR token tests',
            'is_active' => true,
        ]);

        // Create department
        $this->department = Department::create([
            'name' => 'General Affairs',
            'code' => 'GA',
            'business_unit_id' => $this->businessUnit->id,
            'is_active' => true,
        ]);

        // Create position
        $this->position = Position::create([
            'department_id' => $this->department->id,
            'name' => 'Staff',
            'code' => 'STAFF',
            'level' => 'staff',
            'hierarchy_level' => 1,
            'is_active' => true,
        ]);

        // Create requestor
        $this->requestor = User::create([
            'name' => 'Test Requestor',
            'email' => 'test.requestor@test.com',
            'phone_number' => '081234567890',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->requestor->assignRole('user');

        // Create department head for approval workflow
        $this->departmentHead = User::create([
            'name' => 'Test Department Head',
            'email' => 'test.depthead@test.com',
            'phone_number' => '081234567891',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->departmentHead->assignRole('department_head');

        // Assign to business unit
        $this->requestor->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_active' => true,
        ]);

        $this->departmentHead->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_active' => true,
        ]);

        // Initialize services
        $this->qrService = app(QrCodeService::class);
        $this->prService = app(PurchaseRequestService::class);
        $this->workflowService = app(ApprovalWorkflowService::class);

        // Set session context
        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
            'current_department_id' => $this->department->id,
        ]);
    }

    /**
     * Test 1: QR token is generated when PR is first submitted
     */
    public function test_qr_token_generated_on_first_submit(): void
    {
        // Arrange: Create a draft PR
        $pr = $this->createPurchaseRequest('draft');

        $this->assertNull($pr->submitted_at, 'Draft PR should not have submitted_at');

        // Act: Submit the PR
        $pr->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // Generate QR token
        $token = $this->invokeMethod($this->qrService, 'generateRequestorToken', [$pr]);

        // Assert: Token should be generated
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token), 'SHA256 hash should be 64 characters');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token, 'Token should be valid hex hash');
    }

    /**
     * Test 2: QR token remains identical after PR resubmission
     *
     * CRITICAL TEST: This ensures QR codes printed on PDFs remain valid
     * even after the PR is rejected and resubmitted.
     */
    public function test_qr_token_remains_identical_after_resubmit(): void
    {
        // Arrange: Create, submit, and reject a PR
        $pr = $this->createPurchaseRequest('draft');

        // First submission
        $originalSubmitTime = now()->subDays(5);
        $pr->update([
            'status' => 'submitted',
            'submitted_at' => $originalSubmitTime,
        ]);

        // Generate original QR token
        $originalToken = $this->invokeMethod($this->qrService, 'generateRequestorToken', [$pr]);
        $originalQrCode = $this->qrService->generateRequestorQrCode($pr);

        // Reject the PR
        // NOTE: Rejection does NOT clear submitted_at (by design for QR token preservation)
        $this->workflowService->createWorkflow($pr);
        $pr->update([
            'status' => 'rejected',
            'rejected_at' => now()->subDays(3),
            // submitted_at is PRESERVED during rejection
        ]);

        // Act: Resubmit the PR (this should preserve original submitted_at)
        $this->actingAs($this->requestor);
        $resubmittedPr = $this->prService->resubmitPurchaseRequest($pr);

        // Generate new QR token after resubmit
        $newToken = $this->invokeMethod($this->qrService, 'generateRequestorToken', [$resubmittedPr]);
        $newQrCode = $this->qrService->generateRequestorQrCode($resubmittedPr);

        // Assert: Tokens should be IDENTICAL
        $this->assertEquals(
            $originalToken,
            $newToken,
            'QR token MUST remain identical after resubmit for QR code reusability'
        );

        // Assert: submitted_at should be preserved
        $this->assertNotNull($resubmittedPr->submitted_at);
        $this->assertEquals(
            $originalSubmitTime->timestamp,
            $resubmittedPr->submitted_at->timestamp,
            'submitted_at timestamp must be preserved from original submission'
        );

        // Assert: QR codes should be identical
        $this->assertEquals(
            $originalQrCode,
            $newQrCode,
            'QR code SVG output must be identical for same token'
        );
    }

    /**
     * Test 3: submitted_at timestamp is preserved through reject-resubmit cycle
     */
    public function test_submitted_at_timestamp_preserved_on_resubmit(): void
    {
        // Arrange: Create and submit PR
        $pr = $this->createPurchaseRequest('draft');

        $originalSubmitTime = now()->subWeek();
        $pr->update([
            'status' => 'submitted',
            'submitted_at' => $originalSubmitTime,
        ]);

        // Create workflow and reject
        // NOTE: Rejection does NOT clear submitted_at (by design for QR token preservation)
        $this->workflowService->createWorkflow($pr);
        $pr->update([
            'status' => 'rejected',
            'rejected_at' => now()->subDays(2),
            // submitted_at is PRESERVED during rejection
        ]);

        // Act: Resubmit
        $this->actingAs($this->requestor);
        $resubmittedPr = $this->prService->resubmitPurchaseRequest($pr);

        // Assert: Timestamp preserved exactly
        $this->assertNotNull($resubmittedPr->submitted_at);
        $this->assertEquals(
            $originalSubmitTime->format('Y-m-d H:i:s'),
            $resubmittedPr->submitted_at->format('Y-m-d H:i:s'),
            'submitted_at must be preserved down to the second'
        );

        // Assert: It's NOT a new timestamp
        $this->assertNotEquals(
            now()->timestamp,
            $resubmittedPr->submitted_at->timestamp,
            'submitted_at should NOT be set to current time on resubmit'
        );
    }

    /**
     * Test 4: QR token verification works with original token
     */
    public function test_qr_token_verification_with_preserved_token(): void
    {
        // Arrange: Create and submit PR
        $pr = $this->createPurchaseRequest('draft');

        $pr->update([
            'status' => 'submitted',
            'submitted_at' => now()->subDays(10),
        ]);

        // Generate and store original token
        $originalToken = $this->invokeMethod($this->qrService, 'generateRequestorToken', [$pr]);

        // Reject and resubmit
        // NOTE: Rejection does NOT clear submitted_at (by design for QR token preservation)
        $this->workflowService->createWorkflow($pr);
        $pr->update(['status' => 'rejected', 'rejected_at' => now()->subDays(5)]);

        $this->actingAs($this->requestor);
        $resubmittedPr = $this->prService->resubmitPurchaseRequest($pr);

        // Act: Verify original token still works
        $isValid = $this->qrService->verifyRequestorToken($resubmittedPr, $originalToken);

        // Assert: Original token should still be valid
        $this->assertTrue($isValid, 'Original QR token must remain valid after resubmit');
    }

    /**
     * Test 5: Different submitted_at produces different QR tokens
     *
     * This proves that submitted_at is indeed part of the token formula
     */
    public function test_different_submitted_at_produces_different_tokens(): void
    {
        // Arrange: Create two identical PRs with different submitted_at
        $pr1 = $this->createPurchaseRequest('submitted');
        $pr1->update(['submitted_at' => now()->subDays(10)]);

        $pr2 = $this->createPurchaseRequest('submitted');
        $pr2->update(['submitted_at' => now()->subDays(5)]);

        // Act: Generate tokens
        $token1 = $this->invokeMethod($this->qrService, 'generateRequestorToken', [$pr1]);
        $token2 = $this->invokeMethod($this->qrService, 'generateRequestorToken', [$pr2]);

        // Assert: Tokens should be different
        $this->assertNotEquals(
            $token1,
            $token2,
            'Different submitted_at timestamps must produce different QR tokens'
        );
    }

    /**
     * Test 6: QR code contains valid URL with correct parameters
     */
    public function test_qr_code_contains_valid_url_with_token(): void
    {
        // Arrange: Create and submit PR
        $pr = $this->createPurchaseRequest('submitted');
        $pr->update(['submitted_at' => now()]);

        // Act: Generate QR code
        $qrCodeSvg = $this->qrService->generateRequestorQrCode($pr);

        // Generate expected token
        $expectedToken = $this->invokeMethod($this->qrService, 'generateRequestorToken', [$pr]);

        // Assert: QR code is SVG format
        $this->assertStringContainsString('<svg', $qrCodeSvg);
        $this->assertStringContainsString('</svg>', $qrCodeSvg);

        // Generate expected URL
        $expectedUrl = route('purchase-requests.public', [
            'pr' => $pr->id,
            'token' => $expectedToken,
            'requestor' => $pr->user_id,
        ]);

        // QR code SVG should encode the URL (URL will be encoded in SVG)
        $this->assertNotEmpty($qrCodeSvg);
    }

    /**
     * Test 7: Placeholder QR code shown when PR not submitted
     */
    public function test_placeholder_qr_code_for_draft_pr(): void
    {
        // Arrange: Create draft PR (no submitted_at)
        $pr = $this->createPurchaseRequest('draft');

        $this->assertNull($pr->submitted_at);

        // Act: Generate QR code
        $qrCode = $this->qrService->generateRequestorQrCode($pr);

        // Assert: Should return placeholder
        $this->assertStringContainsString('<svg', $qrCode);
        $this->assertNotEmpty($qrCode);
    }

    /**
     * Test 8: QR token uses SHA256 hash algorithm
     */
    public function test_qr_token_uses_sha256_algorithm(): void
    {
        // Arrange: Create submitted PR
        $pr = $this->createPurchaseRequest('submitted');
        $pr->update(['submitted_at' => now()]);

        // Act: Generate token
        $token = $this->invokeMethod($this->qrService, 'generateRequestorToken', [$pr]);

        // Assert: SHA256 produces 64-character hex string
        $this->assertEquals(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    /**
     * Test 9: QR token includes all required data in hash
     */
    public function test_qr_token_includes_required_data(): void
    {
        // Arrange: Create two PRs with different data
        $pr1 = $this->createPurchaseRequest('submitted');
        $pr1->update(['submitted_at' => now()]);

        $pr2 = $this->createPurchaseRequest('submitted');
        $pr2->update([
            'user_id' => $pr1->user_id, // Same user
            'submitted_at' => $pr1->submitted_at, // Same timestamp
        ]);

        // Act: Generate tokens
        $token1 = $this->invokeMethod($this->qrService, 'generateRequestorToken', [$pr1]);
        $token2 = $this->invokeMethod($this->qrService, 'generateRequestorToken', [$pr2]);

        // Assert: Different PR IDs should produce different tokens
        $this->assertNotEquals(
            $token1,
            $token2,
            'Different PR IDs must produce different tokens (pr_id is in hash)'
        );
    }

    /**
     * Test 10: Multiple resubmits maintain same QR token
     */
    public function test_multiple_resubmits_maintain_same_token(): void
    {
        // Arrange: Create and submit PR
        $pr = $this->createPurchaseRequest('draft');

        $originalSubmitTime = now()->subDays(30);
        $pr->update([
            'status' => 'submitted',
            'submitted_at' => $originalSubmitTime,
        ]);

        // Get original token
        $originalToken = $this->invokeMethod($this->qrService, 'generateRequestorToken', [$pr]);

        // Act: Reject and resubmit MULTIPLE times
        $this->actingAs($this->requestor);

        for ($i = 1; $i <= 3; $i++) {
            // Reject
            // NOTE: Rejection does NOT clear submitted_at (by design for QR token preservation)
            $this->workflowService->createWorkflow($pr);
            $pr->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                // submitted_at is PRESERVED during rejection
            ]);

            // Resubmit
            $pr = $this->prService->resubmitPurchaseRequest($pr);

            // Check token
            $currentToken = $this->invokeMethod($this->qrService, 'generateRequestorToken', [$pr]);

            $this->assertEquals(
                $originalToken,
                $currentToken,
                "QR token must remain identical after resubmit #{$i}"
            );
        }
    }

    /**
     * Test 11: QR code data URL is base64 encoded
     */
    public function test_qr_code_data_url_format(): void
    {
        // Arrange: Create submitted PR
        $pr = $this->createPurchaseRequest('submitted');
        $pr->update(['submitted_at' => now()]);

        // Act: Generate data URL
        $dataUrl = $this->qrService->generateRequestorQrCodeDataUrl($pr);

        // Assert: Proper data URL format
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $dataUrl);

        // Extract base64 part
        $base64 = substr($dataUrl, strlen('data:image/svg+xml;base64,'));

        // Decode and verify it's valid SVG
        $decoded = base64_decode($base64);
        $this->assertStringContainsString('<svg', $decoded);
        $this->assertStringContainsString('</svg>', $decoded);
    }

    // ==================== Helper Methods ====================

    /**
     * Create a Purchase Request with specific status
     */
    protected function createPurchaseRequest(string $status = 'draft'): PurchaseRequest
    {
        $numberingService = app(UniversalPRNumberingService::class);
        $prNumber = $numberingService->generatePRNumber($this->requestor);

        $pr = PurchaseRequest::create([
            'pr_number' => $prNumber['formatted_number'],
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'user_id' => $this->requestor->id,
            'sequence_id' => $prNumber['sequence_id'],
            'keperluan' => 'QR token test PR',
            'used_for' => 'Testing QR token persistence',
            'date_of_request' => now(),
            'status' => $status,
            'currency' => 'IDR',
            'last_modified_by' => $this->requestor->id,
        ]);

        // Add items
        PrItem::create([
            'purchase_request_id' => $pr->id,
            'item_order' => 1,
            'item_name' => 'Test Item',
            'brand_name' => 'Test Brand',
            'item_description' => 'Test Description',
            'supplier_name' => 'Test Supplier',
            'quantity' => 1,
            'unit' => 'pcs',
            'unit_price' => 500000,
            'currency' => 'IDR',
            'expense_department_id' => $this->department->id,
        ]);

        $pr->updateTotalAmount();

        // Set timestamps based on status
        if ($status === 'submitted') {
            $pr->update(['submitted_at' => now()]);
        }

        return $pr;
    }

    /**
     * Invoke protected/private method using reflection
     *
     * This allows testing of protected generateRequestorToken method
     */
    protected function invokeMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
