<?php

namespace App\Http\Controllers;

use App\Models\Modules\WNS\PurchaseRequest;
use App\Models\Modules\WNS\PrItem;
use App\Models\Department;
use App\Services\UniversalPRNumberingService;
use App\Services\Modules\WNS\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\QrCodeService;

class PurchaseRequestController extends Controller
{
    protected UniversalPRNumberingService $numberingService;
    protected ApprovalWorkflowService $workflowService;

    public function __construct(UniversalPRNumberingService $numberingService, ApprovalWorkflowService $workflowService)
    {
        $this->numberingService = $numberingService;
        $this->workflowService = $workflowService;
    }

    /**
     * Display a listing of purchase requests and reservations based on user hierarchy
     */
    public function index()
    {
        $user = Auth::user();
        $accessLevel = $user->getAccessLevel();
        
        // Get Purchase Requests
        $prQuery = PurchaseRequest::with(['department', 'user', 'items'])
            ->where('business_unit_id', session('current_business_unit_id'));

        // Get PR Number Reservations
        $reservationQuery = \App\Models\PrNumberReservation::with(['businessUnit', 'department', 'user', 'purchaseRequest'])
            ->where('business_unit_id', session('current_business_unit_id'));

        // Apply hierarchy-based filtering to both queries
        switch ($accessLevel) {
            case 'super_admin':
            case 'director':
                // Can see all PRs and reservations in the business unit
                break;
                
            case 'department_head':
                // Department head can see all PRs and reservations in their department
                $prQuery->where('department_id', $user->primary_department_id);
                $reservationQuery->where('department_id', $user->primary_department_id);
                break;
                
            case 'team_leader':
                // Team leader can see their own + subordinates' PRs and reservations
                $subordinateIds = $user->activeSubordinates()->pluck('id')->toArray();
                $subordinateIds[] = $user->id; // Include own items
                $prQuery->whereIn('user_id', $subordinateIds);
                $reservationQuery->whereIn('user_id', $subordinateIds);
                break;
                
            case 'staff':
            default:
                // Staff can only see their own PRs and reservations
                $prQuery->byUser($user->id);
                $reservationQuery->byUser($user->id);
                break;
        }

        $purchaseRequests = $prQuery->latest('created_at')->get();
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
        if (!in_array($accessLevel, ['super_admin', 'director', 'department_head'])) {
            return redirect()->route('purchase-requests.index')
                ->with('error', 'You do not have permission to view all purchase requests.');
        }
        
        $query = PurchaseRequest::with(['department', 'user', 'items'])
            ->where('business_unit_id', session('current_business_unit_id'));

        // Apply hierarchy-based filtering even for "all" view
        switch ($accessLevel) {
            case 'super_admin':
            case 'director':
                // Can see all PRs in the business unit
                break;
                
            case 'department_head':
                // Department head can see all PRs in their department
                $query->where('department_id', $user->primary_department_id);
                break;
        }

        $purchaseRequests = $query->latest('created_at')->paginate(20);

        return view('purchase-requests.all', compact('purchaseRequests'));
    }

