<?php

namespace App\Http\Controllers;

use App\Models\Modules\WNS\PurchaseRequest;
use App\Models\Modules\WNS\PrItem;
use App\Models\Department;
use App\Services\Modules\WNS\PRNumberingService;
use App\Services\Modules\WNS\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchaseRequestController extends Controller
{
    protected PRNumberingService $numberingService;
    protected ApprovalWorkflowService $workflowService;
    
    public function __construct(PRNumberingService $numberingService, ApprovalWorkflowService $workflowService)
    {
        $this->numberingService = $numberingService;
        $this->workflowService = $workflowService;
    }

    /**
     * Display a listing of user's purchase requests
     */
    public function index()
    {
        $purchaseRequests = PurchaseRequest::with(['department', 'items'])
            ->byUser(Auth::id())
            ->where('business_unit_id', session('current_business_unit_id'))
            ->latest('created_at')
            ->paginate(15);

        return view('purchase-requests.index', compact('purchaseRequests'));
    }

    /**
     * Display all purchase requests (for managers/admins)
     */
    public function all()
    {
        $purchaseRequests = PurchaseRequest::with(['department', 'user', 'items'])
            ->where('business_unit_id', session('current_business_unit_id'))
            ->latest('created_at')
            ->paginate(20);

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
}