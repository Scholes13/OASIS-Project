<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiBusinessUnitContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if business unit ID is provided in header
        $businessUnitId = $request->header('X-Business-Unit-ID');
        
        if (!$businessUnitId) {
            return response()->json([
                'success' => false,
                'message' => 'Business Unit ID is required in X-Business-Unit-ID header'
            ], 400);
        }
        
        // Validate business unit exists and user has access
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }
        
        // Check if user has access to the business unit
        $hasAccess = $user->businessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->exists();
            
        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to specified business unit'
            ], 403);
        }
        
        // Validate business unit is active
        $businessUnit = \App\Models\BusinessUnit::find($businessUnitId);
        
        if (!$businessUnit || !$businessUnit->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive business unit'
            ], 400);
        }
        
        // Add business unit context to request
        $request->merge(['api_business_unit_id' => $businessUnitId]);
        $request->attributes->set('business_unit', $businessUnit);
        
        return $next($request);
    }
}