    /**
     * Show the form for creating a new purchase request
     */
    public function create()
    {
        $departments = Department::where('business_unit_id', session('current_business_unit_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('purchase-requests.create', compact('departments'));
    }

    /**
     * Store a newly created purchase request
     */
    public function store(Request $request)
    {
        $request->validate([
            'keperluan' => 'required|string|max:500',
            'used_for' => 'required|string|max:1000',
            'date_of_request' => 'required|date',
            'designated_date' => 'nullable|date|after_or_equal:date_of_request',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.brand_name' => 'nullable|string|max:255',
            'items.*.item_description' => 'nullable|string|max:1000',
            'items.*.supplier_name' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.currency' => 'required|string|in:IDR,USD,EUR',
            'items.*.expense_department_id' => 'required|exists:departments,id',
        ]);

        DB::beginTransaction();
        
        try {
            // Generate PR number
            $prNumber = $this->numberingService->generatePRNumber(
                Auth::user(),
                Carbon::parse($request->date_of_request)
            );

            // Create purchase request
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => $prNumber['formatted_number'],
                'business_unit_id' => session('current_business_unit_id'),
                'department_id' => session('current_department_id'),
                'user_id' => Auth::id(),
                'sequence_id' => $prNumber['sequence_id'],
                'keperluan' => $request->keperluan,
                'used_for' => $request->used_for,
                'date_of_request' => $request->date_of_request,
                'designated_date' => $request->designated_date,
                'status' => 'draft',
                'currency' => $request->items[0]['currency'], // Use first item's currency
                'last_modified_by' => Auth::id(),
            ]);

            // Create PR items
            foreach ($request->items as $index => $itemData) {
                PrItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'item_order' => $index + 1,
                    'item_name' => $itemData['item_name'],
                    'brand_name' => $itemData['brand_name'],
                    'item_description' => $itemData['item_description'],
                    'supplier_name' => $itemData['supplier_name'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price' => $itemData['unit_price'],
                    'currency' => $itemData['currency'],
                    'expense_department_id' => $itemData['expense_department_id'],
                ]);
            }

            // Update total amount
            $purchaseRequest->updateTotalAmount();

            DB::commit();

            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('success', "Purchase Request {$prNumber['formatted_number']} has been created successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to create purchase request: ' . $e->getMessage());
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
        if (!$purchaseRequest->canBeEdited()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'This purchase request cannot be edited.');
        }

        $departments = Department::where('business_unit_id', session('current_business_unit_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $purchaseRequest->load(['items']);

        return view('purchase-requests.edit', compact('purchaseRequest', 'departments'));
    }

    /**
     * Update the specified purchase request
     */
    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        if (!$purchaseRequest->canBeEdited()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'This purchase request cannot be edited.');
        }

        $request->validate([
            'keperluan' => 'required|string|max:500',
            'used_for' => 'required|string|max:1000',
            'date_of_request' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.brand_name' => 'nullable|string|max:255',
            'items.*.item_description' => 'nullable|string|max:1000',
            'items.*.supplier_name' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.currency' => 'required|string|in:IDR,USD,EUR',
            'items.*.expense_department_id' => 'required|exists:departments,id',
        ]);

        DB::beginTransaction();
        
        try {
            // Update purchase request
            $purchaseRequest->update([
                'keperluan' => $request->keperluan,
                'used_for' => $request->used_for,
                'date_of_request' => $request->date_of_request,
                'currency' => $request->items[0]['currency'],
                'last_modified_by' => Auth::id(),
            ]);

            // Delete existing items
            $purchaseRequest->items()->delete();

            // Create new items
            foreach ($request->items as $index => $itemData) {
                PrItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'item_order' => $index + 1,
                    'item_name' => $itemData['item_name'],
                    'brand_name' => $itemData['brand_name'],
                    'item_description' => $itemData['item_description'],
                    'supplier_name' => $itemData['supplier_name'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price' => $itemData['unit_price'],
                    'currency' => $itemData['currency'],
                    'expense_department_id' => $itemData['expense_department_id'],
                ]);
            }

            // Update total amount
            $purchaseRequest->updateTotalAmount();

            // Reset approvals if not in draft
            $purchaseRequest->resetApprovals(Auth::user());

            DB::commit();

            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('success', 'Purchase request has been updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to update purchase request: ' . $e->getMessage());
        }
    }

    /**
     * Submit purchase request for approval
     */
    public function submit(PurchaseRequest $purchaseRequest)
    {
        if (!$purchaseRequest->canBeSubmitted()) {
            return back()->with('error', 'This purchase request cannot be submitted.');
        }

        DB::beginTransaction();
        
        try {
            // Update status to submitted
            $purchaseRequest->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
            
            // Create approval workflow
            $this->workflowService->createWorkflow($purchaseRequest);
            
            DB::commit();
            
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('success', 'Purchase request has been submitted for approval and workflow has been created.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to submit for approval: ' . $e->getMessage());
        }
    }

    /**
     * Void purchase request
     */
    public function void(Request $request, PurchaseRequest $purchaseRequest)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        if (!$purchaseRequest->canBeVoided()) {
            return back()->with('error', 'This purchase request cannot be voided.');
        }

        $purchaseRequest->void(Auth::user(), $request->reason);

        return redirect()
            ->route('purchase-requests.index')
            ->with('success', 'Purchase request has been voided.');
    }

    /**
     * Remove the specified purchase request
     */
    public function destroy(PurchaseRequest $purchaseRequest)
    {
        if (!$purchaseRequest->canBeEdited()) {
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
            'approvals.approver'
        ]);

        // Generate QR codes for PDF
        $qrCodeService = new QrCodeService();
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
            'approvals.approver'
        ]);

        // Generate QR codes for PDF
        $qrCodeService = new QrCodeService();
        $qrCodes = $this->generateQrCodesForPdf($purchaseRequest, $qrCodeService);

        // Generate PDF using DomPDF
        $pdf = Pdf::loadView('purchase-requests.pdf-simple', compact('purchaseRequest', 'qrCodes'));
        $pdf->setPaper('A4', 'landscape');
        
        // Clean filename by removing invalid characters
        $cleanPrNumber = preg_replace('/[\/\\\\:*?"<>|]/', '-', $purchaseRequest->pr_number);
        $filename = 'PR-' . $cleanPrNumber . '.pdf';
        
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