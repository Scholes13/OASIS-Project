<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Modules\Activity\ActivityType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityConfigurationController extends Controller
{
    /**
     * Display the unified Activity Configuration page.
     *
     * Shows all activity types with their sub-activities, task counts,
     * and department assignments in a single consolidated view.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = $request->get('search', '');
        $businessUnitId = $request->get('business_unit_id', '');

        // Base query: activity types with sub-activities, counts
        $query = ActivityType::query()
            ->withCount(['subActivities', 'employeeTasks' => function ($q) {
                // Only count non-cancelled tasks
                $q->whereNotIn('status', ['cancelled']);
            }])
            ->with(['subActivities' => function ($q) {
                $q->withCount(['employeeTasks' => function ($sq) {
                    $sq->whereNotIn('status', ['cancelled']);
                }]);
                $q->orderBy('name');
            }])
            ->with(['departments:id,name,code,business_unit_id']);

        // Search: match activity type name OR sub-activity name
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('subActivities', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // BU filter (super admin): filter by departments in that BU
        if ($businessUnitId) {
            $query->whereHas('departments', function ($q) use ($businessUnitId) {
                $q->where('business_unit_id', $businessUnitId);
            });
        }

        $activityTypes = $query->orderBy('name')->limit(500)->get();

        // Transform to include assigned_department_ids and normalize count keys
        $activityTypes->each(function ($type) {
            $type->assigned_department_ids = $type->departments->pluck('id')->toArray();
            // Expose employee_tasks_count as tasks_count for frontend consistency
            $type->tasks_count = $type->employee_tasks_count;
            $type->subActivities->each(function ($sub) {
                $sub->tasks_count = $sub->employee_tasks_count;
            });
        });

        $departments = Department::where('is_active', true)
            ->with('businessUnit:id,code,name')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'business_unit_id']);

        $businessUnits = BusinessUnit::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('Admin/ActivityConfiguration/Index', [
            'activityTypes' => $activityTypes,
            'departments' => $departments,
            'businessUnits' => $businessUnits,
            'isSuperAdmin' => $user->isSuperAdmin(),
            'filters' => [
                'search' => $search,
                'business_unit_id' => $businessUnitId,
            ],
        ]);
    }
}
