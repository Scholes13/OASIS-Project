<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BusinessUnit;
use App\Models\Department;
use App\Models\Position;
use App\Models\UserBusinessUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function __construct()
    {
        // Middleware admin.access sudah diterapkan di routes
        // Tidak perlu middleware tambahan di sini
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $query = User::with(['primaryDepartment', 'primaryPosition', 'supervisor', 'activeBusinessUnits.businessUnit'])
            ->orderBy('name');

        // Filter by business unit
        if ($request->filled('business_unit')) {
            $query->whereHas('activeBusinessUnits', function ($q) use ($request) {
                $q->where('business_unit_id', $request->business_unit);
            });
        }

        // Filter by department
        if ($request->filled('department')) {
            $query->where('primary_department_id', $request->department);
        }

        // Filter by role
        if ($request->filled('global_role')) {
            $query->where('global_role', $request->global_role);
        }

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15);

        $businessUnits = BusinessUnit::active()->orderBy('name')->get();
        $departments = Department::with('businessUnit')->orderBy('name')->get();

        return view('admin.users.index', compact('users', 'businessUnits', 'departments'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $businessUnits = BusinessUnit::active()->with('departments.positions')->orderBy('name')->get();
        $users = User::active()->orderBy('name')->get(); // For supervisor selection

        return view('admin.users.create', compact('businessUnits', 'users'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'global_role' => 'required|in:super_admin,user',
            'supervisor_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',

            // Business unit assignments
            'business_units' => 'required|array|min:1',
            'business_units.*.business_unit_id' => 'required|exists:business_units,id',
            'business_units.*.department_id' => 'required|exists:departments,id',
            'business_units.*.position_id' => 'required|exists:positions,id',
            'primary_business_unit' => 'required|integer|min:0',
        ]);

        // Get primary business unit from radio selection
        $primaryIndex = $validated['primary_business_unit'];
        if (!isset($validated['business_units'][$primaryIndex])) {
            return back()->withErrors(['primary_business_unit' => 'Invalid primary business unit selection.']);
        }

        $primaryBU = $validated['business_units'][$primaryIndex];

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'password' => Hash::make($validated['password']),
            'global_role' => $validated['global_role'],
            'supervisor_id' => $validated['supervisor_id'],
            'primary_department_id' => $primaryBU['department_id'],
            'primary_position_id' => $primaryBU['position_id'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Create business unit assignments
        foreach ($validated['business_units'] as $index => $buData) {
            UserBusinessUnit::create([
                'user_id' => $user->id,
                'business_unit_id' => $buData['business_unit_id'],
                'department_id' => $buData['department_id'],
                'position_id' => $buData['position_id'],
                'is_primary' => ($index == $primaryIndex),
                'is_active' => true,
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "User '{$user->name}' created successfully with " . count($validated['business_units']) . ' business unit assignment(s).');
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $user->load([
            'primaryDepartment.businessUnit',
            'primaryPosition',
            'supervisor',
            'subordinates',
            'activeBusinessUnits.businessUnit',
            'activeBusinessUnits.department',
            'activeBusinessUnits.position'
        ]);

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $user->load(['activeBusinessUnits']);
        $businessUnits = BusinessUnit::active()->with('departments.positions')->orderBy('name')->get();
        $users = User::where('id', '!=', $user->id)->active()->orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'businessUnits', 'users'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone_number' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'global_role' => 'required|in:super_admin,user',
            'supervisor_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',

            // Business unit assignments
            'business_units' => 'required|array|min:1',
            'business_units.*.business_unit_id' => 'required|exists:business_units,id',
            'business_units.*.department_id' => 'required|exists:departments,id',
            'business_units.*.position_id' => 'required|exists:positions,id',
            'business_units.*.is_primary' => 'boolean',
        ]);

        // Ensure only one primary business unit
        $primaryCount = collect($validated['business_units'])->where('is_primary', true)->count();
        if ($primaryCount !== 1) {
            return back()->withErrors(['business_units' => 'Exactly one business unit must be set as primary.']);
        }

        $primaryBU = collect($validated['business_units'])->firstWhere('is_primary', true);

        // Update user
        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'global_role' => $validated['global_role'],
            'supervisor_id' => $validated['supervisor_id'],
            'primary_department_id' => $primaryBU['department_id'],
            'primary_position_id' => $primaryBU['position_id'],
            'is_active' => $validated['is_active'] ?? true,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Update business unit assignments
        // Delete existing assignments
        $user->businessUnits()->delete();

        // Create new assignments
        foreach ($validated['business_units'] as $buData) {
            UserBusinessUnit::create([
                'user_id' => $user->id,
                'business_unit_id' => $buData['business_unit_id'],
                'department_id' => $buData['department_id'],
                'position_id' => $buData['position_id'],
                'is_primary' => $buData['is_primary'] ?? false,
                'is_active' => true,
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully with ' . count($validated['business_units']) . ' business unit assignment(s).');
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        // Prevent deleting super admin
        if ($user->isSuperAdmin()) {
            return back()->withErrors(['error' => 'Cannot delete Super Admin user.']);
        }

        // Soft delete by deactivating
        $user->update(['is_active' => false]);
        $user->businessUnits()->update(['is_active' => false]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User deactivated successfully.');
    }

    /**
     * Get departments by business unit (AJAX)
     */
    public function getDepartments(BusinessUnit $businessUnit)
    {
        $departments = $businessUnit->activeDepartments()->with('positions')->get();

        return response()->json($departments);
    }

    /**
     * Get positions by department (AJAX)
     */
    public function getPositions(Department $department)
    {
        $positions = $department->activePositions()->get();

        return response()->json($positions);
    }
}
