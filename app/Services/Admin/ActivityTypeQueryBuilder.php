<?php

namespace App\Services\Admin;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Modules\Activity\ActivityType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Builds props for the {@see \App\Http\Controllers\Admin\ActivityTypeController::index()}
 * page.
 *
 * Behavior is preserved verbatim from the controller — both the super-admin
 * (global view with department + sub-activity + task counts) and the regular
 * user branch (filtered by the current business unit's departments) are
 * reproduced here so the index payload shape and pagination semantics stay
 * identical.
 */
class ActivityTypeQueryBuilder
{
    /**
     * @return array{
     *     activityTypes: LengthAwarePaginator,
     *     departments: Collection,
     *     businessUnits: Collection,
     *     filters: array<string, mixed>,
     * }
     */
    public function buildIndexData(Request $request, bool $isSuperAdmin, mixed $businessUnitId): array
    {
        $businessUnits = $this->loadBusinessUnits($isSuperAdmin);
        $departments = $this->loadDepartments($request, $isSuperAdmin, $businessUnitId);
        $activityTypes = $this->buildActivityTypePaginator(
            $request,
            $isSuperAdmin,
            $departments,
        );

        return [
            'activityTypes' => $activityTypes,
            'departments' => $departments,
            'businessUnits' => $businessUnits,
            'filters' => [
                'search' => $request->search,
                'department_id' => $request->department_id,
                'business_unit_id' => $request->business_unit_id,
            ],
        ];
    }

    private function loadBusinessUnits(bool $isSuperAdmin): Collection
    {
        return $isSuperAdmin
            ? BusinessUnit::active()->orderBy('name')->get(['id', 'code', 'name'])
            : collect();
    }

    private function loadDepartments(Request $request, bool $isSuperAdmin, mixed $businessUnitId): Collection
    {
        $query = Department::query()->orderBy('name');

        if ($request->filled('business_unit_id') && $isSuperAdmin) {
            $query->where('business_unit_id', $request->business_unit_id);
        } elseif (! $isSuperAdmin) {
            $query->where('business_unit_id', $businessUnitId);
        }

        if ($isSuperAdmin) {
            $query->with('businessUnit:id,code,name');
        }

        return $query->get(['id', 'code', 'name', 'business_unit_id'])
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
    }

    private function buildActivityTypePaginator(
        Request $request,
        bool $isSuperAdmin,
        Collection $departments,
    ): LengthAwarePaginator {
        $query = ActivityType::query();

        if ($isSuperAdmin) {
            $query->withCount(['departments', 'subActivities', 'employeeTasks']);

            if ($request->filled('business_unit_id')) {
                $deptIds = Department::where('business_unit_id', $request->business_unit_id)
                    ->pluck('id');
                $query->whereHas('departments', function ($q) use ($deptIds) {
                    $q->whereIn('departments.id', $deptIds);
                });
            }

            if ($request->filled('department_id')) {
                $query->whereHas('departments', function ($q) use ($request) {
                    $q->where('departments.id', $request->department_id);
                });
            }
        } else {
            $deptIds = $departments->pluck('id');

            $query->withCount(['subActivities', 'employeeTasks']);

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

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($isSuperAdmin) {
            $query->with(['departments:id,code,name,business_unit_id']);
        }

        return $query->ordered()->paginate(15)->through(function ($activityType) use ($isSuperAdmin) {
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

            if ($isSuperAdmin) {
                $data['departments_count'] = $activityType->departments_count;
                $data['assigned_department_ids'] = $activityType->departments->pluck('id')->toArray();
            }

            return $data;
        });
    }
}
