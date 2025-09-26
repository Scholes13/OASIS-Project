<?php

namespace App\Http\Controllers;

use App\Models\Modules\WNS\PurchaseRequest;
use App\Services\PurchaseRequestService;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;

class PurchaseRequestController extends Controller
{
    protected PurchaseRequestService $purchaseRequestService;ler extends Controller
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
        $purchaseRequests = $query->latest('created_at')->get();

        // Get PR Number Reservations using same hierarchy logic
        $user = Auth::user();
        $accessLevel = $user->getAccessLevel();

        $reservationQuery = \App\Models\PrNumberReservation::with(['businessUnit', 'department', 'user', 'purchaseRequest'])
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

        $reservations = $reservationQuery->latest('reserved_at')->get();

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
        $purchaseRequests = $query->latest('created_at')->paginate(20);

        return view('purchase-requests.all', compact('purchaseRequests'));
    }

    /**
     * Show the form for creating a new purchase request
     */
    public function create()
    {
        $departments = $this->purchaseRequestService->getDepartments();

        return view('purchase-requests.create', compact('departments'));
    }

    /**
     * Store a newly created purchase request
     */
    public function store(Request $request)
    {
        $rules = $this->purchaseRequestService->getValidationRules();
        $request->validate($rules);

        try {
            $purchaseRequest = $this->purchaseRequestService->createPurchaseRequest($request->all());

            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('success', "Purchase Request {$purchaseRequest->pr_number} has been created successfully.");

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create purchase request: '.$e->getMessage());
        }
    }

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
     * Update the specified purchase request
     */
    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        $rules = $this->purchaseRequestService->getValidationRules();
        $request->validate($rules);

        try {
            $purchaseRequest = $this->purchaseRequestService->updatePurchaseRequest($purchaseRequest, $request->all());

            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('success', 'Purchase request has been updated successfully.');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update purchase request: '.$e->getMessage());
        }
    }

    /**
     * Submit purchase request for approval
     */
    public function submit(PurchaseRequest $purchaseRequest)
    {
        try {
            $purchaseRequest = $this->purchaseRequestService->submitPurchaseRequest($purchaseRequest);

            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('success', 'Purchase request has been submitted for approval and workflow has been created.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit for approval: '.$e->getMessage());
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
            'approvals.approver.primaryDepartment',
        ]);

        // Generate QR codes for PDF
        $qrCodeService = new QrCodeService;
        $qrCodes = $this->generateQrCodesForPdf($purchaseRequest, $qrCodeService);

        return view('purchase-requests.pdf-browser', compact('purchaseRequest', 'qrCodes'));
    }

    /**
     * Generate PDF view for purchase request (public access for Browsershot)
     */
    public function pdfPublic(PurchaseRequest $pr)
    {
        // Load relationships needed for PDF
        $pr->load([
            'user',
            'department',
            'businessUnit',
            'items',
            'approvals.approver.primaryDepartment',
        ]);

        // Generate QR codes for PDF
        $qrCodeService = new QrCodeService;
        $qrCodes = $this->generateQrCodesForPdf($pr, $qrCodeService);

        return view('purchase-requests.pdf-browser', ['purchaseRequest' => $pr, 'qrCodes' => $qrCodes]);
    }

    /**
     * Download PDF for purchase request (public access for Browsershot)
     */
    public function downloadPdfPublic(PurchaseRequest $pr)
    {
        try {
            // Load relationships needed for PDF
            $pr->load([
                'user',
                'department',
                'businessUnit',
                'items',
                'approvals.approver.primaryDepartment',
            ]);

            // For now, skip QR codes to avoid complexity - focus on getting basic PDF working
            $qrCodes = [];

            // Generate HTML content directly using same template as /pdf route
            $html = view('purchase-requests.pdf-browser', [
                'purchaseRequest' => $pr,
                'qrCodes' => $qrCodes
            ])->render();

            // Get Browsershot configuration
            $config = config('pdf.browsershot');

            $browsershot = \Spatie\Browsershot\Browsershot::html($html)
                ->format($config['format'])
                ->landscape()
                ->margins(
                    $config['margins']['top'],
                    $config['margins']['right'],
                    $config['margins']['bottom'],
                    $config['margins']['left']
                )
                ->timeout($config['timeout'])
                ->noSandbox()
                ->dismissDialogs();

            if ($config['print_background']) {
                $browsershot->showBackground();
            }

            if (isset($config['scale'])) {
                $browsershot->scale($config['scale']);
            }

            // Disable network-related features that might cause timeouts
            $browsershot->setOption('args', ['--disable-web-security', '--disable-extensions', '--no-first-run']);

            $pdf = $browsershot->pdf();

            // Clean filename by removing invalid characters
            $cleanPrNumber = preg_replace('/[\/\\\\:*?"<>|]/', '-', $pr->pr_number);
            $filename = 'PR-'.$cleanPrNumber.'.pdf';

            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');

        } catch (\Exception $e) {
            \Log::error('PDF generation failed: '.$e->getMessage());
            
            // Return error response
            return response()->json([
                'error' => 'PDF generation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simple PDF view for testing (public access)
     */
    public function pdfSimpleTest(PurchaseRequest $pr)
    {
        // Load relationships needed for PDF
        $pr->load([
            'user',
            'department',
            'businessUnit',
            'items',
            'approvals.approver',
        ]);

        return view('purchase-requests.pdf-simple-test', ['purchaseRequest' => $pr]);
    }

    /**
     * Debug PDF data loading
     */
    public function pdfDebug(PurchaseRequest $pr)
    {
        // Load relationships needed for PDF
        $pr->load([
            'user',
            'department',
            'businessUnit',
            'items',
            'approvals.approver.primaryDepartment',
        ]);

        return view('purchase-requests.pdf-debug', ['purchaseRequest' => $pr]);
    }

    /**
     * Download PDF for purchase request using Browsershot
     */
    public function downloadPdf(PurchaseRequest $purchaseRequest)
    {
        try {
            // Load relationships needed for PDF
            $purchaseRequest->load([
                'user',
                'department',
                'businessUnit',
                'items',
                'approvals.approver.primaryDepartment',
            ]);

            // Generate QR codes for PDF to match original layout
            $qrCodeService = new QrCodeService;
            $qrCodes = $this->generateQrCodesForPdf($purchaseRequest, $qrCodeService);

            // Generate HTML content directly using same template as /pdf route
            $html = view('purchase-requests.pdf-browser', [
                'purchaseRequest' => $purchaseRequest,
                'qrCodes' => $qrCodes
            ])->render();

            // Get Browsershot configuration
            $config = config('pdf.browsershot');

            $browsershot = \Spatie\Browsershot\Browsershot::html($html)
                ->format($config['format'])
                ->landscape()
                ->margins(
                    $config['margins']['top'],
                    $config['margins']['right'],
                    $config['margins']['bottom'],
                    $config['margins']['left']
                )
                ->timeout($config['timeout'])
                ->noSandbox()
                ->dismissDialogs();

            if ($config['print_background']) {
                $browsershot->showBackground();
            }

            if (isset($config['scale'])) {
                $browsershot->scale($config['scale']);
            }

            // Disable network-related features that might cause timeouts
            $browsershot->setOption('args', ['--disable-web-security', '--disable-extensions', '--no-first-run']);

            $pdf = $browsershot->pdf();

            // Clean filename by removing invalid characters
            $cleanPrNumber = preg_replace('/[\/\\\\:*?"<>|]/', '-', $purchaseRequest->pr_number);
            $filename = 'PR-'.$cleanPrNumber.'.pdf';

            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');

        } catch (\Exception $e) {
            \Log::error('PDF generation failed: '.$e->getMessage());
            
            // Return error response
            return response()->json([
                'error' => 'PDF generation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF using Browsershot
     */
    private function generateBrowsershotPdf($purchaseRequest, $qrCodes, $filename)
    {
        try {
            // Generate the full URL for the PDF view
            $baseUrl = config('app.url');
            if ($baseUrl === 'http://localhost') {
                $baseUrl = 'http://localhost:8000';
            }

            $url = $baseUrl.'/purchase-requests/'.$purchaseRequest->id.'/pdf-public';

            // Get Browsershot configuration
            $config = config('pdf.browsershot');

            $browsershot = Browsershot::url($url)
                ->format($config['format'])
                ->landscape()
                ->margins(
                    $config['margins']['top'],
                    $config['margins']['right'],
                    $config['margins']['bottom'],
                    $config['margins']['left']
                )
                ->timeout($config['timeout']);

            if ($config['wait_until_network_idle']) {
                $browsershot->waitUntilNetworkIdle();
            }

            if ($config['print_background']) {
                $browsershot->showBackground();
            }

            if (isset($config['scale'])) {
                $browsershot->scale($config['scale']);
            }

            $pdf = $browsershot->pdf();

            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');

        } catch (\Exception $e) {
            \Log::error('Browsershot PDF generation failed: '.$e->getMessage());
            
            return response()->json([
                'error' => 'PDF generation failed',
                'message' => 'Unable to generate PDF. Please contact system administrator.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function generatePdfDirectly($purchaseRequest, $qrCodes, $filename)
    {
        try {
            // Generate HTML content directly
            $html = view('purchase-requests.pdf-simple-test', [
                'purchaseRequest' => $purchaseRequest,
                'qrCodes' => $qrCodes
            ])->render();

            // Get Browsershot configuration
            $config = config('pdf.browsershot');

            $browsershot = Browsershot::html($html)
                ->format($config['format'])
                ->landscape()
                ->margins(
                    $config['margins']['top'],
                    $config['margins']['right'],
                    $config['margins']['bottom'],
                    $config['margins']['left']
                )
                ->timeout($config['timeout'])
                ->noSandbox()
                ->dismissDialogs();

            if ($config['print_background']) {
                $browsershot->showBackground();
            }

            if (isset($config['scale'])) {
                $browsershot->scale($config['scale']);
            }

            // Disable network-related features that might cause timeouts
            $browsershot->setOption('args', ['--disable-web-security', '--disable-extensions', '--no-first-run']);

            $pdf = $browsershot->pdf();

            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');

        } catch (\Exception $e) {
            \Log::error('PDF generation failed: '.$e->getMessage());
            
            // Fallback to a simple text-based response
            return response()
                ->view('purchase-requests.pdf-fallback', ['purchaseRequest' => $purchaseRequest])
                ->header('Content-Type', 'text/html');
        }
    }



    /**
     * Generate QR codes for PDF
     */
    protected function generateQrCodesForPdf(PurchaseRequest $purchaseRequest, QrCodeService $qrCodeService): array
    {
        $qrCodes = [];

        // Generate QR code for requestor
        $qrCodes['requestor'] = $qrCodeService->generateRequestorQrCodeDataUrl($purchaseRequest);

        // Generate QR codes for each approval
        $qrCodes['approvals'] = [];
        foreach ($purchaseRequest->approvals as $approval) {
            $qrCodes['approvals'][$approval->id] = $qrCodeService->generateApproverQrCodeDataUrl($approval);
        }

        return $qrCodes;
    }
}
