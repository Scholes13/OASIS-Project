<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modules\Activity\ActivityType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityTypeController extends Controller
{
    /**
     * Display a listing of activity types.
     */
    public function index(Request $request): Response
    {
        $query = ActivityType::withCount(['subActivities', 'employeeTasks']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $activityTypes = $query->ordered()->paginate(15)->through(function ($activityType) {
            return [
                'id' => $activityType->id,
                'name' => $activityType->name,
                'color' => $activityType->color,
                'sub_activities_count' => $activityType->sub_activities_count,
                'usage_count' => $activityType->employee_tasks_count,
                'created_at' => $activityType->created_at->toISOString(),
                'updated_at' => $activityType->updated_at->toISOString(),
            ];
        });

        return Inertia::render('Admin/ActivityTypes/Index', [
            'activityTypes' => $activityTypes,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new activity type.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/ActivityTypes/Create');
    }

    /**
     * Store a newly created activity type.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'required|string|max:20',
        ]);

        ActivityType::create($validated);

        return redirect()->route('admin.activity-types.index')
            ->with('success', 'Activity type created successfully.');
    }

    /**
     * Display the specified activity type.
     */
    public function show(ActivityType $activityType): Response
    {
        $activityType->load(['subActivities' => function ($query) {
            $query->ordered();
        }]);

        return Inertia::render('Admin/ActivityTypes/Show', [
            'activityType' => [
                'id' => $activityType->id,
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
            ],
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
     * Remove the specified activity type.
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
                ->with('error', 'Cannot delete activity type that is being used by tasks. Consider deactivating it instead.');
        }

        $activityType->delete();

        return redirect()->route('admin.activity-types.index')
            ->with('success', 'Activity type deleted successfully.');
    }
}
