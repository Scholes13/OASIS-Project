<?php

namespace App\Http\Controllers\Modules\Purchasing\PurchaseRequest;

use App\Http\Controllers\Controller;
use App\Models\Core\BusinessUnit;
use App\Models\Modules\Purchasing\PurchaseRequest\PrNumberReservation;
use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use App\Services\Core\EmailNotificationService;
use App\Services\Core\QrCodeService;
use App\Services\Modules\Purchasing\PurchaseRequest\ApprovalWorkflowService;
use App\Services\Modules\Purchasing\PurchaseRequest\PurchaseRequestService;
use App\Services\Modules\Purchasing\PurchaseRequest\UniversalPRNumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PurchaseRequestController extends Controller
{
    protected PurchaseRequestService $purchaseRequestService;

    protected ApprovalWorkflowService $approvalWorkflowService;

    protected UniversalPRNumberingService $numberingService;

    public function __construct(
        PurchaseRequestService $purchaseRequestService,
        ApprovalWorkflowService $approvalWorkflowService,
        UniversalPRNumberingService $numberingService
    ) {
        $this->purchaseRequestService = $purchaseRequestService;
        $this->approvalWorkflowService = $approvalWorkflowService;
        $this->numberingService = $numberingService;
    }

    /**
     * Display a listing of the user's purchase requests.
     * Requirements: 2.1, 12.1, 12.6
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $businessUnitId = (int) session('current_business_unit_id');

        // Parse filters from request
        $filters = [
            'search' => $request->get('search', ''),
            'status' => $request->get('status', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
        ];

        // Build query with eager loading to prevent N+1 queries
        $query = PurchaseRequest::with([
            'department:id,name,code',
            'user:id,name,email',
            'category:id,name,code,color',
        ])
            ->withCount('items')
            ->withCount('approvals')
            ->withCount(['approvals as approved_approvals_count' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->where('business_unit_id', $businessUnitId)
            ->where('user_id', $user->id);

        // Apply search filter
        if ($filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('pr_number', 'like', "%{$search}%")
                    ->orWhere('used_for', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        // Apply date range filter
        if ($filters['date_from']) {
            $query->whereDate('date_of_request', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->whereDate('date_of_request', '<=', $filters['date_to']);
        }

        // Get paginated results with sorting
        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $purchaseRequests = $query
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($request->get('per_page', 10))
            ->withQueryString();

        // Transform data to include authorization props
        $purchaseRequests->through(function ($pr) use ($user) {
            return $this->transformPurchaseRequest($pr, $user);
        });

        // Get reservations for the user
        $reservations = $this->getReservations($user, $businessUnitId, $filters['search']);

        // Get available statuses for filter dropdown
        $statuses = [
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'submitted', 'label' => 'Submitted'],
            ['value' => 'in_approval', 'label' => 'In Approval'],
            ['value' => 'approved', 'label' => 'Approved'],
            ['value' => 'rejected', 'label' => 'Rejected'],
            ['value' => 'voided', 'label' => 'Voided'],
        ];

        return Inertia::render('Purchasing/PurchaseRequest/Index', [
            'purchaseRequests' => $purchaseRequests,
            'reservations' => $reservations,
            'filters' => $filters,
            'statuses' => $statuses,
            'can' => [
                'create' => true, // All authenticated users can create PRs
                'viewAll' => $user->can('view-all-purchase-requests') || $user->isSuperAdmin(),
                'export' => $user->can('export-purchase-requests') || $user->isSuperAdmin(),
            ],
        ]);
    }

    /**
     * Display all purchase requests in the current business unit.
     * All users registered to a business unit can view all PRs in that unit.
     * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7
     */
    public function all(Request $request): Response
    {
        $user = Auth::user();
        $businessUnitId = (int) session('current_business_unit_id');

        // Verify user has access to this business unit
        $userBusinessUnitIds = $user->getAccessibleBusinessUnitIds();

        if (! $businessUnitId || ! in_array($businessUnitId, $userBusinessUnitIds)) {
            return redirect()->route('purchase-requests.index')
                ->with('error', 'You do not have access to this business unit.');
        }

        // For c_level/executive users, expand to include all descendant BUs
        $filterBusinessUnitIds = [$businessUnitId];
        if ($user->hasTopManagementAccess()) {
            $bu = BusinessUnit::find($businessUnitId);
            if ($bu) {
                $filterBusinessUnitIds = $bu->getAccessibleBusinessUnits();
            }
        }

        // Parse filters from request
        $filters = [
            'search' => $request->get('search', ''),
            'status' => $request->get('status', ''),
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
            'department_id' => $request->get('department_id', ''),
        ];

        // Build query with eager loading to prevent N+1 queries
        $query = PurchaseRequest::with([
            'department:id,name,code',
            'user:id,name,email',
            'category:id,name,code,color',
        ])
            ->withCount('items')
            ->withCount('approvals')
            ->withCount(['approvals as approved_approvals_count' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->whereIn('business_unit_id', $filterBusinessUnitIds);

        // Apply search filter - Requirements: 4.3
        if ($filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('pr_number', 'like', "%{$search}%")
                    ->orWhere('used_for', 'like', "%{$search}%");
            });
        }

        // Apply status filter - Requirements: 4.3
        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        // Apply date range filter - Requirements: 4.4
        if ($filters['date_from']) {
            $query->whereDate('date_of_request', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->whereDate('date_of_request', '<=', $filters['date_to']);
        }

        // Apply department filter - Requirements: 4.5
        if ($filters['department_id']) {
            $query->where('department_id', $filters['department_id']);
        }

        // Get paginated results with sorting
        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $purchaseRequests = $query
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        // Transform data to include authorization props
        $purchaseRequests->through(function ($pr) use ($user) {
            return $this->transformPurchaseRequest($pr, $user);
        });

        // Get departments for filter dropdown (from all accessible BUs)
        $departments = \App\Models\Core\Department::whereIn('business_unit_id', $filterBusinessUnitIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('Purchasing/PurchaseRequest/All', [
            'purchaseRequests' => $purchaseRequests,
            'filters' => $filters,
            'departments' => $departments,
            'can' => [
                'export' => $user->can('export-purchase-requests') || $user->isSuperAdmin(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new purchase request.
     * Requirements: 3.1, 3.2
     */
    public function create(): Response
    {
        $user = Auth::user();
        $businessUnitId = (int) session('current_business_unit_id');
        $departmentId = (int) session('current_department_id');

        // Get categories
        $categories = \App\Models\Modules\Purchasing\PurchaseRequest\PrCategory::active()
            ->ordered()
            ->get(['id', 'name', 'code', 'color', 'description']);

        // Get departments for current business unit
        $departments = \App\Models\Core\Department::where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        // Get business units (for context switching)
        $businessUnits = $user->activeBusinessUnits()
            ->with('businessUnit:id,name,code')
            ->get()
            ->pluck('businessUnit')
            ->filter();

        // Get available approvers (users in the same business unit AND all ancestor business units)
        $approverBusinessUnitIds = [$businessUnitId];

        // Include all ancestor business unit users as potential approvers
        // Traverse the full parent chain up to the root to include executives from parent groups
        $currentBusinessUnit = \App\Models\Core\BusinessUnit::find($businessUnitId);
        $visited = [$businessUnitId]; // Cycle detection
        while ($currentBusinessUnit && $currentBusinessUnit->parent_id) {
            if (in_array($currentBusinessUnit->parent_id, $visited)) {
                break; // Prevent infinite loop from circular references
            }
            $approverBusinessUnitIds[] = $currentBusinessUnit->parent_id;
            $visited[] = $currentBusinessUnit->parent_id;
            $currentBusinessUnit = \App\Models\Core\BusinessUnit::find($currentBusinessUnit->parent_id);
        }

        $availableApprovers = \App\Models\Core\User::whereHas('activeBusinessUnits', function ($query) use ($approverBusinessUnitIds) {
            $query->whereIn('business_unit_id', $approverBusinessUnitIds);
        })
            ->with(['primaryPosition:id,name', 'primaryDepartment:id,name'])
            ->where('id', '!=', $user->id) // Exclude current user
            ->where('global_role', '!=', 'super_admin') // Exclude system admin accounts
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'primary_position_id', 'primary_department_id'])
            ->map(function ($approver) {
                return [
                    'id' => $approver->id,
                    'name' => $approver->name,
                    'email' => $approver->email,
                    'position' => $approver->primaryPosition?->name ?? 'N/A',
                    'department' => $approver->primaryDepartment?->name ?? 'N/A',
                ];
            });

        return Inertia::render('Purchasing/PurchaseRequest/Create', [
            'categories' => $categories,
            'departments' => $departments,
            'businessUnits' => $businessUnits,
            'availableApprovers' => $availableApprovers,
            'currentBusinessUnitId' => $businessUnitId,
            'currentDepartmentId' => $departmentId,
        ]);
    }

    /**
     * Store a newly created purchase request.
     * Requirements: 3.10, 3.11, 14.1, 14.3
     */
    public function store(\App\Http\Requests\Purchasing\StorePurchaseRequestRequest $request)
    {
        $user = Auth::user();

        try {
            DB::beginTransaction();

            // Generate PR number
            $prNumber = $this->numberingService->generatePRNumber(
                $user,
                $request->business_unit_id,
                null,
                \Carbon\Carbon::parse($request->date_of_request)
            );

            // Handle supporting document upload
            $supportingDocumentPath = null;
            $supportingDocumentName = null;
            if ($request->hasFile('supporting_document')) {
                $file = $request->file('supporting_document');
                $supportingDocumentName = $file->getClientOriginalName();
                $supportingDocumentPath = $file->store('purchase-requests/supporting-documents', 'public');
            }

            // Create purchase request
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => $prNumber['formatted_number'],
                'business_unit_id' => $request->business_unit_id,
                'department_id' => $request->department_id,
                'category_id' => $request->category_id,
                'user_id' => $user->id,
                'sequence_id' => $prNumber['sequence_id'],
                'used_for' => $request->used_for,
                'date_of_request' => $request->date_of_request,
                'expected_date' => $request->expected_date,
                'designated_date' => $request->expected_date,
                'status' => 'submitted', // Directly submit (no draft step)
                'submitted_at' => now(),
                'currency' => $request->currency,
                'supporting_document_path' => $supportingDocumentPath,
                'supporting_document_name' => $supportingDocumentName,
                'last_modified_by' => $user->id,
            ]);

            // Create PR items
            foreach ($request->items as $index => $itemData) {
                // Handle item image upload
                $imagePath = null;
                if (isset($itemData['image']) && $itemData['image'] instanceof \Illuminate\Http\UploadedFile) {
                    $imagePath = $itemData['image']->store('purchase-requests/items', 'public');
                }

                \App\Models\Modules\Purchasing\PurchaseRequest\PrItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'item_order' => $index + 1,
                    'item_name' => $itemData['item_name'],
                    'brand_name' => $itemData['brand_name'] ?? null,
                    'item_description' => $itemData['item_description'] ?? null,
                    'supplier_name' => $itemData['supplier_name'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price' => $itemData['unit_price'],
                    'currency' => $itemData['currency'],
                    'expense_department_id' => $itemData['expense_department_id'],
                    'image_path' => $imagePath,
                ]);
            }

            // Update total amount
            $purchaseRequest->updateTotalAmount();

            // Create approval workflow
            $this->approvalWorkflowService->createWorkflowFromRequest(
                $purchaseRequest,
                $request->approval_workflow,
                $request->approval_notes
            );

            // Clear dashboard cache
            $this->purchaseRequestService->clearDashboardCache($purchaseRequest);

            DB::commit();

            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('success', 'Purchase request created successfully and submitted for approval.');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to create purchase request', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create purchase request. Please try again or contact support.');
        }
    }

    /**
     * Show the form for editing the specified purchase request.
     * Requirements: 3.1, 3.2
     */
    public function editInertia(PurchaseRequest $purchaseRequest): Response
    {
        $user = Auth::user();

        // Check if PR can be edited
        if (! $purchaseRequest->canBeEdited()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'This purchase request cannot be edited.');
        }

        // Validate business unit context
        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this purchase request.');
        }

        // Check if user owns this PR
        if ($purchaseRequest->user_id !== $user->id) {
            abort(403, 'You are not authorized to edit this purchase request.');
        }

        $businessUnitId = $purchaseRequest->business_unit_id;

        // Load relationships
        $purchaseRequest->load([
            'items.expenseDepartment:id,name,code',
            'category:id,name,code,color',
            'approvals.approver:id,name,email',
        ]);

        // Get categories
        $categories = \App\Models\Modules\Purchasing\PurchaseRequest\PrCategory::active()
            ->ordered()
            ->get(['id', 'name', 'code', 'color', 'description']);

        // Get departments for current business unit
        $departments = \App\Models\Core\Department::where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        // Get business units (for context switching)
        $businessUnits = $user->activeBusinessUnits()
            ->with('businessUnit:id,name,code')
            ->get()
            ->pluck('businessUnit')
            ->filter();

        // Get available approvers (users in the same business unit AND all ancestor business units)
        $approverBusinessUnitIds = [$businessUnitId];

        // Include all ancestor business unit users as potential approvers
        // Traverse the full parent chain up to the root to include executives from parent groups
        $currentBusinessUnit = \App\Models\Core\BusinessUnit::find($businessUnitId);
        $visited = [$businessUnitId]; // Cycle detection
        while ($currentBusinessUnit && $currentBusinessUnit->parent_id) {
            if (in_array($currentBusinessUnit->parent_id, $visited)) {
                break; // Prevent infinite loop from circular references
            }
            $approverBusinessUnitIds[] = $currentBusinessUnit->parent_id;
            $visited[] = $currentBusinessUnit->parent_id;
            $currentBusinessUnit = \App\Models\Core\BusinessUnit::find($currentBusinessUnit->parent_id);
        }

        $availableApprovers = \App\Models\Core\User::whereHas('activeBusinessUnits', function ($query) use ($approverBusinessUnitIds) {
            $query->whereIn('business_unit_id', $approverBusinessUnitIds);
        })
            ->with(['primaryPosition:id,name', 'primaryDepartment:id,name'])
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'primary_position_id', 'primary_department_id'])
            ->map(function ($approver) {
                return [
                    'id' => $approver->id,
                    'name' => $approver->name,
                    'email' => $approver->email,
                    'position' => $approver->primaryPosition?->name ?? 'N/A',
                    'department' => $approver->primaryDepartment?->name ?? 'N/A',
                ];
            });

        // Transform approval workflow for form
        $approvalWorkflow = $purchaseRequest->approvals->map(function ($approval) {
            return [
                'approver_id' => $approval->approver_id,
                'task_type' => $approval->approval_type ?? 'approval',
            ];
        })->toArray();

        return Inertia::render('Purchasing/PurchaseRequest/Form', [
            'mode' => 'edit',
            'purchaseRequest' => array_merge($purchaseRequest->toArray(), [
                'approval_workflow' => $approvalWorkflow,
            ]),
            'categories' => $categories,
            'departments' => $departments,
            'businessUnits' => $businessUnits,
            'availableApprovers' => $availableApprovers,
            'currentBusinessUnitId' => $businessUnitId,
            'currentDepartmentId' => $purchaseRequest->department_id,
        ]);
    }

    /**
     * Update the specified purchase request.
     * Requirements: 3.10, 3.11, 14.1, 14.3
     */
    public function update(\App\Http\Requests\Purchasing\StorePurchaseRequestRequest $request, PurchaseRequest $purchaseRequest)
    {
        $user = Auth::user();

        // Check if PR can be edited
        if (! $purchaseRequest->canBeEdited()) {
            return back()->with('error', 'This purchase request cannot be edited.');
        }

        // Validate business unit context
        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this purchase request.');
        }

        // Check if user owns this PR
        if ($purchaseRequest->user_id !== $user->id) {
            abort(403, 'You are not authorized to edit this purchase request.');
        }

        try {
            DB::beginTransaction();

            // Handle supporting document upload
            $supportingDocumentPath = $purchaseRequest->supporting_document_path;
            $supportingDocumentName = $purchaseRequest->supporting_document_name;
            if ($request->hasFile('supporting_document')) {
                // Delete old document if exists
                if ($supportingDocumentPath) {
                    \Storage::disk('public')->delete($supportingDocumentPath);
                }

                $file = $request->file('supporting_document');
                $supportingDocumentName = $file->getClientOriginalName();
                $supportingDocumentPath = $file->store('purchase-requests/supporting-documents', 'public');
            }

            // Update purchase request
            $purchaseRequest->update([
                'category_id' => $request->category_id,
                'used_for' => $request->used_for,
                'date_of_request' => $request->date_of_request,
                'expected_date' => $request->expected_date,
                'designated_date' => $request->expected_date,
                'currency' => $request->currency,
                'supporting_document_path' => $supportingDocumentPath,
                'supporting_document_name' => $supportingDocumentName,
                'last_modified_by' => $user->id,
            ]);

            // Delete existing items
            $purchaseRequest->items()->delete();

            // Create new items
            foreach ($request->items as $index => $itemData) {
                // Handle item image upload
                $imagePath = null;
                if (isset($itemData['image']) && $itemData['image'] instanceof \Illuminate\Http\UploadedFile) {
                    $imagePath = $itemData['image']->store('purchase-requests/items', 'public');
                }

                \App\Models\Modules\Purchasing\PurchaseRequest\PrItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'item_order' => $index + 1,
                    'item_name' => $itemData['item_name'],
                    'brand_name' => $itemData['brand_name'] ?? null,
                    'item_description' => $itemData['item_description'] ?? null,
                    'supplier_name' => $itemData['supplier_name'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price' => $itemData['unit_price'],
                    'currency' => $itemData['currency'],
                    'expense_department_id' => $itemData['expense_department_id'],
                    'image_path' => $imagePath,
                ]);
            }

            // Update total amount
            $purchaseRequest->updateTotalAmount();

            // Reset and recreate approval workflow
            $this->approvalWorkflowService->resetWorkflow($purchaseRequest);
            $this->approvalWorkflowService->createWorkflowFromRequest(
                $purchaseRequest,
                $request->approval_workflow,
                $request->approval_notes
            );

            // Update status to submitted
            $purchaseRequest->update([
                'status' => 'submitted',
                'submitted_at' => $purchaseRequest->submitted_at ?? now(), // Preserve original if exists
                'rejected_at' => null,
            ]);

            // Clear dashboard cache
            $this->purchaseRequestService->clearDashboardCache($purchaseRequest);

            DB::commit();

            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('success', 'Purchase request updated successfully and resubmitted for approval.');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to update purchase request', [
                'pr_id' => $purchaseRequest->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update purchase request. Please try again or contact support.');
        }
    }

    /**
     * Display the specified purchase request.
     * Requirements: 8.1, 8.3, 8.7
     */
    public function show(PurchaseRequest $purchaseRequest): Response
    {
        $user = Auth::user();

        // Validate business unit context
        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this purchase request.');
        }

        // Load relationships with eager loading
        $purchaseRequest->load([
            'businessUnit:id,name,code',
            'department:id,name,code',
            'category:id,name,code,color',
            'user:id,name,email',
            'items.expenseDepartment:id,name,code',
            'approvals.approver:id,name,email',
            'lastModifiedBy:id,name',
            'offlineApprovedBy:id,name',
        ]);

        // Get approval progress
        $approvalProgress = $purchaseRequest->getApprovalProgress();

        // Get authorization props
        $authorization = $this->getShowAuthorization($purchaseRequest, $user);

        return Inertia::render('Purchasing/PurchaseRequest/Show', [
            'purchaseRequest' => array_merge(
                $purchaseRequest->toArray(),
                [
                    'approval_progress' => $approvalProgress,
                    'can' => $authorization,
                ]
            ),
            'can' => $authorization,
        ]);
    }

    /**
     * Approve a purchase request (Inertia endpoint)
     * Requirements: 8.4, 8.5
     */
    public function approve(Request $request, PurchaseRequest $purchaseRequest)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        // Validate business unit context
        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            return back()->with('error', 'You do not have access to this purchase request.');
        }

        // Check if PR can be approved
        if (! $purchaseRequest->canBeApproved()) {
            return back()->with('error', 'This purchase request cannot be approved at this time.');
        }

        // Get current approval for this user
        $currentApproval = $purchaseRequest->currentApproval();

        if (! $currentApproval || $currentApproval->approver_id !== $user->id) {
            return back()->with('error', 'You are not authorized to approve this purchase request at this step.');
        }

        try {
            // Process approval using workflow service
            $this->approvalWorkflowService->processApproval(
                $currentApproval,
                'approved',
                $request->notes
            );

            // Clear dashboard cache
            $this->purchaseRequestService->clearDashboardCache($purchaseRequest);

            return back()->with('success', 'Purchase request approved successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to approve purchase request', [
                'pr_id' => $purchaseRequest->id,
                'pr_number' => $purchaseRequest->pr_number,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to approve purchase request. Please try again or contact support.');
        }
    }

    /**
     * Reject a purchase request (Inertia endpoint)
     * Requirements: 8.4, 8.5
     */
    public function reject(Request $request, PurchaseRequest $purchaseRequest)
    {
        $request->validate([
            'notes' => 'required|string|max:1000',
        ], [
            'notes.required' => 'Rejection reason is required.',
        ]);

        $user = Auth::user();

        // Validate business unit context
        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            return back()->with('error', 'You do not have access to this purchase request.');
        }

        // Check if PR can be approved (same check for rejection)
        if (! $purchaseRequest->canBeApproved()) {
            return back()->with('error', 'This purchase request cannot be rejected at this time.');
        }

        // Get current approval for this user
        $currentApproval = $purchaseRequest->currentApproval();

        if (! $currentApproval || $currentApproval->approver_id !== $user->id) {
            return back()->with('error', 'You are not authorized to reject this purchase request at this step.');
        }

        try {
            // Process rejection using workflow service
            $this->approvalWorkflowService->processApproval(
                $currentApproval,
                'rejected',
                $request->notes
            );

            // Clear dashboard cache
            $this->purchaseRequestService->clearDashboardCache($purchaseRequest);

            return back()->with('success', 'Purchase request rejected successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to reject purchase request', [
                'pr_id' => $purchaseRequest->id,
                'pr_number' => $purchaseRequest->pr_number,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to reject purchase request. Please try again or contact support.');
        }
    }

    /**
     * Show the form for editing the specified purchase request.
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

        return view('purchasing.purchase-requests.edit', compact('purchaseRequest', 'departments'));
    }

    /**
     * Resubmit rejected purchase request (reset workflow).
     */
    public function resubmit(PurchaseRequest $purchaseRequest)
    {
        // Check if PR can be resubmitted (must be rejected)
        if ($purchaseRequest->status !== 'rejected') {
            return back()->with('error', 'Only rejected purchase requests can be resubmitted.');
        }

        // Validate business unit context (prevent cross-tenant access)
        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this purchase request.');
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
            Log::error('Failed to resubmit purchase request', [
                'pr_id' => $purchaseRequest->id,
                'pr_number' => $purchaseRequest->pr_number,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to resubmit purchase request. Please try again or contact support.');
        }
    }

    /**
     * Resend approval email to current pending approver.
     */
    public function resendApprovalEmail(PurchaseRequest $purchaseRequest)
    {
        $user = Auth::user();

        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this purchase request.');
        }

        if ($purchaseRequest->user_id !== $user->id) {
            abort(403, 'Only the PR owner can resend approval email.');
        }

        if ($purchaseRequest->status !== 'in_approval') {
            return back()->with('error', 'Approval email can only be resent when PR is in approval process.');
        }

        $currentApproval = $purchaseRequest->currentApproval();
        if (! $currentApproval || $currentApproval->status !== 'pending') {
            return back()->with('error', 'No active approval step found. The approval workflow may need to be rebuilt. Please contact a purchasing administrator.');
        }

        try {
            $emailService = app(EmailNotificationService::class);
            $emailSent = $emailService->sendApprovalRequested($currentApproval);

            if (! $emailSent) {
                return back()->with('error', 'Failed to resend approval email. Please check notification settings and try again.');
            }

            return back()->with('success', 'Approval email has been resent to the current approver.');

        } catch (\Exception $e) {
            Log::error('Failed to resend purchase request approval email', [
                'pr_id' => $purchaseRequest->id,
                'pr_number' => $purchaseRequest->pr_number,
                'approval_id' => $currentApproval->id,
                'requestor_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to resend approval email. Please try again or contact support.');
        }
    }

    /**
     * Void purchase request.
     */
    public function void(Request $request, PurchaseRequest $purchaseRequest)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Validate business unit context (prevent cross-tenant access)
        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this purchase request.');
        }

        // Check if PR can be voided (not already approved/rejected/voided)
        if (in_array($purchaseRequest->status, ['approved', 'voided'])) {
            return back()->with('error', 'This purchase request cannot be voided.');
        }

        // Authorization: Only owner or admin can void
        $user = Auth::user();
        $canVoid = $purchaseRequest->user_id === $user->id ||
            in_array($user->getAccessLevel(), ['super_admin', 'executive', 'general_manager']);

        if (! $canVoid) {
            abort(403, 'You are not authorized to void this purchase request.');
        }

        try {
            $this->purchaseRequestService->voidPurchaseRequest($purchaseRequest, $request->reason);

            return redirect()
                ->route('purchase-requests.index')
                ->with('success', 'Purchase request has been voided.');

        } catch (\Exception $e) {
            Log::error('Failed to void purchase request', [
                'pr_id' => $purchaseRequest->id,
                'pr_number' => $purchaseRequest->pr_number,
                'user_id' => Auth::id(),
                'reason' => $request->reason,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to void purchase request. Please try again or contact support.');
        }
    }

    /**
     * Mark purchase request as approved offline/manually.
     */
    public function markOfflineApproved(Request $request, PurchaseRequest $purchaseRequest)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
            'offline_approval_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ], [
            'offline_approval_document.required' => 'Bukti approval offline wajib diupload.',
            'offline_approval_document.mimes' => 'Format file harus JPG, PNG, atau PDF.',
            'offline_approval_document.max' => 'Ukuran file maksimal 10MB.',
        ]);

        // Validate business unit context (prevent cross-tenant access)
        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this purchase request.');
        }

        // Check if PR can be marked as offline approved
        if (! in_array($purchaseRequest->status, ['submitted', 'in_approval'])) {
            return back()->with('error', 'This purchase request cannot be marked as offline approved. Only submitted or in-approval PRs are eligible.');
        }

        // Authorization: Only PR owner can mark as offline approved
        $user = Auth::user();
        if ($purchaseRequest->user_id !== $user->id) {
            abort(403, 'Only the PR owner can mark this purchase request as offline approved.');
        }

        try {
            // Handle file upload
            $documentPath = null;
            $documentName = null;
            if ($request->hasFile('offline_approval_document')) {
                $file = $request->file('offline_approval_document');
                $documentName = $file->getClientOriginalName();
                $documentPath = $file->store('offline-approvals/purchase-requests/'.$purchaseRequest->id, 'public');
            }

            $this->purchaseRequestService->markAsOfflineApproved($purchaseRequest, $request->notes, $documentPath, $documentName);

            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('success', 'Purchase request has been marked as approved offline/manually.');

        } catch (\Exception $e) {
            Log::error('Failed to mark purchase request as offline approved', [
                'pr_id' => $purchaseRequest->id,
                'pr_number' => $purchaseRequest->pr_number,
                'user_id' => Auth::id(),
                'notes' => $request->notes,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to mark as offline approved. Please try again or contact support.');
        }
    }

    /**
     * Remove the specified purchase request.
     */
    public function destroy(PurchaseRequest $purchaseRequest)
    {
        // Validate business unit context (prevent cross-tenant access)
        if ($purchaseRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this purchase request.');
        }

        // Check if PR can be deleted
        if (! $purchaseRequest->canBeEdited()) {
            return back()->with('error', 'This purchase request cannot be deleted.');
        }

        // Authorization: Only owner can delete draft/rejected PRs
        if ($purchaseRequest->user_id !== Auth::id()) {
            abort(403, 'You are not authorized to delete this purchase request.');
        }

        $purchaseRequest->delete();

        return redirect()
            ->route('purchase-requests.index')
            ->with('success', 'Purchase request has been deleted.');
    }

    /**
     * Generate PDF view for purchase request.
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

        return view('purchasing.purchase-requests.pdf-browser', compact('purchaseRequest', 'qrCodes'));
    }

    /**
     * Generate PDF view for purchase request - Public access for browsershot.
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
            'offlineApprovedBy',
        ]);

        // Generate QR codes for PDF
        $qrCodeService = new QrCodeService;
        $qrCodes = $this->generateQrCodesForPdf($purchaseRequest, $qrCodeService);

        return view('purchasing.purchase-requests.pdf-browser', compact('purchaseRequest', 'qrCodes'));
    }

    /**
     * Download PDF via public route (no authentication required).
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
            'offlineApprovedBy',
        ]);

        // Generate QR codes for PDF
        $qrCodeService = new QrCodeService;
        $qrCodes = $this->generateQrCodesForPdf($purchaseRequest, $qrCodeService);

        // Clean filename by removing invalid characters
        $cleanPrNumber = preg_replace('/[\/\\\\:*?"<>|]/', '-', $purchaseRequest->pr_number);
        $filename = 'PR-'.$cleanPrNumber.'.pdf';

        try {
            // Generate HTML content directly to avoid URL timeout issues
            $html = view('purchasing.purchase-requests.pdf-browser', compact('purchaseRequest', 'qrCodes'))->render();

            // Generate PDF directly in memory (no temp file needed)
            $browsershot = Browsershot::html($html)
                ->format('A4')
                ->landscape()
                ->margins(10, 10, 10, 10)
                ->timeout(120)
                ->noSandbox()
                ->disableWebSecurity()
                ->setDelay(2000);

            if ($remoteUrl = config('pdf.browsershot.remote_url')) {
                $parsed = parse_url($remoteUrl);
                $browsershot->setRemoteInstance($parsed['host'], $parsed['port'] ?? 9222);
            }

            $pdfContent = $browsershot->pdf();

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
     * Download PDF for purchase request using configured method.
     */
    public function downloadPdf(PurchaseRequest $purchaseRequest)
    {
        // Directly call downloadPdfPublic to avoid redirect issues on hosting
        return $this->downloadPdfPublic($purchaseRequest);
    }

    /**
     * Stream the supporting document for a purchase request.
     */
    public function supportingDocument(PurchaseRequest $purchaseRequest): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        return $this->serveSupportingDocument($purchaseRequest, false);
    }

    /**
     * Download the supporting document for a purchase request.
     */
    public function downloadSupportingDocument(PurchaseRequest $purchaseRequest): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        return $this->serveSupportingDocument($purchaseRequest, true);
    }

    /**
     * Stream the offline approval document for an approved PR.
     *
     * Authorization: only the PR creator or an assigned approver can
     * download the offline approval evidence.  BU scope alone is not
     * enough because the evidence may contain confidential approver
     * signatures and supporting documents.
     */
    public function offlineApprovalDocument(PurchaseRequest $purchaseRequest): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();
        $currentBusinessUnitId = (int) session('current_business_unit_id');

        if (! $this->canAccessOfflineApprovalDocument($purchaseRequest, $user, $currentBusinessUnitId)) {
            abort(403, 'You do not have access to this purchase request.');
        }

        $documentPath = $purchaseRequest->offline_approval_document_path;
        if (! $documentPath || ! Storage::disk('public')->exists($documentPath)) {
            return back()->with('error', 'The requested document file is no longer available. Please contact the request creator to re-upload.');
        }

        return response()->file(Storage::disk('public')->path($documentPath));
    }

    /**
     * Determine whether the authenticated user may access the offline
     * approval evidence for a purchase request.  Mirrors the stock
     * request behaviour at StockRequestController::canAccessOfflineApprovalDocument.
     *
     * Allowed identities (PO 2026-05-26 widening):
     *   - super admin
     *   - top management (`hasTopManagementAccess()` true; e.g. CEO, MD, Chief of Staff)
     *   - purchasing admin in the PR's BU or any ancestor BU
     *   - the assigned approver
     *   - the PR creator (only when the PR is in the user's current BU context)
     */
    private function canAccessOfflineApprovalDocument(PurchaseRequest $purchaseRequest, \App\Models\Core\User $user, int $currentBusinessUnitId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasTopManagementAccess()) {
            return true;
        }

        if ($user->isAdminInBuOrAncestor('is_purchasing_admin', $purchaseRequest->business_unit_id)) {
            return true;
        }

        $isAssignedApprover = $purchaseRequest->approvals()
            ->where('approver_id', $user->id)
            ->exists();

        if ($isAssignedApprover) {
            return true;
        }

        return $purchaseRequest->business_unit_id === $currentBusinessUnitId
            && $purchaseRequest->user_id === $user->id;
    }

    // ============================================
    // Private Helper Methods
    // ============================================

    /**
     * Get reservations for the user.
     */
    private function getReservations($user, int $businessUnitId, string $search = ''): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = PrNumberReservation::with([
            'businessUnit:id,name,code',
            'department:id,name,code',
            'user:id,name,email',
        ])
            ->where('business_unit_id', $businessUnitId)
            ->where('user_id', $user->id)
            ->where('status', 'reserved');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('pr_number', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%");
            });
        }

        return $query->latest('reserved_at')->paginate(5, ['*'], 'reservations_page');
    }

    /**
     * Transform purchase request with authorization props.
     */
    private function transformPurchaseRequest(PurchaseRequest $pr, $user): array
    {
        $data = $pr->toArray();

        // Add authorization props
        $data['can'] = [
            'view' => true, // User can always view their own PRs
            'edit' => $pr->canBeEdited() && $pr->user_id === $user->id,
            'delete' => $pr->canBeEdited() && $pr->user_id === $user->id,
            'void' => $pr->canBeVoided() && (
                $pr->user_id === $user->id ||
                in_array($user->getAccessLevel(), ['super_admin', 'executive', 'general_manager'])
            ),
            'resubmit' => $pr->status === 'rejected' && $pr->user_id === $user->id,
        ];

        // Add computed fields
        $data['current_approval_step'] = null;
        $data['total_approval_steps'] = $pr->approvals_count ?? 0;

        // Add approval progress (from withCount)
        $data['approval_progress'] = [
            'approved' => $pr->approved_approvals_count ?? 0,
            'total' => $pr->approvals_count ?? 0,
        ];

        return $data;
    }

    /**
     * Get authorization props for show page.
     * Requirements: 8.3, 8.7
     */
    private function getShowAuthorization(PurchaseRequest $pr, $user): array
    {
        $isOwner = $pr->user_id === $user->id;
        $isAdmin = in_array($user->getAccessLevel(), ['super_admin', 'executive', 'general_manager']);
        $currentBusinessUnitId = (int) session('current_business_unit_id');

        // Check if user can approve this PR
        $currentApproval = $pr->currentApproval();
        $canApprove = $currentApproval && $currentApproval->approver_id === $user->id;
        $canReject = $canApprove; // Same logic for reject
        $canResendApprovalEmail = $isOwner
            && $pr->status === 'in_approval'
            && $currentApproval
            && $currentApproval->status === 'pending';

        return [
            'edit' => $pr->canBeEdited() && $isOwner,
            'delete' => $pr->canBeEdited() && $isOwner,
            'void' => $pr->canBeVoided() && ($isOwner || $isAdmin),
            'resubmit' => $pr->status === 'rejected' && $isOwner,
            'resendApprovalEmail' => $canResendApprovalEmail,
            'approve' => $canApprove,
            'reject' => $canReject,
            'downloadPdf' => in_array($pr->status, ['submitted', 'in_approval', 'approved']),
            'markOfflineApproved' => in_array($pr->status, ['submitted', 'in_approval']) && $isOwner,
            'supportingDocument' => $pr->supporting_document_path !== null
                && $this->canAccessSupportingDocument($pr, $user, $currentBusinessUnitId),
        ];
    }

    /**
     * Serve a supporting document using inline or attachment disposition.
     */
    private function serveSupportingDocument(PurchaseRequest $purchaseRequest, bool $download): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();
        $currentBusinessUnitId = (int) session('current_business_unit_id');

        if (! $this->canAccessSupportingDocument($purchaseRequest, $user, $currentBusinessUnitId)) {
            abort(403, 'You do not have access to this purchase request.');
        }

        $documentPath = $purchaseRequest->supporting_document_path;
        if (! $documentPath || ! Storage::disk('public')->exists($documentPath)) {
            return back()->with('error', 'The requested document file is no longer available. Please contact the request creator to re-upload.');
        }

        $documentName = $purchaseRequest->supporting_document_name ?? basename($documentPath);
        $fullPath = Storage::disk('public')->path($documentPath);

        if ($download) {
            return response()->download($fullPath, $documentName);
        }

        return response()->file($fullPath, [
            'Content-Disposition' => 'inline; filename="'.$documentName.'"',
        ]);
    }

    /**
     * Check whether the authenticated user may access the supporting document.
     */
    private function canAccessSupportingDocument(PurchaseRequest $purchaseRequest, \App\Models\Core\User $user, int $currentBusinessUnitId): bool
    {
        $isAssignedApprover = $purchaseRequest->approvals()
            ->where('approver_id', $user->id)
            ->exists();

        if ($isAssignedApprover) {
            return true;
        }

        return $purchaseRequest->business_unit_id === $currentBusinessUnitId
            && $purchaseRequest->user_id === $user->id;
    }

    /**
     * Generate QR codes for PDF.
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
