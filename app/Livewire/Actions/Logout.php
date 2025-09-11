<?php

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class Logout
{
    /**
     * Log the current user out of the application.
     * Clear all sessions and ensure complete logout.
     */
    public function __invoke(): void
    {
        // Get user ID before logout for session cleanup
        $userId = Auth::id();
        
        // Logout from all guards
        Auth::guard('web')->logout();
        
        // Invalidate current session
        Session::invalidate();
        Session::regenerateToken();
        
        // Clear all session data completely
        Session::flush();
        
        // Clear any remember me tokens
        if ($userId) {
            DB::table('sessions')->where('user_id', $userId)->delete();
        }
        
        // Clear any cached user data
        if (function_exists('cache')) {
            cache()->forget("user.{$userId}");
        }
    }
}
