<?php

namespace App\Services\Core;

use App\Models\Core\User;
use App\Services\Core\Navigation\ItSupportMenuBuilder;
use App\Services\Core\Navigation\MenuSectionBuilder;
use App\Services\Core\Navigation\MenuVisibilityResolver;

/**
 * Builds the application's left-hand navigation tree.
 *
 * Acts as a thin orchestrator: visibility decisions are delegated to
 * {@see MenuVisibilityResolver} and section construction to
 * {@see MenuSectionBuilder}.  This keeps the service under the 350-line
 * cap and concentrates each responsibility in one place.
 */
class NavigationService
{
    public function __construct(
        protected MenuVisibilityResolver $visibility,
        protected MenuSectionBuilder $sections,
        protected ItSupportMenuBuilder $itSupport,
    ) {}

    /**
     * Build navigation menu for the given user and business unit.
     *
     * Sections are emitted in the legacy order so the frontend's existing
     * rendering does not change.  An empty BU short-circuits because the
     * sidebar has no context to render.
     *
     * @return array{sections: array<int, array{name: string, items: array<int, array<string, mixed>>}>}
     */
    public function buildMenuForUser(User $user, ?int $businessUnitId): array
    {
        if (! $businessUnitId) {
            return ['sections' => []];
        }

        $sections = [];

        $sections[] = $this->sections->dashboardSection($user);

        if ($this->visibility->canAccessPurchasing($user, $businessUnitId)) {
            $sections[] = $this->sections->purchasingSection($user, $businessUnitId);
        }

        if ($this->visibility->canAccessActivityTracking($user, $businessUnitId)) {
            $sections[] = $this->sections->activityTrackingSection($user, $businessUnitId);
        }

        if ($this->visibility->canAccessSalesCrm($user, $businessUnitId)) {
            $sections[] = $this->sections->salesCrmSection($user, $businessUnitId);
        }

        // IT Support is visible to all authenticated users; admin extras are
        // gated inside the section builder via MenuVisibilityResolver.
        $sections[] = $this->itSupport->build($user, $businessUnitId);

        if ($this->visibility->canAccessCashflowProjection($user, $businessUnitId)) {
            $sections[] = $this->sections->cashflowProjectionSection($user, $businessUnitId);
        }

        if ($this->visibility->canAccessAdministration($user)) {
            $sections[] = $this->sections->administrationSection($user);
        }

        // Docs &amp; Help is always available.
        $sections[] = $this->sections->docsHelpSection();

        return ['sections' => array_filter($sections)];
    }
}
