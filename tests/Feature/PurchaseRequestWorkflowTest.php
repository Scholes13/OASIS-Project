<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\BusinessUnit;
use App\Models\Department;
use App\Models\Modules\WNS\PurchaseRequest;
use App\Models\Modules\WNS\PrItem;
use App\Models\Modules\WNS\PrApproval;
use App\Services\Modules\WNS\PRNumberingService;
use App\Services\Modules\WNS\ApprovalWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;

class PurchaseRequestWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $requestor;
    protected User $departmentHead;
    protected User $financeManager;
    protected BusinessUnit $businessUnit;
    protected Department $department;

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
            'description' => 'Test business unit',
            'is_active' => true,
        ]);

        // Create department
        $this->department = Department::create([
            'name' => 'General Affairs',
            'code' => 'GA',
            'business_unit_id' => $this->businessUnit->id,
            'is_active' => true,
        ]);

        // Create users
        $this->requestor = User::create([
            'name' => 'John Requestor',
            'username' => 'john.requestor',
            'email' => 'john.requestor@test.com',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->requestor->assignRole('user');

        $this->departmentHead = User::create([
            'name' => 'Jane Department Head',
            'username' => 'jane.depthead',
            'email' => 'jane.depthead@test.com',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->departmentHead->assignRole('department_head');

        $this->financeManager = User::create([
            'name' => 'Bob Finance Manager',
            'username' => 'bob.finance',
            'email' => 'bob.finance@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->financeManager->assignRole('finance_manager');

        // Assign users to business units
        foreach ([$this->requestor, $this->departmentHead, $this->financeManager] as $user) {
            $user->businessUnits()->create([
                'business_unit_id' => $this->businessUnit->id,
                'is_active' => true,
            ]);
        }

        // Assign users to departments
        foreach ([$this->requestor, $this->departmentHead] as $user) {
            $user->update([
                'primary_department_id' => $this->department->id,
            ]);
        }
    }

    /** @test */
    public function user_can_create_purchase_request()
    {
        $this->actingAs($this->requestor);
        
        // Set session context
        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
            'current_department_id' => $this->department->id,
        ]);

        $requestData = [
            'keperluan' => 'Office supplies for Q1 2025',
            'used_for' => 'General office operations and administrative tasks',
            'date_of_request' => now()->toDateString(),
            'items' => [
                [
                    'item_name' => 'Office Chair',
                    'brand_name' => 'Herman Miller',
                    'item_description' => 'Ergonomic office chair with lumbar support',
                    'supplier_name' => 'Office Furniture Inc',
                    'quantity' => 2,
                    'unit' => 'pcs',
                    'unit_price' => 500000,
                    'currency' => 'IDR',
                    'expense_department_id' => $this->department->id,
                ],
                [
                    'item_name' => 'Laptop',
                    'brand_name' => 'Dell',
                    'item_description' => 'Business laptop with SSD',
                    'supplier_name' => 'Tech Store',
                    'quantity' => 1,
                    'unit' => 'pcs',
                    'unit_price' => 800000,
                    'currency' => 'IDR',
                    'expense_department_id' => $this->department->id,
                ],
            ],
        ];

        $response = $this->post(route('purchase-requests.store'), $requestData);

        $response->assertRedirect();
        
        // Check PR was created
        $this->assertDatabaseHas('purchase_requests', [
            'user_id' => $this->requestor->id,
            'department_id' => $this->department->id,
            'business_unit_id' => $this->businessUnit->id,
            'keperluan' => 'Office supplies for Q1 2025',
            'status' => 'draft',
            'total_amount' => 1800000, // 2*500000 + 1*800000
        ]);

        // Check items were created
        $purchaseRequest = PurchaseRequest::where('user_id', $this->requestor->id)->first();
        $this->assertEquals(2, $purchaseRequest->items()->count());
        
        // Check PR number format
        $this->assertMatchesRegularExpression('/^PR\.' . $this->department->code . '\/\d{4}\/\d{2}\/\d{3}$/', $purchaseRequest->pr_number);
    }

    /** @test */
    public function user_can_submit_purchase_request_for_approval()
    {
        $this->actingAs($this->requestor);
        
        // Create a draft PR
        $purchaseRequest = $this->createSamplePurchaseRequest();
        
        // Set session context
        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
        ]);

        $response = $this->post(route('purchase-requests.submit', $purchaseRequest));

        $response->assertRedirect();
        
        // Check status changed to submitted/in_approval
        $purchaseRequest->refresh();
        $this->assertContains($purchaseRequest->status, ['submitted', 'in_approval']);
        $this->assertNotNull($purchaseRequest->submitted_at);
        
        // Check workflow was created
        $this->assertTrue($purchaseRequest->approvals()->exists());
        
        // Check first approver is assigned
        $firstApproval = $purchaseRequest->approvals()->orderBy('step_order')->first();
        $this->assertEquals('pending', $firstApproval->status);
    }

    /** @test */
    public function department_head_can_approve_purchase_request()
    {
        $this->actingAs($this->departmentHead);
        
        // Create and submit a PR
        $purchaseRequest = $this->createSubmittedPurchaseRequest();
        
        // Get the approval assigned to department head
        $approval = $purchaseRequest->approvals()
            ->where('approver_id', $this->departmentHead->id)
            ->where('status', 'pending')
            ->first();
            
        $this->assertNotNull($approval, 'Department head should have a pending approval');

        $response = $this->post(route('approvals.process'), [
            'approval_id' => $approval->id,
            'action' => 'approve',
            'notes' => 'Approved for business needs',
        ]);

        $response->assertJson(['success' => true]);
        
        // Check approval was processed
        $approval->refresh();
        $this->assertEquals('approved', $approval->status);
        $this->assertEquals('Approved for business needs', $approval->notes);
        $this->assertNotNull($approval->responded_at);
    }

    /** @test */
    public function department_head_can_reject_purchase_request()
    {
        $this->actingAs($this->departmentHead);
        
        // Create and submit a PR
        $purchaseRequest = $this->createSubmittedPurchaseRequest();
        
        // Get the approval assigned to department head
        $approval = $purchaseRequest->approvals()
            ->where('approver_id', $this->departmentHead->id)
            ->where('status', 'pending')
            ->first();

        $response = $this->post(route('approvals.process'), [
            'approval_id' => $approval->id,
            'action' => 'reject',
            'notes' => 'Insufficient business justification',
        ]);

        $response->assertJson(['success' => true]);
        
        // Check approval was rejected
        $approval->refresh();
        $this->assertEquals('rejected', $approval->status);
        
        // Check PR status changed to rejected
        $purchaseRequest->refresh();
        $this->assertEquals('rejected', $purchaseRequest->status);
        $this->assertNotNull($purchaseRequest->rejected_at);
    }

    /** @test */
    public function complete_approval_workflow_for_high_value_request()
    {
        // Create a high-value PR that requires multiple approvals
        $purchaseRequest = $this->createHighValuePurchaseRequest();
        
        // Submit the PR
        $this->actingAs($this->requestor);
        session(['current_business_unit_id' => $this->businessUnit->id]);
        
        $this->post(route('purchase-requests.submit', $purchaseRequest));
        
        $purchaseRequest->refresh();
        
        // Should have multiple approval steps
        $this->assertGreaterThan(1, $purchaseRequest->approvals()->count());
        
        // First approval - Department Head
        $deptHeadApproval = $purchaseRequest->approvals()
            ->where('approver_id', $this->departmentHead->id)
            ->first();
            
        if ($deptHeadApproval) {
            $this->actingAs($this->departmentHead);
            $this->post(route('approvals.process'), [
                'approval_id' => $deptHeadApproval->id,
                'action' => 'approve',
                'notes' => 'Department head approval',
            ]);
        }
        
        // Second approval - Finance Manager
        $financeApproval = $purchaseRequest->approvals()
            ->where('approver_id', $this->financeManager->id)
            ->first();
            
        if ($financeApproval) {
            $this->actingAs($this->financeManager);
            $this->post(route('approvals.process'), [
                'approval_id' => $financeApproval->id,
                'action' => 'approve',
                'notes' => 'Finance manager approval',
            ]);
        }
        
        // Check final status
        $purchaseRequest->refresh();
        $pendingCount = $purchaseRequest->pendingApprovals()->count();
        
        if ($pendingCount === 0) {
            $this->assertEquals('approved', $purchaseRequest->status);
            $this->assertNotNull($purchaseRequest->approved_at);
        } else {
            $this->assertEquals('in_approval', $purchaseRequest->status);
        }
    }

    /** @test */
    public function api_endpoints_work_correctly()
    {
        $this->actingAs($this->requestor);
        
        // Test API list endpoint
        $response = $this->withHeaders([
            'X-Business-Unit-ID' => $this->businessUnit->id,
            'Accept' => 'application/json',
        ])->get('/api/v1/purchase-requests');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                     'meta',
                     'links'
                 ]);

        // Test API create endpoint
        $requestData = [
            'keperluan' => 'API Test Request',
            'used_for' => 'Testing API functionality',
            'date_of_request' => now()->toDateString(),
            'items' => [
                [
                    'item_name' => 'Test Item',
                    'quantity' => 1,
                    'unit' => 'pcs',
                    'unit_price' => 100000,
                    'currency' => 'IDR',
                    'expense_department_id' => $this->department->id,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'X-Business-Unit-ID' => $this->businessUnit->id,
            'Accept' => 'application/json',
        ])->post('/api/v1/purchase-requests', $requestData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'pr_number',
                         'status',
                         'total_amount'
                     ]
                 ]);
    }

    protected function createSamplePurchaseRequest(): PurchaseRequest
    {
        $numberingService = app(PRNumberingService::class);
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
            'status' => 'draft',
            'currency' => 'IDR',
            'last_modified_by' => $this->requestor->id,
        ]);

        // Add items
        PrItem::create([
            'purchase_request_id' => $pr->id,
            'item_order' => 1,
            'item_name' => 'Test Item',
            'quantity' => 1,
            'unit' => 'pcs',
            'unit_price' => 600000,
            'currency' => 'IDR',
            'expense_department_id' => $this->department->id,
        ]);

        $pr->updateTotalAmount();
        return $pr;
    }

    protected function createSubmittedPurchaseRequest(): PurchaseRequest
    {
        $pr = $this->createSamplePurchaseRequest();
        
        $workflowService = app(ApprovalWorkflowService::class);
        $pr->update(['status' => 'submitted', 'submitted_at' => now()]);
        $workflowService->createWorkflow($pr);
        
        return $pr;
    }

    protected function createHighValuePurchaseRequest(): PurchaseRequest
    {
        $numberingService = app(PRNumberingService::class);
        $prNumber = $numberingService->generatePRNumber($this->requestor);
        
        $pr = PurchaseRequest::create([
            'pr_number' => $prNumber['formatted_number'],
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'user_id' => $this->requestor->id,
            'sequence_id' => $prNumber['sequence_id'],
            'keperluan' => 'High value equipment purchase',
            'used_for' => 'Business expansion',
            'date_of_request' => now(),
            'status' => 'draft',
            'currency' => 'IDR',
            'last_modified_by' => $this->requestor->id,
        ]);

        // Add high-value items
        PrItem::create([
            'purchase_request_id' => $pr->id,
            'item_order' => 1,
            'item_name' => 'Server Equipment',
            'quantity' => 2,
            'unit' => 'units',
            'unit_price' => 3000000, // 3 million IDR each
            'currency' => 'IDR',
            'expense_department_id' => $this->department->id,
        ]);

        $pr->updateTotalAmount();
        return $pr;
    }
}