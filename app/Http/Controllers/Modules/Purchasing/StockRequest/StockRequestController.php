<?php

namespace App\Http\Controllers\Modules\Purchasing\StockRequest;

use App\Http\Controllers\Controller;
use App\Models\Modules\Purchasing\StockRequest\StockNumberReservation;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Core\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Browsershot\Browsershot;

class StockRequestController extends Controller
{
    /**
     * Display a listing of the user's stock requests.
     * Requirements: 6.1
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
        $query = StockRequest::with([
            'department:id,name,code',
            'user:id,name,email',
        ])
            ->withCount('items')
            ->where('business_unit_id', $businessUnitId)
            ->where('user_id', $user->id);

        // Apply search filter
        if ($filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('st_number', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%");
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

        $stockRequests = $query
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($request->get('per_page', 10))
            ->withQueryString();

        // Transform data to include authorization props
        $stockRequests->through(function ($st) use ($user) {
            return $this->transformStockRequest($st, $user);
        });

        // Get reservations for the user
        $reservations = $this->getReservations($user, $businessUnitId, $filters['search']);

        return Inertia::render('Purchasing/StockRequest/Index', [
            'stockRequests' => $stockRequests,
            'reservations' => $reservations,
            'filters' => $filters,
            'can' => [
                'create' => true, // All authenticated users can create STs
                'viewAll' => $user->can('view-all-stock-requests') || $user->isSuperAdmin(),
                'export' => $user->can('export-stock-requests') || $user->isSuperAdmin(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new stock request.
     * Requirements: 6.2, 6.3
     */
    public function createInertia(): Response
    {
        $user = Auth::user();
        $businessUnitId = (int) session('current_business_unit_id');
        $departmentId = (int) session('current_department_id');

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

        return Inertia::render('Purchasing/StockRequest/Form', [
            'mode' => 'create',
            'stockRequest' => null,
            'departments' => $departments,
            'businessUnits' => $businessUnits,
            'availableApprovers' => $availableApprovers,
            'currentBusinessUnitId' => $businessUnitId,
            'currentDepartmentId' => $departmentId,
        ]);
    }

    /**
     * Store a newly created stock request.
     * Requirements: 6.2, 6.3
     */
    public function store(\App\Http\Requests\Purchasing\StoreStockRequestRequest $request)
    {
        $user = Auth::user();

        try {
            DB::beginTransaction();

            // Generate ST number (similar to PR numbering)
            $stNumber = $this->generateSTNumber($user, $request->business_unit_id, $request->date_of_request);

            // Handle offline approval document upload
            $offlineDocumentPath = null;
            $offlineDocumentName = null;
            if ($request->hasFile('offline_approval_document')) {
                $file = $request->file('offline_approval_document');
                $offlineDocumentName = $file->getClientOriginalName();
                $offlineDocumentPath = $file->store('stock-requests/offline-approvals', 'public');
            }

            // Create stock request
            $stockRequest = StockRequest::create([
                'st_number' => $stNumber['formatted_number'],
                'business_unit_id' => $request->business_unit_id,
                'department_id' => $request->department_id,
                'user_id' => $user->id,
                'sequence_id' => $stNumber['sequence_id'],
                'purpose' => $request->purpose,
                'date_of_request' => $request->date_of_request,
                'expected_date' => $request->expected_date,
                'status' => 'submitted', // Directly submit (no draft step)
                'submitted_at' => now(),
                'offline_approval_document_path' => $offlineDocumentPath,
                'offline_approval_document_name' => $offlineDocumentName,
                'last_modified_by' => $user->id,
            ]);

            // Create ST items
            foreach ($request->items as $index => $itemData) {
                // Handle item image upload
                $imagePath = null;
                if (isset($itemData['image']) && $itemData['image'] instanceof \Illuminate\Http\UploadedFile) {
                    $imagePath = $itemData['image']->store('stock-requests/items', 'public');
                }

                \App\Models\Modules\Purchasing\StockRequest\StockItem::create([
                    'stock_request_id' => $stockRequest->id,
                    'item_order' => $index + 1,
                    'item_name' => $itemData['item_name'],
                    'item_description' => $itemData['item_description'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'image_path' => $imagePath,
                ]);
            }

            // Create approval workflow
            $this->createWorkflowFromRequest($stockRequest, $request->approval_workflow, $request->approval_notes ?? null);

            DB::commit();

            return redirect()
                ->route('stock-requests.show', $stockRequest)
                ->with('success', 'Stock request created successfully and submitted for approval.');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to create stock request', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create stock request. Please try again or contact support.');
        }
    }

