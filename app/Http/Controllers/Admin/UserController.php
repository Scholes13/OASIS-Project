<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BusinessUnit;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'businessUnits.businessUnit', 'departmentUsers.department']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->role($request->role);
        }

        // Filter by business unit
        if ($request->filled('business_unit')) {
            $query->whereHas('businessUnits', function ($q) use ($request) {
                $q->where('business_unit_id', $request->business_unit);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->latest()->paginate(15);

        // Data for filters
        $roles = Role::all();
        $businessUnits = BusinessUnit::where('is_active', true)->get();

        return view('admin.users.index', compact('users', 'roles', 'businessUnits'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $roles = Role::all();
        $businessUnits = BusinessUnit::where('is_active', true)->with('departments')->get();
        $departments = Department::where('is_active', true)->get();

        return view('admin.users.create', compact('roles', 'businessUnits', 'departments'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name',
            'business_units' => 'required|array|min:1',
            'business_units.*' => 'exists:business_units,id',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:departments,id',
            'primary_department_id' => 'nullable|exists:departments,id',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'employee_id' => 'nullable|string|max:50|unique:users',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'position' => $request->position,
            'employee_id' => $request->employee_id,
            'primary_department_id' => $request->primary_department_id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Assign roles
        $user->assignRole($request->roles);

        // Assign business units
        foreach ($request->business_units as $businessUnitId) {
            $user->businessUnits()->create([
                'business_unit_id' => $businessUnitId,
                'is_active' => true,
            ]);
        }

        // Assign departments
        if ($request->departments) {
            foreach ($request->departments as $departmentId) {
                $user->departmentUsers()->create([
                    'department_id' => $departmentId,
                    'is_active' => true,
                ]);
            }
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $user->load([
            'roles',
            'businessUnits.businessUnit',
            'departmentUsers.department',
            'purchaseRequests' => function ($query) {
                $query->latest()->limit(10);
            },
            'approvals' => function ($query) {
                $query->latest('responded_at')->limit(10);
            }
        ]);

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        $businessUnits = BusinessUnit::where('is_active', true)->with('departments')->get();
        $departments = Department::where('is_active', true)->get();

        $user->load(['roles', 'businessUnits', 'departmentUsers']);

        return view('admin.users.edit', compact('user', 'roles', 'businessUnits', 'departments'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name',
            'business_units' => 'required|array|min:1',
            'business_units.*' => 'exists:business_units,id',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:departments,id',
            'primary_department_id' => 'nullable|exists:departments,id',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'employee_id' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'is_active' => 'boolean',
        ]);

        $updateData = [
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'position' => $request->position,
            'employee_id' => $request->employee_id,
            'primary_department_id' => $request->primary_department_id,
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Update roles
        $user->syncRoles($request->roles);

        // Update business units
        $user->businessUnits()->delete();
        foreach ($request->business_units as $businessUnitId) {
            $user->businessUnits()->create([
                'business_unit_id' => $businessUnitId,
                'is_active' => true,
            ]);
        }

        // Update departments
        $user->departmentUsers()->delete();
        if ($request->departments) {
            foreach ($request->departments as $departmentId) {
                $user->departmentUsers()->create([
                    'department_id' => $departmentId,
                    'is_active' => true,
                ]);
            }
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Soft delete by deactivating instead of actual deletion
        $user->update(['is_active' => false]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deactivated successfully.');
    }

    /**
     * Activate/deactivate user
     */
    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "User {$status} successfully.");
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password reset successfully.');
    }

    /**
     * Get departments for a business unit (AJAX)
     */
    public function getDepartments(BusinessUnit $businessUnit)
    {
        $departments = $businessUnit->departments()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json($departments);
    }
}