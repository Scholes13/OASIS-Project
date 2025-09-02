<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessUnitSelected
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Super admins can bypass business unit requirement
            if ($user->global_role === 'super_admin') {
                // Ensure super admin session context exists
                if (!session('current_business_unit_code')) {
                    session([
                        'current_business_unit_id' => null,
                        'current_business_unit_code' => 'WG',
                        'current_business_unit_name' => 'Werkudara Group',
                        'current_user_role' => 'super_admin',
                        'current_department_id' => null,
                    ]);
                }
                
                // Make current context available to views
                view()->share([
                    'currentBusinessUnitId' => session('current_business_unit_id'),
                    'currentBusinessUnitCode' => session('current_business_unit_code'),
                    'currentBusinessUnitName' => session('current_business_unit_name'),
                    'currentUserRole' => session('current_user_role'),
                    'currentDepartmentId' => session('current_department_id'),
                ]);
                
                return $next($request);
            }
            
            // Check if user has current business unit context from login
            $currentBusinessUnitId = session('current_business_unit_id');
            
            if (!$currentBusinessUnitId) {
                // Fallback: Set primary business unit if not set during login
                $primaryBu = $user->businessUnits()
                    ->with('businessUnit')
                    ->where('is_active', true)
                    ->orderBy('created_at', 'asc')
                    ->first();
                
                if ($primaryBu) {
                    $businessUnit = $primaryBu->businessUnit;
                    
                    session([
                        'current_business_unit_id' => $businessUnit->id,
                        'current_business_unit_code' => $businessUnit->code,
                        'current_business_unit_name' => $businessUnit->name,
                        'current_user_role' => $primaryBu->role,
                        'current_department_id' => $primaryBu->department_id,
                    ]);
                } else {
                    // User has no business unit access - show error
                    Auth::logout();
                    return redirect()->route('login')
                        ->with('error', 'You do not have access to any business unit. Please contact administrator.');
                }
            } else {
                // Validate that user still has access to current business unit
                $hasAccess = $user->hasAccessToBusinessUnit($currentBusinessUnitId);
                
                if (!$hasAccess) {
                    // Remove invalid business unit from session and logout
                    session()->flush();
                    Auth::logout();
                    
                    return redirect()->route('login')
                        ->with('error', 'You no longer have access to the selected business unit.');
                }
            }
            
            // Make current business unit data available to all views
            view()->share([
                'currentBusinessUnitId' => session('current_business_unit_id'),
                'currentBusinessUnitCode' => session('current_business_unit_code'),
                'currentBusinessUnitName' => session('current_business_unit_name'),
                'currentUserRole' => session('current_user_role'),
                'currentDepartmentId' => session('current_department_id'),
            ]);
        }
        
        return $next($request);
    }
}