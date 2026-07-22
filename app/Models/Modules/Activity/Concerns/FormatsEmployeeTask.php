<?php

namespace App\Models\Modules\Activity\Concerns;

use App\Models\Core\User;
use App\Services\Modules\Activity\BackdatePermissionService;

trait FormatsEmployeeTask
{
    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            'planned' => 'gray',
            'in_progress' => 'blue',
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'planned' => 'Planned',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function getFormattedDuration(): string
    {
        if (! $this->duration_minutes) {
            return '-';
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    }

    public static function canBackdateTo(mixed $date, User $user): bool
    {
        $taskDate = $date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::parse($date);

        return app(BackdatePermissionService::class)->canCreateTaskWithDate($user, $taskDate);
    }
}
