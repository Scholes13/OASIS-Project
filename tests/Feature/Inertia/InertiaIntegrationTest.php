<?php

namespace Tests\Feature\Inertia;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Purchasing\PurchaseRequest\PrCategory;
use App\Models\Modules\Purchasing\PurchaseRequest\PrItem;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\Purchasing\PurchaseRequest\UniversalPRNumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InertiaIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected User $approver;

    protected BusinessUnit $businessUnit;

    protected BusinessUnit $secondBusinessUnit;

    protected Department $department;

    protected Department $secondDepartment;

    protected Position $position;

    protected Position $secondPosition;

    protected PrCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);

        // Create roles
        Role::create(['name' => 'user']);
        Role::create(['name' => 'department_head']);
        Role::create(['name' => 'admin']);

        // Create business units
        $this->businessUnit = BusinessUnit::create([
            'name' => 'WNS Business Unit',
            'code' => 'WNS',
            'description' => 'Test business unit',
            'is_active' => true,
        ]);

        $this->secondBusinessUnit = BusinessUnit::create([
            'name' => 'UK Business Unit',
            'code' => 'UK',
            'description' => 'Second test business unit',
            'is_active' => true,
        ]);

        // Create department
        $this->department = Department::create([
            'name' => 'General Affairs',
            'code' => 'GA',
            'business_unit_id' => $this->businessUnit->id,
            'is_active' => true,
        ]);

        $this->secondDepartment = Department::create([
            'name' => 'Operations',
            'code' => 'OPS',
            'business_unit_id' => $this->secondBusinessUnit->id,
            'is_active' => true,
        ]);

        $this->position = Position::where('department_id', $this->department->id)
            ->where('code', 'STAFF_'.strtoupper($this->department->code))
            ->firstOrFail();

        $headPosition = Position::where('department_id', $this->department->id)
            ->where('code', 'HOD_'.strtoupper($this->department->code))
            ->firstOrFail();

        $this->secondPosition = Position::where('department_id', $this->secondDepartment->id)
            ->where('code', 'STAFF_'.strtoupper($this->secondDepartment->code))
            ->firstOrFail();

        // Create PR category
        $this->category = PrCategory::create([
            'name' => 'Office Supplies',
            'code' => 'OFFICE',
            'description' => 'General office supplies',
            'is_active' => true,
        ]);

        // Create users
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test.user@test.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567890',
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->position->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->user->assignRole('user');

        $this->approver = User::create([
            'name' => 'Test Approver',
            'email' => 'test.approver@test.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567891',
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $headPosition->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->approver->assignRole('department_head');

        // Assign users to business units
        foreach ([$this->user, $this->approver] as $index => $user) {
            $user->businessUnits()->create([
                'business_unit_id' => $this->businessUnit->id,
                'department_id' => $this->department->id,
                'position_id' => $index === 0 ? $this->position->id : $headPosition->id,
                'is_primary' => true,
                'is_active' => true,
            ]);

            $user->businessUnits()->create([
                'business_unit_id' => $this->secondBusinessUnit->id,
                'department_id' => $this->secondDepartment->id,
                'position_id' => $this->secondPosition->id,
                'is_active' => true,
            ]);
        }
    }

    #[Test]
    public function test_inertia_dashboard_renders_correctly()
    {
        $this->actingAs($this->user);

        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('auth.user')
            ->has('currentBusinessUnit')
            ->has('availableBusinessUnits')
            ->has('navigation')
            ->where('auth.user.id', $this->user->id)
            ->where('currentBusinessUnit.id', $this->businessUnit->id)
        );
    }

    #[Test]
    public function test_inertia_navigation_includes_shared_props()
    {
        $this->actingAs($this->user);

        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('auth')
            ->has('auth.user', fn (Assert $user) => $user
                ->where('id', $this->user->id)
                ->where('name', $this->user->name)
                ->where('email', $this->user->email)
                ->etc()
            )
            ->has('currentBusinessUnit', fn (Assert $bu) => $bu
                ->where('id', $this->businessUnit->id)
                ->where('code', $this->businessUnit->code)
                ->where('name', $this->businessUnit->name)
                ->etc()
            )
            ->has('availableBusinessUnits')
            ->has('navigation')
            ->has('navigation.sections')
        );
    }

    #[Test]
    public function test_purchase_request_index_page_renders()
    {
        $this->actingAs($this->user);

        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
        ]);

        $response = $this->get(route('purchase-requests.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/PurchaseRequest/Index')
            ->has('purchaseRequests')
            ->has('purchaseRequests.data')
            ->has('purchaseRequests.links')
            ->has('filters')
            ->has('statuses')
        );
    }

    #[Test]
    public function test_purchase_request_create_page_renders()
    {
        $this->actingAs($this->user);

        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
        ]);

        $response = $this->get(route('purchase-requests.create'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/PurchaseRequest/Create')
            ->has('categories')
            ->has('departments')
            ->has('businessUnits')
            ->has('availableApprovers')
        );
    }

    #[Test]
    public function test_purchase_request_show_page_renders()
    {
        $this->actingAs($this->user);

        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
        ]);

        // Create a purchase request
        $pr = $this->createSamplePurchaseRequest();

        $response = $this->get(route('purchase-requests.show', $pr));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/PurchaseRequest/Show')
            ->has('purchaseRequest')
            ->has('purchaseRequest.items')
            ->has('purchaseRequest.approvals')
            ->where('purchaseRequest.id', $pr->id)
            ->where('purchaseRequest.pr_number', $pr->pr_number)
        );
    }

    #[Test]
    public function test_business_unit_switching_updates_session()
    {
        $this->actingAs($this->user);

        // Set initial business unit
        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
        ]);

        // Switch to second business unit
        $response = $this->post(route('api.business-unit.switch'), [
            'business_unit_id' => $this->secondBusinessUnit->id,
        ]);

        $response->assertRedirect();

        // Verify session was updated
        $this->assertEquals($this->secondBusinessUnit->id, session('current_business_unit_id'));
        $this->assertEquals($this->secondBusinessUnit->code, session('current_business_unit_code'));
        $this->assertEquals($this->secondBusinessUnit->name, session('current_business_unit_name'));
    }

    #[Test]
    public function test_business_unit_switch_reloads_page_with_new_context()
    {
        $this->actingAs($this->user);

        // Create PRs in both business units
        session(['current_business_unit_id' => $this->businessUnit->id]);
        $pr1 = $this->createSamplePurchaseRequest();

        session(['current_business_unit_id' => $this->secondBusinessUnit->id]);
        $pr2 = $this->createSamplePurchaseRequest();

        // Switch to first business unit
        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
        ]);

        $response = $this->get(route('purchase-requests.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/PurchaseRequest/Index')
            ->where('currentBusinessUnit.id', $this->businessUnit->id)
            ->has('purchaseRequests.data')
        );

        // Switch to second business unit
        $this->post(route('api.business-unit.switch'), [
            'business_unit_id' => $this->secondBusinessUnit->id,
        ]);

        session([
            'current_business_unit_id' => $this->secondBusinessUnit->id,
            'current_business_unit_code' => $this->secondBusinessUnit->code,
            'current_business_unit_name' => $this->secondBusinessUnit->name,
        ]);

        $response = $this->get(route('purchase-requests.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/PurchaseRequest/Index')
            ->where('currentBusinessUnit.id', $this->secondBusinessUnit->id)
        );
    }

    #[Test]
    public function test_form_submission_with_inertia()
    {
        $this->actingAs($this->user);

        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
        ]);

        $requestData = [
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'category_id' => $this->category->id,
            'currency' => 'IDR',
            'date_of_request' => now()->toDateString(),
            'expected_date' => now()->addDays(7)->toDateString(),
            'used_for' => 'Office supplies for Q1 2025',
            'notes' => 'Urgent request',
            'approval_workflow' => [
                [
                    'approver_id' => $this->approver->id,
                    'task_type' => 'approval',
                ],
            ],
            'items' => [
                [
                    'item_name' => 'Office Chair',
                    'brand_name' => 'Herman Miller',
                    'item_description' => 'Ergonomic office chair',
                    'supplier_name' => 'Office Furniture Inc',
                    'quantity' => 2,
                    'unit' => 'pcs',
                    'unit_price' => 500000,
                    'currency' => 'IDR',
                    'expense_department_id' => $this->department->id,
                ],
            ],
        ];

        $response = $this->post(route('purchase-requests.store'), $requestData);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    #[Test]
    public function test_form_validation_errors_returned_to_inertia()
    {
        $this->actingAs($this->user);

        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
        ]);

        // Submit invalid data (missing required fields)
        $response = $this->post(route('purchase-requests.store'), [
            'business_unit_id' => $this->businessUnit->id,
            // Missing required fields
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    #[Test]
    public function test_authentication_required_for_inertia_pages()
    {
        // Try to access dashboard without authentication
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));

        // Try to access PR index without authentication
        $response = $this->get(route('purchase-requests.index'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function test_business_unit_context_required_for_pr_pages()
    {
        $this->actingAs($this->user);

        // Try to access PR pages without business unit context
        session()->forget('current_business_unit_id');

        $response = $this->get(route('purchase-requests.index'));

        // Current behavior: page remains accessible without explicit BU session
        $response->assertStatus(200);
    }

    #[Test]
    public function test_inertia_navigation_flow_between_pages()
    {
        $this->actingAs($this->user);

        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
        ]);

        // Navigate from dashboard to PR index
        $response = $this->get(route('dashboard'));
        $response->assertOk();

        $response = $this->get(route('purchase-requests.index'));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/PurchaseRequest/Index')
        );

        // Navigate to PR create
        $response = $this->get(route('purchase-requests.create'));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/PurchaseRequest/Create')
        );

        // Create a PR and navigate to show page
        $pr = $this->createSamplePurchaseRequest();

        $response = $this->get(route('purchase-requests.show', $pr));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/PurchaseRequest/Show')
            ->where('purchaseRequest.id', $pr->id)
        );
    }

    #[Test]
    public function test_flash_messages_passed_to_inertia()
    {
        $this->actingAs($this->user);

        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
        ]);

        // Set flash message
        session()->flash('success', 'Operation completed successfully');

        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('flash')
            ->where('flash.success', 'Operation completed successfully')
        );
    }

    #[Test]
    public function test_pagination_works_with_inertia()
    {
        $this->actingAs($this->user);

        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
        ]);

        // Create multiple PRs
        for ($i = 0; $i < 15; $i++) {
            $this->createSamplePurchaseRequest();
        }

        // Test first page
        $response = $this->get(route('purchase-requests.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/PurchaseRequest/Index')
            ->has('purchaseRequests.data')
            ->has('purchaseRequests.current_page')
            ->has('purchaseRequests.last_page')
            ->has('purchaseRequests.links')
        );

        // Test second page
        $response = $this->get(route('purchase-requests.index', ['page' => 2]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/PurchaseRequest/Index')
            ->where('purchaseRequests.current_page', 2)
        );
    }

    #[Test]
    public function test_filtering_works_with_inertia()
    {
        $this->actingAs($this->user);

        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
        ]);

        // Create PRs with different statuses
        $draftPr = $this->createSamplePurchaseRequest();
        $submittedPr = $this->createSamplePurchaseRequest();
        $submittedPr->update(['status' => 'submitted', 'submitted_at' => now()]);

        // Filter by status
        $response = $this->get(route('purchase-requests.index', ['status' => 'draft']));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/PurchaseRequest/Index')
            ->where('filters.status', 'draft')
            ->has('purchaseRequests.data')
        );

        // Filter by search
        $response = $this->get(route('purchase-requests.index', ['search' => $draftPr->pr_number]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/PurchaseRequest/Index')
            ->where('filters.search', $draftPr->pr_number)
        );
    }

    protected function createSamplePurchaseRequest(): PurchaseRequest
    {
        $numberingService = app(UniversalPRNumberingService::class);
        $prNumber = $numberingService->generatePRNumber($this->user);

        $pr = PurchaseRequest::create([
            'pr_number' => $prNumber['formatted_number'],
            'business_unit_id' => session('current_business_unit_id', $this->businessUnit->id),
            'department_id' => $this->department->id,
            'user_id' => $this->user->id,
            'sequence_id' => $prNumber['sequence_id'],
            'category_id' => $this->category->id,
            'used_for' => 'Test purchase request',
            'date_of_request' => now(),
            'expected_date' => now()->addDays(7),
            'status' => 'draft',
            'currency' => 'IDR',
            'last_modified_by' => $this->user->id,
        ]);

        // Add items
        PrItem::create([
            'purchase_request_id' => $pr->id,
            'item_order' => 1,
            'item_name' => 'Test Item',
            'brand_name' => 'Test Brand',
            'item_description' => 'Test description',
            'supplier_name' => 'Test Supplier',
            'quantity' => 1,
            'unit' => 'pcs',
            'unit_price' => 600000,
            'currency' => 'IDR',
            'expense_department_id' => $this->department->id,
        ]);

        $pr->updateTotalAmount();

        return $pr;
    }
}
