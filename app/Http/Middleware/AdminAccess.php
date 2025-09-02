<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class AdminAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Check if user is authenticated
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        // Ensure we have the correct User model instance
        if (!$user instanceof User) {
            abort(401, 'Invalid user type');
        }

        // Check if user is super admin (using our custom method)
        if (!$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can access this area.');
        }

        return $next($request);
    }
}
