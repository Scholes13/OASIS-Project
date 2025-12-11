<?php

namespace App\Http\Controllers;

use App\Models\Modules\Purchasing\PurchaseRequest\PrNumberReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrNumberReservationController extends Controller
{
    /**
     * Void a PR number reservation
     */
    public function void(Request $request, PrNumberReservation $reservation)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Check if user can void this reservation
        if ($reservation->user_id !== Auth::id() && ! Auth::user()->isSuperAdmin()) {
            return back()->with('error', 'You can only void your own PR number reservations.');
        }

        if (! $reservation->canBeVoided()) {
            return back()->with('error', 'This PR number cannot be voided.');
        }

        $reservation->void($request->reason);

        return back()->with('success', "PR number {$reservation->pr_number} has been voided.");
    }

    /**
     * Continue to create PR form with reserved number
     */
    public function continueToForm(PrNumberReservation $reservation)
    {
        // Check if user can use this reservation
        if ($reservation->user_id !== Auth::id()) {
            return back()->with('error', 'You can only continue with your own PR number reservations.');
        }

        if (! $reservation->canBeUsed()) {
            return back()->with('error', 'This PR number is no longer available.');
        }

        // Store reservation details in session for CreateWithNumber component
        session([
            'pr_number_details' => [
                'formatted_number' => $reservation->pr_number,
                'sequence_id' => $reservation->sequence_id,
                'business_unit_code' => $reservation->businessUnit->code ?? '',
                'business_unit_name' => $reservation->businessUnit->name ?? '',
                'department_code' => $reservation->department->code ?? '',
                'department_name' => $reservation->department->name ?? '',
                'submission_date' => $reservation->reserved_at->format('Y-m-d'),
                'purpose' => $reservation->purpose,
                'description' => $reservation->description,
                'currency' => 'IDR',
                'requested_by' => $reservation->user->name,
                'requested_at' => $reservation->reserved_at->format('Y-m-d H:i:s'),
                'reservation_id' => $reservation->id, // Track which reservation this is for
            ],
        ]);

        return redirect()->route('purchase-requests.create-with-number');
    }
}
