<?php

namespace App\Http\Controllers\Modules\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\UpdateSlaSettingsRequest;
use App\Models\Core\BusinessUnit;
use App\Services\Modules\Ticket\SlaService;
use App\Services\Modules\Ticket\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketDashboardController extends Controller
{
    public function __construct(
        private TicketService $ticketService,
        private SlaService $slaService,
    ) {}

    /**
     * IT Support dashboard with metrics, SLA breach count, charts, and recent tickets.
     *
     * GET /it-support/dashboard
     */
    public function index(Request $request): Response
    {
        $scopedBuIds = $this->resolveScopedBusinessUnitIds();

        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $metrics = $this->ticketService->getDashboardMetrics($scopedBuIds, $dateFrom, $dateTo);

        return Inertia::render('Ticket/Dashboard', [
            'metrics' => $metrics,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * Show SLA settings page.
     *
     * GET /it-support/sla-settings
     */
    public function slaSettings(): Response
    {
        $buId = (int) session('current_business_unit_id');

        $settings = $this->slaService->getSettings($buId);

        // If no settings exist yet, seed defaults and reload
        if ($settings->isEmpty()) {
            $this->slaService->seedDefaults($buId);
            $settings = $this->slaService->getSettings($buId);
        }

        return Inertia::render('Ticket/SlaSettings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update SLA settings.
     *
     * PUT /it-support/sla-settings
     */
    public function updateSlaSettings(UpdateSlaSettingsRequest $request): RedirectResponse
    {
        $buId = (int) session('current_business_unit_id');

        $validated = $request->validated();

        // Transform array of {priority, resolution_hours} into keyed map
        $settingsMap = [];
        foreach ($validated['settings'] as $setting) {
            $settingsMap[$setting['priority']] = $setting['resolution_hours'];
        }

        $this->slaService->updateSettings($buId, $settingsMap);

        return back()->with('success', 'SLA settings berhasil diperbarui.');
    }

    /**
     * Resolve the active BU scope for IT Support admin.
     * Parent or holding BUs include all descendants for roll-up views.
     *
     * @return array<int>
     */
    private function resolveScopedBusinessUnitIds(): array
    {
        $currentBusinessUnitId = (int) session('current_business_unit_id');

        if ($currentBusinessUnitId <= 0) {
            return [];
        }

        $currentBusinessUnit = BusinessUnit::with('descendants')->find($currentBusinessUnitId);

        if (! $currentBusinessUnit) {
            return [$currentBusinessUnitId];
        }

        return $currentBusinessUnit->getAccessibleBusinessUnits();
    }
}
