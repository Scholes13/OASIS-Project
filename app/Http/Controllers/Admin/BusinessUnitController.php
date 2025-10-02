<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BusinessUnitController extends Controller
{
    /**
     * Display a listing of business units
     */
    public function index(Request $request)
    {
        $query = BusinessUnit::withCount(['departments', 'users', 'purchaseRequests']);

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

        return view('admin.business-units.index', compact('businessUnits'));
    }

    /**
     * Show the form for creating a new business unit
     */
    public function create()
    {
        $parentBusinessUnits = BusinessUnit::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get users who can be General Managers
        $managers = \App\Models\User::where('is_active', true)
            ->whereIn('global_role', ['super_admin', 'admin', 'manager'])
            ->orderBy('name')
            ->get();

        return view('admin.business-units.create', compact('parentBusinessUnits', 'managers'));
    }

    /**
     * Store a newly created business unit
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:business_units,code',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'parent_id' => 'nullable|exists:business_units,id',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        // Create business unit only
        $businessUnit = BusinessUnit::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'parent_id' => $request->parent_id,
            'manager_id' => $request->manager_id,
            'numbering_config' => [],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.business-units.index')
            ->with('success_create_unit', [
                'title' => 'Success Create Business Unit',
                'name' => $businessUnit->name,
            ]);
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
            'departments.users',
            'users.user',
            'purchaseRequests' => function ($query) {
                $query->latest()->limit(10);
            },
        ]);

        // Statistics
        $stats = [
            'total_departments' => $businessUnit->departments()->count(),
            'active_departments' => $businessUnit->departments()->where('is_active', true)->count(),
            'total_users' => $businessUnit->users()->count(),
            'active_users' => $businessUnit->users()->where('is_active', true)->count(),
            'total_prs' => $businessUnit->purchaseRequests()->count(),
            'pending_prs' => $businessUnit->purchaseRequests()->where('status', 'submitted')->count(),
        ];

        return view('admin.business-units.show', compact('businessUnit', 'stats'));
    }

    /**
     * Show the form for editing the specified business unit
     */
    public function edit(BusinessUnit $businessUnit)
    {
        $parentBusinessUnits = BusinessUnit::where('id', '!=', $businessUnit->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $managers = \App\Models\User::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.business-units.edit', compact('businessUnit', 'parentBusinessUnits', 'managers'));
    }

    /**
     * Update the specified business unit
     */
    public function update(Request $request, BusinessUnit $businessUnit)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:10', Rule::unique('business_units')->ignore($businessUnit->id)],
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'parent_id' => 'nullable|exists:business_units,id',
            'manager_id' => 'nullable|exists:users,id',
            'config' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        // Prevent setting parent to itself or creating circular reference
        if ($request->parent_id == $businessUnit->id) {
            return back()->withErrors(['parent_id' => 'Business unit cannot be its own parent.']);
        }

        $businessUnit->update($validatedData);

        return redirect()
            ->route('admin.business-units.index')
            ->with('success_update_unit', [
                'title' => 'Success Update Business Unit',
                'name' => $businessUnit->name,
            ]);
    }

    /**
     * Remove the specified business unit
     */
    public function destroy(BusinessUnit $businessUnit)
    {
        // Prevent deletion of Werkudara Group (parent company)
        if ($businessUnit->code === 'WG') {
            return back()->with('error', 'Cannot delete the parent company (Werkudara Group).');
        }

        // Check if business unit has child business units
        if ($businessUnit->children()->exists()) {
            return back()->with('error', 'Cannot delete business unit that has child business units. Please reassign or delete child units first.');
        }

        // Check if business unit has active users
        $activeUsers = $businessUnit->users()->count();

        if ($activeUsers > 0) {
            return back()->with('error', "Cannot delete business unit that has {$activeUsers} users. Please reassign users first.");
        }

        DB::transaction(function () use ($businessUnit) {
            // Delete all positions in departments
            foreach ($businessUnit->departments as $department) {
                $department->positions()->delete();
            }

            // Delete all departments
            $businessUnit->departments()->delete();

            // Delete user assignments (inactive users)
            $businessUnit->users()->delete();

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
            ->with('success_delete_unit', [
                'title' => 'Success Delete Business Unit',
                'name' => $businessUnitName,
            ]);
    }

    /**
     * Toggle business unit status
     */
    public function toggleStatus(BusinessUnit $businessUnit)
    {
        $businessUnit->update(['is_active' => ! $businessUnit->is_active]);

        $status = $businessUnit->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Business unit {$status} successfully.");
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
