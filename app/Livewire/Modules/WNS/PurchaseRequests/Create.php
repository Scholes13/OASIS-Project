<?php

namespace App\Livewire\Modules\WNS\PurchaseRequests;

use App\Models\Department;
use App\Models\Modules\WNS\PurchaseRequest;
use App\Models\Modules\WNS\PrItem;
use App\Services\UniversalPRNumberingService;
use App\Services\Modules\WNS\ApprovalWorkflowService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Create extends Component
{
    // Form fields for complete PR
    public $purpose = '';           // Keperluan
    public $used_for = '';         // Digunakan untuk
    public $expected_date = '';    // Expected delivery date
    public $currency = 'IDR';      // Currency
    public $items = [];            // Items array
    public $departments = [];      // Available departments
    
    // Approval Flow Settings
    public $approvalFlow = 'automatic';     // 'automatic' or 'custom'
    public $customApprovalLayers = 1;       // Number of custom approval layers (1-5)
    public $customApprovers = [];           // Array of custom approvers
    public $availableApprovers = [];        // Available approvers for selection
    
    // Auto-generated fields (display only)
    public $submission_date;
    public $department_name;
    public $department_code;
    public $user_name;
    
    // State
    public $isLoading = false;
    public $totalAmount = 0;
    
    // Validation rules for complete PR
    protected $rules = [
        'purpose' => 'required|string|min:3|max:500',
        'used_for' => 'required|string|min:10|max:1000',
        'items' => 'required|array|min:1',
        'items.*.item_name' => 'required|string|max:255',
        'items.*.brand_name' => 'nullable|string|max:255',
        'items.*.item_description' => 'nullable|string|max:1000',
        'items.*.supplier_name' => 'nullable|string|max:255',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.unit' => 'required|string|max:50',
        'items.*.unit_price' => 'required|numeric|min:0',
    ];

    protected $messages = [
        'purpose.required' => 'Purpose field is required.',
        'purpose.min' => 'Purpose must be at least 3 characters.',
        'used_for.required' => 'Used for field is required.',
        'used_for.min' => 'Used for must be at least 10 characters.',
        'items.required' => 'At least one item is required.',
        'items.min' => 'At least one item is required.',
        'items.*.item_name.required' => 'Item name is required.',
        'items.*.quantity.required' => 'Quantity is required.',
        'items.*.quantity.min' => 'Quantity must be greater than 0.',
        'items.*.unit_price.required' => 'Unit price is required.',
        'items.*.unit_price.min' => 'Unit price must be 0 or greater.',
    ];

    public function mount()
    {
        // Initialize form properties
        $this->purpose = '';
        $this->used_for = '';
        
        // Initialize approval flow settings
        $this->approvalFlow = 'automatic';
        $this->customApprovalLayers = 1;
        $this->customApprovers = [];
        
        // Auto-populate data from current user and session
        $this->submission_date = Carbon::today()->format('d/m/Y');
        $this->user_name = Auth::user()->name;
        
        // Get department from user's current department
        $user = Auth::user();
        if ($user->primaryDepartment) {
            $this->department_name = $user->primaryDepartment->name;
            $this->department_code = $user->primaryDepartment->code;
        } else {
            $this->department_name = 'Department not set';
            $this->department_code = 'N/A';
        }
        
        // Load departments and add first item
        $this->loadDepartments();
        $this->loadAvailableApprovers();
        $this->addItem();
    }

    public function loadDepartments()
    {
        $this->departments = Department::where('business_unit_id', session('current_business_unit_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function loadAvailableApprovers()
    {
        // Get all active users from current business unit (not just specific roles)
        $businessUnitId = session('current_business_unit_id');
        
        $this->availableApprovers = \App\Models\User::whereHas('businessUnits', function ($query) use ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId)
                  ->where('is_active', true);
        })
        ->where('is_active', true)
        ->where('id', '!=', Auth::id()) // Exclude current user
        ->orderBy('name') // Sort by name for better UX
        ->get()
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->roles->first()->name ?? 'User',
                'department' => $user->primaryDepartment->name ?? 'N/A',
                'email' => $user->email
            ];
        })
        ->toArray();
    }

    public function addItem()
    {
        $this->items[] = [
            'item_name' => '',
            'brand_name' => '',
            'item_description' => '',
            'supplier_name' => '',
            'quantity' => 1,
            'unit' => 'pcs',
            'unit_price' => 0,
            'currency' => $this->currency,
        ];
        
        $this->calculateTotals();
    }

    public function removeItem($index)
    {
        if (count($this->items) > 1 && isset($this->items[$index])) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
            $this->calculateTotals();
        }
    }

    public function updatedItems()
    {
        $this->calculateTotals();
    }

    // Add specific updaters for item properties to ensure real-time calculation
    public function updatedItemsQuantity($value, $key)
    {
        // Sanitize quantity input
        $keyParts = explode('.', $key);
        if (count($keyParts) >= 2) {
            $index = $keyParts[0];
            // Ensure quantity is numeric and positive integer
            $cleanValue = is_numeric($value) ? max(0, intval($value)) : 0;
            $this->items[$index]['quantity'] = $cleanValue;
        }
        $this->calculateTotals();
    }

    public function updatedItemsUnitPrice($value, $key)
    {
        // Sanitize price input
        $keyParts = explode('.', $key);
        if (count($keyParts) >= 2) {
            $index = $keyParts[0];
            // Remove all non-numeric characters and ensure it's numeric
            $cleanValue = preg_replace('/[^0-9]/', '', $value);
            $cleanValue = is_numeric($cleanValue) ? max(0, intval($cleanValue)) : 0;
            $this->items[$index]['unit_price'] = $cleanValue;
        }
        $this->calculateTotals();
    }

    // Add listener for any item property changes
    public function updatedItemsItemName()
    {
        $this->calculateTotals();
    }

    public function updatedItemsBrandName()
    {
        $this->calculateTotals();
    }

    public function updatedItemsUnit()
    {
        $this->calculateTotals();
    }

    public function updatedCurrency()
    {
        // Update currency for all items when main currency changes
        foreach ($this->items as $index => $item) {
            $this->items[$index]['currency'] = $this->currency;
        }
        $this->calculateTotals();
    }

    public function updatedApprovalFlow()
    {
        // Reset custom approval settings when switching to automatic
        if ($this->approvalFlow === 'automatic') {
            $this->customApprovalLayers = 1;
            $this->customApprovers = [];
        }
        
        // Force re-render to ensure UI updates
        $this->dispatch('approval-flow-changed');
    }

    public function updatedCustomApprovalLayers()
    {
        // Reset custom approvers when changing number of layers
        $this->customApprovers = [];
        
        // Force re-render to ensure UI updates
        $this->dispatch('approval-layers-changed');
    }

    protected function createCustomApprovalWorkflow(PurchaseRequest $purchaseRequest)
    {
        // Validate that custom approvers are selected
        $selectedApprovers = array_filter($this->customApprovers);
        
        if (empty($selectedApprovers)) {
            throw new \Exception('Please select at least one approver for custom approval workflow.');
        }

        // Create approval steps for custom workflow
        $stepOrder = 1;
        $workflowStructure = [];
        
        foreach ($selectedApprovers as $layerIndex => $approverId) {
            if (!empty($approverId)) {
                $approver = \App\Models\User::find($approverId);
                
                if (!$approver) {
                    throw new \Exception("Approver not found for layer {$layerIndex}");
                }

                // Create approval record
                \App\Models\Modules\WNS\PrApproval::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'approver_id' => $approverId,
                    'step_order' => $stepOrder,
                    'approval_type' => 'custom',
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
                    'approval_type' => 'custom',
                    'reason' => "Custom approval layer {$stepOrder}",
                    'due_date' => $this->addBusinessDays(now(), 3)->toISOString(),
                ];

                $stepOrder++;
            }
        }

        // Update purchase request with workflow information
        $purchaseRequest->update([
            'approval_workflow' => $workflowStructure,
            'is_sequential_approval' => true,
            'status' => 'in_approval'
        ]);

        // Log the custom workflow creation
        \Illuminate\Support\Facades\Log::info("Custom approval workflow created", [
            'pr_number' => $purchaseRequest->pr_number,
            'total_layers' => count($selectedApprovers),
            'approvers' => collect($workflowStructure)->pluck('approver_name')->toArray()
        ]);
    }

    protected function validateCustomApproval()
    {
        $selectedApprovers = array_filter($this->customApprovers);
        
        if (empty($selectedApprovers)) {
            $this->addError('customApprovers', 'Please select at least one approver for custom approval workflow.');
            throw new \Illuminate\Validation\ValidationException(validator([], []));
        }

        // Check for duplicate approvers
        $uniqueApprovers = array_unique($selectedApprovers);
        if (count($selectedApprovers) !== count($uniqueApprovers)) {
            $this->addError('customApprovers', 'Cannot select the same approver for multiple layers.');
            throw new \Illuminate\Validation\ValidationException(validator([], []));
        }

        // Validate that all selected approvers exist and are active
        foreach ($selectedApprovers as $layerIndex => $approverId) {
            $approver = \App\Models\User::where('id', $approverId)
                ->where('is_active', true)
                ->first();
                
            if (!$approver) {
                $this->addError("customApprovers.{$layerIndex}", "Selected approver is not valid or inactive.");
                throw new \Illuminate\Validation\ValidationException(validator([], []));
            }
        }
    }

    public function calculateTotals()
    {
        $this->totalAmount = 0;
        
        foreach ($this->items as $index => $item) {
            // Ensure quantity is numeric and integer
            $quantity = 0;
            if (isset($item['quantity']) && is_numeric($item['quantity'])) {
                $quantity = intval($item['quantity']);
            }
            
            // Ensure unit_price is numeric and integer (no decimals)
            $unitPrice = 0;
            if (isset($item['unit_price'])) {
                // Remove all non-numeric characters
                $cleanPrice = preg_replace('/[^0-9]/', '', $item['unit_price']);
                if (is_numeric($cleanPrice)) {
                    $unitPrice = intval($cleanPrice);
                }
            }
            
            // Calculate item total and add to grand total
            $itemTotal = $quantity * $unitPrice;
            $this->totalAmount += $itemTotal;
            
            // Update the item in the array with clean values
            $this->items[$index]['quantity'] = $quantity;
            $this->items[$index]['unit_price'] = $unitPrice;
        }
        
        // Force re-render to ensure UI updates
        $this->dispatch('totals-updated');
    }

    public function getTotalAmountProperty()
    {
        $total = 0;
        
        foreach ($this->items as $item) {
            $quantity = 0;
            if (isset($item['quantity']) && is_numeric($item['quantity'])) {
                $quantity = intval($item['quantity']);
            }

            $unitPrice = 0;
            if (isset($item['unit_price'])) {
                // Remove all non-numeric characters
                $cleanPrice = preg_replace('/[^0-9]/', '', $item['unit_price']);
                if (is_numeric($cleanPrice)) {
                    $unitPrice = intval($cleanPrice);
                }
            }

            $total += $quantity * $unitPrice;
        }
        
        return $total;
    }

    public function saveDraft()
    {
        $this->isLoading = true;
        
        try {
            // Validate basic fields
            $this->validate([
                'purpose' => 'required|string|min:3|max:500',
                'used_for' => 'required|string|min:10|max:1000',
            ]);

            DB::beginTransaction();
            
            // Generate PR number with current date
            $currentDate = Carbon::today();
            $numberingService = app(UniversalPRNumberingService::class);
            $result = $numberingService->generatePRNumber(
                Auth::user(),
                session('current_business_unit_id'),
                null,
                $currentDate
            );

            // Create purchase request as draft
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => $result['formatted_number'],
                'business_unit_id' => session('current_business_unit_id'),
                'department_id' => session('current_department_id'),
                'user_id' => Auth::id(),
                'sequence_id' => $result['sequence_id'],
                'keperluan' => $this->purpose,
                'used_for' => $this->used_for,
                'date_of_request' => $currentDate->format('Y-m-d'),
                'status' => 'draft',
                'currency' => $this->currency,
                'last_modified_by' => Auth::id(),
            ]);

            // Create PR items if any
            foreach ($this->items as $index => $itemData) {
                if (!empty($itemData['item_name'])) {
                    PrItem::create([
                        'purchase_request_id' => $purchaseRequest->id,
                        'item_order' => $index + 1,
                        'item_name' => $itemData['item_name'],
                        'brand_name' => $itemData['brand_name'],
                        'item_description' => $itemData['item_description'],
                        'supplier_name' => $itemData['supplier_name'],
                        'quantity' => $itemData['quantity'] ?? 1,
                        'unit' => $itemData['unit'] ?? 'pcs',
                        'unit_price' => str_replace(',', '', $itemData['unit_price'] ?? 0),
                        'currency' => $this->currency,
                        'expense_department_id' => session('current_department_id'),
                    ]);
                }
            }

            // Update total amount
            $purchaseRequest->updateTotalAmount();

            DB::commit();

            session()->flash('success', "Purchase Request {$result['formatted_number']} has been saved as draft.");
            return redirect()->route('purchase-requests.show', $purchaseRequest);

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to save draft: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function submitPurchaseRequest()
    {
        $this->isLoading = true;
        
        try {
            // Initialize session data from authenticated user if not exists
            $this->ensureSessionData();
            
            // Check session data
            if (!session('current_business_unit_id') || !session('current_department_id')) {
                $this->dispatch('notify',
                    message: 'Session expired. Please refresh the page and try again.',
                    type: 'error'
                );
                throw new \Exception('Missing business unit or department session data. Please refresh the page and try again.');
            }
            
            // Validate the complete form with enhanced error reporting
            $this->validateForm();
            
            // Additional validation for custom approval
            if ($this->approvalFlow === 'custom') {
                $this->validateCustomApproval();
            }

            DB::beginTransaction();
            
            // Generate PR number with current date
            $currentDate = Carbon::today();
            $numberingService = app(UniversalPRNumberingService::class);
            $result = $numberingService->generatePRNumber(
                Auth::user(),
                session('current_business_unit_id'),
                null,
                $currentDate
            );

            // Create purchase request
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => $result['formatted_number'],
                'business_unit_id' => session('current_business_unit_id'),
                'department_id' => session('current_department_id'),
                'user_id' => Auth::id(),
                'sequence_id' => $result['sequence_id'],
                'keperluan' => $this->purpose,
                'used_for' => $this->used_for,
                'date_of_request' => $currentDate->format('Y-m-d'),
                'status' => 'submitted',
                'submitted_at' => now(),
                'currency' => $this->currency,
                'last_modified_by' => Auth::id(),
            ]);

            // Create PR items
            foreach ($this->items as $index => $itemData) {
                PrItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'item_order' => $index + 1,
                    'item_name' => $itemData['item_name'],
                    'brand_name' => $itemData['brand_name'],
                    'item_description' => $itemData['item_description'],
                    'supplier_name' => $itemData['supplier_name'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price' => str_replace(',', '', $itemData['unit_price']),
                    'currency' => $this->currency,
                    'expense_department_id' => session('current_department_id'),
                ]);
            }

            // Update total amount
            $purchaseRequest->updateTotalAmount();

            // Create approval workflow based on selected flow
            if ($this->approvalFlow === 'custom') {
                $this->createCustomApprovalWorkflow($purchaseRequest);
            } else {
                // Use automatic approval workflow
                $workflowService = app(ApprovalWorkflowService::class);
                $workflowService->createWorkflow($purchaseRequest);
            }

            DB::commit();

            session()->flash('success', "Purchase Request {$result['formatted_number']} has been submitted for approval.");
            return redirect()->route('purchase-requests.show', $purchaseRequest);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            
            // Consolidate validation errors into single toast
            $errors = $e->validator->errors();
            $totalErrors = $errors->count();
            
            if ($totalErrors > 1) {
                // Create consolidated error message
                $errorList = [];
                foreach ($errors->all() as $error) {
                    $errorList[] = "• " . $error;
                }
                $consolidatedMessage = "Found {$totalErrors} validation errors:<br>" . implode("<br>", array_slice($errorList, 0, 5));
                
                if ($totalErrors > 5) {
                    $consolidatedMessage .= "<br>• ... and " . ($totalErrors - 5) . " more errors";
                }
                
                $this->dispatch('notify',
                    message: $consolidatedMessage,
                    type: 'error',
                    duration: 10000
                );
            } else {
                // Single error message
                $this->dispatch('notify',
                    message: 'Validation Error: ' . $errors->first(),
                    type: 'error',
                    duration: 8000
                );
            }
            
            // Re-throw to show field-specific errors
            throw $e;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->dispatch('notify',
                message: 'Failed to submit: ' . $e->getMessage(),
                type: 'error',
                duration: 8000
            );
            
            session()->flash('error', 'Failed to submit purchase request: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

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

    /**
     * Ensure session data is available from authenticated user
     */
    private function ensureSessionData()
    {
        if (!session('current_business_unit_id') || !session('current_department_id')) {
            $user = Auth::user();
            
            if ($user) {
                // Get user's primary business unit and department
                if ($user->primaryDepartment && $user->primaryDepartment->businessUnit) {
                    session([
                        'current_business_unit_id' => $user->primaryDepartment->businessUnit->id,
                        'current_business_unit_code' => $user->primaryDepartment->businessUnit->code,
                        'current_business_unit_name' => $user->primaryDepartment->businessUnit->name,
                        'current_department_id' => $user->primaryDepartment->id,
                        'current_user_role' => $user->global_role,
                    ]);
                } elseif ($user->global_role === 'super_admin') {
                    // For super admin, use WG business unit as fallback
                    $wgBusinessUnit = \App\Models\BusinessUnit::where('code', 'WG')->first();
                    if ($wgBusinessUnit) {
                        $corporateDept = \App\Models\Department::where('business_unit_id', $wgBusinessUnit->id)
                                                               ->where('code', 'CORP')
                                                               ->first();
                        
                        session([
                            'current_business_unit_id' => $wgBusinessUnit->id,
                            'current_business_unit_code' => $wgBusinessUnit->code,
                            'current_business_unit_name' => $wgBusinessUnit->name,
                            'current_department_id' => $corporateDept ? $corporateDept->id : null,
                            'current_user_role' => 'super_admin',
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Enhanced validation with toast notifications
     */
    public function validateForm()
    {
        try {
            $this->validate();
            
            // If validation passes, show success message
            $this->dispatch('notify',
                message: 'Form validation passed!',
                type: 'success',
                duration: 3000
            );
            
            return true;
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Get all validation errors
            $errors = $e->validator->errors();
            $totalErrors = $errors->count();
            
            if ($totalErrors > 1) {
                // Create consolidated error message
                $errorList = [];
                foreach ($errors->all() as $error) {
                    $errorList[] = "• " . $error;
                }
                $consolidatedMessage = "Found {$totalErrors} validation errors:<br>" . implode("<br>", array_slice($errorList, 0, 5));
                
                if ($totalErrors > 5) {
                    $consolidatedMessage .= "<br>• ... and " . ($totalErrors - 5) . " more errors";
                }
                
                $this->dispatch('notify',
                    message: $consolidatedMessage,
                    type: 'error',
                    duration: 12000
                );
            } else {
                // Show specific error for single issue
                $this->dispatch('notify',
                    message: 'Validation Error: ' . $errors->first(),
                    type: 'error',
                    duration: 8000
                );
            }
            
            // Re-throw to show field-specific errors
            throw $e;
        }
    }

    /**
     * Validate specific field and show toast if error
     */
    public function validateField($field)
    {
        try {
            $this->validateOnly($field);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $error = $e->validator->errors()->first($field);
            if ($error) {
                $this->dispatch('notify',
                    message: $error,
                    type: 'error',
                    duration: 5000
                );
            }
        }
    }

    public function render()
    {
        return view('livewire.modules.wns.purchase-requests.create');
    }
}