<?php

namespace App\Http\Controllers;

use App\Models\Modules\WNS\PurchaseRequest;
use App\Services\PurchaseRequestService;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $purchaseRequests = $query->latest('created_at')->get();

        // Get PR Number Reservations using same hierarchy logic
        $user = Auth::user();
        $accessLevel = $user->getAccessLevel();

        $reservationQuery = \App\Models\PrNumberReservation::with(['businessUnit', 'department', 'user', 'purchaseRequest'])
            ->where('business_unit_id', session('current_business_unit_id'));

        // Apply same hierarchy filtering as PR
        switch ($accessLevel) {
            case 'super_admin':
            case 'director':
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
        if (! in_array($accessLevel, ['super_admin', 'director', 'department_head'])) {
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

        $departments = $this->purchaseRequestService->getDepartments();
        $purchaseRequest->load(['items']);

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
            'approvals.approver',
        ]);

        // Generate QR codes for PDF
        $qrCodeService = new QrCodeService;
        $qrCodes = $this->generateQrCodesForPdf($purchaseRequest, $qrCodeService);

        return view('purchase-requests.pdf-simple', compact('purchaseRequest', 'qrCodes'));
    }

    /**
     * Download PDF for purchase request
     */
    public function downloadPdf(PurchaseRequest $purchaseRequest)
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

        // Generate PDF using DomPDF
        $pdf = Pdf::loadView('purchase-requests.pdf-simple', compact('purchaseRequest', 'qrCodes'));
        $pdf->setPaper('A4', 'landscape');

        // Clean filename by removing invalid characters
        $cleanPrNumber = preg_replace('/[\/\\\\:*?"<>|]/', '-', $purchaseRequest->pr_number);
        $filename = 'PR-'.$cleanPrNumber.'.pdf';

        return $pdf->download($filename);
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
