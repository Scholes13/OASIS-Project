<?php

namespace App\Http\Controllers\Modules\Purchasing\StockRequest;

use App\Http\Controllers\Controller;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Core\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

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

        return view('purchasing.stock-requests.index', compact('stockRequests'));
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

        return view('purchasing.stock-requests.all');
    }

    /**
     * Show the form for creating a new stock request
     */
    public function create()
    {
        return view('purchasing.stock-requests.create');
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
            'approvals.approver',
            'lastModifiedBy',
        ]);

        return view('purchasing.stock-requests.show', compact('stockRequest'));
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

        return view('purchasing.stock-requests.edit', compact('stockRequest'));
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
     * Mark stock request as approved offline/manually
     *
     * Used when digital approval is too slow and user has exported the ST
     * for manual/offline approval. This marks the entire ST as approved at once.
     */
    public function markOfflineApproved(Request $request, StockRequest $stockRequest)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        // Verify business unit access
        if ($stockRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        // Check if ST can be marked as offline approved
        // Only STs that are submitted or in_approval can be marked
        if (! in_array($stockRequest->status, ['submitted', 'in_approval'])) {
            return back()->with('error', 'This stock request cannot be marked as offline approved. Only submitted or in-approval STs are eligible.');
        }

        // Authorization: Only ST owner can mark as offline approved
        $user = Auth::user();
        if ($stockRequest->user_id !== $user->id) {
            abort(403, 'Only the ST owner can mark this stock request as offline approved.');
        }

        try {
            $stockRequest->update([
                'status' => 'approved',
                'approved_at' => now(),
                'offline_approved_at' => now(),
                'offline_approved_by' => $user->id,
                'offline_approval_notes' => $request->notes,
            ]);

            // Log activity
            activity()
                ->performedOn($stockRequest)
                ->causedBy($user)
                ->withProperties([
                    'notes' => $request->notes,
                    'previous_status' => $stockRequest->getOriginal('status'),
                ])
                ->log('Stock request marked as offline approved');

            return redirect()
                ->route('stock-requests.show', $stockRequest)
                ->with('success', 'Stock request has been marked as approved offline/manually.');

        } catch (\Exception $e) {
            Log::error('Failed to mark stock request as offline approved', [
                'st_id' => $stockRequest->id,
                'st_number' => $stockRequest->st_number,
                'user_id' => Auth::id(),
                'notes' => $request->notes,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to mark as offline approved. Please try again or contact support.');
        }
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

    /**
     * Display PDF view for public access (no authentication required)
     */
    public function pdfPublic(StockRequest $stockRequest)
    {
        $stockRequest->load([
            'user',
            'department',
            'businessUnit',
            'items',
            'approvals.approver',
        ]);

        // Generate QR codes for PDF
        $qrCodeService = new QrCodeService;
        $qrCodes = $this->generateQrCodesForPdf($stockRequest, $qrCodeService);

        return view('purchasing.stock-requests.pdf-browser', compact('stockRequest', 'qrCodes'));
    }

    /**
     * Download PDF for stock request (public access)
     */
    public function downloadPdfPublic(StockRequest $stockRequest)
    {
        // Increase PHP execution time for PDF generation
        set_time_limit(300); // 5 minutes

        // Load relationships needed for PDF
        $stockRequest->load([
            'user',
            'department',
            'businessUnit',
            'items',
            'approvals.approver',
        ]);

        // Generate QR codes for PDF
        $qrCodeService = new QrCodeService;
        $qrCodes = $this->generateQrCodesForPdf($stockRequest, $qrCodeService);

        // Clean filename by removing invalid characters
        $cleanStNumber = preg_replace('/[\/\\\\:*?"<>|]/', '-', $stockRequest->st_number);
        $filename = 'ST-'.$cleanStNumber.'.pdf';

        try {
            // Generate HTML content directly to avoid URL timeout issues
            $html = view('purchasing.stock-requests.pdf-browser', compact('stockRequest', 'qrCodes'))->render();

            // Generate PDF directly in memory (no temp file needed)
            $pdfContent = Browsershot::html($html)
                ->format('A4')
                ->landscape()
                ->margins(10, 10, 10, 10)
                ->timeout(120)
                ->noSandbox()
                ->disableWebSecurity()
                ->setDelay(2000) // Wait 2 seconds for rendering
                ->pdf();

            // Return PDF content directly as response
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]);

        } catch (\Exception $e) {
            Log::error('Browsershot PDF generation failed: '.$e->getMessage());

            // Fallback: redirect to PDF view with better print styles
            return redirect()->route('stock-requests.pdf-public', $stockRequest)
                ->with('error', 'Automatic PDF generation failed. Please use Ctrl+P to save as PDF.');
        }
    }

    /**
     * Download PDF for stock request using configured method
     */
    public function downloadPdf(StockRequest $stockRequest)
    {
        // Directly call downloadPdfPublic to avoid redirect issues on hosting
        return $this->downloadPdfPublic($stockRequest);
    }

    /**
     * Generate QR codes for PDF
     */
    protected function generateQrCodesForPdf(StockRequest $stockRequest, QrCodeService $qrCodeService): array
    {
        $qrCodes = [];

        // Skip QR code generation for rejected SRs
        if ($stockRequest->status === 'rejected') {
            return $qrCodes;
        }

        // Generate QR code for requestor (only if submitted)
        if ($stockRequest->submitted_at) {
            $qrCodes['requestor'] = $qrCodeService->generateStockRequestorQrCodeDataUrl($stockRequest);
        }

        // Generate QR codes for each approved approval only
        $qrCodes['approvals'] = [];
        foreach ($stockRequest->approvals->where('status', 'approved') as $approval) {
            $qrCodes['approvals'][$approval->id] = $qrCodeService->generateStockApprovalQrCodeDataUrl($approval);
        }

        return $qrCodes;
    }
}
