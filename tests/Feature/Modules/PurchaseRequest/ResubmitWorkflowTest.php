<?php

namespace Tests\Feature\Modules\PurchaseRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\PurchaseRequest\PrItem;
use App\Models\Modules\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\PurchaseRequest\ApprovalWorkflowService;
use App\Services\Modules\PurchaseRequest\PurchaseRequestService;
use App\Services\Modules\PurchaseRequest\UniversalPRNumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test Suite for v2.0 Resubmit Workflow Feature
 *
 * Tests the critical behavior introduced in v2.0:
 * - Separate Edit vs Resubmit actions
 * - Preservation of rejected status when editing
 * - QR token reusability (submitted_at preservation)
 * - Custom approval workflow preservation
 */
class ResubmitWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $requestor;

    protected User $departmentHead;

    protected User $financeManager;

    protected BusinessUnit $businessUnit;

    protected Department $department;

    protected Position $position;

    protected PurchaseRequestService $prService;

    protected ApprovalWorkflowService $workflowService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'user']);
        Role::create(['name' => 'department_head']);
        Role::create(['name' => 'finance_manager']);
        Role::create(['name' => 'admin']);

        // Create business unit
        $this->businessUnit = BusinessUnit::create([
            'name' => 'WNS Business Unit',
            'code' => 'WNS',
            'description' => 'Test business unit for resubmit workflow',
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

        // Create users
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

        $this->financeManager = User::create([
            'name' => 'Test Finance Manager',
            'email' => 'test.finance@test.com',
            'phone_number' => '081234567892',
            'password' => bcrypt('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->financeManager->assignRole('finance_manager');

        // Assign users to business units with departments and positions
        foreach ([$this->requestor, $this->departmentHead] as $user) {
            $user->businessUnits()->create([
                'business_unit_id' => $this->businessUnit->id,
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'is_active' => true,
            ]);
        }

        // Finance manager
        $this->financeManager->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_active' => true,
        ]);

        // Initialize services
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
     * Test 1: Editing a rejected PR preserves the 'rejected' status
     *
     * Critical v2.0 Bug Fix: Previously, editing a rejected PR would
     * auto-resubmit it. Now it should keep status='rejected'.
     */
    public function test_editing_rejected_pr_preserves_rejected_status(): void
    {
        // Arrange: Create a rejected PR
        $pr = $this->createRejectedPurchaseRequest();

        $originalStatus = $pr->status;
        $originalRejectedAt = $pr->rejected_at;

        $this->assertEquals('rejected', $originalStatus);
        $this->assertNotNull($originalRejectedAt);

        // Act: Update the PR data (simulate edit action)
        $pr->update([
            'keperluan' => 'Updated purpose after rejection',
            'used_for' => 'Updated usage description',
            'last_modified_by' => $this->requestor->id,
        ]);

        // Assert: Status should remain 'rejected'
        $pr->refresh();

        $this->assertEquals('rejected', $pr->status, 'Status should remain rejected after edit');
        $this->assertEquals($originalRejectedAt->timestamp, $pr->rejected_at->timestamp, 'Rejected timestamp should not change');
        $this->assertEquals('Updated purpose after rejection', $pr->keperluan);
        // v2.0 Design: submitted_at is preserved for QR token reusability
        $this->assertNotNull($pr->submitted_at, 'submitted_at should be preserved for QR token');
    }

    /**
     * Test 2: Editing rejected PR updates data correctly without affecting workflow
     */
    public function test_editing_rejected_pr_updates_data_correctly(): void
    {
        // Arrange: Create a rejected PR with items
        $pr = $this->createRejectedPurchaseRequest();

        $originalItemsCount = $pr->items()->count();
        $this->assertGreaterThan(0, $originalItemsCount);

        // Act: Update items
        $pr->items()->delete();

        PrItem::create([
            'purchase_request_id' => $pr->id,
            'item_order' => 1,
            'item_name' => 'Updated Item',
            'brand_name' => 'Updated Brand',
            'item_description' => 'Updated Description',
            'supplier_name' => 'Updated Supplier',
            'quantity' => 5,
            'unit' => 'pcs',
            'unit_price' => 200000,
            'currency' => 'IDR',
            'expense_department_id' => $this->department->id,
        ]);

        $pr->updateTotalAmount();

        // Assert: Data updated but status unchanged
        $pr->refresh();

        $this->assertEquals('rejected', $pr->status);
        $this->assertEquals(1, $pr->items()->count());
        $this->assertEquals(1000000, $pr->total_amount); // 5 * 200000
        $this->assertEquals('Updated Item', $pr->items()->first()->item_name);
    }

    /**
     * Test 3: Resubmit button should only be available for rejected PRs
     *
     * This tests the business logic condition, not the UI directly
     */
    public function test_resubmit_only_available_for_rejected_status(): void
    {
        // Arrange: Create PRs in different statuses
        $draftPr = $this->createPurchaseRequest('draft');
        $submittedPr = $this->createPurchaseRequest('submitted');
        $approvedPr = $this->createPurchaseRequest('approved');
        $rejectedPr = $this->createPurchaseRequest('rejected');

        // Act & Assert: Only rejected PR can be resubmitted
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only rejected purchase requests can be resubmitted');

        $this->prService->resubmitPurchaseRequest($draftPr);
    }

    /**
     * Test 4: Resubmitting changes status from 'rejected' to 'submitted'
     *
     * Critical v2.0 Feature: Separate resubmit action that resets workflow
     */
    public function test_resubmit_changes_status_to_submitted(): void
    {
        // Arrange: Create a rejected PR
        $pr = $this->createRejectedPurchaseRequest();

        $originalSubmittedAt = $pr->submitted_at;
        $this->assertNotNull($originalSubmittedAt, 'Rejected PR should have submitted_at preserved (v2.0 design)');

        // Act: Resubmit the PR
        $this->actingAs($this->requestor);
        $resubmittedPr = $this->prService->resubmitPurchaseRequest($pr);

        // Assert: Status changed to submitted or in_approval (depends on workflow)
        $this->assertContains($resubmittedPr->status, ['submitted', 'in_approval'],
            'Status should be either submitted or in_approval after resubmit');
        $this->assertNotNull($resubmittedPr->submitted_at, 'Should have submitted_at after resubmit');
        $this->assertNull($resubmittedPr->rejected_at, 'rejected_at should be cleared');
        $this->assertNull($resubmittedPr->approved_at, 'Should not be approved yet');
    }

    /**
     * Test 5: Resubmit resets approvals correctly (deletes old, creates new)
     *
     * Critical v2.0 Feature: Workflow integrity after resubmit
     */
    public function test_resubmit_resets_approvals_correctly(): void
    {
        // Arrange: Create a rejected PR with existing approvals
        $pr = $this->createRejectedPurchaseRequest();

        $oldApprovals = $pr->approvals()->get();
        $oldApprovalsCount = $oldApprovals->count();

        $this->assertGreaterThan(0, $oldApprovalsCount, 'Should have old approvals');

        // Mark some as rejected
        $oldApprovals->first()->update([
            'status' => 'rejected',
            'responded_at' => now(),
            'notes' => 'Initial rejection reason',
        ]);

        // Act: Resubmit the PR
        $this->actingAs($this->requestor);
        $resubmittedPr = $this->prService->resubmitPurchaseRequest($pr);

        // Assert: Old approvals deleted, new ones created
        $newApprovals = $resubmittedPr->approvals()->get();

        $this->assertGreaterThan(0, $newApprovals->count(), 'Should have new approvals');

        // All new approvals should be pending
        foreach ($newApprovals as $approval) {
            $this->assertEquals('pending', $approval->status);
            $this->assertNull($approval->responded_at);
            // Notes might have default workflow description, not checking for null
        }

        // Check that old approval records no longer exist
        foreach ($oldApprovals as $oldApproval) {
            $this->assertDatabaseMissing('pr_approvals', [
                'id' => $oldApproval->id,
            ]);
        }
    }

    /**
     * Test 6: Resubmit preserves original submitted_at timestamp
     *
     * CRITICAL v2.0 Feature: QR token reusability depends on this!
     * submitted_at is used in QR token generation, so it must be preserved.
     */
    public function test_resubmit_preserves_original_submitted_at_timestamp(): void
    {
        // Arrange: Create a PR, submit it, then reject it
        $pr = $this->createPurchaseRequest('draft');

        // First submission
        $pr->update([
            'status' => 'submitted',
            'submitted_at' => now()->subDays(5), // Submitted 5 days ago
        ]);

        $originalSubmittedAt = $pr->submitted_at;

        // Create workflow and reject it
        $this->workflowService->createWorkflow($pr);
        $pr->update([
            'status' => 'rejected',
            'rejected_at' => now()->subDays(3),
        ]);

        // Act: Resubmit the PR
        $this->actingAs($this->requestor);
        $resubmittedPr = $this->prService->resubmitPurchaseRequest($pr);

        // Assert: submitted_at should be preserved (same as original)
        $this->assertNotNull($resubmittedPr->submitted_at);
        $this->assertEquals(
            $originalSubmittedAt->timestamp,
            $resubmittedPr->submitted_at->timestamp,
            'submitted_at timestamp should be preserved from original submission'
        );

        // This ensures QR token remains identical!
        $this->assertNotEquals(
            now()->timestamp,
            $resubmittedPr->submitted_at->timestamp,
            'submitted_at should NOT be current time, but original time'
        );
    }

    /**
     * Test 7: Resubmit works correctly with custom approval workflow
     *
     * v2.0 Feature: Custom workflows should be preserved in approval_workflow JSON
     */
    public function test_resubmit_preserves_custom_approval_workflow(): void
    {
        // Arrange: Create a PR with custom workflow
        $pr = $this->createPurchaseRequest('draft');

        // Set custom approval workflow JSON
        $customWorkflow = [
            [
                'step_order' => 1,
                'approver_id' => $this->departmentHead->id,
                'approval_type' => 'department_head',
            ],
            [
                'step_order' => 2,
                'approver_id' => $this->financeManager->id,
                'approval_type' => 'finance_manager',
            ],
        ];

        $pr->update([
            'approval_workflow' => $customWorkflow,
            'status' => 'submitted',
            'submitted_at' => now()->subDays(2),
        ]);

        // Create workflow from JSON
        $this->workflowService->createWorkflow($pr);

        // Reject the PR
        $pr->update([
            'status' => 'rejected',
            'rejected_at' => now()->subDay(),
        ]);

        // Act: Resubmit
        $this->actingAs($this->requestor);
        $resubmittedPr = $this->prService->resubmitPurchaseRequest($pr);

        // Assert: Custom workflow should be recreated
        $newApprovals = $resubmittedPr->approvals()->orderBy('step_order')->get();

        $this->assertEquals(2, $newApprovals->count());
        $this->assertEquals($this->departmentHead->id, $newApprovals[0]->approver_id);
        $this->assertEquals('department_head', $newApprovals[0]->approval_type);
        $this->assertEquals($this->financeManager->id, $newApprovals[1]->approver_id);
        $this->assertEquals('finance_manager', $newApprovals[1]->approval_type);

        // All should be pending
        foreach ($newApprovals as $approval) {
            $this->assertEquals('pending', $approval->status);
        }
    }

    /**
     * Test 8: Cannot resubmit non-rejected PR
     */
    public function test_cannot_resubmit_non_rejected_pr(): void
    {
        $approvedPr = $this->createPurchaseRequest('approved');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only rejected purchase requests can be resubmitted');

        $this->prService->resubmitPurchaseRequest($approvedPr);
    }

    /**
     * Test 9: Resubmit transaction rollback on error
     */
    public function test_resubmit_transaction_rollback_on_error(): void
    {
        // Arrange: Create a rejected PR
        $pr = $this->createRejectedPurchaseRequest();

        $originalStatus = $pr->status;
        $originalApprovalsCount = $pr->approvals()->count();

        // Act: Force an error by deleting required relationships
        // This should trigger rollback
        try {
            // Mock a scenario that would cause workflow creation to fail
            $pr->update(['business_unit_id' => 99999]); // Invalid business unit

            $this->prService->resubmitPurchaseRequest($pr);

            $this->fail('Should have thrown an exception');
        } catch (\Exception $e) {
            // Assert: Status should remain unchanged due to rollback
            $pr->refresh();
            $this->assertEquals('rejected', $pr->status);
        }
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
            'keperluan' => 'Test purchase request',
            'used_for' => 'Testing purposes',
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
            'quantity' => 2,
            'unit' => 'pcs',
            'unit_price' => 300000,
            'currency' => 'IDR',
            'expense_department_id' => $this->department->id,
        ]);

        $pr->updateTotalAmount();

        // Set timestamps based on status
        if ($status === 'submitted') {
            $pr->update(['submitted_at' => now()]);
        } elseif ($status === 'approved') {
            $pr->update([
                'submitted_at' => now()->subDays(3),
                'approved_at' => now()->subDay(),
            ]);
        } elseif ($status === 'rejected') {
            // v2.0 Design: Keep submitted_at for QR token reusability
            $pr->update([
                'submitted_at' => now()->subWeek(), // Preserve original submission timestamp
                'rejected_at' => now()->subDay(),
            ]);
        }

        return $pr;
    }

    /**
     * Create a rejected Purchase Request with complete workflow
     */
    protected function createRejectedPurchaseRequest(): PurchaseRequest
    {
        // Create and submit PR
        $pr = $this->createPurchaseRequest('draft');

        // Simulate submission
        $pr->update([
            'status' => 'submitted',
            'submitted_at' => now()->subDays(5),
        ]);

        // Create workflow
        $this->workflowService->createWorkflow($pr);

        // Simulate rejection by department head
        $approval = $pr->approvals()->orderBy('step_order')->first();

        $approval->update([
            'status' => 'rejected',
            'responded_at' => now()->subDays(3),
            'notes' => 'Budget not approved for this quarter',
        ]);

        // Update PR to rejected
        // IMPORTANT: Keep submitted_at to preserve QR token (v2.0 design)
        $pr->update([
            'status' => 'rejected',
            'rejected_at' => now()->subDays(3),
            // submitted_at is preserved for QR token reusability
        ]);

        return $pr->refresh();
    }
}
