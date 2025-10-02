<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureLivewireAuthPersistence
{
    /**
     * Handle an incoming request.
     *
     * Ensures authentication state is properly persisted for Livewire components
     * in hosting environments where session handling may be inconsistent.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force auth check and session regeneration for Livewire
        if (Auth::check()) {
            // Ensure user is fully loaded
            $user = Auth::user();

            // Store in session for Livewire access
            session([
                'auth.user_id' => $user->id,
                'auth.user_name' => $user->name,
                'auth.user_email' => $user->email,
            ]);

            // Ensure department data is in session
            if (! session('current_department_id') && $user->primary_department_id) {
                session([
                    'current_department_id' => $user->primary_department_id,
                ]);

                // Load business unit if department is available
                if ($user->primaryDepartment) {
                    session([
                        'current_business_unit_id' => $user->primaryDepartment->business_unit_id,
                    ]);
                }
            }
        }

        return $next($request);
    }
}
