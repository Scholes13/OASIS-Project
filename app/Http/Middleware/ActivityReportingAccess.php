<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivityReportingAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Super Admin always has access
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has activity reporting access
        // This could be based on role, permission, or position
        if ($user->can('view-activity-reports') || $user->can('access-activity-reporting')) {
            return $next($request);
        }

        // Activity admin with report access toggle
        $hasReportAccess = $user->activeBusinessUnits()
            ->where('is_activity_admin', true)
            ->where('is_activity_report_access', true)
            ->exists();

        if ($hasReportAccess) {
            return $next($request);
        }

        // Check if user has a management position
        $hasManagementAccess = $user->activeBusinessUnits()
            ->whereHas('position', function ($query) {
                $query->whereIn('access_level', [1, 2, 3]); // CEO, GM, Manager levels
            })
            ->exists();

        if ($hasManagementAccess) {
            return $next($request);
        }

        abort(403, 'Unauthorized access to activity reporting.');
    }
}
