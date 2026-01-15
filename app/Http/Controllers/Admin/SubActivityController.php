<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubActivityController extends Controller
{
    /**
     * Display a listing of sub-activities.
     */
    public function index(Request $request): View
    {
        $query = SubActivity::with('activityType')
            ->withCount('employeeTasks');

        // Filter by activity type
        if ($request->filled('activity_type')) {
            $query->where('activity_type_id', $request->activity_type);
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
        $activityTypes = ActivityType::ordered()->get();

        return view('admin.sub-activities.index', compact('subActivities', 'activityTypes'));
    }

    /**
     * Show the form for creating a new sub-activity.
     */
    public function create(Request $request): View
    {
        $activityTypes = ActivityType::active()->ordered()->get();
        $selectedActivityTypeId = $request->get('activity_type');

        return view('admin.sub-activities.create', compact('activityTypes', 'selectedActivityTypeId'));
    }

    /**
     * Store a newly created sub-activity.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'activity_type_id' => 'required|exists:employee_activity_types,id',
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('employee_sub_activities')->where(function ($query) use ($request) {
                    return $query->where('activity_type_id', $request->activity_type_id);
                }),
            ],
            'name' => 'required|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $request->input('sort_order', 0);

        SubActivity::create($validated);

        return redirect()->route('admin.sub-activities.index', ['activity_type' => $request->activity_type_id])
            ->with('success', 'Sub-activity created successfully.');
    }

    /**
     * Display the specified sub-activity.
     */
    public function show(SubActivity $subActivity): View
    {
        $subActivity->load('activityType');

        return view('admin.sub-activities.show', compact('subActivity'));
    }

    /**
     * Show the form for editing the specified sub-activity.
     */
    public function edit(SubActivity $subActivity): View
    {
        $activityTypes = ActivityType::ordered()->get();

        return view('admin.sub-activities.edit', compact('subActivity', 'activityTypes'));
    }

    /**
     * Update the specified sub-activity.
     */
    public function update(Request $request, SubActivity $subActivity): RedirectResponse
    {
        $validated = $request->validate([
            'activity_type_id' => 'required|exists:employee_activity_types,id',
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('employee_sub_activities')->where(function ($query) use ($request) {
                    return $query->where('activity_type_id', $request->activity_type_id);
                })->ignore($subActivity->id),
            ],
            'name' => 'required|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

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
