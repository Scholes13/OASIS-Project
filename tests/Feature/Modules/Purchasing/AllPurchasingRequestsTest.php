<?php

namespace Tests\Feature\Modules\Purchasing;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\NumberingModule;
use App\Models\Core\NumberSequence;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Models\Modules\Purchasing\StockRequest\StockItem;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AllPurchasingRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    #[Test]
    public function canonical_page_combines_purchase_and_stock_requests_in_selected_business_unit(): void
    {
        [$user, $businessUnit, $department] = $this->createUserContext();
        $purchaseRequest = $this->createPurchaseRequest($user, $businessUnit, $department, now()->subMinute());
        $stockRequest = $this->createStockRequest($user, $businessUnit, $department, now());
        StockItem::create([
            'stock_request_id' => $stockRequest->id,
            'item_order' => 1,
            'item_name' => 'Printer toner',
            'quantity' => 2,
            'unit' => 'box',
            'price' => 150000,
            'total' => 300000,
        ]);

        $foreignBusinessUnit = BusinessUnit::factory()->create();
        $foreignDepartment = Department::factory()->create(['business_unit_id' => $foreignBusinessUnit->id]);
        $this->createStockRequest($user, $foreignBusinessUnit, $foreignDepartment, now()->addMinute());

        $response = $this->actingAs($user)
            ->withSession($this->sessionFor($businessUnit, $department))
            ->get(route('purchasing.all-requests'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/AllRequests')
            ->has('requests.data', 2)
            ->where('requests.meta.total', 2)
            ->where('requests.data.0.type', 'stock_request')
            ->where('requests.data.0.number', $stockRequest->st_number)
            ->where('requests.data.0.items_count', 1)
            ->where('requests.data.0.total_amount', '300000.00')
            ->where('requests.data.0.show_url', route('stock-requests.show', $stockRequest))
            ->where('requests.data.1.type', 'purchase_request')
            ->where('requests.data.1.number', $purchaseRequest->pr_number)
            ->where('requests.data.1.show_url', route('purchase-requests.show', $purchaseRequest))
        );
    }

    #[Test]
    public function canonical_filters_and_legacy_redirect_preserve_contract(): void
    {
        [$user, $businessUnit, $department] = $this->createUserContext();
        $this->createPurchaseRequest($user, $businessUnit, $department, now());
        $stockRequest = $this->createStockRequest($user, $businessUnit, $department, now());

        $this->actingAs($user)
            ->withSession($this->sessionFor($businessUnit, $department))
            ->get(route('purchasing.all-requests', [
                'type' => 'stock_request',
                'search' => $stockRequest->st_number,
                'department_id' => $department->id,
                'per_page' => 25,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('requests.data', 1)
                ->where('requests.data.0.type', 'stock_request')
                ->where('requests.meta.per_page', 25)
            );

        $this->actingAs($user)
            ->withSession($this->sessionFor($businessUnit, $department))
            ->get(route('purchase-requests.all', ['type' => 'stock_request', 'status' => 'draft']))
            ->assertRedirect(route('purchasing.all-requests', ['type' => 'stock_request', 'status' => 'draft']));
    }

    #[Test]
    public function filters_reject_unknown_status_and_departments_outside_visible_business_units(): void
    {
        [$user, $businessUnit, $department] = $this->createUserContext();
        $foreignBusinessUnit = BusinessUnit::factory()->create();
        $foreignDepartment = Department::factory()->create(['business_unit_id' => $foreignBusinessUnit->id]);
        $session = $this->sessionFor($businessUnit, $department);

        $this->actingAs($user)->withSession($session)
            ->from(route('purchasing.all-requests'))
            ->get(route('purchasing.all-requests', ['status' => 'unknown']))
            ->assertRedirect(route('purchasing.all-requests'))
            ->assertSessionHasErrors('status');

        $this->actingAs($user)->withSession($session)
            ->from(route('purchasing.all-requests'))
            ->get(route('purchasing.all-requests', ['department_id' => $foreignDepartment->id]))
            ->assertRedirect(route('purchasing.all-requests'))
            ->assertSessionHasErrors('department_id');
    }

    #[Test]
    public function staff_can_view_requests_owned_by_another_user_in_selected_business_unit(): void
    {
        [$viewer, $businessUnit, $department] = $this->createUserContext();
        $owner = $this->createAssignedUser($businessUnit, $department, 'staff');
        $stockRequest = $this->createStockRequest($owner, $businessUnit, $department, now());

        $this->actingAs($viewer)
            ->withSession($this->sessionFor($businessUnit, $department))
            ->get(route('purchasing.all-requests'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('requests.meta.total', 1)
                ->where('requests.data.0.number', $stockRequest->st_number)
                ->where('requests.data.0.requester_name', $owner->name)
            );
    }

    #[Test]
    public function top_management_can_open_descendant_rows_but_regular_staff_cannot(): void
    {
        $parent = BusinessUnit::factory()->create();
        $child = BusinessUnit::factory()->create(['parent_id' => $parent->id]);
        $parentDepartment = Department::factory()->create(['business_unit_id' => $parent->id]);
        $childDepartment = Department::factory()->create(['business_unit_id' => $child->id]);
        $executive = $this->createAssignedUser($parent, $parentDepartment, 'executive');
        $staff = $this->createAssignedUser($parent, $parentDepartment, 'staff');
        $childOwner = $this->createAssignedUser($child, $childDepartment, 'staff');
        $purchaseRequest = $this->createPurchaseRequest($childOwner, $child, $childDepartment, now());
        $stockRequest = $this->createStockRequest($childOwner, $child, $childDepartment, now());

        $session = $this->sessionFor($parent, $parentDepartment);

        $this->actingAs($executive)->withSession($session)
            ->get(route('purchasing.all-requests'))
            ->assertInertia(fn (Assert $page) => $page->where('requests.meta.total', 2));
        $this->actingAs($executive)->withSession($session)
            ->get(route('purchase-requests.show', $purchaseRequest))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('can.edit', false)
                ->where('can.void', false)
                ->where('can.approve', false)
                ->where('can.markOfflineApproved', false)
            );
        $this->actingAs($executive)->withSession($session)
            ->get(route('stock-requests.show', $stockRequest))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('can.edit', false)
                ->where('can.void', false)
                ->where('can.approve', false)
                ->where('can.markOfflineApproved', false)
            );

        $this->actingAs($staff)->withSession($session)
            ->get(route('purchasing.all-requests'))
            ->assertInertia(fn (Assert $page) => $page->where('requests.meta.total', 0));
        $this->actingAs($staff)->withSession($session)->get(route('purchase-requests.show', $purchaseRequest))->assertForbidden();
        $this->actingAs($staff)->withSession($session)->get(route('stock-requests.show', $stockRequest))->assertForbidden();
    }

    /** @return array{User, BusinessUnit, Department} */
    private function createUserContext(): array
    {
        $businessUnit = BusinessUnit::factory()->create();
        $department = Department::factory()->create(['business_unit_id' => $businessUnit->id]);

        return [$this->createAssignedUser($businessUnit, $department, 'staff'), $businessUnit, $department];
    }

    private function createAssignedUser(BusinessUnit $businessUnit, Department $department, string $accessLevel): User
    {
        $position = Position::query()
            ->where('department_id', $department->id)
            ->where('access_level', $accessLevel)
            ->firstOrFail();
        $user = User::factory()->create([
            'primary_department_id' => $department->id,
            'primary_position_id' => $position->id,
            'last_active_business_unit_id' => $businessUnit->id,
        ]);
        UserBusinessUnit::create([
            'user_id' => $user->id,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        return $user;
    }

    private function createPurchaseRequest(User $user, BusinessUnit $businessUnit, Department $department, $createdAt): PurchaseRequest
    {
        $sequence = $this->sequence($businessUnit, $department, 'PR');

        $purchaseRequest = PurchaseRequest::create([
            'pr_number' => 'PR/'.$businessUnit->code.'/'.uniqid(),
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'user_id' => $user->id,
            'sequence_id' => $sequence->id,
            'used_for' => 'Office equipment',
            'date_of_request' => $createdAt->toDateString(),
            'status' => 'draft',
            'total_amount' => 125000,
            'currency' => 'IDR',
        ]);

        $purchaseRequest->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

        return $purchaseRequest;
    }

    private function createStockRequest(User $user, BusinessUnit $businessUnit, Department $department, $createdAt): StockRequest
    {
        $sequence = $this->sequence($businessUnit, $department, 'ST');

        $stockRequest = StockRequest::create([
            'st_number' => 'ST/'.$businessUnit->code.'/'.uniqid(),
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'user_id' => $user->id,
            'sequence_id' => $sequence->id,
            'purpose' => 'Warehouse supplies',
            'date_of_request' => $createdAt->toDateString(),
            'status' => 'draft',
        ]);

        $stockRequest->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

        return $stockRequest;
    }

    private function sequence(BusinessUnit $businessUnit, Department $department, string $code): NumberSequence
    {
        $module = NumberingModule::firstOrCreate([
            'business_unit_id' => $businessUnit->id,
            'module_code' => $code,
        ], [
            'module_name' => $code,
            'format_pattern' => $code.'/{SEQ}',
            'is_active' => true,
        ]);

        return NumberSequence::create([
            'business_unit_id' => $businessUnit->id,
            'numbering_module_id' => $module->id,
            'department_id' => $department->id,
            'year' => (int) now()->format('Y'),
            'month' => (int) now()->format('m'),
            'current_number' => 1,
            'max_number' => 999,
        ]);
    }

    private function sessionFor(BusinessUnit $businessUnit, Department $department): array
    {
        return [
            'current_business_unit_id' => $businessUnit->id,
            'current_department_id' => $department->id,
        ];
    }
}
