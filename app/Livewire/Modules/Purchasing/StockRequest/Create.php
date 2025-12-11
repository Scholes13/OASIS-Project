<?php

namespace App\Livewire\Modules\Purchasing\StockRequest;

use App\Models\Core\Department;
use App\Models\Modules\Purchasing\StockRequest\StockItem;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Modules\Purchasing\StockRequest\UniversalStockNumberingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    // Livewire listeners
    protected $listeners = [
        'refreshComponent' => '$refresh',
        'business-unit-switched' => 'handleBusinessUnitSwitch',
    ];

    // Form fields
    public $business_unit_id = '';
    public $department_id = '';
    public $purpose = '';
    public $expected_date = '';
    public $items = [];
    public $itemImages = [];

    // Auto-generated fields
    public $submission_date = '';
    public $department_name = '';
    public $department_code = '';
    public $user_name = '';

    // Approval Workflow
    public $customApprovalList = [];
    public $availableApprovers = [];

    // State
    public $isLoading = false;
    public $isEdit = false;
    public $isRejected = false;
    public $stockRequestId = null;

    protected $existingStockRequest = null;


    /**
     * Validation rules
     */
    protected function rules()
    {
        return [
            'purpose' => 'required|string|min:10|max:1000',
            'expected_date' => 'nullable|date|after_or_equal:today',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'required|string|max:50',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.specifications' => 'nullable|string|max:1000',
            'customApprovalList' => 'required|array|min:1',
            'customApprovalList.*.approver_id' => 'required|exists:users,id',
            'customApprovalList.*.task_type' => 'required|in:approval,paraf',
            'items.*.item_code' => 'nullable|string|max:100',
        ];
    }

    /**
     * Custom validation messages
     */
    protected function messages()
    {
        return [
            'customApprovalList.required' => 'At least one approver is required.',
            'customApprovalList.min' => 'At least one approver must be added.',
            'customApprovalList.*.approver_id.required' => 'Please select an approver.',
            'customApprovalList.*.approver_id.exists' => 'The selected approver is invalid.',
            'customApprovalList.*.task_type.required' => 'Please select a task type.',
            'customApprovalList.*.task_type.in' => 'Invalid task type selected.',
        ];
    }

    /**
     * Mount component
     */
    public function mount(?StockRequest $stockRequest = null, string $mode = 'create')
    {
        $this->items = [];
        $this->isEdit = false;
        $this->isLoading = false;

        if (!Auth::check()) {
            session()->flash('error', 'Please login to continue.');
            return redirect()->route('login');
        }

        $this->initializeUserProperties();
        $this->loadAvailableApprovers();

        if ($mode === 'edit' && $stockRequest) {
            $this->isEdit = true;
            $this->stockRequestId = $stockRequest->id;
            $this->existingStockRequest = $stockRequest->load(['items', 'businessUnit', 'department', 'user']);
            $this->initializeEditState();
        } else {
            $this->initializeCreateState();
        }
    }

    protected function initializeUserProperties(): void
    {
        $user = Auth::user();
        $this->user_name = $user->name ?? 'Unknown';
        $this->submission_date = now()->format('d/m/Y');

        $departmentId = session('current_department_id') ?? $user->primary_department_id;
        $department = Department::find($departmentId);

        if ($department) {
            $this->department_name = $department->name;
            $this->department_code = $department->code;
            $this->department_id = $department->id;
        }

        $this->business_unit_id = session('current_business_unit_id') ?? $user->businessUnits()->first()?->id ?? '';
    }

    protected function initializeCreateState(): void
    {
        $this->expected_date = now()->addDays(7)->format('Y-m-d');
        $this->addItem();
        
        // Initialize with one empty approval
        if (empty($this->customApprovalList)) {
            $this->customApprovalList = [
                ['approver_id' => '', 'task_type' => 'approval']
            ];
        }
    }

    protected function initializeEditState(): void
    {
        $sr = $this->existingStockRequest;
        $this->purpose = $sr->purpose;
        $this->expected_date = $sr->expected_date?->format('Y-m-d') ?? '';
        $this->isRejected = $sr->status === 'rejected';

        $this->items = $sr->items->map(fn($item) => [
            'id' => $item->id,
            'item_order' => $item->item_order,
            'item_name' => $item->item_name,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'price' => $item->price ?? 0,
            'total' => $item->total ?? 0,
            'specifications' => $item->specifications,
            'item_code' => $item->item_code,
            'image_path' => $item->image_path,
        ])->toArray();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'item_order' => count($this->items) + 1,
            'item_name' => '',
            'quantity' => 1,
            'unit' => 'pcs',
            'price' => 0,
            'total' => 0,
            'specifications' => '',
            'item_code' => '',
            'image_path' => null,
        ];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
            
            foreach ($this->items as $key => $item) {
                $this->items[$key]['item_order'] = $key + 1;
            }
        }
    }

    /**
     * Handle image upload for specific item
     */
    public function updatedItemImages($value, $key)
    {
        // Validate uploaded image
        $this->validate([
            "itemImages.{$key}" => 'nullable|image|max:2048', // Max 2MB
        ], [
            "itemImages.{$key}.image" => 'File must be an image (jpg, png, jpeg, gif, svg).',
            "itemImages.{$key}.max" => 'Image size must not exceed 2MB.',
        ]);
    }

    /**
     * Remove uploaded image from specific item
     */
    public function removeItemImage($index)
    {
        // Remove temporary upload
        if (isset($this->itemImages[$index])) {
            unset($this->itemImages[$index]);
        }
        
        // Remove existing image from storage and database if editing
        if ($this->isEdit && !empty($this->items[$index]['image_path'])) {
            \Storage::delete($this->items[$index]['image_path']);
            $this->items[$index]['image_path'] = null;
            
            // Update database if item already exists
            if ($this->stockRequestId && !empty($this->items[$index]['id'])) {
                $stockItem = StockItem::find($this->items[$index]['id']);
                if ($stockItem) {
                    $stockItem->update(['image_path' => null]);
                }
            }
        }
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Image removed successfully.'
        ]);
    }

    public function refreshTotals(): void
    {
        // Force component refresh to recalculate totals
        $this->dispatch('$refresh');
    }

    public function handleBusinessUnitSwitch($businessUnitId): void
    {
        // Reset form state
        $this->purpose = '';
        $this->expected_date = now()->addDays(7)->format('Y-m-d');
        $this->items = [];
        $this->customApprovalList = [];
        
        // Reinitialize user properties with new business unit
        $this->initializeUserProperties();
        
        // Reload approvers for new business unit
        $this->loadAvailableApprovers();
        
        // Add initial item
        $this->addItem();
        
        // ✅ ORCHESTRATOR: Acknowledge completion
        $this->dispatch('bu-switch-acknowledge', component: 'sr-create');
        
        $buName = session('current_business_unit_name', 'new business unit');
        $this->dispatch('notify',
            message: "Switched to {$buName}. Form has been reset.",
            type: 'success'
        );
    }


    public function submitStockRequest()
    {
        $this->isLoading = true;

        try {
            $this->validate();

            DB::beginTransaction();

            if ($this->isEdit) {
                $this->updateStockRequest();
            } else {
                $this->createNewStockRequest();
            }

            DB::commit();

            session()->flash('success', 'Stock request submitted successfully!');
            return redirect()->route('stock-requests.index');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->isLoading = false;
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->isLoading = false;
            session()->flash('error', 'Failed to submit: ' . $e->getMessage());
        }
    }

    protected function createNewStockRequest(): void
    {
        $numberingService = app(UniversalStockNumberingService::class);
        
        $stNumberData = $numberingService->generateStockNumber(
            Auth::user(),
            $this->business_unit_id,
            $this->department_id
        );

        // Prepare approval workflow JSON
        $approvalWorkflow = collect($this->customApprovalList)->map(function ($approval, $index) {
            return [
                'step_order' => $index + 1,
                'approver_id' => $approval['approver_id'],
                'task_type' => $approval['task_type'],
            ];
        })->toArray();

        $stockRequest = StockRequest::create([
            'st_number' => $stNumberData['formatted_number'],
            'sequence_id' => $stNumberData['sequence_id'],
            'business_unit_id' => $this->business_unit_id,
            'department_id' => $this->department_id,
            'user_id' => Auth::id(),
            'purpose' => $this->purpose,
            'date_of_request' => now(),
            'expected_date' => $this->expected_date ? Carbon::parse($this->expected_date) : null,
            'status' => 'submitted',
            'submitted_at' => now(),
            'last_modified_by' => Auth::id(),
            'approval_workflow' => $approvalWorkflow,
            'is_sequential_approval' => true,
        ]);

        $this->createStockItems($stockRequest);
        $this->createCustomApprovalWorkflow($stockRequest);
    }

    protected function createCustomApprovalWorkflow(StockRequest $stockRequest)
    {
        // Ensure customApprovalList is an array
        if (!is_array($this->customApprovalList)) {
            $this->customApprovalList = [];
        }

        // Validate that custom approvers are selected
        $validApprovals = array_filter($this->customApprovalList, function ($approval) {
            return !empty($approval['approver_id']);
        });

        if (empty($validApprovals)) {
            throw new \Exception('Please select at least one approver for custom approval workflow.');
        }

        // Check for duplicate approvers
        $approverIds = array_column($validApprovals, 'approver_id');
        if (count($approverIds) !== count(array_unique($approverIds))) {
            throw new \Exception('Cannot select the same approver for multiple steps.');
        }

        // Create approval steps for custom workflow
        $stepOrder = 1;
        $workflowStructure = [];

        foreach ($validApprovals as $index => $approval) {
            $approverId = $approval['approver_id'];
            $taskType = $approval['task_type'] ?? 'approval';

            $approver = \App\Models\Core\User::find($approverId);

            if (!$approver) {
                throw new \Exception("Approver not found for step {$stepOrder}");
            }

            // Create approval record
            \App\Models\Modules\Purchasing\StockRequest\StockApproval::create([
                'stock_request_id' => $stockRequest->id,
                'approver_id' => $approverId,
                'step_order' => $stepOrder,
                'approval_type' => $taskType, // Use task_type (approval/paraf)
                'task_type' => $taskType,
                'status' => 'pending',
                'assigned_at' => now(),
                'due_date' => $this->addBusinessDays(now(), 3), // Default 3 business days
                'notes' => null,
                'responded_at' => null,
            ]);

            // Build workflow structure
            $workflowStructure[] = [
                'approver_id' => $approverId,
                'approver_name' => $approver->name,
                'approver_email' => $approver->email,
                'step_order' => $stepOrder,
                'approval_type' => $taskType,
                'reason' => "Custom {$taskType} step {$stepOrder}",
                'due_date' => $this->addBusinessDays(now(), 3)->toISOString(),
            ];

            $stepOrder++;
        }

        // Update stock request with workflow information
        $stockRequest->update([
            'approval_workflow' => $workflowStructure,
            'is_sequential_approval' => true,
            'status' => 'in_approval',
        ]);

        // Log the custom workflow creation
        \Illuminate\Support\Facades\Log::info('Custom approval workflow created', [
            'st_number' => $stockRequest->st_number,
            'total_steps' => count($validApprovals),
            'approvers' => collect($workflowStructure)->pluck('approver_name')->toArray(),
        ]);

        // Send email notification to first approver
        $this->sendApprovalNotification($stockRequest);
    }

    /**
     * Send email notification to the first pending approver
     */
    protected function sendApprovalNotification(StockRequest $stockRequest): void
    {
        try {
            // Get the first pending approval
            $firstApproval = $stockRequest->approvals()
                ->where('status', 'pending')
                ->orderBy('step_order')
                ->first();

            if ($firstApproval && $firstApproval->approver) {
                // Send notification
                $firstApproval->approver->notify(
                    new \App\Notifications\Purchasing\StockRequest\ApprovalRequested($firstApproval)
                );

                // Mark email as sent
                $firstApproval->update([
                    'email_sent' => true,
                    'email_sent_at' => now(),
                ]);

                \Illuminate\Support\Facades\Log::info('Stock request approval notification sent', [
                    'st_number' => $stockRequest->st_number,
                    'approver_id' => $firstApproval->approver_id,
                    'approver_name' => $firstApproval->approver->name,
                    'approver_email' => $firstApproval->approver->email,
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the submission
            \Illuminate\Support\Facades\Log::error('Failed to send stock request approval notification', [
                'st_number' => $stockRequest->st_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Add business days to a date (skip weekends)
     */
    protected function addBusinessDays(Carbon $date, int $days): Carbon
    {
        $result = $date->copy();

        while ($days > 0) {
            $result->addDay();

            // Skip weekends
            if ($result->isWeekday()) {
                $days--;
            }
        }

        return $result;
    }

    protected function updateStockRequest(): void
    {
        $stockRequest = $this->existingStockRequest;
        $targetStatus = $stockRequest->status === 'rejected' ? 'rejected' : 'submitted';

        $stockRequest->update([
            'purpose' => $this->purpose,
            'expected_date' => $this->expected_date ? Carbon::parse($this->expected_date) : null,
            'status' => $targetStatus,
            'last_modified_by' => Auth::id(),
        ]);

        $stockRequest->items()->delete();
        $this->createStockItems($stockRequest);
    }

    protected function createStockItems(StockRequest $stockRequest): void
    {
        foreach ($this->items as $index => $itemData) {
            // Clean price (remove thousand separator)
            $price = isset($itemData['price']) ? (float) preg_replace('/[^0-9]/', '', $itemData['price']) : 0;
            $quantity = (int) ($itemData['quantity'] ?? 0);
            $total = $quantity * $price;
            
            $stockItemData = [
                'stock_request_id' => $stockRequest->id,
                'item_order' => $index + 1,
                'item_name' => $itemData['item_name'],
                'quantity' => $quantity,
                'unit' => $itemData['unit'],
                'price' => $price,
                'total' => $total,
                'specifications' => $itemData['specifications'] ?? null,
                'item_code' => $itemData['item_code'] ?? null,
            ];

            if (isset($this->itemImages[$index]) && $this->itemImages[$index]) {
                $imagePath = $this->itemImages[$index]->store('stock-items', 'public');
                $stockItemData['image_path'] = $imagePath;
            } elseif (isset($itemData['image_path'])) {
                $stockItemData['image_path'] = $itemData['image_path'];
            }

            StockItem::create($stockItemData);
        }
    }


    /**
     * Fetch available approvers with business unit hierarchy support
     */
    protected function fetchAvailableApprovers()
    {
        $currentUser = Auth::user();
        $currentBusinessUnitId = session('current_business_unit_id');

        if (!$currentUser || !$currentBusinessUnitId) {
            return [];
        }

        // Get current business unit and its hierarchy
        $currentBU = \App\Models\Core\BusinessUnit::with(['parent', 'children'])->find($currentBusinessUnitId);
        $accessibleBusinessUnitIds = [$currentBusinessUnitId];

        // Include parent business unit for top management access
        if ($currentBU && $currentBU->parent) {
            $accessibleBusinessUnitIds[] = $currentBU->parent->id;
        }

        // Include child business units for broader management access
        if ($currentBU && $currentBU->children) {
            foreach ($currentBU->children as $child) {
                $accessibleBusinessUnitIds[] = $child->id;
            }
        }

        $users = \App\Models\Core\User::where(function ($query) use ($accessibleBusinessUnitIds) {
            // Users assigned to business units via UserBusinessUnit pivot
            $query->whereHas('businessUnits', function ($subQuery) use ($accessibleBusinessUnitIds) {
                $subQuery->whereIn('business_unit_id', $accessibleBusinessUnitIds)
                    ->where('is_active', true);
            })
            // OR users whose primary department belongs to accessible business units
            ->orWhereHas('primaryDepartment', function ($subQuery) use ($accessibleBusinessUnitIds) {
                $subQuery->whereIn('business_unit_id', $accessibleBusinessUnitIds);
            });
        })
        ->where('is_active', true)
        ->where('id', '!=', $currentUser->id)
        ->with(['primaryDepartment'])
        ->orderBy('name')
        ->get();

        return $users->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'role' => ucfirst(str_replace('_', ' ', $user->global_role ?? 'staff')),
            'department' => optional($user->primaryDepartment)->name ?? 'No Department',
            'email' => $user->email,
        ])
        ->values()
        ->toArray();
    }

    /**
     * Load available approvers based on business unit
     */
    public function loadAvailableApprovers(): void
    {
        // Use Laravel Cache with user-scoped key to prevent data leakage between users
        $cacheKey = sprintf(
            'approvers:bu:%s:user:%s',
            session('current_business_unit_id', 0),
            Auth::id()
        );

        $this->availableApprovers = \Illuminate\Support\Facades\Cache::remember(
            $cacheKey,
            300, // 5 minutes
            fn() => $this->fetchAvailableApprovers()
        );
    }

    /**
     * Add new approval step
     */
    public function addCustomApproval(): void
    {
        $this->customApprovalList[] = [
            'approver_id' => '',
            'task_type' => 'approval'
        ];

        $this->dispatch('approval-row-added');
    }

    /**
     * Remove approval step
     */
    public function removeCustomApproval(int $index): void
    {
        if (is_array($this->customApprovalList) && count($this->customApprovalList) > 1) {
            unset($this->customApprovalList[$index]);
            $this->customApprovalList = array_values($this->customApprovalList);
        }

        $this->dispatch('approval-row-removed');
    }

    public function render()
    {
        return view('livewire.modules.purchasing.stock-request.create');
    }
}
