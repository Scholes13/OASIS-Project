<?php

namespace App\Services\Modules\Ticket;

use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketSlaSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SlaService
{
    /**
     * Default SLA resolution hours per priority level.
     *
     * @var array<string, int>
     */
    protected const DEFAULTS = [
        'low' => 48,
        'medium' => 24,
        'high' => 8,
        'critical' => 2,
    ];

    /**
     * Get all SLA settings for a business unit.
     */
    public function getSettings(int $buId): Collection
    {
        return TicketSlaSettings::getForBusinessUnit($buId);
    }

    /**
     * Bulk update SLA settings for a business unit.
     *
     * @param  array<string, int>  $settings  Keyed by priority, value is resolution_hours
     */
    public function updateSettings(int $buId, array $settings): void
    {
        foreach ($settings as $priority => $resolutionHours) {
            TicketSlaSettings::updateOrCreate(
                [
                    'business_unit_id' => $buId,
                    'priority' => $priority,
                ],
                [
                    'resolution_hours' => (int) $resolutionHours,
                ]
            );
        }
    }

    /**
     * Get the SLA deadline for a ticket based on its priority and BU settings.
     */
    public function getDeadline(Ticket $ticket): ?Carbon
    {
        return $ticket->sla_deadline;
    }

    /**
     * Check if a ticket has breached its SLA.
     */
    public function isBreached(Ticket $ticket): bool
    {
        return $ticket->isSlaBreach();
    }

    /**
     * Get all tickets that have breached their SLA across the given business units.
     *
     * Only considers open tickets (waiting, in_progress) and resolved tickets
     * that were resolved after their deadline.
     *
     * @param  array<int>  $buIds
     */
    public function getBreachedTickets(array $buIds): Collection
    {
        // Load tickets with their SLA settings eagerly
        $tickets = Ticket::forBusinessUnits($buIds)
            ->whereIn('status', ['waiting', 'in_progress', 'done'])
            ->with(['businessUnit', 'assignedUser', 'category', 'requester'])
            ->get();

        return $tickets->filter(fn (Ticket $ticket): bool => $ticket->isSlaBreach())
            ->values();
    }

    /**
     * Seed default SLA settings for a new business unit.
     */
    public function seedDefaults(int $buId): void
    {
        foreach (self::DEFAULTS as $priority => $hours) {
            TicketSlaSettings::firstOrCreate(
                [
                    'business_unit_id' => $buId,
                    'priority' => $priority,
                ],
                [
                    'resolution_hours' => $hours,
                ]
            );
        }
    }
}
