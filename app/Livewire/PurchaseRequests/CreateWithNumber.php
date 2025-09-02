<?php

namespace App\Livewire\PurchaseRequests;

use App\Models\Department;
use App\Models\Modules\WNS\PurchaseRequest;
use App\Models\Modules\WNS\PrItem;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CreateWithNumber extends Component
{
    // PR Number from previous step
    public $prNumber;
    public $sequenceId;
    public $numberDetails;
    
    // Form fields
    public $keperluan = '';
    public $used_for = '';
    public $date_of_request; // Auto dari PR number creation
    public $expected_date; // User input - kapan barang dibutuhkan
    public $items = [];
    public $departments = [];
    public $approvers = []; // Manual approval selection
    public $selected_approvers = []; // Selected approvers
    
    // State
    public $isLoading = false;
    public $totalAmount = 0;
    public $currency = 'IDR';
    
    // Validation rules
    protected $rules = [
        'keperluan' => 'required|string|max:500',
        'used_for' => 'required|string|max:1000',
        'expected_date' => 'required|date|after_or_equal:today',
        'selected_approvers' => 'required|array|min:1',
        'selected_approvers.*' => 'required|exists:users,id',
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
    ];

    protected $messages = [
        'keperluan.required' => 'The purpose field is required.',
        'used_for.required' => 'The description field is required.',
        'expected_date.required' => 'Expected date is required.',
        'expected_date.after_or_equal' => 'Expected date cannot be in the past.',
        'selected_approvers.required' => 'At least one approver must be selected.',
        'selected_approvers.min' => 'At least one approver must be selected.',
        'items.required' => 'At least one item is required.',
        'items.min' => 'At least one item is required.',
        'items.*.item_name.required' => 'Item name is required.',
        'items.*.quantity.required' => 'Quantity is required.',
        'items.*.quantity.min' => 'Quantity must be greater than 0.',
        'items.*.unit_price.required' => 'Unit price is required.',
        'items.*.unit_price.min' => 'Unit price must be 0 or greater.',
        'items.*.expense_department_id.required' => 'Expense department is required.',
    ];

    public function mount()
    {
        // Get number details from session
        $numberDetails = session('pr_number_details');
        
        if (!$numberDetails) {
            session()->flash('error', 'No PR number found. Please generate a PR number first.');
            return $this->redirect(route('purchase-requests.request-number'));
        }
        
        $this->numberDetails = $numberDetails;
        $this->prNumber = $numberDetails['formatted_number'] ?? null;
        $this->sequenceId = $numberDetails['sequence_id'] ?? null;
        
        // Pre-fill from number request
        $this->keperluan = $numberDetails['purpose'] ?? '';
        $this->used_for = $numberDetails['description'] ?? '';
        $this->currency = $numberDetails['currency'] ?? 'IDR';
        $this->date_of_request = $numberDetails['submission_date'] ?? Carbon::today()->format('Y-m-d');
        $this->expected_date = ''; // User akan input manual
        
        $this->loadDepartments();
        $this->loadApprovers();
        $this->addItem();
    }

    public function loadDepartments()
    {
        $this->departments = Department::where('business_unit_id', session('current_business_unit_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function loadApprovers()
    {
        // Load semua user yang bisa jadi approver (tidak termasuk user yang sedang login)
        $this->approvers = \App\Models\User::where('business_unit_id', session('current_business_unit_id'))
            ->where('is_active', true)
            ->where('id', '!=', Auth::id()) // Exclude current user
            ->with(['department', 'position'])
            ->orderBy('name')
            ->get();
    }

    public function addApprover($userId)
    {
        if (!in_array($userId, $this->selected_approvers)) {
            $this->selected_approvers[] = $userId;
        }
    }

    public function removeApprover($userId)
    {
        $this->selected_approvers = array_filter($this->selected_approvers, function($id) use ($userId) {
            return $id != $userId;
        });
        $this->selected_approvers = array_values($this->selected_approvers); // Reset array keys
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
            'expense_department_id' => session('current_department_id'),
            'total_price' => 0,
        ];
        
        $this->calculateTotals();
    }

    public function removeItem($index)
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
            $this->calculateTotals();
        }
    }

    public function updatedItems()
    {
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->totalAmount = 0;
        
        foreach ($this->items as $index => $item) {
            $quantity = floatval($item['quantity'] ?? 0);
            $unitPrice = floatval($item['unit_price'] ?? 0);
            $totalPrice = $quantity * $unitPrice;
            
            $this->items[$index]['total_price'] = $totalPrice;
            $this->totalAmount += $totalPrice;
        }
    }

    public function saveDraft()
    {
        $this->isLoading = true;
        
        try {
            $this->validate();
            
            DB::beginTransaction();
            
            // Create purchase request with pre-generated number
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => $this->prNumber,
                'business_unit_id' => session('current_business_unit_id'),
                'department_id' => session('current_department_id'),
                'user_id' => Auth::id(),
                'sequence_id' => $this->sequenceId,
                'keperluan' => $this->keperluan,
                'used_for' => $this->used_for,
                'date_of_request' => $this->date_of_request, // Auto dari PR number creation
                'expected_date' => $this->expected_date, // User input
                'status' => 'draft',
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
                    'unit_price' => $itemData['unit_price'],
                    'currency' => $itemData['currency'],
                    'expense_department_id' => $itemData['expense_department_id'],
                ]);
            }

            // Update total amount
            $purchaseRequest->updateTotalAmount();

            DB::commit();

            // Clear session data
            session()->forget('pr_number_details');

            session()->flash('success', "Purchase Request {$this->prNumber} has been saved as draft.");
            
            return $this->redirect(route('purchase-requests.show', $purchaseRequest));

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to save purchase request: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function saveAndSubmit()
    {
        $this->isLoading = true;
        
        try {
            $this->validate();
            
            DB::beginTransaction();
            
            // Create purchase request with pre-generated number
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => $this->prNumber,
                'business_unit_id' => session('current_business_unit_id'),
                'department_id' => session('current_department_id'),
                'user_id' => Auth::id(),
                'sequence_id' => $this->sequenceId,
                'keperluan' => $this->keperluan,
                'used_for' => $this->used_for,
                'date_of_request' => $this->date_of_request, // Auto dari PR number creation
                'expected_date' => $this->expected_date, // User input
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
                    'unit_price' => $itemData['unit_price'],
                    'currency' => $itemData['currency'],
                    'expense_department_id' => $itemData['expense_department_id'],
                ]);
            }

            // Update total amount
            $purchaseRequest->updateTotalAmount();
            
            // Create manual approval workflow
            $this->createManualApprovalWorkflow($purchaseRequest);

            DB::commit();

            // Clear session data
            session()->forget('pr_number_details');

            session()->flash('success', "Purchase Request {$this->prNumber} has been submitted for approval.");
            
            return $this->redirect(route('purchase-requests.show', $purchaseRequest));

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to submit purchase request: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Create manual approval workflow based on selected approvers
     */
    protected function createManualApprovalWorkflow(PurchaseRequest $purchaseRequest): void
    {
        // Set status to in approval
        $purchaseRequest->update([
            'status' => 'in_approval',
            'is_sequential_approval' => true // Default to sequential
        ]);
        
        // Create PrApproval records for each selected approver
        foreach ($this->selected_approvers as $index => $approverId) {
            \App\Models\Modules\WNS\PrApproval::create([
                'purchase_request_id' => $purchaseRequest->id,
                'approver_id' => $approverId,
                'step_order' => $index + 1, // Sequential order
                'status' => 'pending',
                'assigned_at' => now(),
                'due_date' => now()->addDays(3), // Default 3-day deadline
            ]);
        }
    }

    /**
     * Create approval workflow (proper implementation)
     */
    protected function createApprovalWorkflow(PurchaseRequest $purchaseRequest): void
    {
        // Prepare data for workflow evaluation
        $workflowData = [
            'total_amount' => $purchaseRequest->total_amount,
            'department_code' => $purchaseRequest->department->code,
            'business_unit_id' => $purchaseRequest->business_unit_id,
        ];
        
        // Find matching workflow based on conditions
        $workflow = \App\Models\ApprovalWorkflow::getWorkflowForConditions(
            $purchaseRequest->business_unit_id,
            'purchase_request',
            $workflowData
        );
        
        // Fallback to default workflow if no match
        if (!$workflow) {
            $workflow = \App\Models\ApprovalWorkflow::getDefaultWorkflow(
                $purchaseRequest->business_unit_id,
                'purchase_request'
            );
        }
        
        // Store workflow and create approval steps
        if ($workflow) {
            $purchaseRequest->update([
                'approval_workflow' => $workflow->approval_steps,
                'is_sequential_approval' => $workflow->is_sequential,
                'status' => 'in_approval'
            ]);
            
            // Create PrApproval records for each step
            foreach ($workflow->approval_steps as $step) {
                \App\Models\Modules\WNS\PrApproval::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'approver_id' => $step['approver_id'],
                    'step_order' => $step['step'],
                    'status' => 'pending',
                    'assigned_at' => now(),
                    'due_date' => now()->addDays(3), // Default 3-day deadline
                ]);
            }
        } else {
            throw new \Exception('No approval workflow found for this purchase request');
        }
    }

    public function render()
    {
        return view('livewire.purchase-requests.create-with-number');
    }
}