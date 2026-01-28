<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubActivityController extends Controller
{
    /**
     * Display a listing of sub-activities.
     */
    public function index(Request $request): Response
    {
        $query = SubActivity::with('activityType')
            ->withCount('employeeTasks');

        // Filter by activity type
        if ($request->filled('activity_type_id')) {
            $query->where('activity_type_id', $request->activity_type_id);
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
                ],
                'usage_count' => $subActivity->employee_tasks_count,
                'created_at' => $subActivity->created_at->toISOString(),
            ];
        });
        
        $activityTypes = ActivityType::ordered()->get()->map(function ($type) {
            return [
                'id' => $type->id,
                'name' => $type->name,
                'color' => $type->color,
            ];
        });

        return Inertia::render('Admin/SubActivities/Index', [
            'subActivities' => $subActivities,
            'activityTypes' => $activityTypes,
            'filters' => [
                'search' => $request->search,
                'activity_type_id' => $request->activity_type_id ? (int) $request->activity_type_id : null,
            ],
        ]);
    }

    /**
     * Show the form for creating a new sub-activity.
     */
    public function create(Request $request): Response
    {
        $activityTypes = ActivityType::active()->ordered()->get()->map(function ($type) {
            return [
                'id' => $type->id,
                'name' => $type->name,
                'color' => $type->color,
            ];
        });
        
        $selectedActivityTypeId = $request->get('activity_type');

        return Inertia::render('Admin/SubActivities/Create', [
            'activityTypes' => $activityTypes,
            'selectedActivityTypeId' => $selectedActivityTypeId,
        ]);
    }

    /**
     * Store a newly created sub-activity.
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

        // Generate code from name if not provided
        if (!isset($validated['code'])) {
            $validated['code'] = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']), 0, 20));
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $request->input('sort_order', 0);

        SubActivity::create($validated);

        return redirect()->route('admin.sub-activities.index', ['activity_type' => $request->activity_type_id])
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
        $activityTypes = ActivityType::ordered()->get()->map(function ($type) {
            return [
                'id' => $type->id,
                'name' => $type->name,
                'color' => $type->color,
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
        ]);
    }

    /**
     * Update the specified sub-activity.
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

        // Generate code from name if not provided
        if (!isset($validated['code'])) {
            $validated['code'] = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']), 0, 20));
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $subActivity->update($validated);

        return redirect()->route('admin.sub-activities.index', ['activity_type' => $subActivity->activity_type_id])
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
            return redirect()->route('admin.sub-activities.index', ['activity_type' => $activityTypeId])
                ->with('error', 'Cannot delete sub-activity that is being used by tasks. Consider deactivating it instead.');
        }

        $subActivity->delete();

        return redirect()->route('admin.sub-activities.index', ['activity_type' => $activityTypeId])
            ->with('success', 'Sub-activity deleted successfully.');
    }
}
