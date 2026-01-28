<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments
     */
    public function index(Request $request): InertiaResponse
    {
        $query = Department::with(['businessUnit', 'positions', 'users'])
            ->withCount(['positions', 'users']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhereHas('businessUnit', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        // Business unit filter
        if ($request->filled('business_unit_id')) {
            $query->where('business_unit_id', $request->business_unit_id);
        }

        // Sorting
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        
        if ($sortField === 'business_unit') {
            $query->join('business_units', 'departments.business_unit_id', '=', 'business_units.id')
                  ->orderBy('business_units.name', $sortDirection)
                  ->select('departments.*');
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        $departments = $query->paginate(15)->through(function ($department) {
            return [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'is_active' => $department->is_active,
                'is_purchasing_enabled' => $department->is_purchasing_department ?? false,
                'purchasing_admin_id' => $department->default_purchasing_admin_id,
                'business_unit' => $department->businessUnit ? [
                    'id' => $department->businessUnit->id,
                    'code' => $department->businessUnit->code,
                    'name' => $department->businessUnit->name,
                ] : null,
                'positions_count' => $department->positions_count ?? 0,
                'users_count' => $department->users_count ?? 0,
                'created_at' => $department->created_at->toISOString(),
                'updated_at' => $department->updated_at->toISOString(),
            ];
        });

        $businessUnits = BusinessUnit::active()
            ->orderBy('name')
            ->get()
            ->map(fn($bu) => [
                'id' => $bu->id,
                'code' => $bu->code,
                'name' => $bu->name,
            ]);

        return Inertia::render('Admin/Departments/Index', [
            'departments' => [
                'data' => $departments->items(),
                'pagination' => [
                    'current_page' => $departments->currentPage(),
                    'last_page' => $departments->lastPage(),
                    'per_page' => $departments->perPage(),
                    'total' => $departments->total(),
                    'from' => $departments->firstItem(),
                    'to' => $departments->lastItem(),
                ],
            ],
            'businessUnits' => $businessUnits,
            'filters' => [
                'search' => $request->search,
                'business_unit_id' => $request->business_unit_id ? (int) $request->business_unit_id : null,
            ],
        ]);
    }

    /**
     * Show the form for creating a new department
     */
    public function create(): InertiaResponse
    {
        $businessUnits = BusinessUnit::active()
            ->orderBy('name')
            ->get()
            ->map(fn($bu) => [
                'id' => $bu->id,
                'code' => $bu->code,
                'name' => $bu->name,
            ]);

        $users = \App\Models\Core\User::active()
            ->orderBy('name')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        return Inertia::render('Admin/Departments/Create', [
            'businessUnits' => $businessUnits,
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created department
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_unit_id' => 'required|exists:business_units,id',
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('departments')->where(function ($query) use ($request) {
                    return $query->where('business_unit_id', $request->business_unit_id);
                }),
            ],
            'name' => 'required|string|max:255',
            'head_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'is_purchasing_enabled' => 'boolean',
            'purchasing_admin_id' => 'nullable|exists:users,id',
            'positions' => 'nullable|array',
            'positions.*.id' => 'nullable|exists:positions,id',
            'positions.*.name' => 'required_with:positions|string|max:255',
            'positions.*.code' => 'required_with:positions|string|max:10',
            'positions.*.access_level' => 'nullable|in:staff,supervisor,manager,head',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_purchasing_department'] = $request->boolean('is_purchasing_enabled', false);
        $validated['default_purchasing_admin_id'] = $request->purchasing_admin_id;

        // Remove fields that don't exist in departments table
        unset($validated['is_purchasing_enabled'], $validated['purchasing_admin_id']);

        $department = Department::create($validated);

        // Create positions if provided
        if ($request->has('positions')) {
            foreach ($request->positions as $positionData) {
                if (!empty($positionData['name']) && !empty($positionData['code'])) {
                    $department->positions()->create([
                        'name' => $positionData['name'],
                        'code' => $positionData['code'],
                        'access_level' => $positionData['access_level'] ?? 'staff',
                    ]);
                }
            }
        }

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    /**
     * Display the specified department
     */
    public function show(Department $department): InertiaResponse
    {
        $department->load([
            'businessUnit',
            'positions',
            'head',
            'purchasingAdmin',
        ]);

        // Get user assignments
        $userAssignments = \App\Models\Core\UserBusinessUnit::where('department_id', $department->id)
            ->with(['user', 'position'])
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->user->id,
                    'name' => $assignment->user->name,
                    'email' => $assignment->user->email,
                    'position' => $assignment->position ? [
                        'id' => $assignment->position->id,
                        'name' => $assignment->position->name,
                        'code' => $assignment->position->code,
                    ] : null,
                ];
            });

        return Inertia::render('Admin/Departments/Show', [
            'department' => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'is_active' => $department->is_active,
                'is_purchasing_enabled' => $department->is_purchasing_department ?? false,
                'purchasing_admin_id' => $department->default_purchasing_admin_id,
                'business_unit_id' => $department->business_unit_id,
                'head_id' => $department->head_id,
                'sort_order' => $department->sort_order ?? 0,
                'business_unit' => $department->businessUnit ? [
                    'id' => $department->businessUnit->id,
                    'code' => $department->businessUnit->code,
                    'name' => $department->businessUnit->name,
                ] : null,
                'head' => $department->head ? [
                    'id' => $department->head->id,
                    'name' => $department->head->name,
                    'email' => $department->head->email,
                ] : null,
                'purchasing_admin' => $department->purchasingAdmin ? [
                    'id' => $department->purchasingAdmin->id,
                    'name' => $department->purchasingAdmin->name,
                    'email' => $department->purchasingAdmin->email,
                ] : null,
                'positions' => $department->positions->map(fn($position) => [
                    'id' => $position->id,
                    'code' => $position->code,
                    'name' => $position->name,
                    'access_level' => $position->access_level ?? 'staff',
                ]),
                'user_assignments' => $userAssignments,
                'created_at' => $department->created_at->toISOString(),
                'updated_at' => $department->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified department
     */
    public function edit(Department $department): InertiaResponse
    {
        $department->load(['businessUnit', 'positions', 'head', 'purchasingAdmin']);

        $businessUnits = BusinessUnit::active()
            ->orderBy('name')
            ->get()
            ->map(fn($bu) => [
                'id' => $bu->id,
                'code' => $bu->code,
                'name' => $bu->name,
            ]);

        $users = \App\Models\Core\User::active()
            ->orderBy('name')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        return Inertia::render('Admin/Departments/Edit', [
            'department' => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'is_active' => $department->is_active,
                'is_purchasing_enabled' => $department->is_purchasing_department ?? false,
                'purchasing_admin_id' => $department->default_purchasing_admin_id,
                'business_unit_id' => $department->business_unit_id,
                'head_id' => $department->head_id,
                'sort_order' => $department->sort_order ?? 0,
                'positions' => $department->positions->map(fn($position) => [
                    'id' => $position->id,
                    'code' => $position->code,
                    'name' => $position->name,
                    'access_level' => $position->access_level ?? 'staff',
                ]),
            ],
            'businessUnits' => $businessUnits,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified department
     */
    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'business_unit_id' => 'required|exists:business_units,id',
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('departments')->where(function ($query) use ($request) {
                    return $query->where('business_unit_id', $request->business_unit_id);
                })->ignore($department->id),
            ],
            'name' => 'required|string|max:255',
            'head_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'is_purchasing_enabled' => 'boolean',
            'purchasing_admin_id' => 'nullable|exists:users,id',
            'positions' => 'nullable|array',
            'positions.*.id' => 'nullable|exists:positions,id',
            'positions.*.name' => 'required_with:positions|string|max:255',
            'positions.*.code' => 'required_with:positions|string|max:10',
            'positions.*.access_level' => 'nullable|in:staff,supervisor,manager,head',
            'positions.*._destroy' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_purchasing_department'] = $request->boolean('is_purchasing_enabled', false);
        $validated['default_purchasing_admin_id'] = $request->purchasing_admin_id;

        // Remove fields that don't exist in departments table
        unset($validated['is_purchasing_enabled'], $validated['purchasing_admin_id'], $validated['positions']);

        $department->update($validated);

        // Handle positions
        if ($request->has('positions')) {
            $existingPositionIds = [];

            foreach ($request->positions as $positionData) {
                // Skip if marked for deletion
                if (!empty($positionData['_destroy'])) {
                    if (!empty($positionData['id'])) {
                        $department->positions()->where('id', $positionData['id'])->delete();
                    }
                    continue;
                }

                // Skip if empty
                if (empty($positionData['name']) || empty($positionData['code'])) {
                    continue;
                }

                if (!empty($positionData['id'])) {
                    // Update existing position
                    $position = $department->positions()->find($positionData['id']);
                    if ($position) {
                        $position->update([
                            'name' => $positionData['name'],
                            'code' => $positionData['code'],
                            'access_level' => $positionData['access_level'] ?? 'staff',
                        ]);
                        $existingPositionIds[] = $position->id;
                    }
                } else {
                    // Create new position
                    $position = $department->positions()->create([
                        'name' => $positionData['name'],
                        'code' => $positionData['code'],
                        'access_level' => $positionData['access_level'] ?? 'staff',
                    ]);
                    $existingPositionIds[] = $position->id;
                }
            }
        }

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    /**
     * Show the purchasing admin configuration page
     */
    public function purchasingConfig(Department $department): View
    {
        $department->load(['businessUnit', 'defaultPurchasingAdmin']);

        return view('admin.departments.purchasing-config', compact('department'));
    }

    /**
     * Remove the specified department
     */
    public function destroy(Department $department): RedirectResponse
    {
        // Check if department has positions or users
        if ($department->positions()->count() > 0) {
            return redirect()
                ->route('admin.departments.index')
                ->with('error', 'Cannot delete department that has positions assigned.');
        }

        if ($department->users()->count() > 0) {
            return redirect()
                ->route('admin.departments.index')
                ->with('error', 'Cannot delete department that has users assigned.');
        }

        $department->delete();

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
