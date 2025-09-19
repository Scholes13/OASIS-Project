<div class="max-w-none mx-auto space-y-8">
    <!-- Purchase Request Header -->
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="px-8 py-6 border-b border-gray-100">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Purchase Request Form</h1>
            <p class="mt-2 text-sm text-gray-600 leading-relaxed">Complete all required fields to submit your purchase request for approval</p>
        </div>
        
        <!-- Basic Information Section -->
        <div class="px-8 py-8">
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Request Information</h2>
                <div class="h-px bg-gray-200 mb-6"></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Purpose / Requirements -->
                <div class="lg:col-span-2">
                    <label for="purpose" class="block text-sm font-semibold text-gray-900 mb-3">
                        Purpose / Requirements
                        <span class="text-red-500 ml-1">*</span>
                    </label>
                    <textarea 
                        wire:model.live="purpose" 
                        id="purpose"
                        rows="4"
                        class="w-full px-4 py-3 border border-gray-300 rounded-md text-sm placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                        placeholder="Describe the purpose or requirements for this purchase request"
                        maxlength="500"
                    ></textarea>
                    @error('purpose')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Used For / Details -->
                <div class="lg:col-span-2">
                    <label for="used_for" class="block text-sm font-semibold text-gray-900 mb-3">
                        Usage Details
                        <span class="text-red-500 ml-1">*</span>
                    </label>
                    <textarea 
                        wire:model.live="used_for" 
                        id="used_for"
                        rows="5"
                        class="w-full px-4 py-3 border border-gray-300 rounded-md text-sm placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                        placeholder="Provide detailed information about how these items will be used"
                        maxlength="1000"
                    ></textarea>
                    @error('used_for')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Expected Delivery Date -->
                <div>
                    <label for="expected_date" class="block text-sm font-semibold text-gray-900 mb-3">
                        Expected Delivery Date
                    </label>
                    <input 
                        wire:model.live="expected_date" 
                        id="expected_date"
                        type="date"
                        min="{{ date('Y-m-d') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                    >
                    @error('expected_date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Currency -->
                <div>
                    <label for="currency" class="block text-sm font-semibold text-gray-900 mb-3">
                        Currency
                        <span class="text-red-500 ml-1">*</span>
                    </label>
                    <select 
                        wire:model.live="currency" 
                        id="currency"
                        class="w-full px-4 py-3 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 bg-white">
                        <option value="IDR">IDR - Indonesian Rupiah</option>
                        <option value="USD">USD - US Dollar</option>
                        <option value="EUR">EUR - Euro</option>
                        <option value="SGD">SGD - Singapore Dollar</option>
                    </select>
                    @error('currency')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Request Details (Auto-filled) -->
                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-gray-900 mb-3">Submission Details</label>
                    <div class="bg-gray-50 border border-gray-200 rounded-md p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Submission Date</div>
                                <div class="text-sm font-medium text-gray-900">{{ $submission_date }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Requested By</div>
                                <div class="text-sm font-medium text-gray-900">{{ $user_name }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Department</div>
                                <div class="text-sm font-medium text-gray-900">{{ $department_name }} ({{ $department_code }})</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Management Section -->
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="px-8 py-6 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Request Items</h2>
                    <p class="mt-1 text-sm text-gray-600">Add and configure items for this purchase request</p>
                </div>
                
                <!-- Add Item Button -->
                <button 
                    wire:click="addItem" 
                    type="button"
                    onclick="this.disabled=true; setTimeout(() => this.disabled=false, 500);"
                    class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-semibold rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                    Add New Item
                </button>
            </div>
        </div>

        <!-- Items Table -->
        <div class="px-8 py-8">
            @if(count($items) === 0)
                <div class="text-center py-12 border-2 border-dashed border-gray-300 rounded-md">
                    <div class="text-gray-400 mb-2 text-lg font-medium">No Items Added</div>
                    <p class="text-sm text-gray-500 mb-4">Click "Add New Item" to begin adding items to your purchase request</p>
                    <button 
                        wire:click="addItem" 
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        Add First Item
                    </button>
                </div>
            @else
                <div class="overflow-hidden border border-gray-200 rounded-md">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider border-r border-gray-200 w-12">No</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider border-r border-gray-200 min-w-56">Item Name</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider border-r border-gray-200 min-w-32">Brand</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider border-r border-gray-200 min-w-48">Description</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider border-r border-gray-200 min-w-36">Supplier</th>
                                <th class="px-4 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider border-r border-gray-200 w-24">Quantity</th>
                                <th class="px-4 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider border-r border-gray-200 w-24">Unit</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider border-r border-gray-200 w-32">Unit Price</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider border-r border-gray-200 w-32">Total</th>
                                <th class="px-4 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($items as $index => $item)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <!-- Row Number -->
                                    <td class="px-4 py-4 text-sm text-gray-900 text-center font-semibold border-r border-gray-200">
                                        {{ $index + 1 }}
                                    </td>

                                    <!-- Item Name -->
                                    <td class="px-4 py-4 border-r border-gray-200">
                                        <input 
                                            wire:model.live="items.{{ $index }}.item_name" 
                                            type="text"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                            placeholder="Enter item name"
                                            maxlength="255"
                                        >
                                        @error("items.{$index}.item_name") 
                                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </td>

                                    <!-- Brand -->
                                    <td class="px-4 py-4 border-r border-gray-200">
                                        <input 
                                            wire:model.live="items.{{ $index }}.brand_name" 
                                            type="text"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                            placeholder="Brand name"
                                            maxlength="255"
                                        >
                                        @error("items.{$index}.brand_name") 
                                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </td>

                                    <!-- Description -->
                                    <td class="px-4 py-4 border-r border-gray-200">
                                        <input 
                                            wire:model.live="items.{{ $index }}.item_description" 
                                            type="text"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                            placeholder="Description"
                                            maxlength="500"
                                        >
                                        @error("items.{$index}.item_description") 
                                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </td>

                                    <!-- Supplier -->
                                    <td class="px-4 py-4 border-r border-gray-200">
                                        <input 
                                            wire:model.live="items.{{ $index }}.supplier_name" 
                                            type="text"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                            placeholder="Supplier name"
                                            maxlength="255"
                                        >
                                        @error("items.{$index}.supplier_name") 
                                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </td>

                                    <!-- Quantity -->
                                    <td class="px-4 py-4 border-r border-gray-200">
                                        <input 
                                            wire:model.blur="items.{{ $index }}.quantity" 
                                            type="number"
                                            step="1"
                                            min="1"
                                            value="{{ $item['quantity'] ?? 1 }}"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 text-center [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                            placeholder="1"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, ''); calculateRowTotal({{ $index }});"
                                        >
                                        @error("items.{$index}.quantity") 
                                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </td>

                                    <!-- Unit -->
                                    <td class="px-4 py-4 border-r border-gray-200">
                                        <select 
                                            wire:model.live="items.{{ $index }}.unit" 
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 bg-white text-center appearance-none">
                                            <option value="">Select unit</option>
                                            <option value="pcs">pcs</option>
                                            <option value="unit">unit</option>
                                            <option value="set">set</option>
                                            <option value="box">box</option>
                                            <option value="pack">pack</option>
                                            <option value="roll">roll</option>
                                            <option value="meter">meter</option>
                                            <option value="kg">kg</option>
                                            <option value="liter">liter</option>
                                            <option value="dozen">dozen</option>
                                            <option value="carton">carton</option>
                                            <option value="bottle">bottle</option>
                                            <option value="pair">pair</option>
                                            <option value="sheet">sheet</option>
                                            <option value="rim">rim</option>
                                            <option value="other">other</option>
                                        </select>
                                        @error("items.{$index}.unit") 
                                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </td>

                                    <!-- Unit Price -->
                                    <td class="px-4 py-4 border-r border-gray-200">
                                        <input 
                                            wire:model.blur="items.{{ $index }}.unit_price" 
                                            type="text"
                                            value="{{ number_format($item['unit_price'] ?? 0, 0, ',', '.') }}"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 text-right"
                                            placeholder="0"
                                            oninput="
                                                this.value = this.value.replace(/[^0-9]/g, '');
                                                if(this.value) this.value = new Intl.NumberFormat('id-ID').format(this.value.replace(/\./g, ''));
                                                calculateRowTotal({{ $index }});
                                            "
                                        >
                                        @error("items.{$index}.unit_price") 
                                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </td>

                                    <!-- Total Price -->
                                    <td class="px-4 py-4 border-r border-gray-200">
                                        <div class="px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-700 text-right font-medium">
                                            <span id="row-total-{{ $index }}">
                                                {{ number_format(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0), 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Remove Action -->
                                    <td class="px-4 py-4 text-center">
                                        <button 
                                            wire:click="removeItem({{ $index }})" 
                                            type="button"
                                            class="inline-flex items-center justify-center w-8 h-8 text-red-600 hover:bg-red-50 rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                            title="Remove item">
                                            <span class="text-lg font-bold">×</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Add Item Button -->
    <div class="flex justify-start mt-4">
        <button 
            wire:click="addItem" 
            type="button"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 border border-blue-300 rounded-md hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
            <span class="mr-2">+</span>
            Add Item
        </button>
    </div>
</div>

<!-- Request Summary -->
<div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Summary</h3>
    
    <div class="space-y-4">
        <div class="flex justify-between items-center pb-3 border-b border-gray-200">
            <span class="text-sm font-medium text-gray-600">Total Items:</span>
            <span class="text-sm font-semibold text-gray-900" id="total-items">{{ count($items) }}</span>
        </div>
        
        <div class="flex justify-between items-center">
            <span class="text-base font-semibold text-gray-900">Grand Total:</span>
            <span class="text-xl font-bold text-blue-600" id="grand-total">
                Rp {{ number_format($this->getGrandTotal(), 0, ',', '.') }}
            </span>
        </div>
    </div>
</div>

<!-- Approval Request -->
<div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Approval</h3>
    
    <div class="space-y-4">
        <div>
            <label for="approval_notes" class="block text-sm font-medium text-gray-700 mb-2">
                Additional Notes (Optional)
            </label>
            <textarea 
                wire:model="approval_notes" 
                id="approval_notes"
                rows="4"
                class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 resize-none"
                placeholder="Add any additional notes or context for approvers..."></textarea>
            @error('approval_notes')
                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-4 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <p class="text-sm text-gray-600">
                    This request will be sent to the approval workflow based on the total amount.
                </p>
                
                <div class="flex space-x-3">
                    <button 
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200">
                        Save as Draft
                    </button>
                    
                    <button 
                        wire:click="submitRequest" 
                        wire:loading.attr="disabled"
                        type="button"
                        class="inline-flex items-center px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove>Submit Request</span>
                        <span wire:loading class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
// Enterprise-style calculation functions
function calculateRowTotal(index) {
    const qtyInput = document.querySelector(`input[wire\\:model\\.blur="items.${index}.quantity"]`);
    const priceInput = document.querySelector(`input[wire\\:model\\.blur="items.${index}.unit_price"]`);
    const totalSpan = document.getElementById(`row-total-${index}`);
    
    if (qtyInput && priceInput && totalSpan) {
        const qty = parseInt(qtyInput.value) || 0;
        const price = parseInt(priceInput.value.replace(/[^0-9]/g, '')) || 0;
        const total = qty * price;
        
        totalSpan.textContent = new Intl.NumberFormat('id-ID').format(total);
        updateGrandTotal();
    }
}

function updateGrandTotal() {
    let grandTotal = 0;
    const totalSpans = document.querySelectorAll('[id^="row-total-"]');
    
    totalSpans.forEach(span => {
        const value = parseInt(span.textContent.replace(/[^0-9]/g, '')) || 0;
        grandTotal += value;
    });
    
    const grandTotalElement = document.getElementById('grand-total');
    const totalItemsElement = document.getElementById('total-items');
    
    if (grandTotalElement) {
        grandTotalElement.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal);
    }
    
    if (totalItemsElement) {
        totalItemsElement.textContent = totalSpans.length;
    }
}

// Initialize calculations on page load
document.addEventListener('DOMContentLoaded', function() {
    updateGrandTotal();
});

// Update calculations after Livewire updates
document.addEventListener('livewire:navigated', function() {
    updateGrandTotal();
});
</script>
</div>


            <!-- Total Summary -->
            @if(count($items) > 0)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-medium text-gray-900">Total Amount:</span>
                        <span class="text-xl font-bold text-indigo-600">
                            <span id="grand-total">
                                @php
                                    try {
                                        $totalDisplay = number_format($this->totalAmount, 0, '', ',');
                                    } catch (Exception $e) {
                                        $totalDisplay = '0';
                                    }
                                @endphp
                                {{ $totalDisplay }}
                            </span>
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Approval Flow Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Approval Workflow</h3>
            <p class="text-sm text-gray-600 mt-1">Select how your purchase request should be approved</p>
        </div>

        <div class="p-6">
            <div class="space-y-6">
                <!-- Approval Flow Selection -->
                <div class="space-y-4 mb-6">
                    <div class="flex items-center space-x-4">
                        <input type="radio" 
                               wire:model.live="approvalFlow" 
                               value="automatic" 
                               id="approval-automatic"
                               name="approval_flow"
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 transition-colors duration-200">
                        <label for="approval-automatic" class="text-sm font-medium text-gray-700 cursor-pointer">
                            Automatic Approval (Default)
                            <svg wire:loading wire:target="approvalFlow" class="animate-spin ml-2 h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </label>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <input type="radio" 
                               wire:model.live="approvalFlow" 
                               value="custom" 
                               id="approval-custom"
                               name="approval_flow"
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 transition-colors duration-200">
                        <label for="approval-custom" class="text-sm font-medium text-gray-700 cursor-pointer">
                            Custom Approval (Manual Selection)
                        </label>
                    </div>
                </div>

                <!-- Automatic Approval Info -->
                @if($approvalFlow === 'automatic')
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h5 class="text-sm font-medium text-blue-900">Automatic Approval Workflow</h5>
                            <p class="text-sm text-blue-800 mt-1">
                                The approval workflow will be automatically created based on your department hierarchy and the total amount of this purchase request.
                            </p>
                            <div class="mt-2 text-xs text-blue-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>> IDR 500K: Department Head approval</li>
                                    <li>> IDR 1M: Finance Manager approval</li>
                                    <li>> IDR 5M: General Manager approval</li>
                                    <li>> IDR 10M: Director approval</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Custom Approval Settings -->
                @if($approvalFlow === 'custom')
                <div class="space-y-4">
                    <div class="bg-amber-50 rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-amber-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <div>
                                <h5 class="text-sm font-medium text-amber-900">Custom Approval Setup</h5>
                                <p class="text-sm text-amber-800 mt-1">
                                    You can set up to 5 approval layers and select specific approvers for each layer.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Number of Approval Layers -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Number of Approval Layers</label>
                        <div class="relative">
                            <select wire:model.live="customApprovalLayers" 
                                    class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 pr-10 transition-colors duration-200">
                                <option value="1">1 Layer</option>
                                <option value="2">2 Layers</option>
                                <option value="3">3 Layers</option>
                                <option value="4">4 Layers</option>
                                <option value="5">5 Layers</option>
                            </select>
                        </div>
                    </div>

                    <!-- Custom Approvers Selection -->
                    <div class="space-y-3">
                        @error('customApprovers')
                            <div class="bg-red-50 border border-red-200 rounded-md p-3">
                                <div class="flex">
                                    <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-sm text-red-700">{{ $message }}</span>
                                </div>
                            </div>
                        @enderror
                        
                        @for($i = 1; $i <= ($customApprovalLayers ?? 1); $i++)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Approval Layer {{ $i }}
                                </label>
                                <div class="relative">
                                    <select wire:model.live="customApprovers.{{ $i }}" 
                                            class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 @error('customApprovers.'.$i) border-red-300 @enderror pr-10 transition-colors duration-200">
                                        <option value="">Select Approver...</option>
                                        @foreach($availableApprovers as $approver)
                                            <option value="{{ $approver['id'] }}">
                                                {{ $approver['name'] }} - {{ ucfirst(str_replace('_', ' ', $approver['role'])) }} ({{ $approver['department'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('customApprovers.'.$i)
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        @endfor
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Submit Section with Enhanced UI -->
    <div class="bg-gradient-to-br from-indigo-50 via-white to-purple-50 rounded-xl shadow-lg border border-indigo-200">
        <!-- Header dengan gradient -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-t-xl p-4">
            <h3 class="text-lg font-semibold text-white flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Ready to Submit Your Purchase Request
            </h3>
        </div>

        <!-- Content Area -->
        <div class="px-6 py-6 space-y-6">
            <!-- Quick Summary Card -->
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">Purchase Request Summary</h4>
                            <p class="text-sm text-gray-500">Review your request details</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Total Items</p>
                        <p class="text-lg font-bold text-indigo-600" id="summary-item-count">{{ count($items) }}</p>
                    </div>
                </div>
                
                <!-- Total amount display -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-900">Estimated Total</span>
                        <span class="text-xl font-bold text-green-600" id="summary-grand-total">
                            Rp {{ number_format(collect($items)->sum(function($item) { return $item['quantity'] * $item['unit_price']; }), 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Instructions & Next Steps -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-blue-800">What happens next?</h4>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Your PR will be assigned a unique tracking number</li>
                                <li>Approval workflow will begin automatically based on amount</li>
                                <li>You'll receive email notifications for status updates</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons dengan enhanced styling -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <!-- Left side: Save as Draft -->
                <button 
                    wire:click="saveDraft" 
                    type="button"
                    onclick="showSavingState(this);"
                    class="inline-flex items-center justify-center px-5 py-3 border-2 border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:cursor-not-allowed transition-all duration-200 shadow-sm hover:shadow-md">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a.997.997 0 01-1.414 0L7 14.414V3z"></path>
                    </svg>
                    Save as Draft
                </button>

                <!-- Right side: Submit Button with gradient -->
                <button 
                    wire:click="submitPurchaseRequest" 
                    type="button"
                    onclick="showSubmittingState(this);"
                    class="inline-flex items-center justify-center px-8 py-3 border border-transparent rounded-lg text-base font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:cursor-not-allowed transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    Submit for Approval
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast notification listener for Livewire events -->
<script>
    document.addEventListener('livewire:init', function () {
        Livewire.on('notify', function(data) {
            if (typeof window.notify === 'function') {
                window.notify(data.message, data.type || 'info', data.duration || 5000);
            }
        });
    });
    
    // Loading state functions untuk buttons
    function showSavingState(button) {
        button.disabled = true;
        button.classList.add('opacity-75');
        button.innerHTML = '<svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Saving...';
    }
    
    function showSubmittingState(button) {
        button.disabled = true;
        button.classList.add('opacity-75');
        button.innerHTML = '<svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Submitting...';
    }
    
    // Client-side calculation untuk instant feedback
    function calculateRowTotal(index) {
        // Get quantity and price inputs
        const qtyInput = document.querySelector(`input[wire\\:model\\.blur="items.${index}.quantity"]`);
        const priceInput = document.querySelector(`input[wire\\:model\\.blur="items.${index}.unit_price"]`);
        const totalSpan = document.getElementById(`total-${index}`);
        
        if (qtyInput && priceInput && totalSpan) {
            const qty = parseInt(qtyInput.value) || 0;
            const price = parseInt(priceInput.value.replace(/[^0-9]/g, '')) || 0;
            const total = qty * price;
            
            // Update row total with number formatting
            totalSpan.textContent = new Intl.NumberFormat('id-ID').format(total);
            
            // Recalculate grand total
            calculateGrandTotal();
        }
    }
    
    function calculateGrandTotal() {
        let grandTotal = 0;
        let itemCount = 0;
        
        // Sum all row totals and count active items
        document.querySelectorAll('[id^="total-"]').forEach(function(totalSpan) {
            const value = totalSpan.textContent.replace(/[^0-9]/g, '');
            const total = parseInt(value) || 0;
            if (total > 0) {
                grandTotal += total;
                itemCount++;
            }
        });
        
        // Update grand total display
        const grandTotalSpan = document.getElementById('grand-total');
        if (grandTotalSpan) {
            grandTotalSpan.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal);
        }
        
        // Update summary section
        const summaryGrandTotal = document.getElementById('summary-grand-total');
        const summaryItemCount = document.getElementById('summary-item-count');
        
        if (summaryGrandTotal) {
            summaryGrandTotal.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal);
        }
        
        if (summaryItemCount) {
            summaryItemCount.textContent = itemCount;
        }
    }
    
    // Auto-validate fields on blur for better UX
    document.addEventListener('blur', function(e) {
        if (e.target.hasAttribute('wire:model') || e.target.hasAttribute('wire:model.blur')) {
            const fieldName = e.target.getAttribute('wire:model') || e.target.getAttribute('wire:model.blur');
            if (fieldName && typeof @this !== 'undefined') {
                // Extract field name without array indices for validation
                const baseField = fieldName.split('.')[0];
                @this.validateField(baseField);
            }
        }
    }, true);
</script>

<!-- Purchase Request Toast System -->
<script>
    // Wait for Toast Manager Helper Function
    function waitForToastManager(callback, maxAttempts = 50) {
        let attempts = 0;
        function check() {
            if (window.toastManager && typeof window.toastManager.show === 'function') {
                callback();
            } else if (attempts < maxAttempts) {
                attempts++;
                setTimeout(check, 100);
            } else {
                console.error('Toast manager not available for Purchase Request');
            }
        }
        check();
    }

    // Toast helper functions for Purchase Request
    function showSuccess(message, duration = 5000) {
        waitForToastManager(() => {
            if (window.toastManager && window.toastManager.show) {
                window.toastManager.show(message, 'success', duration);
            }
        });
    }

    function showError(message, duration = 8000) {
        waitForToastManager(() => {
            if (window.toastManager && window.toastManager.show) {
                window.toastManager.show(message, 'error', duration);
            }
        });
    }

    function showWarning(message, duration = 6000) {
        waitForToastManager(() => {
            if (window.toastManager && window.toastManager.show) {
                window.toastManager.show(message, 'warning', duration);
            }
        });
    }

    function showInfo(message, duration = 5000) {
        waitForToastManager(() => {
            if (window.toastManager && window.toastManager.show) {
                window.toastManager.show(message, 'info', duration);
            }
        });
    }

    function showValidationErrors(errors) {
        errors.forEach((error, index) => {
            setTimeout(() => showError(error, 8000), index * 300);
        });
    }

    // Check for Laravel session messages and show them as toast
    document.addEventListener('DOMContentLoaded', function() {
        @if (session('success'))
            showSuccess("{{ session('success') }}");
        @elseif (session('error'))
            showError("{{ session('error') }}");
        @elseif (session('warning'))
            showWarning("{{ session('warning') }}");
        @elseif (session('info'))
            showInfo("{{ session('info') }}");
        @endif

        @if ($errors->any())
            showValidationErrors({!! json_encode($errors->all()) !!});
        @endif
    });

    // Listen for Livewire events for toast notifications
    document.addEventListener('livewire:initialized', function() {
        Livewire.on('notify', (event) => {
            const { message, type = 'success', duration = 5000 } = event;
            switch(type) {
                case 'success':
                    showSuccess(message, duration);
                    break;
                case 'error':
                    showError(message, duration);
                    break;
                case 'warning':
                    showWarning(message, duration);
                    break;
                case 'info':
                    showInfo(message, duration);
                    break;
                default:
                    showSuccess(message, duration);
            }
        });
    });
</script>

<style>
/* Fix untuk dropdown appearance yang duplikat */
select.appearance-none {
    background-image: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important; 
    appearance: none !important;
}

/* Untuk browser Internet Explorer */
select::-ms-expand {
    display: none;
}

/* Untuk browser WebKit (Safari, Chrome) */
select.appearance-none::-webkit-outer-spin-button,
select.appearance-none::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>
