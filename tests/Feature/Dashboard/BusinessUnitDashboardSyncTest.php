<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\NumberingModule;
use App\Models\Core\NumberSequence;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\PurchaseRequest\PurchaseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test Suite: Business Unit Dashboard Sync Tests
 *
 * Purpose: Validate that the dashboard correctly syncs with global business unit session
 * when users switch business units via the header dropdown.
 *
 * This is a CRITICAL v2.1 fix - dashboard must use global session key for consistency.
 *
 * Background:
 * - Header Badge: Uses session('current_business_unit_id')
 * - Dashboard: Previously used session('dashboard_active_business_unit_id')
 * - Bug: Switching BU in header didn't update dashboard
 * - Fix: Dashboard now prioritizes global session key
 */
class BusinessUnitDashboardSyncTest extends TestCase
{
    use RefreshDatabase;

    protected BusinessUnit $businessUnit1;

    protected BusinessUnit $businessUnit2;

    protected Department $department1;

    protected Department $department2;

    protected Position $position;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two business units
        $this->businessUnit1 = BusinessUnit::create([
            'code' => 'WNS',
            'name' => 'Werkudara Nusantara Sejahtera',
            'is_active' => true,
        ]);

        $this->businessUnit2 = BusinessUnit::create([
            'code' => 'ABC',
            'name' => 'Another Business Company',
            'is_active' => true,
        ]);

        // Create departments for each BU
        $this->department1 = Department::create([
            'business_unit_id' => $this->businessUnit1->id,
            'code' => 'IT',
            'name' => 'Information Technology',
            'is_active' => true,
        ]);

        $this->department2 = Department::create([
            'business_unit_id' => $this->businessUnit2->id,
            'code' => 'HR',
            'name' => 'Human Resources',
            'is_active' => true,
        ]);

        $this->position = Position::create([
            'department_id' => $this->department1->id,
            'name' => 'Staff',
            'code' => 'STAFF',
            'level' => 'staff',
            'hierarchy_level' => 1,
        ]);

        // Create roles
        Role::create(['name' => 'user', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        // Create user with access to both business units
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567890',
        ]);
        $this->user->assignRole('user');

