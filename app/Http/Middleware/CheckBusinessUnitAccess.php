<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBusinessUnitAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Get business unit ID from route parameter or request
        $businessUnitId = $request->route('business_unit') 
            ?? $request->route('businessUnit')
            ?? $request->input('business_unit_id')
            ?? $request->input('businessUnitId');

        // If no business unit specified, allow access
        if (!$businessUnitId) {
            return $next($request);
        }

        // Check if user has access to the business unit
        if (!$user->hasAccessToBusinessUnit($businessUnitId)) {
            abort(403, 'You do not have access to this business unit.');
        }

        return $next($request);
    }
}