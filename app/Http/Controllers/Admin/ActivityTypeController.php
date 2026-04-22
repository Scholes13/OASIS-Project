<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\DepartmentActivityType;
use App\Models\Modules\Activity\ActivityType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ActivityTypeController extends Controller
{
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

        // Get business units for filter (super admin only)
        $businessUnits = $isSuperAdmin
            ? BusinessUnit::active()->orderBy('name')->get(['id', 'code', 'name'])
            : collect();

        // Get departments based on filter
        $departmentsQuery = Department::query()->orderBy('name');

        if ($request->filled('business_unit_id') && $isSuperAdmin) {
            $departmentsQuery->where('business_unit_id', $request->business_unit_id);
        } elseif (! $isSuperAdmin) {
            $departmentsQuery->where('business_unit_id', $businessUnitId);
        }

        // For super admin, include business unit info for assignment modal
        if ($isSuperAdmin) {
            $departmentsQuery->with('businessUnit:id,code,name');
        }

        $departments = $departmentsQuery->get(['id', 'code', 'name', 'business_unit_id'])
            ->map(function ($dept) use ($isSuperAdmin) {
                $data = [
                    'id' => $dept->id,
                    'code' => $dept->code,
                    'name' => $dept->name,
                    'business_unit_id' => $dept->business_unit_id,
                ];

                if ($isSuperAdmin && $dept->businessUnit) {
                    $data['business_unit'] = [
                        'id' => $dept->businessUnit->id,
                        'code' => $dept->businessUnit->code,
                        'name' => $dept->businessUnit->name,
                    ];
                }

                return $data;
            });

        // Build activity types query
        $query = ActivityType::query();

        // Super admin: show all activity types globally
        // Regular users: filter by departments in current business unit
        if ($isSuperAdmin) {
            // Add department count
            $query->withCount([
                'departments',
                'subActivities',
                'employeeTasks',
            ]);

            // Filter by business unit if specified
            if ($request->filled('business_unit_id')) {
                $deptIds = Department::where('business_unit_id', $request->business_unit_id)
                    ->pluck('id');
                $query->whereHas('departments', function ($q) use ($deptIds) {
                    $q->whereIn('departments.id', $deptIds);
                });
            }

            // Filter by department if specified
            if ($request->filled('department_id')) {
                $query->whereHas('departments', function ($q) use ($request) {
                    $q->where('departments.id', $request->department_id);
                });
            }
        } else {
            // Regular user: filter by current business unit's departments
            $deptIds = $departments->pluck('id');

            $query->withCount([
                'subActivities',
                'employeeTasks',
            ]);

            if ($request->filled('department_id')) {
                $query->whereHas('departments', function ($q) use ($request) {
                    $q->where('departments.id', $request->department_id);
                });
            } else {
                $query->whereHas('departments', function ($q) use ($deptIds) {
                    $q->whereIn('departments.id', $deptIds);
                });
            }
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Load department relationships for super admin
        if ($isSuperAdmin) {
            $query->with(['departments:id,code,name,business_unit_id']);
        }

        $activityTypes = $query->ordered()->paginate(15)->through(function ($activityType) use ($isSuperAdmin) {
            $data = [
                'id' => $activityType->id,
                'code' => $activityType->code,
                'name' => $activityType->name,
                'color' => $activityType->color,
                'sub_activities_count' => $activityType->sub_activities_count,
                'usage_count' => $activityType->employee_tasks_count,
                'created_at' => $activityType->created_at->toISOString(),
                'updated_at' => $activityType->updated_at->toISOString(),
            ];

            // Add department count and assigned department IDs for super admin
            if ($isSuperAdmin) {
                $data['departments_count'] = $activityType->departments_count;
                $data['assigned_department_ids'] = $activityType->departments->pluck('id')->toArray();
            }

            return $data;
        });

        return Inertia::render('Admin/ActivityTypes/Index', [
            'activityTypes' => $activityTypes,
            'departments' => $departments,
            'businessUnits' => $businessUnits,
            'isSuperAdmin' => $isSuperAdmin,
            'filters' => [
                'search' => $request->search,
                'department_id' => $request->department_id,
                'business_unit_id' => $request->business_unit_id,
            ],
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
            'color' => 'required|string|max:20',
        ];

        // Non-super admin still requires department_id for backward compatibility
        if (! $isSuperAdmin) {
            $rules['department_id'] = 'required|exists:departments,id';
        }

        $validated = $request->validate($rules);

        // Generate code without department prefix for super admin
        $baseCode = strtoupper(str_replace(' ', '_', $validated['name']));

        if ($isSuperAdmin) {
            // Global activity type: no prefix
            $code = $baseCode;
        } else {
            // Legacy behavior: with department prefix
            $department = Department::findOrFail($validated['department_id']);
            $code = "{$department->code}_{$baseCode}";
        }

        // Ensure unique code
        $existingCount = ActivityType::where('code', 'like', "{$code}%")->count();
        if ($existingCount > 0) {
            $code = "{$code}_{$existingCount}";
        }

        DB::transaction(function () use ($validated, $code, $isSuperAdmin, $request) {
            $activityType = ActivityType::create([
                'code' => $code,
                'name' => $validated['name'],
                'color' => $validated['color'],
                'is_active' => true,
            ]);

            // For non-super admin, link to department
            if (! $isSuperAdmin && $request->filled('department_id')) {
                DB::table('department_activity_types')->insert([
                    'department_id' => $validated['department_id'],
                    'activity_type_id' => $activityType->id,
                    'is_default' => false,
                    'sort_order' => 999,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $redirectParams = [];
        if (! $isSuperAdmin && $request->filled('department_id')) {
            $redirectParams['department_id'] = $validated['department_id'];
        }

        return redirect()->route('admin.activity-types.index', $redirectParams)
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

        return redirect()->route('admin.activity-types.index')
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
            return redirect()->route('admin.activity-types.index')
                ->with('error', 'Only super admin can assign activity types to departments.');
        }

        $validated = $request->validate([
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'exists:departments,id',
            'is_default' => 'boolean',
        ]);

        $isDefault = $validated['is_default'] ?? false;

        DB::transaction(function () use ($activityType, $validated, $isDefault) {
            foreach ($validated['department_ids'] as $departmentId) {
                // Check if assignment already exists
                $exists = DepartmentActivityType::where('department_id', $departmentId)
                    ->where('activity_type_id', $activityType->id)
                    ->exists();

                if (! $exists) {
                    // If setting as default, unset other defaults for this department
                    if ($isDefault) {
                        DepartmentActivityType::where('department_id', $departmentId)
                            ->update(['is_default' => false]);
                    }

                    // Get max sort order for this department
                    $maxSortOrder = DepartmentActivityType::where('department_id', $departmentId)
                        ->max('sort_order') ?? 0;

                    DepartmentActivityType::create([
                        'department_id' => $departmentId,
                        'activity_type_id' => $activityType->id,
                        'is_default' => $isDefault,
                        'sort_order' => $maxSortOrder + 1,
                    ]);
                }
            }
        });

        $count = count($validated['department_ids']);

        return redirect()->route('admin.activity-types.index')
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
            return redirect()->route('admin.activity-types.index')
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
            return redirect()->route('admin.activity-types.index')
                ->with('error', 'Cannot delete activity type with sub-activities. Delete sub-activities first.');
        }

        // Check if activity type is being used by tasks
        if ($activityType->employeeTasks()->exists()) {
            return redirect()->route('admin.activity-types.index')
                ->with('error', 'Cannot delete activity type that is being used by tasks.');
        }

        $activityType->delete();

        return redirect()->route('admin.activity-types.index')
            ->with('success', 'Activity type deleted successfully.');
    }
}
