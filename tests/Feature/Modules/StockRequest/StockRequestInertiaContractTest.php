<?php

namespace Tests\Feature\Modules\StockRequest;

use App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController;
use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\NumberingModule;
use App\Models\Core\NumberSequence;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Modules\Purchasing\StockRequest\StockRequestDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Response as InertiaResponse;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StockRequestInertiaContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    #[Test]
    public function create_surface_renders_through_inertia_and_legacy_method_delegates(): void
    {
        [$user, $businessUnit, $department] = $this->createUserContext();

        $response = $this->actingAs($user)
            ->withSession([
                'current_business_unit_id' => $businessUnit->id,
                'current_department_id' => $department->id,
            ])
            ->get(route('stock-requests.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/StockRequest/Form')
            ->where('mode', 'create')
            ->where('currentBusinessUnitId', $businessUnit->id)
            ->where('currentDepartmentId', $department->id)
        );

        $controllerResponse = app(StockRequestController::class)->create();

        $this->assertInstanceOf(InertiaResponse::class, $controllerResponse);
    }

    #[Test]
    public function show_surface_renders_through_inertia_and_legacy_method_delegates(): void
    {
        [$user, $businessUnit, $department] = $this->createUserContext();
        $stockRequest = $this->createDraftStockRequest($user, $businessUnit, $department);

        $response = $this->actingAs($user)
            ->withSession([
                'current_business_unit_id' => $businessUnit->id,
                'current_department_id' => $department->id,
            ])
            ->get(route('stock-requests.show', $stockRequest));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/StockRequest/Show')
            ->where('stockRequest.id', $stockRequest->id)
            ->where('stockRequest.st_number', $stockRequest->st_number)
            ->where('can.edit', true)
        );

        $controllerResponse = app(StockRequestController::class)->show($stockRequest);

        $this->assertInstanceOf(InertiaResponse::class, $controllerResponse);
    }

    #[Test]
    public function edit_surface_renders_through_inertia_and_legacy_method_delegates(): void
    {
        [$user, $businessUnit, $department] = $this->createUserContext();
        $stockRequest = $this->createDraftStockRequest($user, $businessUnit, $department);

        $response = $this->actingAs($user)
            ->withSession([
                'current_business_unit_id' => $businessUnit->id,
                'current_department_id' => $department->id,
            ])
            ->get(route('stock-requests.edit', $stockRequest));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/StockRequest/Form')
            ->where('mode', 'edit')
            ->where('stockRequest.id', $stockRequest->id)
            ->where('currentBusinessUnitId', $businessUnit->id)
            ->where('currentDepartmentId', $department->id)
        );

        $controllerResponse = app(StockRequestController::class)->edit($stockRequest);

        $this->assertInstanceOf(InertiaResponse::class, $controllerResponse);
    }

    #[Test]
    public function stock_request_pdf_acknowledger_query_resolves_department_head_without_ambiguous_columns(): void
    {
        [$admin, $businessUnit, $department] = $this->createUserContext();
        $stockRequest = $this->createDraftStockRequest($admin, $businessUnit, $department);

        $headPosition = Position::query()
            ->where('department_id', $department->id)
            ->where('code', 'HOD_'.strtoupper($department->code))
            ->firstOrFail();

        $departmentHead = User::factory()->create([
            'primary_department_id' => $department->id,
            'primary_position_id' => $headPosition->id,
            'email_verified_at' => now(),
        ]);

        UserBusinessUnit::create([
            'user_id' => $departmentHead->id,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'position_id' => $headPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        AdminTask::create([
            'taskable_type' => StockRequest::class,
            'taskable_id' => $stockRequest->id,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'assigned_admin_id' => $admin->id,
            'status' => 'in_progress',
            'entered_at' => now(),
            'estimated_total_price' => 100000,
        ]);

        $acknowledger = app(StockRequestDocumentService::class)
            ->resolvePurchasingAcknowledger($stockRequest->fresh('adminTask.assignedAdmin'));

        $this->assertTrue($departmentHead->is($acknowledger));
    }

    /**
     * @return array{0: User, 1: BusinessUnit, 2: Department}
     */
    protected function createUserContext(): array
    {
        $businessUnit = BusinessUnit::factory()->create();
        $department = Department::factory()->create([
            'business_unit_id' => $businessUnit->id,
        ]);

        $position = Position::query()
            ->where('department_id', $department->id)
            ->where('code', 'STAFF_'.strtoupper($department->code))
            ->firstOrFail();

        $user = User::factory()->create([
            'email' => fake()->unique()->safeEmail(),
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

        $numberingModule = NumberingModule::create([
            'business_unit_id' => $businessUnit->id,
            'module_code' => 'ST',
            'module_name' => 'Stock Request',
            'format_pattern' => 'ST/{BU}/{YYYYMM}/{SEQ}',
            'is_active' => true,
        ]);

        NumberSequence::create([
            'business_unit_id' => $businessUnit->id,
            'numbering_module_id' => $numberingModule->id,
            'department_id' => $department->id,
            'year' => (int) now()->format('Y'),
            'month' => (int) now()->format('m'),
            'current_number' => 1,
            'max_number' => 999,
        ]);

        return [$user, $businessUnit, $department];
    }

    protected function createDraftStockRequest(User $user, BusinessUnit $businessUnit, Department $department): StockRequest
    {
        $sequence = NumberSequence::query()
            ->where('business_unit_id', $businessUnit->id)
            ->where('department_id', $department->id)
            ->firstOrFail();

        return StockRequest::create([
            'st_number' => 'ST/'.$businessUnit->code.'/'.now()->format('Ym').'/001',
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'user_id' => $user->id,
            'sequence_id' => $sequence->id,
            'purpose' => 'Stock item for operational needs',
            'date_of_request' => now()->toDateString(),
            'expected_date' => now()->addDay()->toDateString(),
            'status' => 'draft',
        ]);
    }
}
