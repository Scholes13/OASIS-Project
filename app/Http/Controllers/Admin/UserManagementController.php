<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

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

        // Transform users to include primary_business_unit
        $transformedUsers = collect($users->items())->map(function ($user) {
            $primaryBU = $user->activeBusinessUnits->firstWhere('is_primary', true);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'global_role' => $user->global_role,
                'is_active' => $user->is_active,
                'is_super_admin' => $user->isSuperAdmin(),
                'primary_business_unit' => $primaryBU ? [
                    'id' => $primaryBU->businessUnit->id,
                    'name' => $primaryBU->businessUnit->name,
                    'code' => $primaryBU->businessUnit->code,
                ] : null,
                'business_units' => $user->activeBusinessUnits->map(fn ($ubu) => [
                    'id' => $ubu->businessUnit->id,
                    'name' => $ubu->businessUnit->name,
                    'code' => $ubu->businessUnit->code,
                ]),
            ];
        });

        return Inertia::render('Admin/Users/Index', [
            'users' => [
                'data' => $transformedUsers,
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ],
            ],
            'filters' => [
                'businessUnits' => $businessUnits->map(fn ($bu) => [
                    'value' => $bu->id,
                    'label' => $bu->name,
                ]),
                'departments' => $departments->map(fn ($d) => [
                    'value' => $d->id,
                    'label' => $d->name,
                ]),
                'roles' => [
                    ['value' => 'super_admin', 'label' => 'Super Admin'],
                    ['value' => 'user', 'label' => 'User'],
                ],
            ],
            'queryParams' => [
                'search' => $request->input('search'),
                'business_unit' => $request->input('business_unit'),
                'department' => $request->input('department'),
                'global_role' => $request->input('global_role'),
                'page' => $request->input('page', 1),
            ],
        ]);
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $businessUnits = BusinessUnit::active()->with('departments.positions')->orderBy('name')->get();
        $users = User::active()->orderBy('name')->get(); // For supervisor selection

        return Inertia::render('Admin/Users/Create', [
            'businessUnits' => $businessUnits,
            'users' => $users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ]),
        ]);
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        // Log raw input untuk debugging
        \Log::info('User creation attempt - raw input', [
            'global_role_input' => $request->input('global_role'),
            'form_data' => $request->except(['password', 'password_confirmation']),
        ]);

        try {
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

            \Log::info('User creation validation passed', [
                'validated_global_role' => $validated['global_role'],
                'type' => gettype($validated['global_role']),
                'length' => strlen($validated['global_role']),
            ]);

            // Get primary business unit from radio selection
            $primaryIndex = $validated['primary_business_unit'];
            if (! isset($validated['business_units'][$primaryIndex])) {
                return back()->withErrors(['primary_business_unit' => 'Invalid primary business unit selection.'])
                    ->withInput();
            }

            $primaryBU = $validated['business_units'][$primaryIndex];

            \DB::beginTransaction();

            // Create user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'] ?? null,
                'password' => Hash::make($validated['password']),
                'global_role' => $validated['global_role'],
                'supervisor_id' => $validated['supervisor_id'] ?? null,
                'primary_department_id' => $primaryBU['department_id'],
                'primary_position_id' => $primaryBU['position_id'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            \Log::info('User created successfully', ['user_id' => $user->id, 'user_name' => $user->name]);

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

            \DB::commit();

            return redirect()->route('admin.users.index')
                ->with('success', "User '{$user->name}' created successfully with ".count($validated['business_units']).' business unit assignment(s).');

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('User creation validation failed', [
                'errors' => $e->errors(),
                'input' => $request->except(['password', 'password_confirmation']),
            ]);

            // Re-throw validation exception untuk ditampilkan ke user
            throw $e;
        } catch (\Exception $e) {
            \DB::rollBack();

            \Log::error('User creation failed with exception', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'input_data' => $request->except(['password', 'password_confirmation']),
            ]);

            // Handle specific constraint violation
            if (str_contains($e->getMessage(), 'CHECK constraint failed: global_role')) {
                $errorMessage = 'Invalid role value. Please select either Super Admin or User from the dropdown.';
                \Log::error('CHECK constraint violation detected', [
                    'selected_role' => $request->input('global_role'),
                    'available_options' => ['super_admin', 'user'],
                ]);
            } else {
                $errorMessage = 'Failed to create user: '.$e->getMessage();
            }

            return back()
                ->withErrors(['error' => $errorMessage])
                ->withInput()
                ->with('error', $errorMessage);
        }
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
            'activeBusinessUnits.position',
        ]);

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'global_role' => $user->global_role,
                'is_active' => $user->is_active,
                'is_super_admin' => $user->isSuperAdmin(),
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
                'supervisor' => $user->supervisor ? [
                    'id' => $user->supervisor->id,
                    'name' => $user->supervisor->name,
                    'email' => $user->supervisor->email,
                ] : null,
                'supervisor_id' => $user->supervisor_id,
                'business_units' => $user->activeBusinessUnits->map(fn ($ubu) => [
                    'business_unit' => [
                        'id' => $ubu->businessUnit->id,
                        'name' => $ubu->businessUnit->name,
                        'code' => $ubu->businessUnit->code,
                    ],
                    'department' => [
                        'id' => $ubu->department->id,
                        'name' => $ubu->department->name,
                    ],
                    'position' => [
                        'id' => $ubu->position->id,
                        'name' => $ubu->position->name,
                    ],
                    'is_primary' => $ubu->is_primary,
                ]),
                'subordinates' => $user->subordinates->map(fn ($sub) => [
                    'id' => $sub->id,
                    'name' => $sub->name,
                    'email' => $sub->email,
                    'is_active' => $sub->is_active,
                ]),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $user->load(['activeBusinessUnits.businessUnit', 'activeBusinessUnits.department', 'activeBusinessUnits.position']);
        $businessUnits = BusinessUnit::active()->with('departments.positions')->orderBy('name')->get();
        $users = User::where('id', '!=', $user->id)->active()->orderBy('name')->get();

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'global_role' => $user->global_role,
                'supervisor_id' => $user->supervisor_id,
                'is_active' => $user->is_active,
                'is_super_admin' => $user->isSuperAdmin(),
                'business_units' => $user->activeBusinessUnits->map(fn ($ubu) => [
                    'business_unit' => [
                        'id' => $ubu->businessUnit->id,
                        'name' => $ubu->businessUnit->name,
                        'code' => $ubu->businessUnit->code,
                    ],
                    'department' => [
                        'id' => $ubu->department->id,
                        'name' => $ubu->department->name,
                    ],
                    'position' => [
                        'id' => $ubu->position->id,
                        'name' => $ubu->position->name,
                    ],
                    'is_primary' => $ubu->is_primary,
                ]),
            ],
            'businessUnits' => $businessUnits,
            'users' => $users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ]),
        ]);
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
            'primary_business_unit' => 'required|integer|min:0',
        ]);

        // Get primary business unit from radio selection
        $primaryIndex = $validated['primary_business_unit'];
        if (! isset($validated['business_units'][$primaryIndex])) {
            return back()->withErrors(['primary_business_unit' => 'Invalid primary business unit selection.']);
        }

        $primaryBU = $validated['business_units'][$primaryIndex];

        // Update user
        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'global_role' => $validated['global_role'],
            'supervisor_id' => $validated['supervisor_id'] ?? null,
            'primary_department_id' => $primaryBU['department_id'],
            'primary_position_id' => $primaryBU['position_id'],
            'is_active' => $validated['is_active'] ?? true,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Update business unit assignments
        // Delete existing assignments
        $user->businessUnits()->delete();

        // Create new assignments
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
            ->with('success', 'User updated successfully with '.count($validated['business_units']).' business unit assignment(s).');
    }

    /**
     * Remove the specified user (deactivate)
     */
    public function destroy(User $user)
    {
        // Prevent deleting super admin
        if ($user->isSuperAdmin()) {
            return back()->withErrors(['error' => 'Cannot deactivate Super Admin user.']);
        }

        // Soft delete by deactivating
        $user->update(['is_active' => false]);
        $user->businessUnits()->update(['is_active' => false]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User deactivated successfully.');
    }

    /**
     * Permanently delete the specified user
     *
     * This method preserves user names in related records before deletion.
     * The migration adds _name columns and SET NULL on delete for FKs.
     */
    public function forceDelete(User $user)
    {
        // Prevent deleting super admin
        if ($user->isSuperAdmin()) {
            return back()->withErrors(['error' => 'Cannot delete Super Admin user.']);
        }

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Cannot delete your own account.']);
        }

        \DB::beginTransaction();

        try {
            $userName = $user->name;

            // Save user name to related records before deletion
            // (The FK SET NULL will clear the user_id, but name is preserved)

            // PR Approvals
            \DB::table('pr_approvals')
                ->where('approver_id', $user->id)
                ->whereNull('approver_name')
                ->update(['approver_name' => $userName]);

            // Stock Approvals
            \DB::table('stock_approvals')
                ->where('approver_id', $user->id)
                ->whereNull('approver_name')
                ->update(['approver_name' => $userName]);

            // Purchase Requests
            \DB::table('purchase_requests')
                ->where('user_id', $user->id)
                ->whereNull('requester_name')
                ->update(['requester_name' => $userName]);

            \DB::table('purchase_requests')
                ->where('last_modified_by', $user->id)
                ->whereNull('last_modified_by_name')
                ->update(['last_modified_by_name' => $userName]);

            \DB::table('purchase_requests')
                ->where('offline_approved_by', $user->id)
                ->whereNull('offline_approved_by_name')
                ->update(['offline_approved_by_name' => $userName]);

            // Stock Requests
            \DB::table('stock_requests')
                ->where('user_id', $user->id)
                ->whereNull('requester_name')
                ->update(['requester_name' => $userName]);

            \DB::table('stock_requests')
                ->where('last_modified_by', $user->id)
                ->whereNull('last_modified_by_name')
                ->update(['last_modified_by_name' => $userName]);

            \DB::table('stock_requests')
                ->where('offline_approved_by', $user->id)
                ->whereNull('offline_approved_by_name')
                ->update(['offline_approved_by_name' => $userName]);

            // Employee Tasks
            \DB::table('employee_tasks')
                ->where('created_by', $user->id)
                ->whereNull('created_by_name')
                ->update(['created_by_name' => $userName]);

            \DB::table('employee_tasks')
                ->where('completed_by', $user->id)
                ->whereNull('completed_by_name')
                ->update(['completed_by_name' => $userName]);

            // Task Participants
            \DB::table('task_participants')
                ->where('user_id', $user->id)
                ->whereNull('participant_name')
                ->update(['participant_name' => $userName]);

            // Backdate Permissions
            \DB::table('backdate_permissions')
                ->where('user_id', $user->id)
                ->whereNull('user_name')
                ->update(['user_name' => $userName]);

            \DB::table('backdate_permissions')
                ->where('approved_by', $user->id)
                ->whereNull('approved_by_name')
                ->update(['approved_by_name' => $userName]);

            \DB::table('backdate_permissions')
                ->where('rejected_by', $user->id)
                ->whereNull('rejected_by_name')
                ->update(['rejected_by_name' => $userName]);

            // Delete user's business unit assignments
            $user->businessUnits()->delete();

            // Detach roles and permissions (Spatie)
            $user->roles()->detach();
            $user->permissions()->detach();

            // Delete activity log entries for this user
            \DB::table('activities')->where('user_id', $user->id)->delete();

            // Hard delete user (FK SET NULL will handle the rest)
            $user->delete();

            \DB::commit();

            return redirect()->route('admin.users.index')
                ->with('success', "User '{$userName}' permanently deleted.");

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Failed to delete user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Failed to delete user: '.$e->getMessage()]);
        }
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
