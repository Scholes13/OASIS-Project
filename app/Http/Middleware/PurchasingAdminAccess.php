<?php

namespace App\Http\Middleware;

use App\Models\Core\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PurchasingAdminAccess
{
    /**
     * Handle an incoming request.
     *
     * Ensures the authenticated user has access to purchasing admin features.
     * Access is granted to:
     * - Super Admins
     * - Top management in parent business unit (Werkudara Group)
     * - Purchasing admins in purchasing departments for current business unit
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Check if user is authenticated
        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        // Ensure we have the correct User model instance
        if (! $user instanceof User) {
            abort(401, 'Invalid user type');
        }

        // Check access using the gate
        if (! Gate::allows('access-purchasing-admin')) {
            abort(403, 'You do not have permission to access purchasing admin features.');
        }

        return $next($request);
    }
}
