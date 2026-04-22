<?php

namespace App\Http\Controllers\Modules\Purchasing;

use App\Http\Controllers\Controller;

class PurchasingController extends Controller
{
    /**
     * Display all PR and SR in current business unit (based on hierarchy).
     *
     * Redirects to the Inertia-powered All Purchase Requests page which
     * already handles BU scoping, filtering, and pagination.
     */
    public function allRequests(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('purchase-requests.all', request()->query());
    }
}
