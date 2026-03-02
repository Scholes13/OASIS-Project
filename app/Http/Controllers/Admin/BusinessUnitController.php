<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BusinessUnitController extends Controller
{
    /**
     * Display a listing of business units
     */
    public function index(Request $request)
    {
        $query = BusinessUnit::with(['parent', 'children'])
            ->withCount(['departments', 'users', 'purchaseRequests']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $businessUnits = $query->orderBy('name')->paginate(15);

        // Format business units for Inertia
        $formattedBusinessUnits = $businessUnits->through(function ($bu) {
            return [
                'id' => $bu->id,
                'name' => $bu->name,
                'code' => $bu->code,
                'logo_url' => $bu->logo ? asset('storage/'.$bu->logo) : null,
                'is_active' => $bu->is_active,
                'parent_id' => $bu->parent_id,
                'parent' => $bu->parent ? [
                    'id' => $bu->parent->id,
                    'name' => $bu->parent->name,
                    'code' => $bu->parent->code,
                ] : null,
                'children' => $bu->children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'code' => $child->code,
                        'is_active' => $child->is_active,
                    ];
                }),
                'user_count' => $bu->users_count ?? 0,
                'department_count' => $bu->departments_count ?? 0,
                'created_at' => $bu->created_at->toISOString(),
            ];
        });

        return Inertia::render('Admin/BusinessUnits/Index', [
            'businessUnits' => $formattedBusinessUnits,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
            ],
        ]);
    }

    /**
     * Show the form for creating a new business unit
     */
    public function create()
    {
        $parentBusinessUnits = BusinessUnit::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($bu) {
                return [
                    'value' => (string) $bu->id,
                    'label' => "{$bu->name} ({$bu->code})",
                ];
            });

        // Get active users as potential managers
        $managers = \App\Models\Core\User::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => [
                'value' => (string) $user->id,
                'label' => $user->name,
            ]);

        return Inertia::render('Admin/BusinessUnits/Create', [
            'parentBusinessUnits' => $parentBusinessUnits,
            'managers' => $managers,
        ]);
    }

    /**
     * Store a newly created business unit
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:business_units,code',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'parent_id' => 'nullable|exists:business_units,id',
            'is_active' => 'boolean',
        ]);

        // Handle logo upload
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('business-units', 'public');
        }

        // Create business unit
        $businessUnit = BusinessUnit::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'logo' => $logoPath,
            'description' => $validated['description'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'numbering_config' => [],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('admin.business-units.index')
            ->with('success', "Business unit '{$businessUnit->name}' created successfully.");
    }

    /**
     * Display the specified business unit
     */
    public function show(BusinessUnit $businessUnit)
    {
        $businessUnit->load([
            'parent',
            'children',
            'manager',
            'departments',
        ]);

        // Get users assigned to this business unit
        $users = \App\Models\Core\User::whereHas('businessUnits', function ($query) use ($businessUnit) {
            $query->where('business_unit_id', $businessUnit->id);
        })->with(['primaryBusinessUnit', 'primaryDepartment', 'primaryPosition'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'primary_business_unit' => $user->primaryBusinessUnit ? [
                        'id' => $user->primaryBusinessUnit->id,
                        'name' => $user->primaryBusinessUnit->name,
                        'code' => $user->primaryBusinessUnit->code,
                    ] : null,
                    'primary_department' => $user->primaryDepartment ? [
                        'id' => $user->primaryDepartment->id,
                        'name' => $user->primaryDepartment->name,
                    ] : null,
                    'primary_position' => $user->primaryPosition ? [
                        'id' => $user->primaryPosition->id,
                        'name' => $user->primaryPosition->name,
                    ] : null,
                ];
            });

        // Get departments
        $departments = $businessUnit->departments->map(function ($dept) {
            return [
                'id' => $dept->id,
                'name' => $dept->name,
                'code' => $dept->code,
                'is_active' => $dept->is_active,
                'users_count' => $dept->users()->count(),
            ];
        });

        // Statistics
        $stats = [
            'total_departments' => $businessUnit->departments()->count(),
            'total_users' => \App\Models\Core\UserBusinessUnit::where('business_unit_id', $businessUnit->id)->count(),
            'total_purchase_requests' => $businessUnit->purchaseRequests()->count(),
            'active_sequences' => $businessUnit->numberSequences()->count(),
        ];

        // Format business unit data
        $businessUnitData = [
            'id' => $businessUnit->id,
            'code' => $businessUnit->code,
            'name' => $businessUnit->name,
            'description' => $businessUnit->description,
            'address' => $businessUnit->address,
            'phone' => $businessUnit->phone,
            'email' => $businessUnit->email,
            'logo' => $businessUnit->logo ? asset('storage/'.$businessUnit->logo) : null,
            'is_active' => $businessUnit->is_active,
            'parent_id' => $businessUnit->parent_id,
            'manager_id' => $businessUnit->manager_id,
            'sort_order' => $businessUnit->sort_order,
            'created_at' => $businessUnit->created_at->toISOString(),
            'updated_at' => $businessUnit->updated_at->toISOString(),
            'parent' => $businessUnit->parent ? [
                'id' => $businessUnit->parent->id,
                'name' => $businessUnit->parent->name,
                'code' => $businessUnit->parent->code,
            ] : null,
            'children' => $businessUnit->children->map(function ($child) {
                return [
                    'id' => $child->id,
                    'name' => $child->name,
                    'code' => $child->code,
                    'is_active' => $child->is_active,
                ];
            }),
            'manager' => $businessUnit->manager ? [
                'id' => $businessUnit->manager->id,
                'name' => $businessUnit->manager->name,
                'email' => $businessUnit->manager->email,
            ] : null,
        ];

        // Check permissions
        $can = [
            'edit' => auth()->user()->isSuperAdmin(),
            'delete' => auth()->user()->isSuperAdmin() && $businessUnit->code !== 'WG',
        ];

        return inertia('Admin/BusinessUnits/Show', [
            'businessUnit' => $businessUnitData,
            'departments' => $departments,
            'users' => $users,
            'stats' => $stats,
            'can' => $can,
        ]);
    }

    /**
     * Show the form for editing the specified business unit
     */
    public function edit(BusinessUnit $businessUnit)
    {
        $parentBusinessUnits = BusinessUnit::where('id', '!=', $businessUnit->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($bu) {
                return [
                    'value' => (string) $bu->id,
                    'label' => "{$bu->name} ({$bu->code})",
                ];
            });

        // Get active users as potential managers
        $managers = \App\Models\Core\User::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => [
                'value' => (string) $user->id,
                'label' => $user->name,
            ]);

        // Format business unit data
        $businessUnitData = [
            'id' => $businessUnit->id,
            'name' => $businessUnit->name,
            'code' => $businessUnit->code,
            'logo' => $businessUnit->logo ? asset('storage/'.$businessUnit->logo) : null,
            'description' => $businessUnit->description,
            'address' => $businessUnit->address,
            'phone' => $businessUnit->phone,
            'email' => $businessUnit->email,
            'parent_id' => $businessUnit->parent_id,
            'manager_id' => $businessUnit->manager_id,
            'is_active' => $businessUnit->is_active,
        ];

        return Inertia::render('Admin/BusinessUnits/Edit', [
            'businessUnit' => $businessUnitData,
            'parentBusinessUnits' => $parentBusinessUnits,
            'managers' => $managers,
        ]);
    }

    /**
     * Update the specified business unit
     */
    public function update(Request $request, BusinessUnit $businessUnit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:10', Rule::unique('business_units')->ignore($businessUnit->id)],
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'remove_logo' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'parent_id' => 'nullable|exists:business_units,id',
            'is_active' => 'boolean',
        ]);

        // Prevent setting parent to itself or creating circular reference
        if ($request->parent_id == $businessUnit->id) {
            return back()->withErrors(['parent_id' => 'Business unit cannot be its own parent.']);
        }

        // Handle logo removal
        if ($request->boolean('remove_logo') && $businessUnit->logo) {
            \Storage::disk('public')->delete($businessUnit->logo);
            $validated['logo'] = null;
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($businessUnit->logo) {
                \Storage::disk('public')->delete($businessUnit->logo);
            }
            $validated['logo'] = $request->file('logo')->store('business-units', 'public');
        }

        // Update business unit
        $businessUnit->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'logo' => $validated['logo'] ?? $businessUnit->logo,
            'description' => $validated['description'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'is_active' => $validated['is_active'] ?? $businessUnit->is_active,
        ]);

        return redirect()
            ->route('admin.business-units.index')
            ->with('success', "Business unit '{$businessUnit->name}' updated successfully.");
    }

    /**
     * Remove the specified business unit
     */
    public function destroy(BusinessUnit $businessUnit)
    {
        // Prevent deletion of Werkudara Group (parent company)
        if ($businessUnit->code === 'WG') {
            return back()->withErrors(['delete' => 'Cannot delete the parent company (Werkudara Group).']);
        }

        // Check if business unit has child business units
        if ($businessUnit->children()->exists()) {
            return back()->withErrors(['delete' => 'Cannot delete business unit that has child business units. Please reassign or delete child units first.']);
        }

        // Check if business unit has departments
        $departmentCount = $businessUnit->departments()->count();
        if ($departmentCount > 0) {
            return back()->withErrors(['delete' => "Cannot delete business unit that has {$departmentCount} departments. Please delete or reassign departments first."]);
        }

        // Check if business unit has active users
        $activeUsers = $businessUnit->users()->count();
        if ($activeUsers > 0) {
            return back()->withErrors(['delete' => "Cannot delete business unit that has {$activeUsers} users. Please reassign users first."]);
        }

        DB::transaction(function () use ($businessUnit) {
            // Delete logo if exists
            if ($businessUnit->logo) {
                \Storage::disk('public')->delete($businessUnit->logo);
            }

            // Delete numbering sequences
            $businessUnit->numberSequences()->delete();

            // Delete numbering modules
            $businessUnit->numberingModules()->delete();

            // Finally delete the business unit
            $businessUnit->delete();
        });

        $businessUnitName = $businessUnit->name;

        return redirect()
            ->route('admin.business-units.index')
            ->with('success', "Business unit '{$businessUnitName}' deleted successfully.");
    }

    /**
     * Toggle business unit status
     */
    public function toggleStatus(BusinessUnit $businessUnit)
    {
        $businessUnit->update(['is_active' => ! $businessUnit->is_active]);

        $status = $businessUnit->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Business unit '{$businessUnit->name}' {$status} successfully.");
    }

    /**
     * Get business unit configuration
     */
    public function getConfiguration(BusinessUnit $businessUnit)
    {
        return response()->json([
            'config' => $businessUnit->numbering_config,
            'numbering_modules' => $businessUnit->numberingModules,
        ]);
    }

    /**
     * Update business unit configuration
     */
    public function updateConfiguration(Request $request, BusinessUnit $businessUnit)
    {
        $request->validate([
            'config' => 'required|array',
        ]);

        $businessUnit->update([
            'numbering_config' => $request->config,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Configuration updated successfully.',
        ]);
    }
}
