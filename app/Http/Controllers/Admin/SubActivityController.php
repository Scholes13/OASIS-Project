<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubActivityController extends Controller
{
    /**
     * Display a listing of sub-activities.
     * Super admin sees all sub-activities globally grouped by activity type.
     * Regular users see sub-activities filtered by their business unit's activity types.
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

        // Build activity types query for filter dropdown
        $activityTypesQuery = ActivityType::query()->ordered();

        // For non-super admin, filter activity types by current business unit's departments
        if (!$isSuperAdmin) {
            $deptIds = Department::where('business_unit_id', $businessUnitId)->pluck('id');
            $activityTypesQuery->whereHas('departments', function ($q) use ($deptIds) {
                $q->whereIn('departments.id', $deptIds);
            });
        } elseif ($request->filled('business_unit_id')) {
            // Super admin with business unit filter
            $deptIds = Department::where('business_unit_id', $request->business_unit_id)->pluck('id');
            $activityTypesQuery->whereHas('departments', function ($q) use ($deptIds) {
                $q->whereIn('departments.id', $deptIds);
            });
        }

        $activityTypes = $activityTypesQuery->get()->map(function ($type) {
            // Extract department prefix from code (e.g., "BAS_ADMINISTRATION" -> "BAS")
            $prefix = $type->code ? explode('_', $type->code)[0] : null;
            return [
                'id' => $type->id,
                'name' => $type->name,
                'color' => $type->color,
                'code' => $type->code,
                'department_prefix' => $prefix,
            ];
        });

        // Build sub-activities query
        $query = SubActivity::with('activityType')
            ->withCount('employeeTasks');

        // Filter by activity type
        if ($request->filled('activity_type_id')) {
            $query->where('activity_type_id', $request->activity_type_id);
        } else {
            // If no specific activity type filter, apply business unit filter for non-super admin
            if (!$isSuperAdmin) {
                $deptIds = Department::where('business_unit_id', $businessUnitId)->pluck('id');
                $activityTypeIds = ActivityType::whereHas('departments', function ($q) use ($deptIds) {
                    $q->whereIn('departments.id', $deptIds);
                })->pluck('id');
                $query->whereIn('activity_type_id', $activityTypeIds);
            } elseif ($request->filled('business_unit_id')) {
                // Super admin with business unit filter
                $deptIds = Department::where('business_unit_id', $request->business_unit_id)->pluck('id');
                $activityTypeIds = ActivityType::whereHas('departments', function ($q) use ($deptIds) {
                    $q->whereIn('departments.id', $deptIds);
                })->pluck('id');
                $query->whereIn('activity_type_id', $activityTypeIds);
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

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $subActivities = $query->ordered()->paginate(15)->appends($request->query());
        
        // Transform data for Inertia
        $subActivities->through(function ($subActivity) {
            $prefix = $subActivity->activityType->code ? explode('_', $subActivity->activityType->code)[0] : null;
            return [
                'id' => $subActivity->id,
                'name' => $subActivity->name,
                'code' => $subActivity->code,
                'is_active' => $subActivity->is_active,
                'sort_order' => $subActivity->sort_order,
                'activity_type' => [
                    'id' => $subActivity->activityType->id,
                    'name' => $subActivity->activityType->name,
                    'color' => $subActivity->activityType->color,
                    'code' => $subActivity->activityType->code,
                    'department_prefix' => $prefix,
                ],
                'usage_count' => $subActivity->employee_tasks_count,
                'created_at' => $subActivity->created_at->toISOString(),
            ];
        });

        return Inertia::render('Admin/SubActivities/Index', [
            'subActivities' => $subActivities,
            'activityTypes' => $activityTypes,
            'businessUnits' => $businessUnits,
            'isSuperAdmin' => $isSuperAdmin,
            'filters' => [
                'search' => $request->search,
                'activity_type_id' => $request->activity_type_id ? (int) $request->activity_type_id : null,
                'business_unit_id' => $request->business_unit_id ? (int) $request->business_unit_id : null,
                'status' => $request->status,
            ],
        ]);
    }

    /**
     * Show the form for creating a new sub-activity.
     * Super admin creates global sub-activities without department context.
     */
    public function create(Request $request): Response
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $businessUnitId = session('current_business_unit_id');

        // Build activity types query
        $activityTypesQuery = ActivityType::active()->ordered();

        // For non-super admin, filter by current business unit's departments
        if (!$isSuperAdmin) {
            $deptIds = Department::where('business_unit_id', $businessUnitId)->pluck('id');
            $activityTypesQuery->whereHas('departments', function ($q) use ($deptIds) {
                $q->whereIn('departments.id', $deptIds);
            });
        }

        $activityTypes = $activityTypesQuery->get()->map(function ($type) {
            $prefix = $type->code ? explode('_', $type->code)[0] : null;
            return [
                'id' => $type->id,
                'name' => $type->name,
                'color' => $type->color,
                'code' => $type->code,
                'department_prefix' => $prefix,
            ];
        });
        
        $selectedActivityTypeId = $request->get('activity_type');

        return Inertia::render('Admin/SubActivities/Create', [
            'activityTypes' => $activityTypes,
            'selectedActivityTypeId' => $selectedActivityTypeId,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    /**
     * Store a newly created sub-activity.
     * Super admin creates global sub-activities without department prefix.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'activity_type_id' => 'required|exists:employee_activity_types,id',
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('employee_sub_activities')->where(function ($query) use ($request) {
                    return $query->where('activity_type_id', $request->activity_type_id);
                }),
            ],
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Generate code from name without department prefix
        // Format: ACTIVITY_TYPE_CODE_SUBACTIVITY_NAME (e.g., LEAVE_SICK, TRAINING_INTERNAL)
        $activityType = ActivityType::findOrFail($validated['activity_type_id']);
        $baseCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '_', $validated['name']));
        $baseCode = preg_replace('/_+/', '_', $baseCode); // Remove multiple underscores
        $baseCode = trim($baseCode, '_'); // Remove leading/trailing underscores
        
        // Limit to 20 characters for the sub-activity part
        $baseCode = substr($baseCode, 0, 20);
        
        // Check for uniqueness within the activity type
        $code = $baseCode;
        $counter = 1;
        while (SubActivity::where('activity_type_id', $validated['activity_type_id'])
            ->where('code', $code)
            ->exists()) {
            $code = $baseCode . '_' . $counter;
            $counter++;
        }

        $validated['code'] = $code;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $request->input('sort_order', 0);

        SubActivity::create($validated);

        return redirect()->route('admin.sub-activities.index', ['activity_type_id' => $request->activity_type_id])
            ->with('success', 'Sub-activity created successfully.');
    }

    /**
     * Display the specified sub-activity.
     */
    public function show(SubActivity $subActivity): Response
    {
        $subActivity->load('activityType');

        return Inertia::render('Admin/SubActivities/Show', [
            'subActivity' => [
                'id' => $subActivity->id,
                'name' => $subActivity->name,
                'code' => $subActivity->code,
                'is_active' => $subActivity->is_active,
                'sort_order' => $subActivity->sort_order,
                'activity_type' => [
                    'id' => $subActivity->activityType->id,
                    'name' => $subActivity->activityType->name,
                    'color' => $subActivity->activityType->color,
                ],
                'created_at' => $subActivity->created_at->toISOString(),
                'updated_at' => $subActivity->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified sub-activity.
     */
    public function edit(SubActivity $subActivity): Response
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $businessUnitId = session('current_business_unit_id');

        // Build activity types query
        $activityTypesQuery = ActivityType::ordered();

        // For non-super admin, filter by current business unit's departments
        if (!$isSuperAdmin) {
            $deptIds = Department::where('business_unit_id', $businessUnitId)->pluck('id');
            $activityTypesQuery->whereHas('departments', function ($q) use ($deptIds) {
                $q->whereIn('departments.id', $deptIds);
            });
        }

        $activityTypes = $activityTypesQuery->get()->map(function ($type) {
            $prefix = $type->code ? explode('_', $type->code)[0] : null;
            return [
                'id' => $type->id,
                'name' => $type->name,
                'color' => $type->color,
                'code' => $type->code,
                'department_prefix' => $prefix,
            ];
        });

        return Inertia::render('Admin/SubActivities/Edit', [
            'subActivity' => [
                'id' => $subActivity->id,
                'name' => $subActivity->name,
                'code' => $subActivity->code,
                'is_active' => $subActivity->is_active,
                'sort_order' => $subActivity->sort_order,
                'activity_type_id' => $subActivity->activity_type_id,
            ],
            'activityTypes' => $activityTypes,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    /**
     * Update the specified sub-activity.
     * Code is regenerated without department prefix.
     */
    public function update(Request $request, SubActivity $subActivity): RedirectResponse
    {
        $validated = $request->validate([
            'activity_type_id' => 'required|exists:employee_activity_types,id',
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('employee_sub_activities')->where(function ($query) use ($request) {
                    return $query->where('activity_type_id', $request->activity_type_id);
                })->ignore($subActivity->id),
            ],
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Regenerate code from name without department prefix if name changed
        if ($validated['name'] !== $subActivity->name || $validated['activity_type_id'] !== $subActivity->activity_type_id) {
            $baseCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '_', $validated['name']));
            $baseCode = preg_replace('/_+/', '_', $baseCode);
            $baseCode = trim($baseCode, '_');
            $baseCode = substr($baseCode, 0, 20);
            
            $code = $baseCode;
            $counter = 1;
            while (SubActivity::where('activity_type_id', $validated['activity_type_id'])
                ->where('code', $code)
                ->where('id', '!=', $subActivity->id)
                ->exists()) {
                $code = $baseCode . '_' . $counter;
                $counter++;
            }
            $validated['code'] = $code;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $subActivity->update($validated);

        return redirect()->route('admin.sub-activities.index', ['activity_type_id' => $subActivity->activity_type_id])
            ->with('success', 'Sub-activity updated successfully.');
    }

    /**
     * Remove the specified sub-activity.
     */
    public function destroy(SubActivity $subActivity): RedirectResponse
    {
        $activityTypeId = $subActivity->activity_type_id;

        // Check if sub-activity is being used by tasks
        if ($subActivity->employeeTasks()->exists()) {
            return redirect()->route('admin.sub-activities.index', ['activity_type_id' => $activityTypeId])
                ->with('error', 'Cannot delete sub-activity that is being used by tasks. Consider deactivating it instead.');
        }

        $subActivity->delete();

        return redirect()->route('admin.sub-activities.index', ['activity_type_id' => $activityTypeId])
            ->with('success', 'Sub-activity deleted successfully.');
    }
}
