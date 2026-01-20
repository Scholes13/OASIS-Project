<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Business Unit API Controller
 * 
 * Handles API endpoints for business unit switching.
 */
class BusinessUnitController extends Controller
{
    /**
     * Switch the current business unit context.
     * 
     * Requirements: 9.1
     */
    public function switch(Request $request): JsonResponse
    {
        $request->validate([
            'business_unit_id' => 'required|integer|exists:business_units,id',
        ]);

        $user = Auth::user();
        $businessUnitId = $request->input('business_unit_id');

        // Verify user has access to this business unit
        $hasAccess = $user->businessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->exists();

        // Also check if user has access to parent BU (for child BU access)
        if (!$hasAccess) {
            $businessUnit = BusinessUnit::find($businessUnitId);
            if ($businessUnit && $businessUnit->parent_id) {
                $hasAccess = $user->businessUnits()
                    ->where('business_unit_id', $businessUnit->parent_id)
                    ->exists();
            }
        }

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this business unit.',
            ], 403);
        }

        // Get business unit details
        $businessUnit = BusinessUnit::find($businessUnitId);

        // Update session
        session([
            'current_business_unit_id' => $businessUnit->id,
            'current_business_unit_name' => $businessUnit->name,
            'current_business_unit_code' => $businessUnit->code,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Business unit switched successfully.',
            'business_unit' => [
                'id' => $businessUnit->id,
                'name' => $businessUnit->name,
                'code' => $businessUnit->code,
            ],
        ]);
    }
}
