<?php

namespace Tests\Feature\Modules\SalesCrm;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Services\Core\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SalesCrmShowPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['features.sales_crm' => false]);
    }

    #[Test]
    public function sales_crm_activity_routes_are_disabled_by_feature_flag(): void
    {
        [$user, $businessUnit, $department] = $this->createSalesUserContext();

        $response = $this->actingAs($user)
            ->withSession([
                'current_business_unit_id' => $businessUnit->id,
                'current_business_unit_code' => $businessUnit->code,
                'current_business_unit_name' => $businessUnit->name,
                'current_department_id' => $department->id,
            ])
            ->get('/sales-crm/activities');

        $response->assertNotFound();
    }

    #[Test]
    public function sales_crm_contact_routes_are_disabled_by_feature_flag(): void
    {
        [$user, $businessUnit, $department] = $this->createSalesUserContext();

        $response = $this->actingAs($user)
            ->withSession([
                'current_business_unit_id' => $businessUnit->id,
                'current_business_unit_code' => $businessUnit->code,
                'current_business_unit_name' => $businessUnit->name,
                'current_department_id' => $department->id,
            ])
            ->get('/sales-crm/contacts');

        $response->assertNotFound();
    }

    #[Test]
    public function deprecated_sales_crm_section_is_hidden_from_navigation(): void
    {
        [$user, $businessUnit] = $this->createSalesUserContext();

        $menu = app(NavigationService::class)->buildMenuForUser($user, $businessUnit->id);
        $sectionNames = collect($menu['sections'])->pluck('name');

        $this->assertFalse($sectionNames->contains('Sales CRM'));
    }

    /**
     * @return array{0: User, 1: BusinessUnit, 2: Department}
     */
    protected function createSalesUserContext(): array
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

        Permission::findOrCreate('view_activities', 'web');
        Permission::findOrCreate('view_contacts', 'web');
        $user->givePermissionTo(['view_activities', 'view_contacts']);

        return [$user, $businessUnit, $department];
    }
}
