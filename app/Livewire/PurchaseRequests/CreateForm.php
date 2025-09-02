<?php

namespace App\Livewire\PurchaseRequests;

use App\Models\Department;
use App\Models\Modules\WNS\PurchaseRequest;
use App\Models\Modules\WNS\PrItem;
use App\Services\Modules\WNS\PRNumberingService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CreateForm extends Component
{
    // Form fields
    public $keperluan = '';
    public $used_for = '';
    public $date_of_request;
    public $items = [];
    public $departments = [];
    
    // State
    public $isLoading = false;
    public $totalAmount = 0;
    public $currency = 'IDR';
    
    // Validation rules
    protected $rules = [
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
    ];

    protected $messages = [
        'keperluan.required' => 'The purpose field is required.',
        'used_for.required' => 'The description field is required.',
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
        $this->date_of_request = Carbon::today()->format('Y-m-d');
        $this->loadDepartments();
        $this->addItem();
    }

    public function loadDepartments()
    {
        $this->departments = Department::where('business_unit_id', session('current_business_unit_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
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
            
            $numberingService = app(PRNumberingService::class);
            
            DB::beginTransaction();
            
            // Generate PR number
            $prNumber = $numberingService->generatePRNumber(
                Auth::user(),
                Carbon::parse($this->date_of_request)
            );

            // Create purchase request
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => $prNumber['formatted_number'],
                'business_unit_id' => session('current_business_unit_id'),
                'department_id' => session('current_department_id'),
                'user_id' => Auth::id(),
                'sequence_id' => $prNumber['sequence_id'],
                'keperluan' => $this->keperluan,
                'used_for' => $this->used_for,
                'date_of_request' => $this->date_of_request,
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

            session()->flash('success', "Purchase Request {$prNumber['formatted_number']} has been saved as draft.");
            
            return $this->redirect(route('purchase-requests.show', $purchaseRequest), navigate: true);

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
            
            $numberingService = app(PRNumberingService::class);
            
            DB::beginTransaction();
            
            // Generate PR number
            $prNumber = $numberingService->generatePRNumber(
                Auth::user(),
                Carbon::parse($this->date_of_request)
            );

            // Create purchase request
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => $prNumber['formatted_number'],
                'business_unit_id' => session('current_business_unit_id'),
                'department_id' => session('current_department_id'),
                'user_id' => Auth::id(),
                'sequence_id' => $prNumber['sequence_id'],
                'keperluan' => $this->keperluan,
                'used_for' => $this->used_for,
                'date_of_request' => $this->date_of_request,
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

            DB::commit();

            session()->flash('success', "Purchase Request {$prNumber['formatted_number']} has been submitted for approval.");
            
            return $this->redirect(route('purchase-requests.show', $purchaseRequest), navigate: true);

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to submit purchase request: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('livewire.purchase-requests.create-form');
    }
}