<?php

namespace App\Http\Controllers\Modules\Ticket;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Core\User;
use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    /**
     * Stream a ticket attachment to the authenticated user.
     *
     * Authorization is layered so the same endpoint serves both the
     * requester (read-only access to their own ticket) and IT Support
     * admins (BU-scoped access including descendants).  Files live on
     * the private `local` disk by default; legacy rows on `public`
     * still resolve correctly.
     */
    public function download(Request $request, TicketAttachment $attachment): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $ticket = $attachment->ticket()->first();
        abort_unless($ticket !== null, 404);

        abort_unless($this->canAccessTicketAttachment($ticket, $user), 403);

        $disk = $attachment->disk ?: 'public';

        abort_unless(
            $attachment->file_path && Storage::disk($disk)->exists($attachment->file_path),
            404
        );

        return Storage::disk($disk)->download(
            $attachment->file_path,
            $attachment->original_filename ?? basename($attachment->file_path),
            [
                'Content-Type' => $attachment->file_type ?: 'application/octet-stream',
            ]
        );
    }

    /**
     * Authorization gate for ticket attachment access.
     *
     * Allow when:
     *   - the user is the ticket requester, OR
     *   - the user is a super admin, OR
     *   - the user is an IT Support admin in the ticket's BU or any
     *     ancestor BU (matches the cascade used elsewhere for tickets).
     */
    private function canAccessTicketAttachment(Ticket $ticket, User $user): bool
    {
        if ($this->isRequesterInCurrentScope($ticket, $user)) {
            return true;
        }

        if ($user->global_role === 'super_admin') {
            return true;
        }

        $scopedBuIds = $this->resolveItSupportScopedBusinessUnitIds($user);

        return in_array((int) $ticket->business_unit_id, $scopedBuIds, true)
            && $this->isItSupportAdminInScope($user, $scopedBuIds);
    }

    private function isRequesterInCurrentScope(Ticket $ticket, User $user): bool
    {
        $businessUnitId = (int) session('current_business_unit_id');

        if ((int) $ticket->requester_id !== (int) $user->id || $businessUnitId <= 0) {
            return false;
        }

        $assignment = $user->businessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->first();

        return (int) $ticket->business_unit_id === $businessUnitId
            && $assignment !== null
            && (int) $ticket->department_id === (int) $assignment->department_id;
    }

    /**
     * Resolve the IT Support BU scope for the user, including descendants
     * of the currently selected business unit so admins of holding BUs
     * keep their roll-up access to attachments.
     *
     * @return array<int>
     */
    private function resolveItSupportScopedBusinessUnitIds(User $user): array
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

    /**
     * Confirm the user holds the IT Support admin flag inside the scope.
     *
     * @param  array<int>  $scopedBuIds
     */
    private function isItSupportAdminInScope(User $user, array $scopedBuIds): bool
    {
        if (empty($scopedBuIds)) {
            return false;
        }

        return $user->businessUnits()
            ->whereIn('business_unit_id', $scopedBuIds)
            ->where('is_active', true)
            ->where('is_it_support_admin', true)
            ->exists();
    }
}
