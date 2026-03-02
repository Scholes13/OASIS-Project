<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Core\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Department API Controller
 *
 * Handles department switching within the current business unit via Inertia.
 */
class DepartmentController extends Controller
{
    /**
     * Switch the current department context within the active business unit.
     *
     * Validates that:
     * - Department exists and is active
     * - User has assignment to this department in current business unit
     *
     * Returns Inertia redirect back to refresh page with new department context.
     */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'department_id' => 'required|integer|exists:departments,id',
        ]);

        $user = Auth::user();
        $departmentId = $request->input('department_id');
        $currentBusinessUnitId = session('current_business_unit_id');

        // Debug logging
        Log::info('Department Switch Request', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'requested_department_id' => $departmentId,
            'current_business_unit_id' => $currentBusinessUnitId,
        ]);

        if (! $user) {
            Log::warning('Department Switch: No authenticated user');

            return back()->withErrors(['message' => 'Not authenticated.']);
        }

        if (! $currentBusinessUnitId) {
            Log::warning('Department Switch: No current business unit in session');

            return back()->withErrors(['message' => 'No active business unit selected.']);
        }

        // Get department details
        $department = Department::find($departmentId);

        if (! $department || ! $department->is_active) {
            return back()->withErrors(['message' => 'Department not found or inactive.']);
        }

        // Verify user has assignment to this department in current business unit
        $hasAssignment = $user->activeBusinessUnits()
            ->where('business_unit_id', $currentBusinessUnitId)
            ->where('department_id', $departmentId)
            ->exists();

        if (! $hasAssignment) {
            Log::warning('Department Switch: User does not have assignment', [
                'user_id' => $user->id,
                'department_id' => $departmentId,
                'business_unit_id' => $currentBusinessUnitId,
            ]);

            return back()->withErrors(['message' => 'You do not have access to this department in the current business unit.']);
        }

        // Update session with department details
        session()->put([
            'current_department_id' => $department->id,
            'current_department_name' => $department->name,
            'current_department_code' => $department->code,
        ]);

        Log::info('Department Switch Success', [
            'user_id' => $user->id,
            'new_department_id' => $department->id,
            'new_department_name' => $department->name,
            'business_unit_id' => $currentBusinessUnitId,
        ]);

        // Redirect back - Inertia will refresh the page with new department context
        return back();
    }
}
