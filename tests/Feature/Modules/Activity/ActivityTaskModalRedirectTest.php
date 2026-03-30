<?php

namespace Tests\Feature\Modules\Activity;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActivityTaskModalRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $teammate;

    protected BusinessUnit $businessUnit;

    protected Department $department;

    protected Department $otherDepartment;

    protected Position $position;

    protected ActivityType $activityType;

    protected EmployeeTask $task;

    protected EmployeeTask $externalTask;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);

        $this->businessUnit = BusinessUnit::create([
            'name' => 'Test Business Unit',
            'code' => 'TBU',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'name' => 'Test Department',
            'code' => 'TDP',
            'business_unit_id' => $this->businessUnit->id,
            'is_active' => true,
        ]);

        $this->otherDepartment = Department::create([
            'name' => 'Other Department',
            'code' => 'ODP',
            'business_unit_id' => $this->businessUnit->id,
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Task Tester',
            'username' => 'task.tester',
            'email' => 'task.tester@example.com',
            'phone_number' => '081234567800',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->position = Position::create([
            'department_id' => $this->department->id,
            'name' => 'Staff Test',
            'code' => 'STF_TEST',
            'level' => 'staff',
            'access_level' => 'staff',
            'hierarchy_level' => 3,
            'is_active' => true,
        ]);

        $this->user->update([
            'primary_position_id' => $this->position->id,
        ]);

        $this->user->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->activityType = ActivityType::create([
            'code' => 'PLAN',
            'name' => 'Planning',
            'color' => '#16599c',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->teammate = User::create([
            'name' => 'Department Teammate',
            'username' => 'department.teammate',
            'email' => 'department.teammate@example.com',
            'phone_number' => '081234567801',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->position->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->teammate->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->department->activityTypes()->attach($this->activityType->id, [
            'is_default' => true,
            'sort_order' => 1,
        ]);

        $this->task = EmployeeTask::create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'activity_type_id' => $this->activityType->id,
            'task_title' => 'Prepare board pack',
            'task_description' => 'Collect and summarize materials',
            'task_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'planned',
            'priority' => 'medium',
        ]);

        $this->task->participants()->attach($this->user->id, [
            'is_owner' => true,
            'joined_at' => now(),
        ]);

        $externalPosition = Position::create([
            'department_id' => $this->otherDepartment->id,
            'name' => 'Other Staff',
            'code' => 'OTH_STF',
            'level' => 'staff',
            'access_level' => 'staff',
            'hierarchy_level' => 3,
            'is_active' => true,
        ]);

        $externalUser = User::create([
            'name' => 'Outside Department User',
            'username' => 'outside.department',
            'email' => 'outside.department@example.com',
            'phone_number' => '081234567802',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->otherDepartment->id,
            'primary_position_id' => $externalPosition->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $externalUser->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->otherDepartment->id,
            'position_id' => $externalPosition->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->externalTask = EmployeeTask::create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->otherDepartment->id,
            'created_by' => $externalUser->id,
            'activity_type_id' => $this->activityType->id,
            'task_title' => 'Hidden department task',
            'task_description' => 'Should not be visible outside the department',
            'task_date' => now()->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
            'status' => 'planned',
            'priority' => 'medium',
        ]);

        session([
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
            'current_business_unit_logo' => $this->businessUnit->logo,
            'current_department_id' => $this->department->id,
            'current_user_role' => $this->user->getAccessLevel(),
        ]);
    }

    public function test_show_route_redirects_to_task_index_with_detail_modal_query(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('activity.task.show', $this->task));

        $response->assertRedirect(route('activity.task.index', [
            'task' => $this->task->id,
            'modal' => 'detail',
        ]));
    }

    public function test_edit_route_redirects_to_task_index_with_edit_modal_query(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('activity.task.edit', $this->task));

        $response->assertRedirect(route('activity.task.index', [
            'task' => $this->task->id,
            'modal' => 'edit',
        ]));
    }

    public function test_show_route_returns_not_found_for_inaccessible_task(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('activity.task.show', $this->externalTask));

        $response->assertNotFound();
    }

    public function test_edit_route_returns_not_found_for_inaccessible_task(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('activity.task.edit', $this->externalTask));

        $response->assertNotFound();
    }

    public function test_create_route_redirects_to_task_index_with_create_modal_query(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('activity.task.create'));

        $response->assertRedirect(route('activity.task.index', [
            'modal' => 'create',
        ]));
    }

    public function test_create_route_preserves_requested_date_when_redirecting_to_create_modal(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('activity.task.create', [
                'date' => '2026-03-31',
            ]));

        $location = $response->headers->get('Location');

        $this->assertNotNull($location);
        $this->assertStringStartsWith(route('activity.task.index'), $location);
        $this->assertStringContainsString('modal=create', $location);
        $this->assertStringContainsString('date=2026-03-31', $location);
    }

    public function test_task_index_includes_selected_task_payload_when_modal_query_is_present(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('activity.task.index', [
                'task' => $this->task->id,
                'modal' => 'detail',
            ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Activity/Dashboard')
        );

        $selectedTask = $response->inertiaProps('selectedTask');

        $this->assertIsArray($selectedTask);
        $this->assertSame($this->task->id, $selectedTask['id']);
        $this->assertSame('Prepare board pack', $selectedTask['task_title']);
        $this->assertSame('detail', $response->inertiaProps('selectedTaskModal'));
    }

    public function test_task_index_includes_avatar_fields_for_calendar_owner_badges(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('activity.task.index', [
                'view' => 'calendar',
            ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Activity/Dashboard')
        );

        $task = $response->inertiaProps('tasks.data.0');

        $this->assertIsArray($task);
        $this->assertArrayHasKey('creator', $task);
        $this->assertArrayHasKey('avatar_url', $task['creator']);
        $this->assertArrayHasKey('participants', $task);
        $this->assertArrayHasKey(0, $task['participants']);
        $this->assertArrayHasKey('avatar_url', $task['participants'][0]);
    }

    public function test_task_index_does_not_hydrate_edit_modal_for_same_department_user_without_edit_access(): void
    {
        $response = $this->actingAs($this->teammate)
            ->get(route('activity.task.index', [
                'task' => $this->task->id,
                'modal' => 'edit',
            ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Activity/Dashboard')
            ->where('selectedTask', null)
            ->where('selectedTaskModal', null)
        );
    }

    public function test_update_forbidden_for_same_department_user_without_edit_access(): void
    {
        $response = $this->actingAs($this->teammate)
            ->put(route('activity.task.update', $this->task), [
                'status' => 'completed',
            ]);

        $response->assertForbidden();
    }

    public function test_full_update_redirect_preserves_dashboard_context_and_returns_to_detail_modal(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('activity.task.update', [
                'task' => $this->task,
                'view' => 'calendar',
                'scope' => 'department',
                'search' => 'board',
            ]), [
                'task_title' => 'Prepare board pack',
                'task_description' => 'Collect and summarize materials',
                'activity_type_id' => $this->activityType->id,
                'sub_activity_id' => null,
                'status' => 'planned',
                'priority' => 'medium',
                'due_date' => $this->task->due_date->format('Y-m-d'),
                'participant_ids' => [$this->user->id],
            ]);

        $location = $response->headers->get('Location');

        $this->assertNotNull($location);
        $this->assertStringStartsWith(route('activity.task.index'), $location);
        $this->assertStringContainsString('view=calendar', $location);
        $this->assertStringContainsString('scope=department', $location);
        $this->assertStringContainsString('search=board', $location);
        $this->assertStringContainsString('task='.$this->task->id, $location);
        $this->assertStringContainsString('modal=detail', $location);
    }
}