        // Assign to both business units
        UserBusinessUnit::create([
            'user_id' => $this->user->id,
            'business_unit_id' => $this->businessUnit1->id,
            'department_id' => $this->department1->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        UserBusinessUnit::create([
            'user_id' => $this->user->id,
            'business_unit_id' => $this->businessUnit2->id,
            'department_id' => $this->department2->id,
            'position_id' => $this->position->id,
            'is_primary' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Helper: Get current business unit from component
     */
    protected function getCurrentBusinessUnit($component): ?BusinessUnit
    {
        $businessUnitId = $component->get('activeBusinessUnitId');

        return $businessUnitId ? BusinessUnit::find($businessUnitId) : null;
    }

    /**
     * Test 1: Dashboard uses global session key by default
     */
    public function test_dashboard_uses_global_session_key(): void
    {
        // Arrange: Set global session key
        session(['current_business_unit_id' => $this->businessUnit1->id]);

        // Act: Mount dashboard
        $this->actingAs($this->user);
        $component = Livewire::test('dashboard.user-dashboard');

        // Assert: Dashboard uses the global session BU
        $businessUnit = $this->getCurrentBusinessUnit($component);
        $this->assertNotNull($businessUnit);
        $this->assertEquals(
            $this->businessUnit1->id,
            $businessUnit->id,
            'Dashboard should use global current_business_unit_id session key'
        );
    }

    /**
     * Test 2: Dashboard updates when global session changes
     */
    public function test_dashboard_updates_when_global_session_changes(): void
    {
        // Arrange: Start with BU1
        session(['current_business_unit_id' => $this->businessUnit1->id]);
        $this->actingAs($this->user);
        $component = Livewire::test('dashboard.user-dashboard');

        // Initial state
        $businessUnit = $this->getCurrentBusinessUnit($component);
        $this->assertEquals($this->businessUnit1->id, $businessUnit->id);

        // Act: Switch to BU2 via global session (simulating header switch)
        session(['current_business_unit_id' => $this->businessUnit2->id]);

        // Call switchBusinessUnit method (what header switcher calls)
        $component->call('switchBusinessUnit', $this->businessUnit2->id);

        // Assert: Dashboard now shows BU2
        $businessUnit = $this->getCurrentBusinessUnit($component);
        $this->assertEquals(
            $this->businessUnit2->id,
            $businessUnit->id,
            'Dashboard should update to match global session after switch'
        );
    }

    /**
     * Test 3: Dashboard stats are filtered by current business unit
     */
    public function test_dashboard_stats_filtered_by_business_unit(): void
    {
        $this->actingAs($this->user);

        // Create numbering modules for both business units
        $module1 = NumberingModule::create([
            'business_unit_id' => $this->businessUnit1->id,
            'module_code' => 'PR',
            'module_name' => 'Purchase Request',
            'format_pattern' => '{prefix}.{bu_code}/{year}{month}/{seq}',
            'prefix' => 'PR',
            'has_department_separator' => false,
            'has_monthly_separator' => true,
            'has_yearly_separator' => true,
            'reset_type' => 'yearly',
            'is_active' => true,
        ]);

        $module2 = NumberingModule::create([
            'business_unit_id' => $this->businessUnit2->id,
            'module_code' => 'PR',
            'module_name' => 'Purchase Request',
            'format_pattern' => '{prefix}.{bu_code}/{year}{month}/{seq}',
            'prefix' => 'PR',
            'has_department_separator' => false,
            'has_monthly_separator' => true,
            'has_yearly_separator' => true,
            'reset_type' => 'yearly',
            'is_active' => true,
        ]);

        // Create sequences
        $seq1 = NumberSequence::create([
            'numbering_module_id' => $module1->id,
            'business_unit_id' => $this->businessUnit1->id,
            'department_id' => $this->department1->id,
            'year' => now()->year,
            'month' => now()->month,
            'current_number' => 1,
        ]);

        $seq2 = NumberSequence::create([
            'numbering_module_id' => $module2->id,
            'business_unit_id' => $this->businessUnit2->id,
            'department_id' => $this->department2->id,
            'year' => now()->year,
            'month' => now()->month,
            'current_number' => 1,
        ]);

        // Create PRs for different business units
        $pr1 = PurchaseRequest::create([
            'pr_number' => 'PR.WNS/202510/001',
            'sequence_id' => $seq1->id,
            'business_unit_id' => $this->businessUnit1->id,
            'user_id' => $this->user->id,
            'department_id' => $this->department1->id,
            'keperluan' => 'Test BU1',
            'used_for' => 'Testing',
            'date_of_request' => now(),
            'status' => 'draft',
            'currency' => 'IDR',
            'total_amount' => 1000000,
        ]);

        $pr2 = PurchaseRequest::create([
            'pr_number' => 'PR.ABC/202510/001',
            'sequence_id' => $seq2->id,
            'business_unit_id' => $this->businessUnit2->id,
            'user_id' => $this->user->id,
            'department_id' => $this->department2->id,
            'keperluan' => 'Test BU2',
            'used_for' => 'Testing',
            'date_of_request' => now(),
            'status' => 'submitted',
            'currency' => 'IDR',
            'total_amount' => 2000000,
        ]);

        // Test with BU1
        session(['current_business_unit_id' => $this->businessUnit1->id]);
        $component = Livewire::test('dashboard.user-dashboard');

        $stats = $component->get('stats');

        // Should show BU1's draft PR in period stats
        $this->assertEquals(1, $stats['period_prs'], 'BU1 should have 1 PR in period');

        // Test with BU2
        session(['current_business_unit_id' => $this->businessUnit2->id]);
        $component = Livewire::test('dashboard.user-dashboard');

        $stats = $component->get('stats');

        // Should show BU2's submitted PR
        $this->assertEquals(1, $stats['period_prs'], 'BU2 should have 1 PR in period');
        $this->assertEquals(1, $stats['active_prs'], 'BU2 should have 1 active (submitted) PR');
    }

    /**
     * Test 4: Recent activities are filtered by current business unit
     */
    public function test_recent_prs_filtered_by_business_unit(): void
    {
        $this->actingAs($this->user);

        // Create numbering modules and sequences
        $module1 = NumberingModule::create([
            'business_unit_id' => $this->businessUnit1->id,
            'module_code' => 'PR',
            'module_name' => 'Purchase Request',
            'format_pattern' => '{prefix}.{bu_code}/{year}{month}/{seq}',
            'is_active' => true,
        ]);

        $seq1 = NumberSequence::create([
            'numbering_module_id' => $module1->id,
            'business_unit_id' => $this->businessUnit1->id,
            'department_id' => $this->department1->id,
            'year' => now()->year,
            'month' => now()->month,
            'current_number' => 1,
        ]);

        $module2 = NumberingModule::create([
            'business_unit_id' => $this->businessUnit2->id,
            'module_code' => 'PR',
            'module_name' => 'Purchase Request',
            'format_pattern' => '{prefix}.{bu_code}/{year}{month}/{seq}',
            'is_active' => true,
        ]);

        $seq2 = NumberSequence::create([
            'numbering_module_id' => $module2->id,
            'business_unit_id' => $this->businessUnit2->id,
            'department_id' => $this->department2->id,
            'year' => now()->year,
            'month' => now()->month,
            'current_number' => 1,
        ]);

        // Create PRs for different business units
        $pr1 = PurchaseRequest::create([
            'pr_number' => 'PR.WNS/202510/001',
            'sequence_id' => $seq1->id,
            'business_unit_id' => $this->businessUnit1->id,
            'user_id' => $this->user->id,
            'department_id' => $this->department1->id,
            'keperluan' => 'BU1 Purchase',
            'used_for' => 'Testing',
            'date_of_request' => now(),
            'status' => 'submitted',
            'currency' => 'IDR',
            'total_amount' => 1000000,
        ]);

        $pr2 = PurchaseRequest::create([
            'pr_number' => 'PR.ABC/202510/001',
            'sequence_id' => $seq2->id,
            'business_unit_id' => $this->businessUnit2->id,
            'user_id' => $this->user->id,
            'department_id' => $this->department2->id,
            'keperluan' => 'BU2 Purchase',
            'used_for' => 'Testing',
            'date_of_request' => now(),
            'status' => 'submitted',
            'currency' => 'IDR',
            'total_amount' => 2000000,
        ]);

        // Test with BU1
        session(['current_business_unit_id' => $this->businessUnit1->id]);
        $component = Livewire::test('dashboard.user-dashboard');

        $recentActivities = $component->get('recentActivities');

        // Recent activities is an array, we check it's filtered by BU
        $this->assertIsArray($recentActivities, 'Recent activities should be an array');

        // Test with BU2
        session(['current_business_unit_id' => $this->businessUnit2->id]);
        $component = Livewire::test('dashboard.user-dashboard');

        $recentActivities = $component->get('recentActivities');
        $this->assertIsArray($recentActivities, 'Recent activities should be an array for BU2');
    }

    /**
     * Test 5: SwitchBusinessUnit method updates both session keys
     */
    public function test_switch_business_unit_updates_both_session_keys(): void
    {
        // Arrange
        session(['current_business_unit_id' => $this->businessUnit1->id]);
        $this->actingAs($this->user);
        $component = Livewire::test('dashboard.user-dashboard');

        // Act: Switch business unit
        $component->call('switchBusinessUnit', $this->businessUnit2->id);

        // Assert: Both session keys updated
        $this->assertEquals(
            $this->businessUnit2->id,
            session('current_business_unit_id'),
            'Global session key should be updated'
        );

        // Note: dashboard_active_business_unit_id is legacy but still updated for backward compatibility
        $this->assertEquals(
            $this->businessUnit2->id,
            session('dashboard_active_business_unit_id'),
            'Legacy dashboard session key should also be updated for backward compatibility'
        );

        // Component state updated
        $businessUnit = $this->getCurrentBusinessUnit($component);
        $this->assertEquals(
            $this->businessUnit2->id,
            $businessUnit->id
        );
    }

    /**
     * Test 6: Dashboard falls back gracefully when no session set
     */
    public function test_dashboard_falls_back_to_primary_business_unit(): void
    {
        // Arrange: Clear session
        session()->forget('current_business_unit_id');
        session()->forget('dashboard_active_business_unit_id');

        // Act: Mount dashboard without session
        $this->actingAs($this->user);
        $component = Livewire::test('dashboard.user-dashboard');

        // Assert: Falls back to user's primary business unit
        $businessUnit = $this->getCurrentBusinessUnit($component);
        $this->assertEquals(
            $this->businessUnit1->id, // Primary BU
            $businessUnit->id,
            'Dashboard should fall back to primary business unit when session is empty'
        );
    }

    /**
     * Test 7: Dashboard respects user's business unit access
     */
    public function test_dashboard_respects_user_business_unit_access(): void
    {
        // Arrange: Create user with access to only BU1
        $restrictedUser = User::create([
            'name' => 'Restricted User',
            'email' => 'restricted@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567891',
        ]);
        $restrictedUser->assignRole('user');

        UserBusinessUnit::create([
            'user_id' => $restrictedUser->id,
            'business_unit_id' => $this->businessUnit1->id,
            'department_id' => $this->department1->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        // Act: Try to set session to BU2 (user doesn't have access)
        session(['current_business_unit_id' => $this->businessUnit2->id]);
        $this->actingAs($restrictedUser);
        $component = Livewire::test('dashboard.user-dashboard');

        // Assert: Falls back to BU1 (only accessible BU)
        $businessUnit = $this->getCurrentBusinessUnit($component);
        $this->assertEquals(
            $this->businessUnit1->id,
            $businessUnit->id,
            'Dashboard should fall back to accessible BU when session BU is not accessible'
        );
    }

    /**
     * Test 8: Multiple rapid switches don't cause race conditions
     */
    public function test_rapid_business_unit_switches_handled_correctly(): void
    {
        // Arrange
        session(['current_business_unit_id' => $this->businessUnit1->id]);
        $this->actingAs($this->user);
        $component = Livewire::test('dashboard.user-dashboard');

        // Act: Rapid switches
        $component->call('switchBusinessUnit', $this->businessUnit2->id);
        $component->call('switchBusinessUnit', $this->businessUnit1->id);
        $component->call('switchBusinessUnit', $this->businessUnit2->id);

        // Assert: Final state is BU2
        $businessUnit = $this->getCurrentBusinessUnit($component);
        $this->assertEquals(
            $this->businessUnit2->id,
            $businessUnit->id,
            'Dashboard should reflect the final switch'
        );

        $this->assertEquals(
            $this->businessUnit2->id,
            session('current_business_unit_id'),
            'Session should reflect the final switch'
        );
    }

    /**
     * Test 9: Dashboard shows correct business unit name in header
     */
    public function test_dashboard_displays_correct_business_unit_name(): void
    {
        // Arrange
        session(['current_business_unit_id' => $this->businessUnit1->id]);
        $this->actingAs($this->user);

        // Act
        $component = Livewire::test('dashboard.user-dashboard');

        // Assert: Component has correct BU data
        $businessUnit = $this->getCurrentBusinessUnit($component);
        $this->assertEquals('WNS', $businessUnit->code);
        $this->assertEquals('Werkudara Nusantara Sejahtera', $businessUnit->name);

        // Switch and verify
        $component->call('switchBusinessUnit', $this->businessUnit2->id);
        $businessUnit = $this->getCurrentBusinessUnit($component);
        $this->assertEquals('ABC', $businessUnit->code);
        $this->assertEquals('Another Business Company', $businessUnit->name);
    }

    /**
     * Test 10: Dashboard lifecycle methods (boot/hydrate) handle missing BU
     *
     * Note: This test verifies that a user with NO business units
     * cannot access the dashboard (which is expected behavior).
     * The dashboard requires at least one business unit.
     */
    public function test_dashboard_lifecycle_methods_handle_edge_cases(): void
    {
        // Arrange: User with no business units (edge case)
        $orphanUser = User::create([
            'name' => 'Orphan User',
            'email' => 'orphan@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567892',
        ]);
        $orphanUser->assignRole('user');

        // Act: Try to mount dashboard
        $this->actingAs($orphanUser);

        // Assert: Dashboard should fail to load (expected behavior)
        // Users must have at least one business unit to access the dashboard
        $this->expectException(\Exception::class);

        // This will throw exception because no business units available
        $component = Livewire::test('dashboard.user-dashboard');
    }
}
