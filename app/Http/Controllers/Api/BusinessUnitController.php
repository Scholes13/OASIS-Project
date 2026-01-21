<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
     * Uses hierarchical access check - if user has access to parent BU,
     * they also have access to all child BUs.
     */
    public function switch(Request $request): JsonResponse
    {
        $request->validate([
            'business_unit_id' => 'required|integer|exists:business_units,id',
        ]);

        $user = Auth::user();
        $businessUnitId = $request->input('business_unit_id');

        // Debug logging
        Log::info('BU Switch Request', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'requested_bu_id' => $businessUnitId,
        ]);

        if (!$user) {
            Log::warning('BU Switch: No authenticated user');
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated.',
            ], 401);
        }

        // ✅ Use hierarchical access check from User model
        // This includes direct assignments AND inherited access from parent BUs
        $accessibleBuIds = $user->getAccessibleBusinessUnitIds();
        
        Log::info('BU Switch Access Check', [
            'accessible_bu_ids' => $accessibleBuIds,
            'requested_bu_id' => $businessUnitId,
            'has_access' => in_array($businessUnitId, $accessibleBuIds),
        ]);
        
        $hasAccess = in_array($businessUnitId, $accessibleBuIds);

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this business unit.',
            ], 403);
        }

        // Get business unit details
        $businessUnit = BusinessUnit::find($businessUnitId);

        if (!$businessUnit || !$businessUnit->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Business unit not found or inactive.',
            ], 404);
        }

        // Update session with full BU details
        session([
            'current_business_unit_id' => $businessUnit->id,
            'current_business_unit_name' => $businessUnit->name,
            'current_business_unit_code' => $businessUnit->code,
            'current_business_unit_logo' => $businessUnit->logo,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Business unit switched successfully.',
            'businessUnit' => [
                'id' => $businessUnit->id,
                'name' => $businessUnit->name,
                'code' => $businessUnit->code,
                'logo' => $businessUnit->logo,
            ],
        ]);
    }
}
