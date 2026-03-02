<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    /**
     * Show the login page.
     */
    public function show()
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => true,
        ]);
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        // Set default business unit context
        $this->setDefaultBusinessUnit($request);

        // Set default department context
        $this->setDefaultDepartment($request);

        // Flash login success with user name for welcome overlay
        $request->session()->flash('just_logged_in', Auth::user()->name);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Set the default business unit for the user session.
     * Priority: last_active_business_unit_id > primary_department's BU > first accessible BU
     */
    protected function setDefaultBusinessUnit(Request $request): void
    {
        $user = Auth::user();

        // Skip if session already has a BU set (shouldn't happen on fresh login, but safety check)
        if ($request->session()->has('current_business_unit_id')) {
            return;
        }

        $defaultBuId = null;

        // Priority 1: Last active business unit (if user has access)
        if ($user->last_active_business_unit_id) {
            $accessibleIds = $user->getAccessibleBusinessUnitIds();
            if (in_array($user->last_active_business_unit_id, $accessibleIds, true)) {
                $defaultBuId = $user->last_active_business_unit_id;
            }
        }

        // Priority 2: Primary department's business unit
        if (! $defaultBuId && $user->primary_department_id) {
            $primaryAssignment = $user->activeBusinessUnits()
                ->where('department_id', $user->primary_department_id)
                ->first();

            if ($primaryAssignment) {
                $defaultBuId = $primaryAssignment->business_unit_id;
            }
        }

        // Priority 3: First accessible business unit
        if (! $defaultBuId) {
            $firstAssignment = $user->activeBusinessUnits()->first();
            if ($firstAssignment) {
                $defaultBuId = $firstAssignment->business_unit_id;
            }
        }

        // Set session if we found a BU
        if ($defaultBuId) {
            $businessUnit = \App\Models\Core\BusinessUnit::find($defaultBuId);
            if ($businessUnit) {
                $request->session()->put([
                    'current_business_unit_id' => $businessUnit->id,
                    'current_business_unit_name' => $businessUnit->name,
                    'current_business_unit_code' => $businessUnit->code,
                ]);
            }
        }
    }

    /**
     * Set the default department for the user session.
     * Sets to primary department in the active business unit.
     */
    protected function setDefaultDepartment(Request $request): void
    {
        $user = Auth::user();

        // Skip if no business unit is set
        if (! $request->session()->has('current_business_unit_id')) {
            return;
        }

        $currentBusinessUnitId = $request->session()->get('current_business_unit_id');

        // Find user's primary department in the current business unit
        $primaryAssignment = $user->activeBusinessUnits()
            ->where('business_unit_id', $currentBusinessUnitId)
            ->where('department_id', $user->primary_department_id)
            ->with('department')
            ->first();

        if ($primaryAssignment && $primaryAssignment->department) {
            $request->session()->put([
                'current_department_id' => $primaryAssignment->department->id,
                'current_department_name' => $primaryAssignment->department->name,
                'current_department_code' => $primaryAssignment->department->code,
            ]);
        } else {
            // If primary department not in current BU, use first available department
            $firstAssignment = $user->activeBusinessUnits()
                ->where('business_unit_id', $currentBusinessUnitId)
                ->with('department')
                ->first();

            if ($firstAssignment && $firstAssignment->department) {
                $request->session()->put([
                    'current_department_id' => $firstAssignment->department->id,
                    'current_department_name' => $firstAssignment->department->name,
                    'current_department_code' => $firstAssignment->department->code,
                ]);
            }
        }
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        // Save last active BU before logout
        $user = Auth::user();
        if ($user && $request->session()->has('current_business_unit_id')) {
            $user->update([
                'last_active_business_unit_id' => $request->session()->get('current_business_unit_id'),
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
