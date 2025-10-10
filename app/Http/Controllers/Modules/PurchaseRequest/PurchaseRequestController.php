<?php

namespace App\Http\Controllers\Modules\PurchaseRequest;

use App\Http\Controllers\Controller;
use App\Models\Modules\PurchaseRequest\PurchaseRequest;
use App\Services\Core\QrCodeService;
use App\Services\Modules\PurchaseRequest\PurchaseRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class PurchaseRequestController extends Controller
{
    protected PurchaseRequestService $purchaseRequestService;

    public function __construct(PurchaseRequestService $purchaseRequestService)
    {
        $this->purchaseRequestService = $purchaseRequestService;
    }

    /**
     * Display a listing of purchase requests and reservations based on user hierarchy
     */
    public function index()
    {
        $query = $this->purchaseRequestService->getPurchaseRequestsQuery();
        $purchaseRequests = $query->latest('created_at')->paginate(10);

        // Get PR Number Reservations using same hierarchy logic
        $user = Auth::user();
        $accessLevel = $user->getAccessLevel();

        $reservationQuery = \App\Models\Modules\PurchaseRequest\PrNumberReservation::with(['businessUnit', 'department', 'user', 'purchaseRequest'])
            ->where('business_unit_id', session('current_business_unit_id'));

        // Apply same hierarchy filtering as PR
        switch ($accessLevel) {
            case 'super_admin':
            case 'executive':
            case 'general_manager':
                break;
            case 'department_head':
                $reservationQuery->where('department_id', $user->primary_department_id);
                break;
            case 'team_leader':
                $subordinateIds = $user->activeSubordinates()->pluck('id')->toArray();
                $subordinateIds[] = $user->id;
                $reservationQuery->whereIn('user_id', $subordinateIds);
                break;
            case 'staff':
            default:
                $reservationQuery->byUser($user->id);
                break;
        }

        $reservations = $reservationQuery->latest('reserved_at')->paginate(10);

        return view('purchase-requests.index', compact('purchaseRequests', 'reservations'));
    }

    /**
     * Display all purchase requests (for managers/admins with proper hierarchy)
     */
    public function all()
    {
        $user = Auth::user();
        $accessLevel = $user->getAccessLevel();

        // Only allow certain access levels to view "all" PRs
        if (! in_array($accessLevel, ['super_admin', 'executive', 'general_manager', 'department_head'])) {
            return redirect()->route('purchase-requests.index')
                ->with('error', 'You do not have permission to view all purchase requests.');
        }

        $query = $this->purchaseRequestService->getPurchaseRequestsQuery();
        $purchaseRequests = $query->latest('created_at')->paginate(10);

        return view('purchase-requests.all', compact('purchaseRequests'));
    }

    /**
     * Store method removed - Livewire component handles PR creation
     * See: app/Livewire/Modules/Wns/PurchaseRequests/Create.php
     */

    /**
     * Display the specified purchase request
     */
    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load(['department', 'user', 'items.expenseDepartment', 'approvals.approver']);

        return view('purchase-requests.show', compact('purchaseRequest'));
    }

    /**
     * Show the form for editing the specified purchase request
     */
    public function edit(PurchaseRequest $purchaseRequest)
    {
        if (! $purchaseRequest->canBeEdited()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'This purchase request cannot be edited.');
        }

        if (session('current_business_unit_id') !== $purchaseRequest->business_unit_id) {
            session(['current_business_unit_id' => $purchaseRequest->business_unit_id]);
        }

        if (session('current_department_id') !== $purchaseRequest->department_id) {
            session(['current_department_id' => $purchaseRequest->department_id]);
        }

        $departments = $this->purchaseRequestService->getDepartments();
        $purchaseRequest->load(['items', 'businessUnit']);

        return view('purchase-requests.edit', compact('purchaseRequest', 'departments'));
    }

    /**
     * Update method removed - Livewire component handles PR updates
     * See: app/Livewire/Modules/Wns/PurchaseRequests/Create.php
     */

    /**
     * Resubmit rejected purchase request (reset workflow)
     */
    public function resubmit(PurchaseRequest $purchaseRequest)
    {
        // Check if PR can be resubmitted (must be rejected)
        if ($purchaseRequest->status !== 'rejected') {
            return back()->with('error', 'Only rejected purchase requests can be resubmitted.');
        }

        // Check if user owns this PR
        if ($purchaseRequest->user_id !== Auth::id()) {
            abort(403, 'You are not authorized to resubmit this purchase request.');
        }

        try {
            // Reset workflow and resubmit
            $purchaseRequest = $this->purchaseRequestService->resubmitPurchaseRequest($purchaseRequest);

            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('success', 'Purchase request has been resubmitted for approval. Approval workflow has been reset.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to resubmit purchase request: '.$e->getMessage());
        }
    }

    /**
     * Void purchase request
     */
    public function void(Request $request, PurchaseRequest $purchaseRequest)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->purchaseRequestService->voidPurchaseRequest($purchaseRequest, $request->reason);

            return redirect()
                ->route('purchase-requests.index')
                ->with('success', 'Purchase request has been voided.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to void purchase request: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified purchase request
     */
    public function destroy(PurchaseRequest $purchaseRequest)
    {
        if (! $purchaseRequest->canBeEdited()) {
            return back()->with('error', 'This purchase request cannot be deleted.');
        }

        $purchaseRequest->delete();

        return redirect()
            ->route('purchase-requests.index')
            ->with('success', 'Purchase request has been deleted.');
    }

    /**
     * Generate PDF view for purchase request
     */
    public function pdf(PurchaseRequest $purchaseRequest)
    {
        // Load relationships needed for PDF
        $purchaseRequest->load([
            'user',
            'department',
            'businessUnit',
            'items',
            'approvals.approver',
        ]);

        // Generate QR codes for PDF
        $qrCodeService = new QrCodeService;
        $qrCodes = $this->generateQrCodesForPdf($purchaseRequest, $qrCodeService);

        return view('purchase-requests.pdf-browser', compact('purchaseRequest', 'qrCodes'));
    }

    /**
     * Generate PDF view for purchase request - Public access for browsershot
     */
    public function pdfPublic(PurchaseRequest $purchaseRequest)
    {
        // Load relationships needed for PDF
        $purchaseRequest->load([
            'user',
            'department',
            'businessUnit',
            'items',
            'approvals.approver',
        ]);

        // Generate QR codes for PDF
        $qrCodeService = new QrCodeService;
        $qrCodes = $this->generateQrCodesForPdf($purchaseRequest, $qrCodeService);

        return view('purchase-requests.pdf-browser', compact('purchaseRequest', 'qrCodes'));
    }

    /**
     * Download PDF via public route (no authentication required)
     * This method is accessible by Browsershot without authentication
     */
    public function downloadPdfPublic(PurchaseRequest $purchaseRequest)
    {
        // Increase PHP execution time for PDF generation
        set_time_limit(300); // 5 minutes

        // Load relationships needed for PDF
        $purchaseRequest->load([
            'user',
            'department',
            'businessUnit',
            'items',
            'approvals.approver',
        ]);

        // Generate QR codes for PDF
        $qrCodeService = new QrCodeService;
        $qrCodes = $this->generateQrCodesForPdf($purchaseRequest, $qrCodeService);

        // Clean filename by removing invalid characters
        $cleanPrNumber = preg_replace('/[\/\\\\:*?"<>|]/', '-', $purchaseRequest->pr_number);
        $filename = 'PR-'.$cleanPrNumber.'.pdf';

        try {
            // Generate HTML content directly to avoid URL timeout issues
            $html = view('purchase-requests.pdf-browser', compact('purchaseRequest', 'qrCodes'))->render();

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
            return redirect()->route('purchase-requests.pdf-public', $purchaseRequest)
                ->with('error', 'Automatic PDF generation failed. Please use Ctrl+P to save as PDF.');
        }
    }

    /**
     * Download PDF for purchase request using configured method
     */
    public function downloadPdf(PurchaseRequest $purchaseRequest)
    {
        // Directly call downloadPdfPublic to avoid redirect issues on hosting
        return $this->downloadPdfPublic($purchaseRequest);
    }

    /**
     * Generate PDF using Browsershot
     */
    private function generateBrowsershotPdf($purchaseRequest, $qrCodes, $filename)
    {
        try {
            // Generate the full URL for the PDF view using public route (no auth required)
            $baseUrl = config('app.url');
            if ($baseUrl === 'http://localhost') {
                $baseUrl = 'http://localhost:8000';
            }

            $url = $baseUrl.'/purchase-requests/'.$purchaseRequest->id.'/pdf-public';

            Log::info('Browsershot attempting to access URL: '.$url);

            // Get Browsershot configuration
            $config = config('pdf.browsershot');

            // Use working configuration with proper timeout
            $browsershot = Browsershot::url($url)
                ->format('A4')
                ->landscape()
                ->margins(10, 10, 10, 10)
                ->timeout(120) // Increased timeout for complex PDF pages
                ->noSandbox()
                ->disableWebSecurity();

            // Don't wait for network idle to avoid timeout
            // Network idle can cause timeout on slow connections

            Log::info('Browsershot configuration applied, generating PDF...');

            $pdf = $browsershot->pdf();

            Log::info('Browsershot PDF generation successful');

            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');

        } catch (\Exception $e) {
            // Log error and return proper error response
            Log::error('Browsershot PDF generation failed: '.$e->getMessage());

            return response()->json([
                'error' => 'PDF generation failed. Please try again later.',
                'message' => 'Browsershot encountered an error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate QR codes for PDF
     */
    protected function generateQrCodesForPdf(PurchaseRequest $purchaseRequest, QrCodeService $qrCodeService): array
    {
        $qrCodes = [];

        // Skip QR code generation for rejected PRs
        if ($purchaseRequest->status === 'rejected') {
            return $qrCodes;
        }

        // Generate QR code for requestor (only if submitted)
        if ($purchaseRequest->submitted_at) {
            $qrCodes['requestor'] = $qrCodeService->generateRequestorQrCodeDataUrl($purchaseRequest);
        }

        // Generate QR codes for each approved approval only
        $qrCodes['approvals'] = [];
        foreach ($purchaseRequest->approvals->where('status', 'approved') as $approval) {
            $qrCodes['approvals'][$approval->id] = $qrCodeService->generateApproverQrCodeDataUrl($approval);
        }

        return $qrCodes;
    }
}
