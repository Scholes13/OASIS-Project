<?php

namespace App\Services\Modules\Purchasing\Admin;

use App\Models\Modules\Purchasing\Admin\AdminTask;
use App\Models\Modules\Purchasing\Admin\SlaSettings;
use Illuminate\Support\Collection;

class SlaMonitoringService
{
    /**
     * Get SLA settings for a business unit (falls back to global if not found)
     */
    public function getSlaSettings(?int $businessUnitId = null): ?SlaSettings
    {
        // Try to get business unit specific settings first
        if ($businessUnitId) {
            $settings = SlaSettings::forBusinessUnit($businessUnitId)->first();
            if ($settings) {
                return $settings;
            }
        }

        // Fall back to global settings
        return SlaSettings::global()->first();
    }

    /**
     * Check if a task has exceeded follow-up SLA
     */
    public function hasExceededFollowupSla(AdminTask $task): bool
    {
        $settings = $this->getSlaSettings($task->business_unit_id);

        if (! $settings || $task->status !== 'pending_followup') {
            return false;
        }

        $slaDeadline = $task->entered_at->copy()->addHours($settings->followup_sla_hours);

        return now()->isAfter($slaDeadline);
    }

    /**
     * Check if a task has exceeded completion SLA
     */
    public function hasExceededCompletionSla(AdminTask $task): bool
    {
        $settings = $this->getSlaSettings($task->business_unit_id);

        if (! $settings || $task->status !== 'in_progress' || ! $task->started_at) {
            return false;
        }

        $slaDeadline = $task->started_at->copy()->addHours($settings->completion_sla_hours);

        return now()->isAfter($slaDeadline);
    }

    /**
     * Get all tasks that have exceeded follow-up SLA
     */
    public function getTasksExceedingFollowupSla(?int $businessUnitId = null): Collection
    {
        $settings = $this->getSlaSettings($businessUnitId);

        if (! $settings) {
            return collect();
        }

        $slaDeadline = now()->subHours($settings->followup_sla_hours);

        $query = AdminTask::where('status', 'pending_followup')
            ->where('entered_at', '<=', $slaDeadline)
            ->with(['assignedAdmin', 'department', 'businessUnit', 'taskable']);

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        return $query->get();
    }

    /**
     * Get all tasks that have exceeded completion SLA
     */
    public function getTasksExceedingCompletionSla(?int $businessUnitId = null): Collection
    {
        $settings = $this->getSlaSettings($businessUnitId);

        if (! $settings) {
            return collect();
        }

        $slaDeadline = now()->subHours($settings->completion_sla_hours);

        $query = AdminTask::where('status', 'in_progress')
            ->whereNotNull('started_at')
            ->where('started_at', '<=', $slaDeadline)
            ->with(['assignedAdmin', 'department', 'businessUnit', 'taskable']);

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        return $query->get();
    }

    /**
     * Check if email alerts are enabled for a business unit
     */
    public function areEmailAlertsEnabled(?int $businessUnitId = null): bool
    {
        $settings = $this->getSlaSettings($businessUnitId);

        return $settings ? $settings->email_alerts_enabled : false;
    }

    /**
     * Get time remaining until follow-up SLA deadline
     *
     * @return int|null Minutes remaining (negative if exceeded)
     */
    public function getFollowupSlaTimeRemaining(AdminTask $task): ?int
    {
        $settings = $this->getSlaSettings($task->business_unit_id);

        if (! $settings || $task->status !== 'pending_followup') {
            return null;
        }

        $slaDeadline = $task->entered_at->copy()->addHours($settings->followup_sla_hours);

        return now()->diffInMinutes($slaDeadline, false);
    }

    /**
     * Get time remaining until completion SLA deadline
     *
     * @return int|null Minutes remaining (negative if exceeded)
     */
    public function getCompletionSlaTimeRemaining(AdminTask $task): ?int
    {
        $settings = $this->getSlaSettings($task->business_unit_id);

        if (! $settings || $task->status !== 'in_progress' || ! $task->started_at) {
            return null;
        }

        $slaDeadline = $task->started_at->copy()->addHours($settings->completion_sla_hours);

        return now()->diffInMinutes($slaDeadline, false);
    }

    /**
     * Get SLA status for a task
     *
     * @return array{type: string, exceeded: bool, time_remaining_minutes: int|null}
     */
    public function getTaskSlaStatus(AdminTask $task): array
    {
        if ($task->status === 'pending_followup') {
            return [
                'type' => 'followup',
                'exceeded' => $this->hasExceededFollowupSla($task),
                'time_remaining_minutes' => $this->getFollowupSlaTimeRemaining($task),
            ];
        }

        if ($task->status === 'in_progress') {
            return [
                'type' => 'completion',
                'exceeded' => $this->hasExceededCompletionSla($task),
                'time_remaining_minutes' => $this->getCompletionSlaTimeRemaining($task),
            ];
        }

        return [
            'type' => 'none',
            'exceeded' => false,
            'time_remaining_minutes' => null,
        ];
    }

    /**
     * Get count of tasks exceeding SLA for a business unit
     *
     * @return array{followup: int, completion: int}
     */
    public function getSlaViolationCounts(?int $businessUnitId = null): array
    {
        return [
            'followup' => $this->getTasksExceedingFollowupSla($businessUnitId)->count(),
            'completion' => $this->getTasksExceedingCompletionSla($businessUnitId)->count(),
        ];
    }
}
