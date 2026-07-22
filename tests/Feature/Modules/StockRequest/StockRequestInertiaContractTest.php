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
use App\Services\Core\QrCodeService;
use App\Services\Modules\Purchasing\StockRequest\StockRequestDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        Storage::fake('public');
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
    public function ga_staff_submit_skips_department_approval_and_routes_directly_to_purchasing(): void
    {
        [$user, $businessUnit, $primaryDepartment] = $this->createUserContext();
        $gaDepartment = Department::factory()->create([
            'business_unit_id' => $businessUnit->id,
            'is_ga_stock_review_department' => true,
        ]);
        $purchasingDepartment = Department::factory()->create([
            'business_unit_id' => $businessUnit->id,
            'name' => 'Strategic Sourcing',
            'code' => 'SS-'.fake()->unique()->numerify('###'),
            'is_purchasing_department' => true,
        ]);
        $gaStaffPosition = Position::query()
            ->where('department_id', $gaDepartment->id)
            ->where('level', 'staff')
            ->firstOrFail();
        $primaryLeaderPosition = Position::query()
            ->where('department_id', $primaryDepartment->id)
            ->whereIn('level', ['leader', 'hod'])
            ->firstOrFail();

        $user->update(['primary_position_id' => $primaryLeaderPosition->id]);
        $user->businessUnits()
            ->where('department_id', $primaryDepartment->id)
            ->update(['position_id' => $primaryLeaderPosition->id]);
        UserBusinessUnit::create([
            'user_id' => $user->id,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $gaDepartment->id,
            'position_id' => $gaStaffPosition->id,
            'is_primary' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->withSession([
                'current_business_unit_id' => $businessUnit->id,
                'current_department_id' => $gaDepartment->id,
            ])
            ->get(route('stock-requests.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('requiresSupervisorApproval', false)
                ->where('routesDirectlyToPurchasing', true)
            );

        $response = $this->actingAs($user)
            ->withSession([
                'current_business_unit_id' => $businessUnit->id,
                'current_department_id' => $gaDepartment->id,
            ])
            ->post(route('stock-requests.store'), [
                'business_unit_id' => $businessUnit->id,
                'department_id' => $gaDepartment->id,
                'purpose' => 'Office stock for General Affairs operations',
                'date_of_request' => now()->toDateString(),
                'expected_date' => now()->addDay()->toDateString(),
                'items' => [[
                    'item_name' => 'Printer paper',
                    'quantity' => 2,
                    'unit' => 'rim',
                ]],
            ]);

        $response->assertSessionMissing('error');
        $response->assertSessionHasNoErrors();
        $response->assertRedirectContains('/stock-requests/');
        $stockRequest = StockRequest::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('stock-requests.show', $stockRequest));
        $this->assertSame($gaDepartment->id, $stockRequest->department_id);
        $this->assertSame('ready_for_purchasing', $stockRequest->status);
        $this->assertTrue($stockRequest->routes_directly_to_purchasing);
        $this->assertTrue($stockRequest->skips_ga_review);
        $this->assertDatabaseMissing('stock_approvals', [
            'stock_request_id' => $stockRequest->id,
        ]);
        $this->assertNull($stockRequest->ga_review_started_at);
        $this->assertNull($stockRequest->ga_reviewed_at);
        $this->assertNull($stockRequest->ga_reviewed_by);
        $this->assertDatabaseHas('stock_items', [
            'stock_request_id' => $stockRequest->id,
            'ga_review_result' => 'need_procurement',
            'warehouse_available_qty' => 0,
        ]);
        $this->assertDatabaseHas('admin_tasks', [
            'taskable_type' => StockRequest::class,
            'taskable_id' => $stockRequest->id,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $purchasingDepartment->id,
            'status' => 'pending_followup',
        ]);

        $qrCodeService = $this->mock(QrCodeService::class);
        $qrCodeService->shouldReceive('generateStockRequestorQrCodeDataUrl')
            ->once()
            ->andReturn('requestor-qr');
        $qrCodes = app(StockRequestDocumentService::class)->generateQrCodesForPdf(
            $stockRequest->load(['approvals.approver', 'adminTask.assignedAdmin']),
            $qrCodeService,
        );

        $this->assertSame('requestor-qr', $qrCodes['requestor']);
        $this->assertSame([], $qrCodes['approvals']);
        $this->assertArrayNotHasKey('ga_reviewer', $qrCodes);
    }

    #[Test]
    public function non_ga_staff_submit_keeps_department_approval(): void
    {
        [$user, $businessUnit, $department] = $this->createUserContext();
        $headPosition = Position::query()
            ->where('department_id', $department->id)
            ->where('level', 'hod')
            ->firstOrFail();
        $head = User::factory()->create([
            'primary_department_id' => $department->id,
            'primary_position_id' => $headPosition->id,
            'email_verified_at' => now(),
        ]);
        UserBusinessUnit::create([
            'user_id' => $head->id,
            'business_unit_id' => $businessUnit->id,
            'department_id' => $department->id,
            'position_id' => $headPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'current_business_unit_id' => $businessUnit->id,
                'current_department_id' => $department->id,
            ])
            ->post(route('stock-requests.store'), [
                'business_unit_id' => $businessUnit->id,
                'department_id' => $department->id,
                'purpose' => 'Non-GA operational stock request',
                'date_of_request' => now()->toDateString(),
                'items' => [[
                    'item_name' => 'Printer paper',
                    'quantity' => 1,
                    'unit' => 'rim',
                ]],
            ]);

        $stockRequest = StockRequest::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('stock-requests.show', $stockRequest));
        $this->assertSame('in_approval', $stockRequest->status);
        $this->assertFalse($stockRequest->routes_directly_to_purchasing);
        $this->assertFalse($stockRequest->skips_ga_review);
        $this->assertDatabaseHas('stock_approvals', [
            'stock_request_id' => $stockRequest->id,
            'approver_id' => $head->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('admin_tasks', [
            'taskable_type' => StockRequest::class,
            'taskable_id' => $stockRequest->id,
        ]);
    }

    #[Test]
    public function failed_direct_routing_rolls_back_request_and_uploaded_files(): void
    {
        [$user, $businessUnit, $department] = $this->createUserContext();
        $department->update(['is_ga_stock_review_department' => true]);

        $response = $this->actingAs($user)
            ->withSession([
                'current_business_unit_id' => $businessUnit->id,
                'current_department_id' => $department->id,
            ])
            ->post(route('stock-requests.store'), [
                'business_unit_id' => $businessUnit->id,
                'department_id' => $department->id,
                'purpose' => 'Direct request without Purchasing configuration',
                'date_of_request' => now()->toDateString(),
                'offline_approval_document' => UploadedFile::fake()->create(
                    'approval.pdf',
                    50,
                    'application/pdf',
                ),
                'items' => [[
                    'item_name' => 'Printer paper',
                    'quantity' => 1,
                    'unit' => 'rim',
                    'image' => UploadedFile::fake()->create('paper.jpg', 25, 'image/jpeg'),
                ]],
            ]);

        $response->assertSessionHas(
            'error',
            'Exactly one Purchasing department must be configured for this business unit.',
        );
        $this->assertDatabaseCount('stock_requests', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    #[Test]
    public function failed_direct_update_preserves_old_document_and_removes_new_files(): void
    {
        [$user, $businessUnit, $department] = $this->createUserContext();
        $department->update(['is_ga_stock_review_department' => true]);
        $stockRequest = $this->createDraftStockRequest($user, $businessUnit, $department);
        $oldDocumentPath = 'stock-requests/offline-approvals/old-approval.pdf';
        Storage::disk('public')->put($oldDocumentPath, 'old approval');
        $stockRequest->update([
            'offline_approval_document_path' => $oldDocumentPath,
            'offline_approval_document_name' => 'old-approval.pdf',
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'current_business_unit_id' => $businessUnit->id,
                'current_department_id' => $department->id,
            ])
            ->put(route('stock-requests.update', $stockRequest), [
                'business_unit_id' => $businessUnit->id,
                'department_id' => $department->id,
                'purpose' => 'Updated request without Purchasing configuration',
                'date_of_request' => now()->toDateString(),
                'offline_approval_document' => UploadedFile::fake()->create(
                    'new-approval.pdf',
                    50,
                    'application/pdf',
                ),
                'items' => [[
                    'item_name' => 'Printer paper',
                    'quantity' => 1,
                    'unit' => 'rim',
                    'image' => UploadedFile::fake()->create('paper.jpg', 25, 'image/jpeg'),
                ]],
            ]);

        $response->assertSessionHas('error');
        $stockRequest->refresh();
        $this->assertSame($oldDocumentPath, $stockRequest->offline_approval_document_path);
        Storage::disk('public')->assertExists($oldDocumentPath);
        $this->assertSame([$oldDocumentPath], Storage::disk('public')->allFiles());
    }

    #[Test]
    public function direct_route_snapshot_survives_department_flag_change(): void
    {
        [$user, $businessUnit, $department] = $this->createUserContext();
        $department->update(['is_ga_stock_review_department' => true]);
        Department::factory()->create([
            'business_unit_id' => $businessUnit->id,
            'is_purchasing_department' => true,
        ]);

        $this->actingAs($user)
            ->withSession([
                'current_business_unit_id' => $businessUnit->id,
                'current_department_id' => $department->id,
            ])
            ->post(route('stock-requests.store'), [
                'business_unit_id' => $businessUnit->id,
                'department_id' => $department->id,
                'purpose' => 'Immutable direct route snapshot',
                'date_of_request' => now()->toDateString(),
                'items' => [[
                    'item_name' => 'Printer paper',
                    'quantity' => 1,
                    'unit' => 'rim',
                ]],
            ])
            ->assertRedirect();

        $stockRequest = StockRequest::query()->latest('id')->firstOrFail();
        $department->update(['is_ga_stock_review_department' => false]);
        app(\App\Services\Modules\Purchasing\StockRequest\StockRequestPostApprovalRouter::class)
            ->route($stockRequest->fresh());

        $this->assertTrue($stockRequest->fresh()->routes_directly_to_purchasing);
        $this->assertSame('ready_for_purchasing', $stockRequest->fresh()->status);
        $this->assertDatabaseCount('admin_tasks', 1);
    }

    #[Test]
    public function active_route_migration_validates_all_destinations_before_mutation(): void
    {
        [$firstUser, $firstBusinessUnit, $firstDepartment] = $this->createUserContext();
        [$secondUser, , $secondDepartment] = $this->createUserContext();
        Department::factory()->create([
            'business_unit_id' => $firstBusinessUnit->id,
            'code' => 'SS',
            'is_active' => true,
        ]);
        $firstRequest = $this->createDraftStockRequest($firstUser, $firstBusinessUnit, $firstDepartment);
        $secondRequest = $this->createDraftStockRequest(
            $secondUser,
            $secondDepartment->businessUnit,
            $secondDepartment,
        );
        StockRequest::query()->whereKey([$firstRequest->id, $secondRequest->id])->update([
            'status' => 'in_approval',
            'routes_directly_to_purchasing' => true,
            'skips_ga_review' => true,
        ]);
        $migration = require database_path(
            'migrations/2026_07_10_000003_route_active_ga_stock_requests_to_purchasing.php',
        );

        try {
            $migration->up();
            $this->fail('Expected missing Strategic Sourcing configuration to abort migration.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Strategic Sourcing', $exception->getMessage());
        }

        $this->assertSame('in_approval', $firstRequest->fresh()->status);
        $this->assertSame('in_approval', $secondRequest->fresh()->status);
        $this->assertDatabaseCount('admin_tasks', 0);
    }

    #[Test]
    public function snapshot_correction_migration_validates_before_resetting_history(): void
    {
        [$firstUser, $firstBusinessUnit, $firstDepartment] = $this->createUserContext();
        [$secondUser, , $secondDepartment] = $this->createUserContext();
        $firstDepartment->update(['is_ga_stock_review_department' => true]);
        $secondDepartment->update(['is_ga_stock_review_department' => true]);
        Department::factory()->create([
            'business_unit_id' => $firstBusinessUnit->id,
            'code' => 'SS',
            'is_active' => true,
        ]);
        $firstRequest = $this->createDraftStockRequest($firstUser, $firstBusinessUnit, $firstDepartment);
        $secondRequest = $this->createDraftStockRequest(
            $secondUser,
            $secondDepartment->businessUnit,
            $secondDepartment,
        );
        $firstRequest->update([
            'status' => 'in_approval',
            'routes_directly_to_purchasing' => false,
            'skips_ga_review' => false,
        ]);
        $secondRequest->update([
            'status' => 'in_approval',
            'routes_directly_to_purchasing' => false,
            'skips_ga_review' => false,
        ]);
        $migration = require database_path(
            'migrations/2026_07_10_000004_correct_ga_route_snapshots_and_active_tasks.php',
        );

        try {
            $migration->up();
            $this->fail('Expected missing Strategic Sourcing configuration to abort migration.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Strategic Sourcing', $exception->getMessage());
        }

        $this->assertFalse($firstRequest->fresh()->routes_directly_to_purchasing);
        $this->assertFalse($firstRequest->fresh()->skips_ga_review);
        $this->assertFalse($secondRequest->fresh()->routes_directly_to_purchasing);
        $this->assertFalse($secondRequest->fresh()->skips_ga_review);
        $this->assertDatabaseCount('admin_tasks', 0);
    }

    #[Test]
    public function task_claimability_migration_validates_all_destinations_before_mutation(): void
    {
        [$firstUser, $firstBusinessUnit, $firstDepartment] = $this->createUserContext();
        [$secondUser, , $secondDepartment] = $this->createUserContext();
        $firstDepartment->update(['is_ga_stock_review_department' => true]);
        $secondDepartment->update(['is_ga_stock_review_department' => true]);
        Department::factory()->create([
            'business_unit_id' => $firstBusinessUnit->id,
            'code' => 'SS',
            'is_active' => true,
        ]);
        $firstRequest = $this->createDraftStockRequest($firstUser, $firstBusinessUnit, $firstDepartment);
        $secondRequest = $this->createDraftStockRequest(
            $secondUser,
            $secondDepartment->businessUnit,
            $secondDepartment,
        );
        $firstTask = AdminTask::create([
            'taskable_type' => StockRequest::class,
            'taskable_id' => $firstRequest->id,
            'business_unit_id' => $firstBusinessUnit->id,
            'department_id' => $firstDepartment->id,
            'assigned_admin_id' => $firstUser->id,
            'status' => 'in_progress',
            'entered_at' => now(),
            'started_at' => now(),
            'estimated_total_price' => 0,
        ]);
        $secondTask = AdminTask::create([
            'taskable_type' => StockRequest::class,
            'taskable_id' => $secondRequest->id,
            'business_unit_id' => $secondDepartment->business_unit_id,
            'department_id' => $secondDepartment->id,
            'assigned_admin_id' => $secondUser->id,
            'status' => 'in_progress',
            'entered_at' => now(),
            'started_at' => now(),
            'estimated_total_price' => 0,
        ]);
        $migration = require database_path(
            'migrations/2026_07_10_000005_finalize_ga_task_claimability.php',
        );

        try {
            $migration->up();
            $this->fail('Expected missing Strategic Sourcing configuration to abort migration.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Strategic Sourcing', $exception->getMessage());
        }

        $this->assertSame('in_progress', $firstTask->fresh()->status);
        $this->assertSame($firstUser->id, $firstTask->fresh()->assigned_admin_id);
        $this->assertSame($firstDepartment->id, $firstTask->fresh()->department_id);
        $this->assertSame('in_progress', $secondTask->fresh()->status);
        $this->assertSame($secondUser->id, $secondTask->fresh()->assigned_admin_id);
    }

    #[Test]
    public function submit_rejects_business_unit_or_department_outside_active_session_context(): void
    {
        [$user, $businessUnit, $department] = $this->createUserContext();
        $otherDepartment = Department::factory()->create([
            'business_unit_id' => $businessUnit->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'current_business_unit_id' => $businessUnit->id,
                'current_department_id' => $department->id,
            ])
            ->post(route('stock-requests.store'), [
                'business_unit_id' => $businessUnit->id,
                'department_id' => $otherDepartment->id,
                'purpose' => 'Attempted stock request outside active department',
                'date_of_request' => now()->toDateString(),
                'items' => [[
                    'item_name' => 'Printer paper',
                    'quantity' => 1,
                    'unit' => 'rim',
                ]],
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('stock_requests', 0);
    }

    #[Test]
    public function ga_department_head_submit_goes_directly_to_purchasing(): void
    {
        [$user, $businessUnit, $gaDepartment] = $this->createUserContext();
        $gaDepartment->update(['is_ga_stock_review_department' => true]);
        $purchasingDepartment = Department::factory()->create([
            'business_unit_id' => $businessUnit->id,
            'name' => 'Strategic Sourcing',
            'code' => 'SS-'.fake()->unique()->numerify('###'),
            'is_purchasing_department' => true,
        ]);
        $headPosition = Position::query()
            ->where('department_id', $gaDepartment->id)
            ->where('level', 'hod')
            ->firstOrFail();

        $user->update(['primary_position_id' => $headPosition->id]);
        $user->businessUnits()->update(['position_id' => $headPosition->id]);

        $response = $this->actingAs($user)
            ->withSession([
                'current_business_unit_id' => $businessUnit->id,
                'current_department_id' => $gaDepartment->id,
            ])
            ->post(route('stock-requests.store'), [
                'business_unit_id' => $businessUnit->id,
                'department_id' => $gaDepartment->id,
                'purpose' => 'Direct GA procurement request from department head',
                'date_of_request' => now()->toDateString(),
                'items' => [[
                    'item_name' => 'Office cabinet',
                    'quantity' => 1,
                    'unit' => 'unit',
                ]],
            ]);

        $stockRequest = StockRequest::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('stock-requests.show', $stockRequest));
        $this->assertSame('ready_for_purchasing', $stockRequest->status);
        $this->assertDatabaseCount('stock_approvals', 0);
        $this->assertDatabaseHas('admin_tasks', [
            'taskable_type' => StockRequest::class,
            'taskable_id' => $stockRequest->id,
            'department_id' => $purchasingDepartment->id,
            'status' => 'pending_followup',
        ]);
    }

    #[Test]
    public function show_surface_renders_through_inertia_and_legacy_method_delegates(): void
    {
        [$user, $businessUnit, $department] = $this->createUserContext();
        $department->update(['is_ga_stock_review_department' => true]);
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
            ->where('stockRequest.department.is_ga_stock_review_department', true)
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
