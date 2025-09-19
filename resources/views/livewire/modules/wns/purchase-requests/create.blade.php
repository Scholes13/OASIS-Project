<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Header -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Create Purchase Request</h1>
                    <p class="text-sm text-gray-600 mt-1">Create a new purchase request for approval workflow</p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('purchase-requests.index') }}" 
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200">
                        <span class="mr-2">←</span>
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        <!-- Basic Information -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                        Request Title <span class="text-red-500">*</span>
                    </label>
                    <input 
                        wire:model="title" 
                        type="text" 
                        id="title"
                        class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                        placeholder="Enter request title"
                        maxlength="255"
                        required>
                    @error('title')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="business_unit_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Business Unit <span class="text-red-500">*</span>
                    </label>
                    <select 
                        wire:model.live="business_unit_id" 
                        id="business_unit_id"
                        class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 bg-white"
                        required>
                        <option value="">Select Business Unit</option>
                        @foreach($businessUnits as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    @error('business_unit_id')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="department_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Department <span class="text-red-500">*</span>
                    </label>
                    <select 
                        wire:model.live="department_id" 
                        id="department_id"
                        class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 bg-white"
                        required>
                        <option value="">Select Department</option>
                        @if($business_unit_id && isset($departments))
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        @endif
                    </select>
                    @error('department_id')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="request_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Request Date <span class="text-red-500">*</span>
                    </label>
                    <input 
                        wire:model="request_date" 
                        type="date" 
                        id="request_date"
                        class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                        required>
                    @error('request_date')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea 
                        wire:model="description" 
                        id="description"
                        rows="3"
                        class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 resize-none"
                        placeholder="Enter request description (optional)"></textarea>
                    @error('description')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Items Management -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Purchase Items</h3>
                <button 
                    wire:click="addItem" 
                    type="button"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 border border-blue-300 rounded-md hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
                    <span class="mr-2">+</span>
                    Add Item
                </button>
            </div>

            @if(count($items) > 0)
                <div class="overflow-x-auto border border-gray-200 rounded-md">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                                    Item Name
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                                    Brand
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                                    Description
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                                    Supplier
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200 min-w-20">
                                    Qty
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200 min-w-32">
                                    Unit
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                                    Unit Price
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                                    Total
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($items as $index => $item)
                                <tr class="hover:bg-gray-50">
                                    <!-- Item Name -->
                                    <td class="px-4 py-4 border-r border-gray-200">
                                        <input 
                                            wire:model.live="items.{{ $index }}.item_name" 
                                            type="text"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                            placeholder="Item name"
                                            maxlength="255"
                                        >
                                        @error("items.{$index}.item_name") 
                                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </td>

                                    <!-- Brand -->
                                    <td class="px-4 py-4 border-r border-gray-200">
                                        <input 
                                            wire:model.live="items.{{ $index }}.brand" 
                                            type="text"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                            placeholder="Brand name"
                                            maxlength="100"
                                        >
                                        @error("items.{$index}.brand") 
                                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </td>

                                    <!-- Description -->
                                    <td class="px-4 py-4 border-r border-gray-200">
                                        <textarea 
                                            wire:model.live="items.{{ $index }}.description" 
                                            rows="2"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 resize-none"
                                            placeholder="Item description"
                                            maxlength="500"></textarea>
                                        @error("items.{$index}.description") 
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
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 text-center no-spinner"
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
            @else
                <div class="text-center py-12 bg-gray-50 border border-gray-200 rounded-md">
                    <p class="text-gray-500">No items added yet. Click "Add Item" to get started.</p>
                </div>
            @endif
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

    @push('styles')
    <style>
    /* Enterprise form styling enhancements */
    .no-spinner::-webkit-outer-spin-button,
    .no-spinner::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .no-spinner {
        -moz-appearance: textfield;
    }

    /* Select dropdown arrow removal */
    select.appearance-none {
        background-image: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
    }

    /* IE support */
    select::-ms-expand {
        display: none;
    }

    /* Hover effects for enterprise styling */
    .hover-shadow:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    /* Loading state styling */
    .loading-overlay {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(2px);
    }
    </style>
    @endpush
</div>