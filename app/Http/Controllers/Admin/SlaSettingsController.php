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
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can access SLA settings.');
        }

        // Get all business units
        $businessUnits = BusinessUnit::orderBy('name')->get();

        // Get SLA settings for each business unit
        $slaSettings = SlaSettings::with('businessUnit')->get()->keyBy('business_unit_id');

        return view('admin.sla-settings.index', compact('businessUnits', 'slaSettings'));
    }

    /**
     * Update SLA settings for a business unit
     */
    public function update(Request $request)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can modify SLA settings.');
        }

        $validated = $request->validate([
            'business_unit_id' => 'required|exists:business_units,id',
            'followup_sla_hours' => 'required|integer|min:1|max:720',
            'completion_sla_hours' => 'required|integer|min:1|max:720',
            'email_alerts_enabled' => 'boolean',
        ]);

        // Convert checkbox to boolean
        $validated['email_alerts_enabled'] = $request->has('email_alerts_enabled');

        // Update or create SLA settings
        $slaSettings = SlaSettings::updateOrCreate(
            ['business_unit_id' => $validated['business_unit_id']],
            $validated
        );

        return redirect()
            ->route('admin.sla-settings.index')
            ->with('success', 'SLA settings updated successfully for ' . $slaSettings->businessUnit->name . '.');
    }
}
