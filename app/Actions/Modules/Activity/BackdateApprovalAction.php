<?php

namespace App\Actions\Modules\Activity;

use App\Models\Core\User;
use App\Models\Modules\Activity\BackdatePermission;
use App\Services\Modules\Activity\BackdatePermissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Approve / reject / submit handlers for backdate permission requests.
 *
 * Lifted verbatim from ActivityInertiaController write methods to preserve
 * validation rules, dept-scope guard, and exception messages exactly.
 */
class BackdateApprovalAction
{
    public function __construct(
        protected BackdatePermissionService $backdateService
    ) {}

    /**
     * @return array{ok: true, message: string}|array{ok: false, error: string}
     */
    public function approve(int $id, User $user): array
    {
        try {
            $request = BackdatePermission::findOrFail($id);

            if ($request->department_id !== $user->getCurrentDepartmentId() && ! $user->isSuperAdmin()) {
                throw new \Exception('You can only approve requests from your department');
            }

            $this->backdateService->approveRequest($request, $user);

            return ['ok' => true, 'message' => 'Backdate request approved successfully'];
        } catch (\Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{ok: true, message: string}|array{ok: false, error: string}
     *
     * @throws ValidationException
     */
    public function reject(Request $request, int $id, User $user): array
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10|max:500',
        ], [
            'rejection_reason.required' => 'Please provide a reason for rejection',
            'rejection_reason.min' => 'Rejection reason must be at least 10 characters',
            'rejection_reason.max' => 'Rejection reason cannot exceed 500 characters',
        ]);

        try {
            $backdateRequest = BackdatePermission::findOrFail($id);

            if ($backdateRequest->department_id !== $user->getCurrentDepartmentId() && ! $user->isSuperAdmin()) {
                throw new \Exception('You can only reject requests from your department');
            }

            $this->backdateService->rejectRequest($backdateRequest, $user, $validated['rejection_reason']);

            return ['ok' => true, 'message' => 'Backdate request rejected'];
        } catch (\Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{ok: true, message: string}|array{ok: false, errors: array<string, string>}
     *
     * @throws ValidationException
     */
    public function submit(Request $request, User $user): array
    {
        $validated = $request->validate([
            'requested_date' => 'required|date|before:today',
            'reason' => 'required|string|min:10|max:500',
        ], [
            'requested_date.required' => 'Please select the date you need to backdate to',
            'requested_date.before' => 'Requested date must be before today',
            'reason.required' => 'Please provide a reason for backdate access',
            'reason.min' => 'Reason must be at least 10 characters',
            'reason.max' => 'Reason cannot exceed 500 characters',
        ]);

        try {
            $this->backdateService->requestPermission([
                'requested_date' => $validated['requested_date'],
                'reason' => $validated['reason'],
            ], $user);

            return [
                'ok' => true,
                'message' => 'Backdate request submitted successfully. Your department head will review it.',
            ];
        } catch (\Exception $e) {
            return ['ok' => false, 'errors' => ['reason' => $e->getMessage()]];
        }
    }
}
