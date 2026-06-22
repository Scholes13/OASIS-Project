<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Modules\Purchasing\Admin\SlaSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SlaSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin.access'); // Only super admin can access
    }

    /**
     * Display SLA settings
     */
    public function index()
    {
        if (! Auth::user()->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can access SLA settings.');
        }

        // Get all business units
        $businessUnits = BusinessUnit::orderBy('name')->get()->map(fn ($bu) => [
            'id' => $bu->id,
            'name' => $bu->name,
            'code' => $bu->code,
            'is_active' => $bu->is_active,
        ]);

        // Get SLA settings for each business unit
        $slaSettings = SlaSettings::with('businessUnit')->get()->keyBy('business_unit_id')->map(fn ($setting) => [
            'id' => $setting->id,
            'business_unit_id' => $setting->business_unit_id,
            'followup_sla_hours' => $setting->followup_sla_hours,
            'completion_sla_hours' => $setting->completion_sla_hours,
            'email_alerts_enabled' => $setting->email_alerts_enabled,
            'updated_at' => $setting->updated_at?->toISOString(),
        ]);

        // Calculate compliance statistics for each business unit
        $statistics = $this->calculateComplianceStatistics($businessUnits);

        return inertia('Admin/SlaSettings/Index', [
            'businessUnits' => $businessUnits,
            'slaSettings' => $slaSettings,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Calculate SLA compliance statistics for each business unit
     */
    private function calculateComplianceStatistics($businessUnits): array
    {
        $statistics = [];

        foreach ($businessUnits as $businessUnit) {
            // Get admin tasks for this business unit
            $tasks = \App\Models\Modules\Purchasing\Admin\AdminTask::where('business_unit_id', $businessUnit['id'])
                ->whereNotNull('completed_at')
                ->get();

            if ($tasks->isEmpty()) {
                continue;
            }

            // Get SLA settings for this business unit
            $slaSettings = SlaSettings::where('business_unit_id', $businessUnit['id'])->first();

            if (! $slaSettings) {
                continue;
            }

            // Calculate compliance metrics
            $totalTasks = $tasks->count();
            $compliantTasks = 0;
            $totalCompletionTime = 0;
            $overdueTasks = 0;

            foreach ($tasks as $task) {
                // Calculate completion time in hours
                $completionTime = $task->created_at->diffInHours($task->completed_at);
                $totalCompletionTime += $completionTime;

                // Check if task met SLA
                if ($completionTime <= $slaSettings->completion_sla_hours) {
                    $compliantTasks++;
                } else {
                    $overdueTasks++;
                }
            }

            $statistics[$businessUnit['id']] = [
                'business_unit_id' => $businessUnit['id'],
                'compliance_rate' => $totalTasks > 0 ? ($compliantTasks / $totalTasks) * 100 : 0,
                'average_completion_time' => $totalTasks > 0 ? $totalCompletionTime / $totalTasks : 0,
                'overdue_count' => $overdueTasks,
            ];
        }

        return $statistics;
    }

    /**
     * Update SLA settings for a business unit
     */
    public function update(Request $request)
    {
        if (! Auth::user()->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can modify SLA settings.');
        }

        $validated = $request->validate([
            'business_unit_id' => 'required|exists:business_units,id',
            'followup_sla_hours' => 'required|integer|min:1|max:720',
            'completion_sla_hours' => 'required|integer|min:1|max:720',
            'email_alerts_enabled' => 'boolean',
        ]);

        // Additional validation: follow-up must be less than completion
        if ($validated['followup_sla_hours'] >= $validated['completion_sla_hours']) {
            return back()->withErrors([
                'followup_sla_hours' => 'Follow-up time must be less than completion time.',
            ]);
        }

        // Update or create SLA settings
        $slaSettings = SlaSettings::updateOrCreate(
            ['business_unit_id' => $validated['business_unit_id']],
            $validated
        );

        return back()->with('success', 'SLA settings updated successfully for '.$slaSettings->businessUnit->name.'.');
    }
}
