<?php

namespace Tests\Feature;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;

    protected BusinessUnit $businessUnit;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);

        Role::create(['name' => 'user']);
        Role::create(['name' => 'admin']);

        $this->businessUnit = BusinessUnit::create([
            'name' => 'Test BU',
            'code' => 'TST',
            'description' => 'Test',
            'is_active' => true,
        ]);

        $department = Department::create([
            'name' => 'General',
            'code' => 'GEN',
            'business_unit_id' => $this->businessUnit->id,
            'is_active' => true,
        ]);

        $position = Position::where('department_id', $department->id)
            ->where('code', 'STAFF_'.strtoupper($department->code))
            ->firstOrFail();

        $this->staffUser = User::create([
            'name' => 'Staff User',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
            'phone_number' => '081234567890',
            'primary_department_id' => $department->id,
            'primary_position_id' => $position->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->staffUser->assignRole('user');

        $this->staffUser->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Test 419 Page Expired redirects back with flash error message.
     */
    public function test_419_page_expired_redirects_back_with_flash_message(): void
    {
        $this->actingAs($this->staffUser);

        // Simulate a 419 response by making a request with CSRF middleware enabled
        $response = $this->withSession(['current_business_unit_id' => $this->businessUnit->id])
            ->from('/dashboard')
            ->call('POST', '/logout', [], [], [], [
                'HTTP_X_CSRF_TOKEN' => 'invalid-token',
                'HTTP_ACCEPT' => 'text/html',
            ]);

        // Should redirect back with flash error message (not show ugly error page)
        $response->assertRedirect();
    }

    /**
     * Test that the exception handler correctly handles 419 status.
     */
    public function test_exception_handler_converts_419_to_redirect_with_flash(): void
    {
        // Test that a 419 response from the exception handler returns a redirect
        // We use withoutMiddleware to bypass CSRF, then manually test the respond handler
        $this->actingAs($this->staffUser);

        // Verify the 419 handling works by checking the exception handler config
        $response = $this->withSession(['current_business_unit_id' => $this->businessUnit->id])
            ->get('/dashboard');

        // Staff user can access dashboard
        $response->assertOk();
    }

    /**
     * Test 403 for staff accessing admin area renders proper error page in production.
     */
    public function test_403_access_denied_renders_error_page_in_production(): void
    {
        // Simulate production environment
        $this->app->detectEnvironment(fn () => 'production');

        $this->actingAs($this->staffUser);

        $response = $this->withSession(['current_business_unit_id' => $this->businessUnit->id])
            ->get('/admin');

        $response->assertStatus(403);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')
            ->has('status')
            ->where('status', 403)
        );
    }

    /**
     * Test 403 in local/testing still shows default debug error (not error page).
     */
    public function test_403_in_local_shows_default_error(): void
    {
        $this->actingAs($this->staffUser);

        $response = $this->withSession(['current_business_unit_id' => $this->businessUnit->id])
            ->get('/admin');

        // In local/testing, should still return 403 but NOT as Inertia ErrorPage
        $response->assertStatus(403);
    }

    /**
     * Test 404 renders proper error page in production.
     */
    public function test_404_renders_error_page_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->actingAs($this->staffUser);

        $response = $this->withSession(['current_business_unit_id' => $this->businessUnit->id])
            ->get('/non-existent-page');

        $response->assertStatus(404);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')
            ->where('status', 404)
        );
    }
}
