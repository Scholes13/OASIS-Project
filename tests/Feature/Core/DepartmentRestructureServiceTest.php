<?php

namespace Tests\Feature\Core;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Services\Core\DepartmentRestructureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Coverage for DepartmentRestructureService::moveUser.
 *
 * Source: PRD docs/specs/2026-05-25-wns-restructure-prd/04-data-migration-plan.md.
 */
class DepartmentRestructureServiceTest extends TestCase
{
    use RefreshDatabase;

    private DepartmentRestructureService $service;

    private BusinessUnit $bu;

    private Department $oldDept;

    private Department $newDept;

    private Position $oldPosition;

    private Position $newPosition;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DepartmentRestructureService::class);

        $this->bu = BusinessUnit::create([
            'name' => 'WNS Test',
            'code' => 'WNS',
            'is_active' => true,
        ]);

        $this->oldDept = Department::create([
            'business_unit_id' => $this->bu->id,
            'code' => 'OLD',
            'name' => 'Old Department',
            'is_active' => true,
        ]);

        $this->newDept = Department::create([
            'business_unit_id' => $this->bu->id,
            'code' => 'NEW',
            'name' => 'New Department',
            'is_active' => true,
        ]);

        // Default positions are auto-generated for root depts.
        $this->oldPosition = Position::where('department_id', $this->oldDept->id)
            ->where('code', 'STAFF_OLD')
            ->firstOrFail();

        $this->newPosition = Position::where('department_id', $this->newDept->id)
            ->where('code', 'STAFF_NEW')
            ->firstOrFail();

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test.user@example.com',
            'phone_number' => '081200000099',
            'password' => bcrypt('secret'),
            'global_role' => 'user',
            'is_active' => true,
            'primary_department_id' => $this->oldDept->id,
            'primary_position_id' => $this->oldPosition->id,
        ]);

        UserBusinessUnit::create([
            'user_id' => $this->user->id,
            'business_unit_id' => $this->bu->id,
            'department_id' => $this->oldDept->id,
            'position_id' => $this->oldPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function dry_run_does_not_change_database(): void
    {
        $result = $this->service->moveUser(
            email: $this->user->email,
            businessUnitCode: 'WNS',
            newDepartmentCode: 'NEW',
            newPositionCode: 'STAFF_NEW',
            dryRun: true,
        );

        $this->assertSame('would_move', $result['status']);
        $this->assertSame($this->oldDept->id, $this->user->fresh()->primary_department_id);
        $this->assertDatabaseHas('user_business_units', [
            'user_id' => $this->user->id,
            'department_id' => $this->oldDept->id,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function execute_moves_user_and_deactivates_old_ubu(): void
    {
        $result = $this->service->moveUser(
            email: $this->user->email,
            businessUnitCode: 'WNS',
            newDepartmentCode: 'NEW',
            newPositionCode: 'STAFF_NEW',
            dryRun: false,
        );

        $this->assertSame('moved', $result['status']);

        $fresh = $this->user->fresh();
        $this->assertSame($this->newDept->id, $fresh->primary_department_id);
        $this->assertSame($this->newPosition->id, $fresh->primary_position_id);

        $this->assertDatabaseHas('user_business_units', [
            'user_id' => $this->user->id,
            'department_id' => $this->newDept->id,
            'position_id' => $this->newPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('user_business_units', [
            'user_id' => $this->user->id,
            'department_id' => $this->oldDept->id,
            'is_primary' => false,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function execute_is_idempotent_on_second_run(): void
    {
        $this->service->moveUser(
            email: $this->user->email,
            businessUnitCode: 'WNS',
            newDepartmentCode: 'NEW',
            newPositionCode: 'STAFF_NEW',
            dryRun: false,
        );

        $second = $this->service->moveUser(
            email: $this->user->email,
            businessUnitCode: 'WNS',
            newDepartmentCode: 'NEW',
            newPositionCode: 'STAFF_NEW',
            dryRun: false,
        );

        $this->assertSame('already_migrated', $second['status']);

        // Only one active UBU row for this user in WNS after a re-run.
        $activeCount = UserBusinessUnit::where('user_id', $this->user->id)
            ->where('business_unit_id', $this->bu->id)
            ->where('is_active', true)
            ->count();
        $this->assertSame(1, $activeCount);
    }

    #[Test]
    public function missing_user_returns_user_not_found(): void
    {
        $result = $this->service->moveUser(
            email: 'ghost@example.com',
            businessUnitCode: 'WNS',
            newDepartmentCode: 'NEW',
            newPositionCode: 'STAFF_NEW',
            dryRun: false,
        );

        $this->assertSame('user_not_found', $result['status']);
    }

    #[Test]
    public function missing_dept_returns_dept_not_found(): void
    {
        $result = $this->service->moveUser(
            email: $this->user->email,
            businessUnitCode: 'WNS',
            newDepartmentCode: 'DOES_NOT_EXIST',
            newPositionCode: 'STAFF_NEW',
            dryRun: false,
        );

        $this->assertSame('dept_not_found', $result['status']);
    }

    #[Test]
    public function missing_position_returns_position_not_found(): void
    {
        $result = $this->service->moveUser(
            email: $this->user->email,
            businessUnitCode: 'WNS',
            newDepartmentCode: 'NEW',
            newPositionCode: 'DOES_NOT_EXIST',
            dryRun: false,
        );

        $this->assertSame('position_not_found', $result['status']);
    }
}
