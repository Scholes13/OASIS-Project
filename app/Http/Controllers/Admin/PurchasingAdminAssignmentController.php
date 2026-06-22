<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchasingAdminAssignmentController extends Controller
{
    /**
     * List all user-BU assignments with purchasing admin toggle.
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
            ->orderByDesc('is_purchasing_admin')
            ->orderBy('business_unit_id');

        $assignments = $query->paginate(30)->withQueryString();

        $businessUnits = BusinessUnit::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        // Count current purchasing admins per BU
        $adminCounts = UserBusinessUnit::where('is_active', true)
            ->where('is_purchasing_admin', true)
            ->selectRaw('business_unit_id, COUNT(*) as count')
            ->groupBy('business_unit_id')
            ->pluck('count', 'business_unit_id');

        $reportAccessCounts = UserBusinessUnit::where('is_active', true)
            ->where('is_purchasing_report_access', true)
            ->selectRaw('business_unit_id, COUNT(*) as count')
            ->groupBy('business_unit_id')
            ->pluck('count', 'business_unit_id');

        return Inertia::render('Admin/PurchasingAdmins/Index', [
            'assignments' => $assignments,
            'businessUnits' => $businessUnits,
            'adminCounts' => $adminCounts,
            'reportAccessCounts' => $reportAccessCounts,
            'filters' => [
                'business_unit_id' => $buFilter,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Toggle is_purchasing_admin for a user-BU assignment.
     */
    public function toggle(Request $request, int $id)
    {
        $ubu = UserBusinessUnit::findOrFail($id);

        $newAdminState = ! $ubu->is_purchasing_admin;
        $updates = ['is_purchasing_admin' => $newAdminState];

        // Auto-revoke report access when admin is turned OFF
        if (! $newAdminState && $ubu->is_purchasing_report_access) {
            $updates['is_purchasing_report_access'] = false;
        }

        $ubu->update($updates);

        cache()->forget("bu_list:{$ubu->user_id}");

        $status = $newAdminState ? 'assigned as' : 'removed from';

        return redirect()->route('admin.purchasing-admins.index', request()->query())
            ->with('success', "{$ubu->user?->name} {$status} Purchasing Admin.");
    }

    /**
     * Toggle is_purchasing_report_access for a user-BU assignment.
     * Only works if is_purchasing_admin is already true.
     */
    public function toggleReportAccess(Request $request, int $id)
    {
        $ubu = UserBusinessUnit::findOrFail($id);

        // Cannot grant report access without admin access
        if (! $ubu->is_purchasing_admin && ! $ubu->is_purchasing_report_access) {
            return back()->with('error', 'User must be a Purchasing Admin first.');
        }

        $newState = ! $ubu->is_purchasing_report_access;
        $ubu->update(['is_purchasing_report_access' => $newState]);

        cache()->forget("bu_list:{$ubu->user_id}");

        $status = $newState ? 'granted' : 'revoked';

        return redirect()->route('admin.purchasing-admins.index', request()->query())
            ->with('success', "Purchasing Report access {$status} for {$ubu->user?->name}.");
    }
}