    /**
     * Show the form for editing the specified stock request.
     * Requirements: 6.2, 6.3
     */
    public function editInertia(StockRequest $stockRequest): Response
    {
        $user = Auth::user();

        // Check if ST can be edited
        if (! $stockRequest->isEditable()) {
            return redirect()
                ->route('stock-requests.show', $stockRequest)
                ->with('error', 'This stock request cannot be edited.');
        }

        // Validate business unit context
        if ($stockRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        // Check if user owns this ST
        if ($stockRequest->user_id !== $user->id) {
            abort(403, 'You are not authorized to edit this stock request.');
        }

        $businessUnitId = $stockRequest->business_unit_id;

        // Load relationships
        $stockRequest->load([
            'items',
            'approvals.approver:id,name,email',
        ]);

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
        $approvalWorkflow = $stockRequest->approvals->map(function ($approval) {
            return [
                'approver_id' => $approval->approver_id,
                'task_type' => $approval->approval_type ?? 'approval',
            ];
        })->toArray();

        return Inertia::render('Purchasing/StockRequest/Form', [
            'mode' => 'edit',
            'stockRequest' => array_merge($stockRequest->toArray(), [
                'approval_workflow' => $approvalWorkflow,
            ]),
            'departments' => $departments,
            'businessUnits' => $businessUnits,
            'availableApprovers' => $availableApprovers,
            'currentBusinessUnitId' => $businessUnitId,
            'currentDepartmentId' => $stockRequest->department_id,
        ]);
    }

    /**
     * Update the specified stock request.
     * Requirements: 6.2, 6.3
     */
    public function update(\App\Http\Requests\Purchasing\StoreStockRequestRequest $request, StockRequest $stockRequest)
    {
        $user = Auth::user();

        // Check if ST can be edited
        if (! $stockRequest->isEditable()) {
            return back()->with('error', 'This stock request cannot be edited.');
        }

        // Validate business unit context
        if ($stockRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        // Check if user owns this ST
        if ($stockRequest->user_id !== $user->id) {
            abort(403, 'You are not authorized to edit this stock request.');
        }

        try {
            DB::beginTransaction();

            // Handle offline approval document upload
            $offlineDocumentPath = $stockRequest->offline_approval_document_path;
            $offlineDocumentName = $stockRequest->offline_approval_document_name;
            if ($request->hasFile('offline_approval_document')) {
                // Delete old document if exists
                if ($offlineDocumentPath) {
                    \Storage::disk('public')->delete($offlineDocumentPath);
                }

                $file = $request->file('offline_approval_document');
                $offlineDocumentName = $file->getClientOriginalName();
                $offlineDocumentPath = $file->store('stock-requests/offline-approvals', 'public');
            }

            // Update stock request
            $stockRequest->update([
                'purpose' => $request->purpose,
                'date_of_request' => $request->date_of_request,
                'expected_date' => $request->expected_date,
                'offline_approval_document_path' => $offlineDocumentPath,
                'offline_approval_document_name' => $offlineDocumentName,
                'last_modified_by' => $user->id,
            ]);

            // Delete existing items
            $stockRequest->items()->delete();

            // Create new items
            foreach ($request->items as $index => $itemData) {
                // Handle item image upload
                $imagePath = null;
                if (isset($itemData['image']) && $itemData['image'] instanceof \Illuminate\Http\UploadedFile) {
                    $imagePath = $itemData['image']->store('stock-requests/items', 'public');
                }

                \App\Models\Modules\Purchasing\StockRequest\StockItem::create([
                    'stock_request_id' => $stockRequest->id,
                    'item_order' => $index + 1,
                    'item_name' => $itemData['item_name'],
                    'item_description' => $itemData['item_description'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'image_path' => $imagePath,
                ]);
            }

            // Reset and recreate approval workflow
            $this->resetWorkflow($stockRequest);
            $this->createWorkflowFromRequest($stockRequest, $request->approval_workflow, $request->approval_notes ?? null);

            // Update status to submitted
            $stockRequest->update([
                'status' => 'submitted',
                'submitted_at' => $stockRequest->submitted_at ?? now(), // Preserve original if exists
                'rejected_at' => null,
            ]);

            DB::commit();

            return redirect()
                ->route('stock-requests.show', $stockRequest)
                ->with('success', 'Stock request updated successfully and resubmitted for approval.');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to update stock request', [
                'st_id' => $stockRequest->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update stock request. Please try again or contact support.');
        }
    }

    /**
     * Display the specified stock request.
     * Requirements: 6.4
     */
    public function showInertia(StockRequest $stockRequest): Response
    {
        $user = Auth::user();

        // Validate business unit context
        if ($stockRequest->business_unit_id !== session('current_business_unit_id')) {
            abort(403, 'You do not have access to this stock request.');
        }

        // Load relationships with eager loading
        $stockRequest->load([
            'businessUnit:id,name,code',
            'department:id,name,code',
            'user:id,name,email',
            'items',
            'approvals.approver:id,name,email',
            'lastModifiedBy:id,name',
            'offlineApprovedBy:id,name',
        ]);

        // Get approval progress
        $approvalProgress = $stockRequest->getApprovalProgress();

        return Inertia::render('Purchasing/StockRequest/Show', [
            'stockRequest' => array_merge(
                $stockRequest->toArray(),
                [
                    'approval_progress' => $approvalProgress,
                    'can' => $this->getShowAuthorization($stockRequest, $user),
                ]
            ),
            'can' => $this->getShowAuthorization($stockRequest, $user),
        ]);
    }

    /**
     * Show the form for creating a new stock request (Blade view - legacy)
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
            'offline_approval_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ], [
            'offline_approval_document.required' => 'Bukti approval offline wajib diupload.',
            'offline_approval_document.mimes' => 'Format file harus JPG, PNG, atau PDF.',
            'offline_approval_document.max' => 'Ukuran file maksimal 10MB.',
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
            // Handle file upload
            $documentPath = null;
            $documentName = null;
            if ($request->hasFile('offline_approval_document')) {
                $file = $request->file('offline_approval_document');
                $documentName = $file->getClientOriginalName();
                $documentPath = $file->store('offline-approvals/stock-requests/'.$stockRequest->id, 'public');
            }

            $stockRequest->update([
                'status' => 'approved',
                'approved_at' => now(),
                'offline_approved_at' => now(),
                'offline_approved_by' => $user->id,
                'offline_approval_notes' => $request->notes,
                'offline_approval_document_path' => $documentPath,
                'offline_approval_document_name' => $documentName,
            ]);

            // Log activity
            activity()
                ->performedOn($stockRequest)
                ->causedBy($user)
                ->withProperties([
                    'notes' => $request->notes,
                    'document_path' => $documentPath,
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

    /**
     * Get reservations for the user.
     */
    private function getReservations($user, int $businessUnitId, string $search = ''): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = StockNumberReservation::with([
            'user:id,name',
            'department:id,name,code',
        ])
            ->where('business_unit_id', $businessUnitId)
            ->where('user_id', $user->id)
            ->whereNull('stock_request_id') // Only unredeemed reservations
            ->where('status', 'reserved'); // Only active reservations

        if ($search) {
            $query->where('reserved_number', 'like', "%{$search}%");
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate(5);
    }

    /**
     * Transform stock request with authorization props.
     */
    private function transformStockRequest(StockRequest $st, $user): array
    {
        $data = $st->toArray();

        $isOwner = $st->user_id === $user->id;
        $isSuperAdmin = $user->isSuperAdmin();

        $data['can'] = [
            'view' => true, // All users in BU can view
            'edit' => $isOwner && $st->isEditable(),
            'delete' => $isOwner && $st->status === 'draft',
            'void' => ($isOwner || $isSuperAdmin) && $st->canBeVoided(),
            'resubmit' => $isOwner && $st->status === 'rejected',
        ];

        return $data;
    }

    /**
     * Get authorization props for show page.
     */
    private function getShowAuthorization(StockRequest $st, $user): array
    {
        $isOwner = $st->user_id === $user->id;
        $isSuperAdmin = $user->isSuperAdmin();

        return [
            'edit' => $isOwner && $st->isEditable(),
            'delete' => $isOwner && $st->status === 'draft',
            'void' => ($isOwner || $isSuperAdmin) && $st->canBeVoided(),
            'resubmit' => $isOwner && $st->status === 'rejected',
            'downloadPdf' => true, // All users can download PDF
        ];
    }

    /**
     * Generate ST number using numbering service.
     */
    private function generateSTNumber($user, int $businessUnitId, string $dateOfRequest): array
    {
        $date = \Carbon\Carbon::parse($dateOfRequest);

        // Get business unit
        $businessUnit = \App\Models\Core\BusinessUnit::find($businessUnitId);
        if (! $businessUnit) {
            throw new \Exception('Business unit not found');
        }

        // Get numbering service
        $numberingService = app(\App\Services\Core\NumberingService::class);

        // Ensure ST numbering module exists
        $moduleCode = 'ST';
        \App\Models\Core\NumberingModule::firstOrCreate(
            [
                'business_unit_id' => $businessUnit->id,
                'module_code' => $moduleCode,
            ],
            [
                'module_name' => 'Stock Request',
                'format_pattern' => 'ST.{BU_CODE}/{YYYYMM}/{SEQUENCE}',
                'config' => [
                    'sequence_padding' => 3,
                    'max_number' => 999,
                    'reset_annually' => true,
                    'reset_monthly' => false,
                    'cross_department' => true,
                    'shared_sequence' => true,
                ],
                'is_active' => true,
            ]
        );

        // Generate sequence number
        $result = $numberingService->generateNumber(
            $businessUnit->id,
            $moduleCode,
            null, // No department separation
            $date->year,
            null  // No monthly reset
        );

        // Format the ST number
        $result['formatted_number'] = sprintf(
            'ST.%s/%d%02d/%03d',
            $businessUnit->code,
            $date->year,
            $date->month,
            $result['sequence_number']
        );

        return $result;
    }

    /**
     * Create approval workflow from request data.
     */
    private function createWorkflowFromRequest(StockRequest $stockRequest, array $approvalWorkflow, ?string $notes): void
    {
        foreach ($approvalWorkflow as $index => $step) {
            \App\Models\Modules\Purchasing\StockRequest\StockApproval::create([
                'stock_request_id' => $stockRequest->id,
                'approver_id' => $step['approver_id'],
                'step_order' => $index + 1,
                'approval_type' => $step['task_type'] ?? 'approval',
                'status' => 'pending',
                'notes' => $notes,
            ]);
        }

        // Update stock request status to in_approval
        $stockRequest->update(['status' => 'in_approval']);
    }

    /**
     * Reset approval workflow.
     */
    private function resetWorkflow(StockRequest $stockRequest): void
    {
        // Delete all existing approvals
        $stockRequest->approvals()->delete();

        // Reset approval-related fields
        $stockRequest->update([
            'status' => 'submitted',
            'approved_at' => null,
            'rejected_at' => null,
            'rejection_notes' => null,
        ]);
    }
}
