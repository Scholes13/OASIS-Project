<?php

namespace App\Livewire\Modules\StockRequest;

use App\Models\Core\Department;
use App\Models\Modules\StockRequest\StockItem;
use App\Models\Modules\StockRequest\StockRequest;
use App\Services\Modules\StockRequest\UniversalStockNumberingService;
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
    public $request_date = '';
    public $expected_date = '';
    public $notes = '';
    public $items = [];
    public $itemImages = [];

    // Auto-generated fields
    public $submission_date = '';
    public $department_name = '';
    public $department_code = '';
    public $user_name = '';

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
            'request_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:request_date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'required|string|max:50',
            'items.*.specifications' => 'nullable|string|max:1000',
            'items.*.item_code' => 'nullable|string|max:100',
            'items.*.notes' => 'nullable|string|max:500',
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
        $this->request_date = now()->format('Y-m-d');
        $this->expected_date = now()->addDays(7)->format('Y-m-d');
        $this->addItem();
    }

    protected function initializeEditState(): void
    {
        $sr = $this->existingStockRequest;
        $this->purpose = $sr->purpose;
        $this->request_date = $sr->date_of_request->format('Y-m-d');
        $this->expected_date = $sr->expected_date?->format('Y-m-d') ?? '';
        $this->notes = $sr->notes;
        $this->isRejected = $sr->status === 'rejected';

        $this->items = $sr->items->map(fn($item) => [
            'id' => $item->id,
            'item_order' => $item->item_order,
            'item_name' => $item->item_name,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'specifications' => $item->specifications,
            'item_code' => $item->item_code,
            'image_path' => $item->image_path,
            'notes' => $item->notes,
        ])->toArray();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'item_order' => count($this->items) + 1,
            'item_name' => '',
            'quantity' => 1,
            'unit' => 'pcs',
            'specifications' => '',
            'item_code' => '',
            'image_path' => null,
            'notes' => '',
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

    public function handleBusinessUnitSwitch($businessUnitId): void
    {
        // Reset form state
        $this->purpose = '';
        $this->expected_date = now()->addDays(7)->format('Y-m-d');
        $this->items = [];
        $this->customApprovalList = [];
        $this->total_amount = 0;
        
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

        $stockRequest = StockRequest::create([
            'st_number' => $stNumberData['formatted_number'],
            'sequence_id' => $stNumberData['sequence_id'],
            'business_unit_id' => $this->business_unit_id,
            'department_id' => $this->department_id,
            'user_id' => Auth::id(),
            'purpose' => $this->purpose,
            'date_of_request' => Carbon::parse($this->request_date),
            'expected_date' => $this->expected_date ? Carbon::parse($this->expected_date) : null,
            'notes' => $this->notes,
            'status' => 'submitted',
            'submitted_at' => now(),
            'last_modified_by' => Auth::id(),
        ]);

        $this->createStockItems($stockRequest);
    }

    protected function updateStockRequest(): void
    {
        $stockRequest = $this->existingStockRequest;
        $targetStatus = $stockRequest->status === 'rejected' ? 'rejected' : 'submitted';

        $stockRequest->update([
            'purpose' => $this->purpose,
            'date_of_request' => Carbon::parse($this->request_date),
            'expected_date' => $this->expected_date ? Carbon::parse($this->expected_date) : null,
            'notes' => $this->notes,
            'status' => $targetStatus,
            'last_modified_by' => Auth::id(),
        ]);

        $stockRequest->items()->delete();
        $this->createStockItems($stockRequest);
    }

    protected function createStockItems(StockRequest $stockRequest): void
    {
        foreach ($this->items as $index => $itemData) {
            $stockItemData = [
                'stock_request_id' => $stockRequest->id,
                'item_order' => $index + 1,
                'item_name' => $itemData['item_name'],
                'quantity' => (int) $itemData['quantity'],
                'unit' => $itemData['unit'],
                'specifications' => $itemData['specifications'] ?? null,
                'item_code' => $itemData['item_code'] ?? null,
                'notes' => $itemData['notes'] ?? null,
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

    public function render()
    {
        return view('livewire.modules.stock-request.create');
    }
}
