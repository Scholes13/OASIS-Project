<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ITSupportAccess
{
    /**
     * Ensure user is an IT Support Admin for the current business unit,
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

        // Top management users (c_level/executive) can access all BU admin dashboards
        if ($user->hasTopManagementAccess()) {
            return $next($request);
        }

        $currentBuId = session('current_business_unit_id');

        if (! $currentBuId) {
            abort(403, 'No business unit selected.');
        }

        $isITSupportAdmin = $user->isAdminInBuOrAncestor('is_it_support_admin', $currentBuId);

        if (! $isITSupportAdmin) {
            abort(403, 'Unauthorized. IT Support Admin access required.');
        }

        return $next($request);
    }
}
