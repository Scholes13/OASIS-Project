<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\AssignActivityTypeDepartmentsAction;
use App\Actions\Admin\CreateActivityTypeAction;
use App\Http\Controllers\Controller;
use App\Models\Core\Department;
use App\Models\Core\DepartmentActivityType;
use App\Models\Modules\Activity\ActivityType;
use App\Services\Admin\ActivityTypeQueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ActivityTypeController extends Controller
{
    public function __construct(
        private readonly ActivityTypeQueryBuilder $queryBuilder,
        private readonly CreateActivityTypeAction $createAction,
        private readonly AssignActivityTypeDepartmentsAction $assignAction,
    ) {}

    /**
     * Display a listing of activity types.
     * Super admin sees all activity types globally with department count and task count.
     * Regular users see activity types filtered by their business unit's departments.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $businessUnitId = session('current_business_unit_id');

        $data = $this->queryBuilder->buildIndexData($request, $isSuperAdmin, $businessUnitId);

        return Inertia::render('Admin/ActivityTypes/Index', [
            'activityTypes' => $data['activityTypes'],
            'departments' => $data['departments'],
            'businessUnits' => $data['businessUnits'],
            'isSuperAdmin' => $isSuperAdmin,
            'filters' => $data['filters'],
        ]);
    }

    /**
     * Show the form for creating a new activity type.
     * Super admin creates global activity types without department context.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/ActivityTypes/Create', [
            'isSuperAdmin' => Auth::user()->isSuperAdmin(),
        ]);
    }

    /**
     * Store a newly created activity type.
     * Super admin creates global activity types without department prefix.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        // Validation rules differ based on user role
        $rules = [
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:20',
        ];

        // Non-super admin still requires department_id for backward compatibility
        if (! $isSuperAdmin) {
            $rules['department_id'] = 'required|exists:departments,id';
        }

        $validated = $request->validate($rules);

        $this->createAction->execute($validated, $isSuperAdmin);

        return redirect()->route('admin.activity-configuration.index')
            ->with('success', 'Activity type created successfully.');
    }

    /**
     * Display the specified activity type.
     * Shows department assignments for super admin.
     */
    public function show(ActivityType $activityType): Response
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $activityType->load([
            'subActivities' => function ($query) {
                $query->ordered();
            },
            'departments' => function ($query) {
                $query->with('businessUnit:id,code,name')
                    ->orderBy('name');
            },
        ]);

        $data = [
            'id' => $activityType->id,
            'code' => $activityType->code,
            'name' => $activityType->name,
            'color' => $activityType->color,
            'sub_activities_count' => $activityType->subActivities->count(),
            'usage_count' => $activityType->employeeTasks()->count(),
            'created_at' => $activityType->created_at->toISOString(),
            'updated_at' => $activityType->updated_at->toISOString(),
            'sub_activities' => $activityType->subActivities->map(function ($subActivity) {
                return [
                    'id' => $subActivity->id,
                    'name' => $subActivity->name,
                    'created_at' => $subActivity->created_at->toISOString(),
                ];
            }),
        ];

        // Add department assignments for super admin
        if ($isSuperAdmin) {
            $data['departments'] = $activityType->departments->map(function ($department) {
                return [
                    'id' => $department->id,
                    'code' => $department->code,
                    'name' => $department->name,
                    'business_unit' => $department->businessUnit ? [
                        'id' => $department->businessUnit->id,
                        'code' => $department->businessUnit->code,
                        'name' => $department->businessUnit->name,
                    ] : null,
                    'is_default' => $department->pivot->is_default,
                    'sort_order' => $department->pivot->sort_order,
                ];
            });
        }

        // Get all departments for assignment modal (super admin only)
        $allDepartments = $isSuperAdmin
            ? Department::with('businessUnit:id,code,name')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'business_unit_id'])
                ->map(function ($dept) {
                    return [
                        'id' => $dept->id,
                        'code' => $dept->code,
                        'name' => $dept->name,
                        'business_unit' => $dept->businessUnit ? [
                            'id' => $dept->businessUnit->id,
                            'code' => $dept->businessUnit->code,
                            'name' => $dept->businessUnit->name,
                        ] : null,
                    ];
                })
            : collect();

        return Inertia::render('Admin/ActivityTypes/Show', [
            'activityType' => $data,
            'allDepartments' => $allDepartments,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    /**
     * Show the form for editing the specified activity type.
     */
    public function edit(ActivityType $activityType): Response
    {
        return Inertia::render('Admin/ActivityTypes/Edit', [
            'activityType' => [
                'id' => $activityType->id,
                'name' => $activityType->name,
                'color' => $activityType->color,
            ],
            'isSuperAdmin' => Auth::user()->isSuperAdmin(),
        ]);
    }

    /**
     * Update the specified activity type.
     */
    public function update(Request $request, ActivityType $activityType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'required|string|max:20',
        ]);

        $activityType->update($validated);

        return redirect()->route('admin.activity-configuration.index')
            ->with('success', 'Activity type updated successfully.');
    }

    /**
     * Assign activity type to multiple departments.
     * Super admin only.
     */
    public function assignDepartments(Request $request, ActivityType $activityType): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->isSuperAdmin()) {
            return redirect()->route('admin.activity-configuration.index')
                ->with('error', 'Only super admin can assign activity types to departments.');
        }

        $validated = $request->validate([
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'exists:departments,id',
            'is_default' => 'boolean',
        ]);

        $count = $this->assignAction->execute(
            $activityType,
            $validated['department_ids'],
            $validated['is_default'] ?? false,
        );

        return redirect()->route('admin.activity-configuration.index')
            ->with('success', "Activity type assigned to {$count} department(s) successfully.");
    }

    /**
     * Remove activity type from a department.
     * Super admin only. Prevents removal if tasks exist.
     */
    public function removeDepartment(Request $request, ActivityType $activityType): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->isSuperAdmin()) {
            return redirect()->route('admin.activity-configuration.index')
                ->with('error', 'Only super admin can remove activity type assignments.');
        }

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
        ]);

        $departmentId = $validated['department_id'];

        // Check if tasks exist using this activity type in this department
        $tasksExist = $activityType->employeeTasks()
            ->where('department_id', $departmentId)
            ->exists();

        if ($tasksExist) {
            return redirect()->route('admin.activity-types.show', $activityType)
                ->with('error', 'Cannot remove activity type from department. Tasks exist using this activity type in the department.');
        }

        // Remove the assignment
        DepartmentActivityType::where('department_id', $departmentId)
            ->where('activity_type_id', $activityType->id)
            ->delete();

        return redirect()->route('admin.activity-types.show', $activityType)
            ->with('success', 'Activity type removed from department successfully.');
    }

    /**
     * Remove the specified activity type.
     * Validates that no sub-activities or tasks exist.
     */
    public function destroy(ActivityType $activityType): RedirectResponse
    {
        // Check if activity type has sub-activities
        if ($activityType->subActivities()->exists()) {
            return redirect()->route('admin.activity-configuration.index')
                ->with('error', 'Cannot delete activity type with sub-activities. Delete sub-activities first.');
        }

        // Check if activity type is being used by tasks
        if ($activityType->employeeTasks()->exists()) {
            return redirect()->route('admin.activity-configuration.index')
                ->with('error', 'Cannot delete activity type that is being used by tasks.');
        }

        $activityType->delete();

        return redirect()->route('admin.activity-configuration.index')
            ->with('success', 'Activity type deleted successfully.');
    }
}
