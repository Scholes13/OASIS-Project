<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityAdminAssignmentController extends Controller
{
    /**
     * List all user-BU assignments with activity admin toggle.
     */
    public function index(Request $request): Response
    {
        $buFilter = $request->get('business_unit_id', '');
        $search = $request->get('search', '');

        $query = UserBusinessUnit::with(['user:id,name,email', 'businessUnit:id,name,code', 'department:id,name', 'position:id,name,access_level'])
            ->where('is_active', true)
            ->when($buFilter, fn ($q) => $q->where('business_unit_id', $buFilter))
            ->when($search, function ($q, $v) {
                $q->whereHas('user', fn ($q2) => $q2->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%"));
            })
            ->orderByDesc('is_activity_admin')
            ->orderBy('business_unit_id');

        $assignments = $query->paginate(30)->withQueryString();

        $businessUnits = BusinessUnit::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        // Count current activity admins per BU
        $adminCounts = UserBusinessUnit::where('is_active', true)
            ->where('is_activity_admin', true)
            ->selectRaw('business_unit_id, COUNT(*) as count')
            ->groupBy('business_unit_id')
            ->pluck('count', 'business_unit_id');

        return Inertia::render('Admin/ActivityAdmins/Index', [
            'assignments' => $assignments,
            'businessUnits' => $businessUnits,
            'adminCounts' => $adminCounts,
            'filters' => [
                'business_unit_id' => $buFilter,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Toggle is_activity_admin for a user-BU assignment.
     */
    public function toggle(Request $request, int $id)
    {
        $ubu = UserBusinessUnit::findOrFail($id);
        $ubu->update(['is_activity_admin' => ! $ubu->is_activity_admin]);

        // Clear navigation cache for this user
        cache()->forget("nav:{$ubu->user_id}:{$ubu->business_unit_id}");
        cache()->forget("bu_list:{$ubu->user_id}");

        $status = $ubu->is_activity_admin ? 'assigned as' : 'removed from';

        return back()->with('success', "{$ubu->user?->name} {$status} Activity Admin.");
    }
}
