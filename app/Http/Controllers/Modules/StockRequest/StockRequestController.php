<?php

namespace App\Http\Controllers\Modules\StockRequest;

use App\Http\Controllers\Controller;
use App\Models\Modules\StockRequest\StockRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockRequestController extends Controller
{
    /**
     * Display a listing of stock requests based on user hierarchy
     */
    public function index()
    {
        $user = Auth::user();
        $accessLevel = $user->getAccessLevel();
        $currentBusinessUnitId = session('current_business_unit_id');

        $query = StockRequest::with(['businessUnit', 'department', 'user', 'items'])
            ->where('business_unit_id', $currentBusinessUnitId);

        // Apply hierarchy filtering
        switch ($accessLevel) {
            case 'super_admin':
            case 'executive':
            case 'general_manager':
                // Can see all stock requests in business unit
                break;
            case 'department_head':
                // Can see department's stock requests
                $query->where('department_id', $user->primary_department_id);
                break;
            case 'team_leader':
                // Can see own and subordinates' stock requests
                $subordinateIds = $user->activeSubordinates()->pluck('id')->toArray();
                $subordinateIds[] = $user->id;
                $query->whereIn('user_id', $subordinateIds);
                break;
            case 'staff':
            default:
                // Can see only own stock requests
                $query->where('user_id', $user->id);
                break;
        }

        $stockRequests = $query->latest('created_at')->paginate(15);

        return view('stock-requests.index', compact('stockRequests'));
    }

    /**
     * Display all stock requests in the current business unit
     */
    public function all(Request $request)
    {
        $user = Auth::user();
        $currentBusinessUnitId = (int) session('current_business_unit_id');

        // Verify user has access to this business unit
        $userBusinessUnitIds = $user->activeBusinessUnits()->pluck('business_unit_id')->toArray();

        if (! $currentBusinessUnitId || ! in_array($currentBusinessUnitId, $userBusinessUnitIds)) {
            return redirect()->route('stock-requests.index')
                ->with('error', 'You do not have access to this business unit.');
        }

        return view('stock-requests.all');
    }

    /**
     * Show the form for creating a new stock request
     */
    public function create()
    {
        return view('stock-requests.create');
    }

    /**
     * Display the specified stock request
     */
    public function show(StockRequest $stockRequest)
    {
        // Verify business unit access
        if ($stockRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        $stockRequest->load([
            'businessUnit',
            'department',
            'user',
            'items',
            'lastModifiedBy',
        ]);

        return view('stock-requests.show', compact('stockRequest'));
    }

    /**
     * Show the form for editing the specified stock request
     */
    public function edit(StockRequest $stockRequest)
    {
        // Verify business unit access
        if ($stockRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        if (! $stockRequest->isEditable()) {
            return redirect()
                ->route('stock-requests.show', $stockRequest)
                ->with('error', 'This stock request cannot be edited.');
        }

        return view('stock-requests.edit', compact('stockRequest'));
    }

    /**
     * Remove the specified stock request
     */
    public function destroy(StockRequest $stockRequest)
    {
        // Verify business unit access
        if ($stockRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        // Only draft stock requests can be deleted
        if ($stockRequest->status !== 'draft') {
            return back()->with('error', 'Only draft stock requests can be deleted.');
        }

        // Check if user owns this stock request or has permission
        if ($stockRequest->user_id !== Auth::id() && ! Auth::user()->can('delete', $stockRequest)) {
            abort(403, 'You do not have permission to delete this stock request.');
        }

        $stockRequest->delete();

        return redirect()
            ->route('stock-requests.index')
            ->with('success', "Stock request {$stockRequest->st_number} has been deleted.");
    }

    /**
     * Void a stock request
     */
    public function void(Request $request, StockRequest $stockRequest)
    {
        // Verify business unit access
        if ($stockRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        if (! $stockRequest->canBeVoided()) {
            return back()->with('error', 'This stock request cannot be voided.');
        }

        $request->validate([
            'void_reason' => 'required|string|max:500',
        ]);

        $stockRequest->update([
            'status' => 'voided',
            'voided_at' => now(),
            'rejection_notes' => $request->void_reason,
        ]);

        return back()->with('success', "Stock request {$stockRequest->st_number} has been voided.");
    }

    /**
     * Resubmit rejected stock request
     */
    public function resubmit(StockRequest $stockRequest)
    {
        // Verify business unit access
        if ($stockRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        // Check if stock request can be resubmitted (must be rejected)
        if ($stockRequest->status !== 'rejected') {
            return back()->with('error', 'Only rejected stock requests can be resubmitted.');
        }

        // Reset workflow
        $stockRequest->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'rejected_at' => null,
            'rejection_notes' => null,
        ]);

        return redirect()
            ->route('stock-requests.show', $stockRequest)
            ->with('success', "Stock request {$stockRequest->st_number} has been resubmitted.");
    }
}
