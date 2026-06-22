<?php

namespace App\Services\Core\Navigation;

use App\Models\Core\User;

/**
 * Builds the IT Support navigation section.
 *
 * Extracted from {@see MenuSectionBuilder} to keep that builder under the
 * 350-line service cap.  Behaviour parity with the legacy
 * {@see \App\Services\Core\NavigationService::getItSupportSection()} is
 * preserved verbatim — every user sees Submit Ticket and My Tickets, and
 * IT Support admins additionally see the admin-only children.
 */
class ItSupportMenuBuilder
{
    public function __construct(
        protected MenuVisibilityResolver $visibility,
    ) {}

    /**
     * Build the IT Support section for the current user.
     *
     * Returns an empty array (filtered out by NavigationService::array_filter)
     * when the `features.it_support` flag is disabled — production deploys
     * that turn the module off get a fully clean sidebar.
     *
     * @return array{name?: string, items?: array<int, array<string, mixed>>}
     */
    public function build(User $user, int $businessUnitId): array
    {
        if (! config('features.it_support', true)) {
            return [];
        }

        $isAdmin = $this->visibility->canAccessItSupportAdmin($user, $businessUnitId);

        $children = [
            [
                'name' => 'Submit Ticket',
                'href' => route('it-support.submit'),
                'icon' => 'plus-circle',
                'active' => request()->routeIs('it-support.submit'),
            ],
            [
                'name' => 'My Tickets',
                'href' => route('it-support.my-tickets'),
                'icon' => 'ticket',
                'active' => request()->routeIs('it-support.my-tickets*'),
            ],
        ];

        if ($isAdmin) {
            $children[] = [
                'name' => 'Dashboard',
                'href' => route('it-support.admin.dashboard'),
                'icon' => 'chart-pie',
                'active' => request()->routeIs('it-support.admin.dashboard'),
            ];
            $children[] = [
                'name' => 'All Tickets',
                'href' => route('it-support.admin.tickets.index'),
                'icon' => 'list',
                'active' => request()->routeIs('it-support.admin.tickets.*'),
            ];
            $children[] = [
                'name' => 'Reporting',
                'href' => route('it-support.admin.reporting'),
                'icon' => 'bar-chart',
                'active' => request()->routeIs('it-support.admin.reporting'),
            ];
            $children[] = [
                'name' => 'Categories',
                'href' => route('it-support.admin.categories.index'),
                'icon' => 'tag',
                'active' => request()->routeIs('it-support.admin.categories.*'),
            ];
            $children[] = [
                'name' => 'Manage Knowledge',
                'href' => route('it-support.admin.knowledge.index'),
                'icon' => 'library',
                'active' => request()->routeIs('it-support.admin.knowledge.*'),
            ];
            $children[] = [
                'name' => 'SLA Settings',
                'href' => route('it-support.admin.sla-settings'),
                'icon' => 'clock',
                'active' => request()->routeIs('it-support.admin.sla-settings'),
            ];
        }

        return [
            'name' => 'IT Support',
            'items' => [
                [
                    'name' => 'IT Support',
                    'href' => route('it-support.my-tickets'),
                    'icon' => 'headphones',
                    'active' => request()->routeIs('it-support.*'),
                    'children' => $children,
                ],
            ],
        ];
    }
}
