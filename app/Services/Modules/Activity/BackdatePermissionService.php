<?php

namespace App\Services\Modules\Activity;

use App\Models\Core\User;
use App\Models\Modules\Activity\BackdatePermission;
use App\Notifications\Activity\BackdateRequestApproved;
use App\Notifications\Activity\BackdateRequestRejected;
use App\Notifications\Activity\BackdateRequestSubmitted;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BackdatePermissionService
{
    /**
     * Request backdate permission for a user
     */
    public function requestPermission(array $data, User $user): BackdatePermission
    {
        // Check if user already has a pending request
        $existingPending = BackdatePermission::forUser($user->id)
            ->pending()
            ->exists();

        if ($existingPending) {
            throw new \Exception('You already have a pending backdate request');
        }

        // Use the requested_date from user input, or default to 7 days ago
        $requestedDate = isset($data['requested_date'])
            ? Carbon::parse($data['requested_date'])->startOfDay()
            : now()->subDays(7)->startOfDay();

        // Validate that requested_date is in the past
        if ($requestedDate->isAfter(now()->subDay()->startOfDay())) {
            throw new \Exception('Requested date must be at least 2 days ago (yesterday is already allowed by default)');
        }

        $permission = BackdatePermission::create([
            'user_id' => $user->id,
            'department_id' => $user->getCurrentDepartmentId(),
            'business_unit_id' => session('current_business_unit_id'),
            'requested_date' => $requestedDate,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        // Notify department heads
        $this->notifyDepartmentHeads($permission);

        return $permission;
    }

    /**
     * Notify department heads about new backdate request
     * If requester IS a department head, notify activity admins instead.
     */
    protected function notifyDepartmentHeads(BackdatePermission $permission): void
    {
        $requester = User::find($permission->user_id);

        // Check if requester is a HOD/team_leader level
        $isHod = $requester?->businessUnits()
            ->where('business_unit_id', $permission->business_unit_id)
            ->whereHas('position', fn ($q) => $q->whereIn('access_level', ['department_head', 'team_leader']))
            ->exists();

        if ($isHod) {
            // HOD requesting → notify activity admins in same BU
            $activityAdmins = User::whereHas('businessUnits', function ($query) use ($permission) {
                $query->where('business_unit_id', $permission->business_unit_id)
                    ->where('is_activity_admin', true)
                    ->where('is_active', true);
            })->get();

            foreach ($activityAdmins as $admin) {
                $admin->notify(new BackdateRequestSubmitted($permission));
            }

            return;
        }

        // Regular staff → notify department heads (existing behavior)
        $departmentHeads = User::whereHas('businessUnits', function ($query) use ($permission) {
            $query->where('department_id', $permission->department_id)
                ->where('business_unit_id', $permission->business_unit_id)
                ->whereHas('position', function ($posQuery) {
                    $posQuery->whereIn('access_level', ['executive', 'general_manager', 'department_head']);
                });
        })
            ->get();

        foreach ($departmentHeads as $head) {
            $head->notify(new BackdateRequestSubmitted($permission));
        }
    }

    /**
     * Approve a backdate permission request
     */
    public function approveRequest(BackdatePermission $permission, User $approver): void
    {
        if ($permission->status !== 'pending') {
            throw new \Exception('Only pending requests can be approved');
        }

        DB::transaction(function () use ($permission, $approver) {
            // Expire any previous active permissions for the same user
            BackdatePermission::forUser($permission->user_id)
                ->active()
                ->update(['status' => 'expired']);

            // Approve the request.  granted_until extends a configurable
            // number of days past approval so an approval close to midnight
            // does not silently expire by the time the user opens the form.
            $grantDays = max(1, (int) config('features.backdate_grant_days', 7));

            $permission->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'granted_until' => now()->addDays($grantDays)->endOfDay(),
            ]);

            // Notify the requester
            $permission->requester->notify(new BackdateRequestApproved($permission));
        });
    }

    /**
     * Reject a backdate permission request
     */
    public function rejectRequest(BackdatePermission $permission, User $rejector, string $reason): void
    {
        if ($permission->status !== 'pending') {
            throw new \Exception('Only pending requests can be rejected');
        }

        $permission->update([
            'status' => 'rejected',
            'rejected_by' => $rejector->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // Notify the requester
        $permission->requester->notify(new BackdateRequestRejected($permission));
    }

    /**
     * Check if a user has active backdate permission
     */
    public function checkUserPermission(int $userId): ?BackdatePermission
    {
        return BackdatePermission::forUser($userId)
            ->active()
            ->first();
    }

    /**
     * Expire old backdate permissions
     */
    public function expireOldPermissions(): int
    {
        return BackdatePermission::where('status', 'approved')
            ->where('granted_until', '<', now())
            ->update(['status' => 'expired']);
    }

    /**
     * Check if backdate approval feature is enabled
     */
    public function isBackdateApprovalEnabled(): bool
    {
        return config('features.backdate_approval', false);
    }

    /**
     * Get the allowed backdate range for a user
     * Returns ['from' => Carbon, 'to' => Carbon]
     */
    public function getAllowedDateRange(User $user): array
    {
        // When backdate approval feature is disabled, allow unrestricted date range
        if (! $this->isBackdateApprovalEnabled()) {
            return [
                'from' => now()->subYears(5)->startOfDay(),
                'to' => now()->addYear(),
            ];
        }

        $activePermission = $this->checkUserPermission($user->id);

        if ($activePermission && $activePermission->isActive()) {
            // User has active permission - can backdate to requested_date
            // Future dates are allowed (1 year ahead)
            return [
                'from' => $activePermission->requested_date,
                'to' => now()->addYear(),
            ];
        }

        // Default: 1 day backdate (yesterday to today)
        // Future dates are allowed (1 year ahead)
        return [
            'from' => now()->subDay()->startOfDay(),
            'to' => now()->addYear(),
        ];
    }

    /**
     * Validate if a user can create a task with the given date
     */
    public function canCreateTaskWithDate(User $user, Carbon $taskDate): bool
    {
        // When backdate approval feature is disabled, allow any date
        if (! $this->isBackdateApprovalEnabled()) {
            return true;
        }

        $allowedRange = $this->getAllowedDateRange($user);

        return $taskDate->greaterThanOrEqualTo($allowedRange['from'])
            && $taskDate->lessThanOrEqualTo($allowedRange['to']);
    }
}
