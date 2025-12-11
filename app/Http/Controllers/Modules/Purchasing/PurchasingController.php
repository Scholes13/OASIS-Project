<?php

namespace App\Http\Controllers\Modules\Purchasing;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PurchasingController extends Controller
{
    /**
     * Display all PR and SR in current business unit (based on hierarchy)
     */
    public function allRequests()
    {
        $user = Auth::user();
        $currentBusinessUnitId = session('current_business_unit_id');

        if (!$currentBusinessUnitId) {
            return redirect()->route('dashboard')
                ->with('error', 'Please select a business unit first.');
        }

        $accessLevel = $user->getAccessLevel();

        return view('purchasing.all-requests', compact('accessLevel'));
    }
}
