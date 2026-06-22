<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Business Unit API Controller
 *
 * Handles business unit switching via Inertia.
 */
class BusinessUnitController extends Controller
{
    /**
     * Switch the current business unit context.
     *
     * Uses hierarchical access check - if user has access to parent BU,
     * they also have access to all child BUs.
     *
     * Returns Inertia redirect back to refresh page with new BU context.
     */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'business_unit_id' => 'required|integer|exists:business_units,id',
        ]);

        $user = Auth::user();
        $businessUnitId = $request->input('business_unit_id');

        // Debug logging
        Log::info('BU Switch Request', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'requested_bu_id' => $businessUnitId,
        ]);

        if (! $user) {
            Log::warning('BU Switch: No authenticated user');

            return back()->withErrors(['message' => 'Not authenticated.']);
        }

        // Use hierarchical access check from User model
        $accessibleBuIds = $user->getAccessibleBusinessUnitIds();
        $hasAccess = in_array($businessUnitId, $accessibleBuIds);

        if (! $hasAccess) {
            return back()->withErrors(['message' => 'You do not have access to this business unit.']);
        }

        // Get business unit details
        $businessUnit = BusinessUnit::find($businessUnitId);

        if (! $businessUnit || ! $businessUnit->is_active) {
            return back()->withErrors(['message' => 'Business unit not found or inactive.']);
        }

        // Update session with full BU details (no session regeneration to preserve CSRF)
        session()->put([
            'current_business_unit_id' => $businessUnit->id,
            'current_business_unit_name' => $businessUnit->name,
            'current_business_unit_code' => $businessUnit->code,
            'current_business_unit_logo' => $businessUnit->logo,
        ]);

        // Re-resolve department for the new BU using shared helper
        $resolvedDeptId = $user->resolveDepartmentForBusinessUnit($businessUnit->id);
        session(['current_department_id' => $resolvedDeptId]);

        // Save last active BU for next login (only if changed to save DB query)
        if ($user->last_active_business_unit_id !== $businessUnit->id) {
            $user->update(['last_active_business_unit_id' => $businessUnit->id]);
        }

        Log::info('BU Switch Success', [
            'user_id' => $user->id,
            'new_bu_id' => $businessUnit->id,
            'new_bu_name' => $businessUnit->name,
        ]);

        // Redirect back - Inertia will refresh the page with new BU context
        return back();
    }

    /**
     * Update department context when business unit is switched.
     * Sets to primary department in the new business unit, or first available department.
     */
    protected function updateDepartmentContext($user, int $businessUnitId): void
    {
        // Find user's primary department in the new business unit
        $primaryAssignment = $user->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->where('department_id', $user->primary_department_id)
            ->with('department')
            ->first();

        if ($primaryAssignment && $primaryAssignment->department) {
            session()->put([
                'current_department_id' => $primaryAssignment->department->id,
                'current_department_name' => $primaryAssignment->department->name,
                'current_department_code' => $primaryAssignment->department->code,
            ]);

            Log::info('Department Context Updated', [
                'user_id' => $user->id,
                'business_unit_id' => $businessUnitId,
                'department_id' => $primaryAssignment->department->id,
                'department_name' => $primaryAssignment->department->name,
            ]);
        } else {
            // If primary department not in new BU, use first available department
            $firstAssignment = $user->activeBusinessUnits()
                ->where('business_unit_id', $businessUnitId)
                ->with('department')
                ->first();

            if ($firstAssignment && $firstAssignment->department) {
                session()->put([
                    'current_department_id' => $firstAssignment->department->id,
                    'current_department_name' => $firstAssignment->department->name,
                    'current_department_code' => $firstAssignment->department->code,
                ]);

                Log::info('Department Context Updated (Fallback)', [
                    'user_id' => $user->id,
                    'business_unit_id' => $businessUnitId,
                    'department_id' => $firstAssignment->department->id,
                    'department_name' => $firstAssignment->department->name,
                ]);
            } else {
                // No department assignment in this BU - clear department context
                session()->forget(['current_department_id', 'current_department_name', 'current_department_code']);

                Log::info('Department Context Cleared (No Assignment)', [
                    'user_id' => $user->id,
                    'business_unit_id' => $businessUnitId,
                ]);
            }
        }
    }
}
