<?php

namespace Tests\Feature\Core;

use App\Http\Middleware\EnsureBusinessUnitSelected;
use App\Http\Requests\Purchasing\StorePurchaseRequestRequest;
use App\Http\Requests\Purchasing\StoreStockRequestRequest;
use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MultiBusinessUnitDepartmentAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function active_secondary_department_assignment_grants_access(): void
    {
        [$user, , $secondaryDepartment] = $this->createMultiBusinessUnitUser();

        $this->assertTrue($user->canAccessDepartment($secondaryDepartment->id));

        $user->businessUnits()
            ->where('department_id', $secondaryDepartment->id)
            ->update(['is_active' => false]);

        $this->assertFalse($user->canAccessDepartment($secondaryDepartment->id));
    }

    #[Test]
    public function purchasing_form_requests_authorize_an_active_secondary_assignment(): void
    {
        [$user, , $secondaryDepartment, $secondaryBusinessUnit] = $this->createMultiBusinessUnitUser();
        session([
            'current_business_unit_id' => $secondaryBusinessUnit->id,
            'current_department_id' => $secondaryDepartment->id,
        ]);

        $purchaseRequest = StorePurchaseRequestRequest::create('/purchase-requests', 'POST', [
            'business_unit_id' => $secondaryBusinessUnit->id,
            'department_id' => $secondaryDepartment->id,
        ]);
        $purchaseRequest->setUserResolver(fn () => $user);

        $stockRequest = StoreStockRequestRequest::create('/stock-requests', 'POST', [
            'business_unit_id' => $secondaryBusinessUnit->id,
            'department_id' => $secondaryDepartment->id,
        ]);
        $stockRequest->setUserResolver(fn () => $user);

        $this->assertTrue($purchaseRequest->authorize());
        $this->assertTrue($stockRequest->authorize());
    }

    #[Test]
    public function middleware_repairs_a_stale_department_from_another_business_unit(): void
    {
        [$user, $primaryDepartment, $secondaryDepartment, $secondaryBusinessUnit] = $this->createMultiBusinessUnitUser();

        Route::middleware(['web', EnsureBusinessUnitSelected::class])
            ->get('/_test/current-department', fn () => response()->json([
                'department_id' => session('current_department_id'),
                'department_name' => session('current_department_name'),
            ]));

        $response = $this->actingAs($user)
            ->withSession([
                'current_business_unit_id' => $secondaryBusinessUnit->id,
                'current_business_unit_code' => $secondaryBusinessUnit->code,
                'current_business_unit_name' => $secondaryBusinessUnit->name,
                'current_department_id' => $primaryDepartment->id,
                'current_department_name' => $primaryDepartment->name,
                'current_department_code' => $primaryDepartment->code,
            ])
            ->get('/_test/current-department');

        $response->assertOk()->assertJson([
            'department_id' => $secondaryDepartment->id,
            'department_name' => $secondaryDepartment->name,
        ]);
        $this->assertSame($secondaryDepartment->id, session('current_department_id'));
    }

    #[Test]
    public function middleware_repairs_stale_department_metadata_for_super_admin(): void
    {
        [$user, $primaryDepartment, $secondaryDepartment, $secondaryBusinessUnit] = $this->createMultiBusinessUnitUser();
        $user->update(['global_role' => 'super_admin']);

        Route::middleware(['web', EnsureBusinessUnitSelected::class])
            ->get('/_test/super-admin-department', fn () => response()->json([
                'department_id' => session('current_department_id'),
                'department_name' => session('current_department_name'),
            ]));

        $response = $this->actingAs($user)
            ->withSession([
                'current_business_unit_id' => $secondaryBusinessUnit->id,
                'current_business_unit_code' => $secondaryBusinessUnit->code,
                'current_business_unit_name' => $secondaryBusinessUnit->name,
                'current_department_id' => $secondaryDepartment->id,
                'current_department_name' => $primaryDepartment->name,
                'current_department_code' => $primaryDepartment->code,
            ])
            ->get('/_test/super-admin-department');

        $response->assertOk()->assertJson([
            'department_id' => $secondaryDepartment->id,
            'department_name' => $secondaryDepartment->name,
        ]);
    }

    #[Test]
    public function business_unit_switch_updates_all_department_session_metadata(): void
    {
        [$user, $primaryDepartment, $secondaryDepartment, $secondaryBusinessUnit] = $this->createMultiBusinessUnitUser();

        $response = $this->actingAs($user)
            ->withSession([
                'current_business_unit_id' => $primaryDepartment->business_unit_id,
                'current_business_unit_code' => 'WNS',
                'current_business_unit_name' => 'Werkudara Nirwana Sakti',
                'current_department_id' => $primaryDepartment->id,
                'current_department_name' => $primaryDepartment->name,
                'current_department_code' => $primaryDepartment->code,
            ])
            ->post(route('api.business-unit.switch'), [
                'business_unit_id' => $secondaryBusinessUnit->id,
            ]);

        $response->assertRedirect();
        $this->assertSame($secondaryDepartment->id, session('current_department_id'));
        $this->assertSame($secondaryDepartment->name, session('current_department_name'));
        $this->assertSame($secondaryDepartment->code, session('current_department_code'));
    }

    #[Test]
    public function secondary_executive_assignment_can_resolve_a_child_business_unit_department(): void
    {
        [$user, , , $secondaryBusinessUnit] = $this->createMultiBusinessUnitUser();
        $childBusinessUnit = BusinessUnit::factory()->create([
            'code' => 'GPR-CHILD',
            'parent_id' => $secondaryBusinessUnit->id,
        ]);
        $childDepartment = Department::factory()->for($childBusinessUnit)->create();
        $secondaryDepartmentId = $user->businessUnits()
            ->where('business_unit_id', $secondaryBusinessUnit->id)
            ->value('department_id');
        $executivePosition = Position::create([
            'department_id' => $secondaryDepartmentId,
            'name' => 'Executive GPR',
            'code' => 'EXEC-GPR',
            'level' => 'c_level',
            'access_level' => 'executive',
            'hierarchy_level' => 0,
            'is_active' => true,
        ]);
        $user->businessUnits()
            ->where('business_unit_id', $secondaryBusinessUnit->id)
            ->update(['position_id' => $executivePosition->id]);

        session([
            'current_business_unit_id' => $childBusinessUnit->id,
            'current_department_id' => $childDepartment->id,
        ]);
        $stockRequest = StoreStockRequestRequest::create('/stock-requests', 'POST', [
            'business_unit_id' => $childBusinessUnit->id,
            'department_id' => $childDepartment->id,
        ]);
        $stockRequest->setUserResolver(fn () => $user);

        $this->assertTrue($user->canAccessDepartment($childDepartment->id));
        $this->assertTrue($stockRequest->authorize());
        $this->assertSame(
            $childDepartment->id,
            $user->resolveDepartmentForBusinessUnit($childBusinessUnit->id),
        );
    }

    /**
     * @return array{User, Department, Department, BusinessUnit}
     */
    private function createMultiBusinessUnitUser(): array
    {
        $primaryBusinessUnit = BusinessUnit::factory()->create(['code' => 'WNS']);
        $secondaryBusinessUnit = BusinessUnit::factory()->create(['code' => 'GPR']);
        $primaryDepartment = Department::factory()->for($primaryBusinessUnit)->create(['code' => 'HR-WNS']);
        $secondaryDepartment = Department::factory()->for($secondaryBusinessUnit)->create(['code' => 'HR-GPR']);

        $primaryPosition = Position::create([
            'department_id' => $primaryDepartment->id,
            'name' => 'Team Leader WNS',
            'code' => 'TL-WNS',
            'level' => 'leader',
            'access_level' => 'team_leader',
            'hierarchy_level' => 4,
            'is_active' => true,
        ]);
        $secondaryPosition = Position::create([
            'department_id' => $secondaryDepartment->id,
            'name' => 'Team Leader GPR',
            'code' => 'TL-GPR',
            'level' => 'leader',
            'access_level' => 'team_leader',
            'hierarchy_level' => 4,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'primary_department_id' => $primaryDepartment->id,
            'primary_position_id' => $primaryPosition->id,
            'global_role' => 'user',
            'last_active_business_unit_id' => $secondaryBusinessUnit->id,
        ]);

        $user->businessUnits()->create([
            'business_unit_id' => $primaryBusinessUnit->id,
            'department_id' => $primaryDepartment->id,
            'position_id' => $primaryPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);
        $user->businessUnits()->create([
            'business_unit_id' => $secondaryBusinessUnit->id,
            'department_id' => $secondaryDepartment->id,
            'position_id' => $secondaryPosition->id,
            'is_primary' => false,
            'is_active' => true,
        ]);

        return [$user, $primaryDepartment, $secondaryDepartment, $secondaryBusinessUnit];
    }
}
