<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivityAdminAccess
{
    /**
     * Ensure user is an Activity Admin for the current business unit,
     * or has top management (c_level/executive) access.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $currentBuId = (int) session('current_business_unit_id');

        if ($currentBuId <= 0 || ! in_array($currentBuId, $user->getAccessibleBusinessUnitIds(), true)) {
            abort(403, 'Invalid business unit context.');
        }

        // Top management users (c_level/executive) can access all BU admin dashboards
        if ($user->hasTopManagementAccess()) {
            return $next($request);
        }

        $isActivityAdmin = $user->isAdminInBuOrAncestor('is_activity_admin', $currentBuId);

        if (! $isActivityAdmin) {
            abort(403, 'Unauthorized. Activity Admin access required.');
        }

        return $next($request);
    }
}
