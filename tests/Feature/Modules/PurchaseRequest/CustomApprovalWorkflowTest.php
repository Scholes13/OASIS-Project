<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\PurchaseRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use App\Models\Modules\Purchasing\PurchaseRequest\PrItem;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Core\NumberingService;
use App\Services\Modules\Purchasing\PurchaseRequest\ApprovalWorkflowService;
use App\Services\Modules\Purchasing\PurchaseRequest\PurchaseRequestService;
use App\Services\Modules\Purchasing\PurchaseRequest\UniversalPRNumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test Suite: Custom Approval Workflow Tests
 *
 * Purpose: Validate that custom approval workflows (manually selected approvers)
 * are properly saved to JSON, preserved during rejection, and recreated after resubmit.
 *
 * This is a CRITICAL v2.0 feature - custom workflows must survive reject/resubmit cycles.
 */
class CustomApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected BusinessUnit $businessUnit;

    protected Department $department;

    protected Position $position;

    protected User $requestor;

    protected User $customApprover1;

    protected User $customApprover2;

    protected User $customApprover3;

    protected ApprovalWorkflowService $workflowService;

    protected PurchaseRequestService $prService;

    protected UniversalPRNumberingService $numberingService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create business unit and department
        $this->businessUnit = BusinessUnit::create([
            'code' => 'WNS',
            'name' => 'Werkudara Nusantara Sejahtera',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'business_unit_id' => $this->businessUnit->id,
            'code' => 'IT',
            'name' => 'Information Technology',
            'is_active' => true,
        ]);

        $this->position = Position::create([
            'department_id' => $this->department->id,
            'name' => 'Staff',
            'code' => 'STAFF',
            'level' => 'staff',
            'hierarchy_level' => 1,
        ]);

        // Create roles
        Role::create(['name' => 'user', 'guard_name' => 'web']);
        Role::create(['name' => 'department_head', 'guard_name' => 'web']);
        Role::create(['name' => 'finance_manager', 'guard_name' => 'web']);
        Role::create(['name' => 'general_manager', 'guard_name' => 'web']);

        // Create requestor
        $this->requestor = User::create([
            'name' => 'John Requestor',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567890',
        ]);
        $this->requestor->assignRole('user');
        UserBusinessUnit::create([
            'user_id' => $this->requestor->id,
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        // Create custom approvers (3 different users for custom workflow)
        $this->customApprover1 = User::create([
            'name' => 'Alice Custom Approver 1',
            'email' => 'alice@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567891',
        ]);
        $this->customApprover1->assignRole('department_head');
        UserBusinessUnit::create([
            'user_id' => $this->customApprover1->id,
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->customApprover2 = User::create([
            'name' => 'Bob Custom Approver 2',
            'email' => 'bob@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567892',
        ]);
        $this->customApprover2->assignRole('finance_manager');
        UserBusinessUnit::create([
            'user_id' => $this->customApprover2->id,
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->customApprover3 = User::create([
            'name' => 'Charlie Custom Approver 3',
            'email' => 'charlie@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567893',
        ]);
        $this->customApprover3->assignRole('general_manager');
        UserBusinessUnit::create([
            'user_id' => $this->customApprover3->id,
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        // Initialize services
        $this->workflowService = new ApprovalWorkflowService;
        $numberingService = new NumberingService;
        $this->numberingService = new UniversalPRNumberingService($numberingService);
        $this->prService = new PurchaseRequestService($this->numberingService, $this->workflowService);

        // Set session context
        session(['current_business_unit_id' => $this->businessUnit->id]);
    }

    /**
     * Helper: Create a PR with custom workflow
     */
    protected function createPRWithCustomWorkflow(): PurchaseRequest
    {
        $this->actingAs($this->requestor);

        // Generate PR number
        $prNumberData = $this->numberingService->generatePRNumber(
            $this->requestor,
            $this->businessUnit->id,
            $this->department->id
        );
        $prNumber = $prNumberData['formatted_number'];

        // Create PR
        $pr = PurchaseRequest::create([
            'pr_number' => $prNumber,
            'sequence_id' => $prNumberData['sequence_id'],
            'business_unit_id' => $this->businessUnit->id,
            'user_id' => $this->requestor->id,
            'department_id' => $this->department->id,
            'keperluan' => 'Test custom workflow',
            'used_for' => 'Testing custom approval workflow preservation',
            'date_of_request' => now(),
            'designated_date' => now()->addDays(7),
            'status' => 'draft',
            'currency' => 'IDR',
            'total_amount' => 15000000, // High value to trigger multiple approvers
            'is_sequential_approval' => true,
        ]);

        // Add items
        PrItem::create([
            'purchase_request_id' => $pr->id,
            'item_order' => 1,
            'item_name' => 'Custom Workflow Item',
            'quantity' => 1,
            'unit' => 'pcs',
            'unit_price' => 15000000,
            'currency' => 'IDR',
            'expense_department_id' => $this->department->id,
        ]);

        $pr->updateTotalAmount();

        return $pr->fresh();
    }

    /**
     * Helper: Create custom approval workflow manually
     */
    protected function createCustomWorkflow(PurchaseRequest $pr): void
    {
        // Create custom workflow: 3 approvers in specific order
        $customWorkflow = [
            [
                'approver_id' => $this->customApprover1->id,
                'step_order' => 1,
                'approval_type' => 'department_head',
                'approver_name' => $this->customApprover1->name,
            ],
            [
                'approver_id' => $this->customApprover2->id,
                'step_order' => 2,
                'approval_type' => 'finance_manager',
                'approver_name' => $this->customApprover2->name,
            ],
            [
                'approver_id' => $this->customApprover3->id,
                'step_order' => 3,
                'approval_type' => 'general_manager',
                'approver_name' => $this->customApprover3->name,
            ],
        ];

        // Save to JSON (simulating user's custom selection)
        $pr->update(['approval_workflow' => $customWorkflow]);

        // Create actual approval records
        foreach ($customWorkflow as $step) {
            PrApproval::create([
                'purchase_request_id' => $pr->id,
                'approver_id' => $step['approver_id'],
                'step_order' => $step['step_order'],
                'approval_type' => $step['approval_type'],
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Test 1: Custom workflow is saved to JSON correctly
     */
    public function test_custom_workflow_saved_to_json_correctly(): void
    {
        // Arrange
        $pr = $this->createPRWithCustomWorkflow();

        // Act: Create custom workflow
        $this->createCustomWorkflow($pr);
        $pr = $pr->fresh();

        // Assert: JSON contains all custom approvers
        $this->assertNotNull($pr->approval_workflow);
        $this->assertIsArray($pr->approval_workflow);
        $this->assertCount(3, $pr->approval_workflow);

        // Verify each step
        $this->assertEquals($this->customApprover1->id, $pr->approval_workflow[0]['approver_id']);
        $this->assertEquals(1, $pr->approval_workflow[0]['step_order']);
        $this->assertEquals('department_head', $pr->approval_workflow[0]['approval_type']);

        $this->assertEquals($this->customApprover2->id, $pr->approval_workflow[1]['approver_id']);
        $this->assertEquals(2, $pr->approval_workflow[1]['step_order']);
        $this->assertEquals('finance_manager', $pr->approval_workflow[1]['approval_type']);

        $this->assertEquals($this->customApprover3->id, $pr->approval_workflow[2]['approver_id']);
        $this->assertEquals(3, $pr->approval_workflow[2]['step_order']);
        $this->assertEquals('general_manager', $pr->approval_workflow[2]['approval_type']);
    }

    /**
     * Test 2: Custom workflow JSON is preserved after rejection
     */
    public function test_custom_workflow_json_preserved_after_rejection(): void
    {
        // Arrange: Create PR with custom workflow
        $pr = $this->createPRWithCustomWorkflow();
        $this->createCustomWorkflow($pr);

        // Submit
        $pr->update(['status' => 'submitted', 'submitted_at' => now()]);

        // Store original workflow JSON
        $originalWorkflow = $pr->approval_workflow;

        // Act: Reject
        $pr->update(['status' => 'rejected', 'rejected_at' => now()]);
        $pr = $pr->fresh();

        // Assert: JSON still intact
        $this->assertNotNull($pr->approval_workflow);
        $this->assertEquals($originalWorkflow, $pr->approval_workflow);
        $this->assertCount(3, $pr->approval_workflow);
    }

    /**
     * Test 3: Custom workflow is recreated from JSON after resubmit
     */
    public function test_custom_workflow_recreated_from_json_after_resubmit(): void
    {
        // Arrange: Create PR with custom workflow
        $pr = $this->createPRWithCustomWorkflow();
        $this->createCustomWorkflow($pr);

        // Submit and reject
        $pr->update([
            'status' => 'submitted',
            'submitted_at' => now()->subDays(5),
        ]);

        $pr->update(['status' => 'rejected', 'rejected_at' => now()->subDays(2)]);

        // Store original workflow for comparison
        $originalWorkflow = $pr->approval_workflow;

        // Act: Resubmit
        $this->actingAs($this->requestor);
        $resubmittedPr = $this->prService->resubmitPurchaseRequest($pr);

        // Assert: Workflow JSON still present
        $this->assertNotNull($resubmittedPr->approval_workflow);
        $this->assertEquals($originalWorkflow, $resubmittedPr->approval_workflow);

        // Assert: New approval records created from JSON
        $newApprovals = $resubmittedPr->approvals()->orderBy('step_order')->get();
        $this->assertCount(3, $newApprovals);

        // Verify each recreated approval matches original workflow
        $this->assertEquals($this->customApprover1->id, $newApprovals[0]->approver_id);
        $this->assertEquals(1, $newApprovals[0]->step_order);
        $this->assertEquals('department_head', $newApprovals[0]->approval_type);
        $this->assertEquals('pending', $newApprovals[0]->status);

        $this->assertEquals($this->customApprover2->id, $newApprovals[1]->approver_id);
        $this->assertEquals(2, $newApprovals[1]->step_order);
        $this->assertEquals('finance_manager', $newApprovals[1]->approval_type);
        $this->assertEquals('pending', $newApprovals[1]->status);

        $this->assertEquals($this->customApprover3->id, $newApprovals[2]->approver_id);
        $this->assertEquals(3, $newApprovals[2]->step_order);
        $this->assertEquals('general_manager', $newApprovals[2]->approval_type);
        $this->assertEquals('pending', $newApprovals[2]->status);
    }

    /**
     * Test 4: Custom approver order is maintained after resubmit
     */
    public function test_custom_approver_order_maintained_after_resubmit(): void
    {
        // Arrange
        $pr = $this->createPRWithCustomWorkflow();
        $this->createCustomWorkflow($pr);

        // Submit, reject, resubmit
        $pr->update(['status' => 'submitted', 'submitted_at' => now()->subDays(3)]);
        $pr->update(['status' => 'rejected', 'rejected_at' => now()->subDays(1)]);

        // Act
        $this->actingAs($this->requestor);
        $resubmittedPr = $this->prService->resubmitPurchaseRequest($pr);

        // Assert: Order preserved
        $approvals = $resubmittedPr->approvals()->orderBy('step_order')->get();

        $this->assertEquals(1, $approvals[0]->step_order);
        $this->assertEquals(2, $approvals[1]->step_order);
        $this->assertEquals(3, $approvals[2]->step_order);

        // Approver IDs in correct sequence
        $this->assertEquals($this->customApprover1->id, $approvals[0]->approver_id);
        $this->assertEquals($this->customApprover2->id, $approvals[1]->approver_id);
        $this->assertEquals($this->customApprover3->id, $approvals[2]->approver_id);
    }

    /**
     * Test 5: Custom approval types are preserved after resubmit
     */
    public function test_custom_approval_types_preserved_after_resubmit(): void
    {
        // Arrange
        $pr = $this->createPRWithCustomWorkflow();
        $this->createCustomWorkflow($pr);

        // Submit, reject, resubmit
        $pr->update(['status' => 'submitted', 'submitted_at' => now()->subDays(4)]);
        $pr->update(['status' => 'rejected', 'rejected_at' => now()->subDays(2)]);

        // Act
        $this->actingAs($this->requestor);
        $resubmittedPr = $this->prService->resubmitPurchaseRequest($pr);

        // Assert: Approval types preserved
        $approvals = $resubmittedPr->approvals()->orderBy('step_order')->get();

        $this->assertEquals('department_head', $approvals[0]->approval_type);
        $this->assertEquals('finance_manager', $approvals[1]->approval_type);
        $this->assertEquals('general_manager', $approvals[2]->approval_type);
    }

    /**
     * Test 6: Multiple resubmits maintain custom workflow integrity
     */
    public function test_multiple_resubmits_maintain_custom_workflow(): void
    {
        // Arrange
        $pr = $this->createPRWithCustomWorkflow();
        $this->createCustomWorkflow($pr);

        $pr->update(['status' => 'submitted', 'submitted_at' => now()->subDays(10)]);

        // Store original workflow
        $originalWorkflow = $pr->approval_workflow;

        // Act: Reject and resubmit 3 times
        $this->actingAs($this->requestor);

        for ($i = 1; $i <= 3; $i++) {
            // Reject
            $pr->update(['status' => 'rejected', 'rejected_at' => now()]);

            // Resubmit
            $pr = $this->prService->resubmitPurchaseRequest($pr);

            // Assert: Workflow JSON unchanged
            $this->assertEquals(
                $originalWorkflow,
                $pr->approval_workflow,
                "Custom workflow JSON must remain identical after resubmit #{$i}"
            );

            // Assert: Approvals recreated correctly
            $approvals = $pr->approvals()->orderBy('step_order')->get();
            $this->assertCount(3, $approvals, "Should have 3 approvals after resubmit #{$i}");

            // Verify approver sequence
            $this->assertEquals($this->customApprover1->id, $approvals[0]->approver_id);
            $this->assertEquals($this->customApprover2->id, $approvals[1]->approver_id);
            $this->assertEquals($this->customApprover3->id, $approvals[2]->approver_id);
        }
    }

    /**
     * Test 7: Custom workflow survives resetWorkflow() method call
     */
    public function test_custom_workflow_survives_reset_workflow(): void
    {
        // Arrange
        $pr = $this->createPRWithCustomWorkflow();
        $this->createCustomWorkflow($pr);

        $originalWorkflow = $pr->approval_workflow;

        // Act: Call resetWorkflow directly (this is what resubmit does internally)
        $this->workflowService->resetWorkflow($pr);
        $pr = $pr->fresh();

        // Assert: JSON preserved
        $this->assertNotNull($pr->approval_workflow, 'approval_workflow JSON must NOT be cleared by resetWorkflow()');
        $this->assertEquals($originalWorkflow, $pr->approval_workflow);

        // Assert: Old approvals deleted
        $this->assertEquals(0, $pr->approvals()->count(), 'Old approval records should be deleted');

        // Assert: Status reset to draft
        $this->assertEquals('draft', $pr->status);
    }

    /**
     * Test 8: Custom workflow can be recreated after edit
     */
    public function test_custom_workflow_recreated_after_edit(): void
    {
        // Arrange: Create and submit PR with custom workflow
        $pr = $this->createPRWithCustomWorkflow();
        $this->createCustomWorkflow($pr);

        $pr->update(['status' => 'submitted', 'submitted_at' => now()->subDays(3)]);

        // Reject
        $pr->update(['status' => 'rejected', 'rejected_at' => now()->subDays(1)]);

        // Store original workflow
        $originalWorkflow = $pr->approval_workflow;

        // Act: Reset workflow (like when editing rejected PR)
        $this->workflowService->resetWorkflow($pr);

        // Then recreate from JSON (this happens during resubmit)
        $this->workflowService->createWorkflow($pr);
        $pr = $pr->fresh();

        // Assert: Workflow recreated correctly
        $approvals = $pr->approvals()->orderBy('step_order')->get();
        $this->assertCount(3, $approvals);

        // Verify against original workflow JSON
        foreach ($originalWorkflow as $index => $step) {
            $this->assertEquals($step['approver_id'], $approvals[$index]->approver_id);
            $this->assertEquals($step['step_order'], $approvals[$index]->step_order);
            $this->assertEquals($step['approval_type'], $approvals[$index]->approval_type);
        }
    }

    /**
     * Test 9: Empty approval_workflow triggers auto-generation
     */
    public function test_empty_workflow_triggers_auto_generation(): void
    {
        // Arrange: Create PR WITHOUT custom workflow
        $pr = $this->createPRWithCustomWorkflow();
        // Note: NOT calling createCustomWorkflow() - approval_workflow will be null

        // Act: Create workflow (should auto-generate based on amount)
        $this->workflowService->createWorkflow($pr);
        $pr = $pr->fresh();

        // Assert: Auto-generated workflow exists
        $approvals = $pr->approvals;
        $this->assertGreaterThan(0, $approvals->count(), 'Should auto-generate approvals when approval_workflow is empty');

        // Assert: Approval workflow JSON is now populated (auto-generated workflow is saved)
        $this->assertNotNull($pr->approval_workflow);
        $this->assertIsArray($pr->approval_workflow);
    }

    /**
     * Test 10: Custom workflow with sequential approval setting
     */
    public function test_custom_workflow_respects_sequential_setting(): void
    {
        // Arrange: Sequential approval
        $pr = $this->createPRWithCustomWorkflow();
        $pr->update(['is_sequential_approval' => true]);
        $this->createCustomWorkflow($pr);

        $pr->update(['status' => 'submitted', 'submitted_at' => now()]);

        // Act: Check current approval (should be step 1 only)
        $currentApproval = $pr->currentApproval();

        // Assert: Only first approver is active
        $this->assertNotNull($currentApproval);
        $this->assertEquals(1, $currentApproval->step_order);
        $this->assertEquals($this->customApprover1->id, $currentApproval->approver_id);

        // Assert: Other approvals are pending but not assigned yet
        $pendingApprovals = $pr->approvals()->where('status', 'pending')->get();
        $this->assertCount(3, $pendingApprovals, 'All approvals should be pending initially');
    }
}
