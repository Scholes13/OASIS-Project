<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modules\Activity\ActivityType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityTypeController extends Controller
{
    /**
     * Display a listing of activity types.
     */
    public function index(Request $request): View
    {
        $query = ActivityType::withCount(['subActivities', 'employeeTasks']);

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

        $activityTypes = $query->ordered()->paginate(15)->appends($request->query());

        return view('admin.activity-types.index', compact('activityTypes'));
    }

    /**
     * Show the form for creating a new activity type.
     */
    public function create(): View
    {
        return view('admin.activity-types.create');
    }

    /**
     * Store a newly created activity type.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:employee_activity_types,code',
            'name' => 'required|string|max:100',
            'color' => 'required|string|max:20',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $request->input('sort_order', 0);

        ActivityType::create($validated);

        return redirect()->route('admin.activity-types.index')
            ->with('success', 'Activity type created successfully.');
    }

    /**
     * Display the specified activity type.
     */
    public function show(ActivityType $activityType): View
    {
        $activityType->load(['subActivities' => function ($query) {
            $query->ordered();
        }]);

        return view('admin.activity-types.show', compact('activityType'));
    }

    /**
     * Show the form for editing the specified activity type.
     */
    public function edit(ActivityType $activityType): View
    {
        return view('admin.activity-types.edit', compact('activityType'));
    }

    /**
     * Update the specified activity type.
     */
    public function update(Request $request, ActivityType $activityType): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:employee_activity_types,code,' . $activityType->id,
            'name' => 'required|string|max:100',
            'color' => 'required|string|max:20',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $activityType->update($validated);

        return redirect()->route('admin.activity-types.index')
            ->with('success', 'Activity type updated successfully.');
    }

    /**
     * Remove the specified activity type.
     */
    public function destroy(ActivityType $activityType): RedirectResponse
    {
        // Check if activity type is being used by tasks
        if ($activityType->employeeTasks()->exists()) {
            return redirect()->route('admin.activity-types.index')
                ->with('error', 'Cannot delete activity type that is being used by tasks. Consider deactivating it instead.');
        }

        // Delete associated sub-activities first
        $activityType->subActivities()->delete();
        $activityType->delete();

        return redirect()->route('admin.activity-types.index')
            ->with('success', 'Activity type deleted successfully.');
    }
}
