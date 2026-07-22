<?php

namespace App\Services\Modules\Purchasing\Admin;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Notifications\Purchasing\Admin\TaskAssigned;
use App\Notifications\Purchasing\Admin\TaskAvailable;

class AdminTaskNotificationService
{
    public function notifyAssignedAdmin(AdminTask $task): void
    {
        if (! $task->assigned_admin_id) {
            return;
        }

        $admin = User::query()->find($task->assigned_admin_id);
        $admin?->notify(new TaskAssigned($task->fresh(['taskable'])));
    }

    public function broadcastAvailableTask(AdminTask $task): void
    {
        $recipients = User::query()
            ->whereHas('businessUnits', fn ($query) => $query
                ->where('business_unit_id', $task->business_unit_id)
                ->where('department_id', $task->department_id)
                ->where('is_purchasing_admin', true)
                ->where('is_purchasing_readonly', false)
                ->where('is_active', true))
            ->where('users.id', '!=', $task->assigned_admin_id ?? 0)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = new TaskAvailable($task->fresh(['taskable']));
        foreach ($recipients as $recipient) {
            $recipient->notify($notification);
        }
    }
}
