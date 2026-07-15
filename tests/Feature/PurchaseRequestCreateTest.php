<?php

namespace Tests\Feature;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PrCategory;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseRequestCreateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $approver;

    protected BusinessUnit $businessUnit;

    protected Department $department;

    protected Position $position;

    protected PrCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);

        // Create roles
        Role::create(['name' => 'user']);
        Role::create(['name' => 'approver']);

        // Create business unit
        $this->businessUnit = BusinessUnit::create([
            'name' => 'Test Business Unit',
            'code' => 'TBU',
            'description' => 'Test business unit',
            'is_active' => true,
        ]);

        // Create department
        $this->department = Department::create([
            'name' => 'Test Department',
            'code' => 'TD',
            'business_unit_id' => $this->businessUnit->id,
            'is_active' => true,
        ]);

        $this->position = Position::where('department_id', $this->department->id)
            ->where('code', 'STAFF_'.strtoupper($this->department->code))
            ->firstOrFail();

        $headPosition = Position::where('department_id', $this->department->id)
            ->where('code', 'HOD_'.strtoupper($this->department->code))
            ->firstOrFail();

        // Create category
        $this->category = PrCategory::create([
            'name' => 'General',
            'code' => 'GEN',
            'color' => 'blue',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Create users
        $this->user = User::create([
            'name' => 'Test User',
            'username' => 'test.user',
            'email' => 'test.user@example.com',
            'phone_number' => '081234567890',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->position->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->user->assignRole('user');

        $this->approver = User::create([
            'name' => 'Test Approver',
            'username' => 'test.approver',
            'email' => 'test.approver@example.com',
            'phone_number' => '081234567891',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $headPosition->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->approver->assignRole('approver');

        // Assign users to business units
        $this->user->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'role' => 'staff',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->approver->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $headPosition->id,
            'role' => 'hod',
            'is_primary' => true,
            'is_active' => true,
        ]);

        // Set session data
        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_department_id' => $this->department->id,
        ]);

        Storage::fake('public');
    }

    #[Test]
    public function user_can_view_create_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('purchase-requests.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Purchasing/PurchaseRequest/Create')
            ->has('categories')
            ->has('departments')
            ->has('availableApprovers')
        );
    }

    #[Test]
    public function user_can_create_purchase_request_with_valid_data()
    {
        $requestData = [
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'category_id' => $this->category->id,
            'used_for' => 'Test purchase request for office supplies',
            'date_of_request' => now()->format('Y-m-d'),
            'expected_date' => now()->addDays(7)->format('Y-m-d'),
            'currency' => 'IDR',
            'approval_workflow' => [
                [
                    'approver_id' => $this->approver->id,
                    'task_type' => 'approval',
                ],
            ],
            'approval_notes' => 'Please approve this request',
            'items' => [
                [
                    'item_name' => 'Laptop',
                    'brand_name' => 'Dell',
                    'item_description' => 'Dell Latitude 5420',
                    'supplier_name' => 'Dell Indonesia',
                    'quantity' => 2,
                    'unit' => 'pcs',
                    'unit_price' => 15000000,
                    'currency' => 'IDR',
                    'expense_department_id' => $this->department->id,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('purchase-requests.store'), $requestData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('purchase_requests', [
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'user_id' => $this->user->id,
            'status' => 'in_approval',
            'currency' => 'IDR',
        ]);

        $this->assertDatabaseHas('pr_items', [
            'item_name' => 'Laptop',
            'brand_name' => 'Dell',
            'quantity' => 2,
            'unit' => 'pcs',
            'unit_price' => 15000000,
        ]);

        $this->assertDatabaseHas('pr_approvals', [
            'approver_id' => $this->approver->id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function validation_fails_when_required_fields_are_missing()
    {
        $response = $this->actingAs($this->user)
            ->post(route('purchase-requests.store'), []);

        $response->assertSessionHasErrors([
            'used_for',
            'date_of_request',
            'approval_workflow',
            'items',
        ]);
    }

    #[Test]
    public function validation_fails_when_items_array_is_empty()
    {
        $requestData = [
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'used_for' => 'Test purchase request',
            'date_of_request' => now()->format('Y-m-d'),
            'currency' => 'IDR',
            'approval_workflow' => [
                [
                    'approver_id' => $this->approver->id,
                    'task_type' => 'approval',
                ],
            ],
            'items' => [],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('purchase-requests.store'), $requestData);

        $response->assertSessionHasErrors(['items']);
    }

    #[Test]
    public function validation_fails_when_approval_workflow_is_empty()
    {
        $requestData = [
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'used_for' => 'Test purchase request',
            'date_of_request' => now()->format('Y-m-d'),
            'currency' => 'IDR',
            'approval_workflow' => [],
            'items' => [
                [
                    'item_name' => 'Laptop',
                    'quantity' => 1,
                    'unit' => 'pcs',
                    'unit_price' => 15000000,
                    'currency' => 'IDR',
                    'expense_department_id' => $this->department->id,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('purchase-requests.store'), $requestData);

        $response->assertSessionHasErrors(['approval_workflow']);
    }

    #[Test]
    public function user_can_upload_supporting_document()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $requestData = [
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'used_for' => 'Test purchase request with document',
            'date_of_request' => now()->format('Y-m-d'),
            'currency' => 'IDR',
            'supporting_document' => $file,
            'approval_workflow' => [
                [
                    'approver_id' => $this->approver->id,
                    'task_type' => 'approval',
                ],
            ],
            'items' => [
                [
                    'item_name' => 'Laptop',
                    'quantity' => 1,
                    'unit' => 'pcs',
                    'unit_price' => 15000000,
                    'currency' => 'IDR',
                    'expense_department_id' => $this->department->id,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('purchase-requests.store'), $requestData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $pr = PurchaseRequest::latest()->first();
        $this->assertNotNull($pr->supporting_document_path);
        Storage::disk('public')->assertExists($pr->supporting_document_path);
    }

    #[Test]
    public function user_can_upload_item_images()
    {
        $image = UploadedFile::fake()->create('laptop.jpg', 100, 'image/jpeg');

        $requestData = [
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'used_for' => 'Test purchase request with item image',
            'date_of_request' => now()->format('Y-m-d'),
            'currency' => 'IDR',
            'approval_workflow' => [
                [
                    'approver_id' => $this->approver->id,
                    'task_type' => 'approval',
                ],
            ],
            'items' => [
                [
                    'item_name' => 'Laptop',
                    'quantity' => 1,
                    'unit' => 'pcs',
                    'unit_price' => 15000000,
                    'currency' => 'IDR',
                    'expense_department_id' => $this->department->id,
                    'image' => $image,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('purchase-requests.store'), $requestData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $pr = PurchaseRequest::latest()->first();
        $item = $pr->items()->first();
        $this->assertNotNull($item->image_path);
        Storage::disk('public')->assertExists($item->image_path);
    }

    #[Test]
    public function save_as_draft_persists_without_approval_records(): void
    {
        $response = $this->actingAs($this->user)->post(route('purchase-requests.store'), [
            'submission_intent' => 'draft',
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'used_for' => 'Draft office equipment purchase request',
            'date_of_request' => now()->toDateString(),
            'currency' => 'IDR',
            'approval_workflow' => [],
            'items' => [[
                'item_name' => 'Desk',
                'quantity' => 1,
                'unit' => 'pcs',
                'unit_price' => 1000,
                'currency' => 'IDR',
                'expense_department_id' => $this->department->id,
            ]],
        ]);

        $response->assertRedirect()->assertSessionHas('success', 'Purchase request saved as draft.');
        $purchaseRequest = PurchaseRequest::query()->latest('id')->firstOrFail();
        $this->assertSame('draft', $purchaseRequest->status);
        $this->assertNull($purchaseRequest->submitted_at);
        $this->assertSame(0, $purchaseRequest->approvals()->count());
    }

    #[Test]
    public function web_create_rejects_forged_business_unit_department_expense_and_approver(): void
    {
        $otherBusinessUnit = BusinessUnit::factory()->create();
        $otherDepartment = Department::factory()->create(['business_unit_id' => $otherBusinessUnit->id]);
        $otherApprover = User::factory()->create(['primary_department_id' => $otherDepartment->id]);
        $otherApprover->businessUnits()->create([
            'business_unit_id' => $otherBusinessUnit->id,
            'department_id' => $otherDepartment->id,
            'position_id' => Position::where('department_id', $otherDepartment->id)->firstOrFail()->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $payload = [
            'business_unit_id' => $otherBusinessUnit->id,
            'department_id' => $otherDepartment->id,
            'used_for' => 'Forged cross business unit request',
            'date_of_request' => now()->toDateString(),
            'currency' => 'IDR',
            'approval_workflow' => [['approver_id' => $otherApprover->id, 'task_type' => 'approval']],
            'items' => [[
                'item_name' => 'Desk',
                'quantity' => 1,
                'unit' => 'pcs',
                'unit_price' => 1000,
                'currency' => 'IDR',
                'expense_department_id' => $otherDepartment->id,
            ]],
        ];

        $this->actingAs($this->user)->post(route('purchase-requests.store'), $payload)
            ->assertSessionHasErrors(['business_unit_id', 'department_id', 'items.0.expense_department_id']);

        $payload['business_unit_id'] = $this->businessUnit->id;
        $payload['department_id'] = $this->department->id;
        $payload['items'][0]['expense_department_id'] = $this->department->id;
        $this->actingAs($this->user)->post(route('purchase-requests.store'), $payload)
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('pr_approvals', ['approver_id' => $otherApprover->id]);
    }

    #[Test]
    public function failed_create_removes_uploaded_supporting_document_and_item_image(): void
    {
        $otherBusinessUnit = BusinessUnit::factory()->create();
        $otherDepartment = Department::factory()->create(['business_unit_id' => $otherBusinessUnit->id]);
        $otherApprover = User::factory()->create(['primary_department_id' => $otherDepartment->id]);

        $this->actingAs($this->user)->post(route('purchase-requests.store'), [
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'used_for' => 'Rollback uploaded files after workflow failure',
            'date_of_request' => now()->toDateString(),
            'currency' => 'IDR',
            'supporting_document' => UploadedFile::fake()->create('support.pdf', 10, 'application/pdf'),
            'approval_workflow' => [['approver_id' => $otherApprover->id, 'task_type' => 'approval']],
            'items' => [[
                'item_name' => 'Laptop',
                'quantity' => 1,
                'unit' => 'pcs',
                'unit_price' => 1000,
                'currency' => 'IDR',
                'expense_department_id' => $this->department->id,
                'image' => UploadedFile::fake()->create('laptop.jpg', 10, 'image/jpeg'),
            ]],
        ])->assertSessionHas('error');

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertDatabaseMissing('purchase_requests', [
            'used_for' => 'Rollback uploaded files after workflow failure',
        ]);
    }

    #[Test]
    public function failed_update_preserves_old_files_and_removes_new_uploads(): void
    {
        $purchaseRequest = $this->createDraftWithFiles();
        $oldDocument = $purchaseRequest->supporting_document_path;
        $oldImage = $purchaseRequest->items()->firstOrFail()->image_path;
        $otherBusinessUnit = BusinessUnit::factory()->create();
        $otherDepartment = Department::factory()->create(['business_unit_id' => $otherBusinessUnit->id]);
        $otherApprover = User::factory()->create(['primary_department_id' => $otherDepartment->id]);

        $this->actingAs($this->user)->put(route('purchase-requests.update', $purchaseRequest), [
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'used_for' => 'Update that must roll back files',
            'date_of_request' => now()->toDateString(),
            'currency' => 'IDR',
            'supporting_document' => UploadedFile::fake()->create('new-support.pdf', 10, 'application/pdf'),
            'approval_workflow' => [['approver_id' => $otherApprover->id, 'task_type' => 'approval']],
            'items' => [[
                'item_name' => 'Replacement laptop',
                'quantity' => 1,
                'unit' => 'pcs',
                'unit_price' => 2000,
                'currency' => 'IDR',
                'expense_department_id' => $this->department->id,
                'image' => UploadedFile::fake()->create('new-laptop.jpg', 10, 'image/jpeg'),
            ]],
        ])->assertSessionHas('error');

        $purchaseRequest->refresh();
        $this->assertSame($oldDocument, $purchaseRequest->supporting_document_path);
        $this->assertSame($oldImage, $purchaseRequest->items()->firstOrFail()->image_path);
        Storage::disk('public')->assertExists($oldDocument);
        Storage::disk('public')->assertExists($oldImage);
        $this->assertEqualsCanonicalizing([$oldDocument, $oldImage], Storage::disk('public')->allFiles());
    }

    #[Test]
    public function successful_update_deletes_replaced_files_after_commit(): void
    {
        $purchaseRequest = $this->createDraftWithFiles();
        $oldDocument = $purchaseRequest->supporting_document_path;
        $oldImage = $purchaseRequest->items()->firstOrFail()->image_path;

        $this->actingAs($this->user)->put(route('purchase-requests.update', $purchaseRequest), [
            'submission_intent' => 'draft',
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'used_for' => 'Successful file replacement update',
            'date_of_request' => now()->toDateString(),
            'currency' => 'IDR',
            'supporting_document' => UploadedFile::fake()->create('new-support.pdf', 10, 'application/pdf'),
            'approval_workflow' => [],
            'items' => [[
                'item_name' => 'Replacement laptop',
                'quantity' => 1,
                'unit' => 'pcs',
                'unit_price' => 2000,
                'currency' => 'IDR',
                'expense_department_id' => $this->department->id,
                'image' => UploadedFile::fake()->create('new-laptop.jpg', 10, 'image/jpeg'),
            ]],
        ])->assertSessionHas('success', 'Purchase request saved as draft.');

        $purchaseRequest->refresh();
        Storage::disk('public')->assertMissing($oldDocument);
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($purchaseRequest->supporting_document_path);
        Storage::disk('public')->assertExists($purchaseRequest->items()->firstOrFail()->image_path);
    }

    private function createDraftWithFiles(): PurchaseRequest
    {
        $this->actingAs($this->user)->post(route('purchase-requests.store'), [
            'submission_intent' => 'draft',
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'used_for' => 'Original draft with files',
            'date_of_request' => now()->toDateString(),
            'currency' => 'IDR',
            'supporting_document' => UploadedFile::fake()->create('old-support.pdf', 10, 'application/pdf'),
            'approval_workflow' => [],
            'items' => [[
                'item_name' => 'Original laptop',
                'quantity' => 1,
                'unit' => 'pcs',
                'unit_price' => 1000,
                'currency' => 'IDR',
                'expense_department_id' => $this->department->id,
                'image' => UploadedFile::fake()->create('old-laptop.jpg', 10, 'image/jpeg'),
            ]],
        ])->assertSessionHas('success', 'Purchase request saved as draft.');

        return PurchaseRequest::query()->latest('id')->firstOrFail();
    }
}
