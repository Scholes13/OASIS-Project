<?php

namespace App\Services\Modules\Activity;

use App\Models\Core\User;
use App\Models\Modules\Activity\BackdatePermission;
use App\Notifications\Activity\BackdateRequestSubmitted;
use App\Notifications\Activity\BackdateRequestApproved;
use App\Notifications\Activity\BackdateRequestRejected;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        // System automatically records the submission date (today)
        $requestedDate = now()->startOfDay();

        $permission = BackdatePermission::create([
            'user_id' => $user->id,
            'department_id' => $user->primary_department_id,
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
     */
    protected function notifyDepartmentHeads(BackdatePermission $permission): void
    {
        // Get department heads from the same department
        // Query through businessUnits (UserBusinessUnit) and check position access_level
        $departmentHeads = User::whereHas('businessUnits', function ($query) use ($permission) {
            $query->where('department_id', $permission->department_id)
                ->where('business_unit_id', $permission->business_unit_id)
                ->whereHas('position', function ($posQuery) {
                    // Department head level or higher (executive, general_manager, department_head)
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

            // Approve the request with granted_until = end of today
            $permission->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'granted_until' => now()->endOfDay(),
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
     * Get the allowed backdate range for a user
     * Returns ['from' => Carbon, 'to' => Carbon]
     */
    public function getAllowedDateRange(User $user): array
    {
        $activePermission = $this->checkUserPermission($user->id);

        if ($activePermission && $activePermission->isActive()) {
            // User has active permission - can backdate to requested_date
            return [
                'from' => $activePermission->requested_date,
                'to' => now(),
            ];
        }

        // Default: 1 day backdate (yesterday to today)
        return [
            'from' => now()->subDay()->startOfDay(),
            'to' => now(),
        ];
    }

    /**
     * Validate if a user can create a task with the given date
     */
    public function canCreateTaskWithDate(User $user, Carbon $taskDate): bool
    {
        $allowedRange = $this->getAllowedDateRange($user);
        
        return $taskDate->greaterThanOrEqualTo($allowedRange['from'])
            && $taskDate->lessThanOrEqualTo($allowedRange['to']);
    }
}
