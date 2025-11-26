<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show admin dashboard
     */
    public function index()
    {
        $user = Auth::user();

        // Super Admin Dashboard
        if ($user->isSuperAdmin()) {
            return $this->superAdminDashboard();
        }

        // Regular User Dashboard
        return $this->userDashboard();
    }

    /**
     * Super Admin Dashboard
     */
    private function superAdminDashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'super_admins' => User::where('global_role', 'super_admin')->count(),
            'total_business_units' => BusinessUnit::count(),
            'active_business_units' => BusinessUnit::where('is_active', true)->count(),
            'total_departments' => Department::count(),
            'total_assignments' => UserBusinessUnit::where('is_active', true)->count(),
        ];

        // Recent users
        $recentUsers = User::with(['primaryDepartment.businessUnit', 'activeBusinessUnits.businessUnit'])
            ->latest()
            ->limit(5)
            ->get();

        // Business unit distribution
        $businessUnitStats = BusinessUnit::withCount(['userBusinessUnits as user_count' => function ($query) {
            $query->where('is_active', true);
        }])
            ->where('is_active', true)
            ->get();

        // User role distribution
        $roleStats = User::selectRaw('global_role, COUNT(*) as count')
            ->where('is_active', true)
            ->groupBy('global_role')
            ->get()
            ->pluck('count', 'global_role');

        return view('admin.dashboard', compact(
            'stats',
            'recentUsers',
            'businessUnitStats',
            'roleStats'
        ));
    }

    /**
     * Regular User Dashboard
     */
    private function userDashboard()
    {
        // Use Livewire component for dynamic dashboard
        return view('dashboard');
    }
}
