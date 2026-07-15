<?php

namespace App\Http\Controllers\Modules\Activity;

use App\Actions\Modules\Activity\BackdateApprovalAction;
use App\Http\Controllers\Controller;
use App\Services\Modules\Activity\BackdateApprovalQueryService;
use App\Services\Modules\Activity\BackdatePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ActivityBackdateController extends Controller
{
    public function __construct(
        protected BackdatePermissionService $backdateService,
        protected BackdateApprovalQueryService $backdateQueryService,
        protected BackdateApprovalAction $backdateApprovalAction,
    ) {}

    public function requests(): Response
    {
        abort_unless(config('features.backdate_approval'), 404);
        $user = Auth::user();
        $businessUnitId = (int) session('current_business_unit_id');

        return Inertia::render('Activity/Backdate/Requests', [
            'requests' => $this->backdateQueryService->paginateUserRequests($user, $businessUnitId),
            'activePermission' => $this->backdateService->checkUserPermission($user->id, $businessUnitId),
            'hasPendingRequest' => $this->backdateQueryService->userHasPendingRequest($user, $businessUnitId),
        ]);
    }

    public function approvals(Request $request): Response
    {
        abort_unless(config('features.backdate_approval'), 404);
        $user = Auth::user();
        $businessUnitId = (int) session('current_business_unit_id');
        $departmentId = $user->getCurrentDepartmentId();

        if (! in_array($user->getAccessLevel($businessUnitId), ['department_head', 'super_admin', 'executive', 'general_manager'], true)
            && ! $user->isSuperAdmin()) {
            abort(403, 'Only department heads can access this page');
        }

        return Inertia::render('Activity/Backdate/Approvals', [
            'requests' => $this->backdateQueryService->paginateApprovals($request, $user, $businessUnitId, $departmentId),
            'pendingCount' => $this->backdateQueryService->pendingCount($user, $businessUnitId, $departmentId),
            'statusFilter' => $request->get('status', 'pending'),
        ]);
    }

    public function approve(int $id): RedirectResponse
    {
        abort_unless(config('features.backdate_approval'), 404);
        $result = $this->backdateApprovalAction->approve($id, Auth::user());

        return $result['ok']
            ? back()->with('success', $result['message'])
            : back()->with('error', $result['error']);
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        abort_unless(config('features.backdate_approval'), 404);
        $result = $this->backdateApprovalAction->reject($request, $id, Auth::user());

        return $result['ok']
            ? back()->with('success', $result['message'])
            : back()->with('error', $result['error']);
    }

    public function submit(Request $request): RedirectResponse
    {
        abort_unless(config('features.backdate_approval'), 404);
        $result = $this->backdateApprovalAction->submit($request, Auth::user());

        return $result['ok']
            ? back()->with('success', $result['message'])
            : back()->withErrors($result['errors']);
    }
}
