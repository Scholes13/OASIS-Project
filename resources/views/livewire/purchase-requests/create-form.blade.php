<div class="space-y-8">
    <!-- Form Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-20">
                                    QTY
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-32">
                                    Unit
                                </th>der-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Purchase Request Information</h3>
            <p class="text-sm text-gray-600 mt-1">Fill in the basic information for your purchase request</p>
        </div>
        
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <!-- Keperluan (Purpose) -->
                <div class="sm:col-span-2">
                    <label for="keperluan" class="block text-sm font-semibold text-gray-700 mb-2">
                        Purpose / Requirements <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        wire:model.live="keperluan" 
                        id="keperluan"
                        rows="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors duration-200"
                        placeholder="Describe the purpose or requirements for this purchase request..."
                        maxlength="500"
                    ></textarea>
                    @error('keperluan') 
                        <p class="mt-1 text-sm text-red-600 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">{{ strlen($keperluan) }}/500 characters</p>
                </div>

                <!-- Used For (Description) -->
                <div class="sm:col-span-2">
                    <label for="used_for" class="block text-sm font-semibold text-gray-700 mb-2">
                        Detailed Description <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        wire:model.live="used_for" 
                        id="used_for"
                        rows="4"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors duration-200"
                        placeholder="Provide detailed description of how these items will be used..."
                        maxlength="1000"
                    ></textarea>
                    @error('used_for') 
                        <p class="mt-1 text-sm text-red-600 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">{{ strlen($used_for) }}/1000 characters</p>
                </div>

                <!-- Date of Request -->
                <div>
                    <label for="date_of_request" class="block text-sm font-semibold text-gray-700 mb-2">
                        Date of Request <span class="text-red-500">*</span>
                    </label>
                    <input 
                        wire:model.live="date_of_request" 
                        type="date"
                        id="date_of_request"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors duration-200"
                    >
                    @error('date_of_request') 
                        <p class="mt-1 text-sm text-red-600 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Currency -->
                <div>
                    <label for="currency" class="block text-sm font-semibold text-gray-700 mb-2">
                        Currency <span class="text-red-500">*</span>
                    </label>
                    <select 
                        wire:model.live="currency" 
                        id="currency"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors duration-200"
                    >
                        <option value="IDR">IDR - Indonesian Rupiah</option>
                        <option value="USD">USD - US Dollar</option>
                        <option value="EUR">EUR - Euro</option>
                    </select>
                    @error('currency') 
                        <p class="mt-1 text-sm text-red-600 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Items Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-6">
                    <h3 class="text-lg font-semibold text-gray-900">Purchase Items</h3>
                    <button 
                        wire:click="addItem" 
                        wire:loading.attr="disabled"
                        wire:target="addItem"
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                        <span wire:loading.remove wire:target="addItem" class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Item
                        </span>
                        <span wire:loading wire:target="addItem" class="flex items-center">
                            <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Adding...
                        </span>
                    </button>
                </div>
                <p class="text-sm text-gray-600">Add items to your purchase request</p>
            </div>
        </div>

        <div class="p-6">
            @if(count($items) > 0)
                <!-- Items Table -->
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8">
                                    No
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-48">
                                    Item Name
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-32">
                                    Brand
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-32">
                                    Supplier
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-20">
                                    Qty
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-20">
                                    Unit
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-28">
                                    Unit Price
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-28">
                                    Total
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-32">
                                    Department
                                </th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($items as $index => $item)
                                <tr wire:key="item-{{ $index }}" class="hover:bg-gray-50">
                                    <!-- Row Number -->
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 font-medium">
                                        {{ $index + 1 }}
                                    </td>
                                    
                                    <!-- Item Name -->
                                    <td class="px-4 py-3">
                                        <div class="space-y-1">
                                            <input 
                                                wire:model.blur="items.{{ $index }}.item_name" 
                                                type="text"
                                                class="w-full px-2 py-1 text-sm border border-gray-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                                placeholder="Enter item name"
                                                maxlength="255"
                                            >
                                            @if(!empty($item['item_description']))
                                                <textarea 
                                                    wire:model.blur="items.{{ $index }}.item_description" 
                                                    rows="2"
                                                    class="w-full px-2 py-1 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50"
                                                    placeholder="Description..."
                                                    maxlength="1000"
                                                ></textarea>
                                            @else
                                                <button 
                                                    type="button" 
                                                    onclick="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                                    class="text-xs text-indigo-600 hover:text-indigo-800">
                                                    + Add description
                                                </button>
                                                <textarea 
                                                    wire:model.blur="items.{{ $index }}.item_description" 
                                                    rows="2"
                                                    style="display:none"
                                                    class="w-full px-2 py-1 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50"
                                                    placeholder="Description..."
                                                    maxlength="1000"
                                                ></textarea>
                                            @endif
                                            @error("items.{$index}.item_name") 
                                                <p class="text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </td>

                                    <!-- Brand Name -->
                                    <td class="px-4 py-3">
                                        <input 
                                            wire:model.blur="items.{{ $index }}.brand_name" 
                                            type="text"
                                            class="w-full px-2 py-1 text-sm border border-gray-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Brand"
                                            maxlength="255"
                                        >
                                        @error("items.{$index}.brand_name") 
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>

                                    <!-- Supplier Name -->
                                    <td class="px-4 py-3">
                                        <input 
                                            wire:model.blur="items.{{ $index }}.supplier_name" 
                                            type="text"
                                            class="w-full px-2 py-1 text-sm border border-gray-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Supplier"
                                            maxlength="255"
                                        >
                                        @error("items.{$index}.supplier_name") 
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>

                                    <!-- Quantity -->
                                    <td class="px-4 py-3">
                                        <input 
                                            wire:model.blur="items.{{ $index }}.quantity" 
                                            type="number"
                                            step="1"
                                            min="1"
                                            value="{{ $item['quantity'] ?? 1 }}"
                                            class="w-full min-w-20 px-2 py-1 text-sm border border-gray-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 text-center"
                                            placeholder="1"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, ''); calculateRowTotal({{ $index }});"
                                        >
                                        @error("items.{$index}.quantity") 
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>

                                    <!-- Unit -->
                                    <td class="px-4 py-3">
                                        <select 
                                            wire:model.blur="items.{{ $index }}.unit" 
                                            class="w-full min-w-32 px-2 py-1 text-sm border border-gray-200 rounded focus:ring-1 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                            <option value="pcs">pcs</option>
                                            <option value="set">set</option>
                                            <option value="box">box</option>
                                            <option value="pack">pack</option>
                                            <option value="unit">unit</option>
                                            <option value="kg">kg</option>
                                            <option value="m">m</option>
                                            <option value="liter">liter</option>
                                            <option value="dozen">dozen</option>
                                            <option value="roll">roll</option>
                                        </select>
                                        @error("items.{$index}.unit") 
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>

                                    <!-- Unit Price -->
                                    <td class="px-4 py-3">
                                        <div class="relative">
                                            <span class="absolute left-2 top-1 text-xs text-gray-500">{{ $currency }}</span>
                                            <input 
                                                wire:model.blur="items.{{ $index }}.unit_price" 
                                                type="number"
                                                step="1"
                                                min="0"
                                                value="{{ $item['unit_price'] ?? 0 }}"
                                                class="w-full pl-8 pr-2 py-1 text-sm border border-gray-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 text-right"
                                                placeholder="0"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, ''); calculateRowTotal({{ $index }});"
                                            >
                                        </div>
                                        @error("items.{$index}.unit_price") 
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>

                                    <!-- Total Price -->
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900 text-right">
                                            {{ $currency }} <span id="total-{{ $index }}">{{ number_format($item['total_price'] ?? 0, 0) }}</span>
                                        </div>
                                    </td>

                                    <!-- Expense Department -->
                                    <td class="px-4 py-3">
                                        <select 
                                            wire:model.blur="items.{{ $index }}.expense_department_id" 
                                            class="w-full px-2 py-1 text-sm border border-gray-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                            <option value="">Select Dept</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department->id }}">{{ $department->code }}</option>
                                            @endforeach
                                        </select>
                                        @error("items.{$index}.expense_department_id") 
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>

                                    <!-- Action -->
                                    <td class="px-4 py-3 text-center">
                                        @if(count($items) > 1)
                                            <button 
                                                wire:click="removeItem({{ $index }})" 
                                                wire:loading.attr="disabled"
                                                wire:target="removeItem({{ $index }})"
                                                type="button"
                                                class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                                                title="Remove item">
                                                <span wire:loading.remove wire:target="removeItem({{ $index }})">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </span>
                                                <span wire:loading wire:target="removeItem({{ $index }})">
                                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </span>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Total Summary -->
                <div class="mt-6 bg-gray-50 rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-gray-900">Total Amount:</span>
                        <span class="text-xl font-bold text-indigo-600">
                            {{ $currency }} <span id="grand-total">{{ number_format($totalAmount, 0) }}</span>
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">{{ count($items) }} item(s) in this purchase request</p>
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-12 h-12 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <div class="text-center sm:text-left">
                            <h3 class="text-sm font-medium text-gray-900">No items added</h3>
                            <p class="text-sm text-gray-500">Get started by adding your first item to the purchase request</p>
                        </div>
                        <button 
                            wire:click="addItem" 
                            wire:loading.attr="disabled"
                            wire:target="addItem"
                            type="button"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                            <span wire:loading.remove wire:target="addItem" class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add First Item
                            </span>
                            <span wire:loading wire:target="addItem" class="flex items-center">
                                <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Adding...
                            </span>
                        </button>
                    </div>
                </div>
            @endif

            @error('items') 
                <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-sm text-red-600 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ $message }}
                    </p>
                </div>
            @enderror
        </div>
    </div>

    <!-- Form Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 flex items-center justify-between space-x-4">
            <div class="text-sm text-gray-600">
                <p>Make sure all information is correct before submitting.</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <button 
                    wire:click="saveDraft" 
                    wire:loading.attr="disabled"
                    wire:target="saveDraft"
                    type="button"
                    class="inline-flex items-center px-6 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                    <span wire:loading.remove wire:target="saveDraft" class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Save as Draft
                    </span>
                    <span wire:loading wire:target="saveDraft" class="flex items-center">
                        <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Saving...
                    </span>
                </button>

                <button 
                    wire:click="saveAndSubmit" 
                    wire:loading.attr="disabled"
                    wire:target="saveAndSubmit"
                    type="button"
                    class="inline-flex items-center px-6 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                    <span wire:loading.remove wire:target="saveAndSubmit" class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Submit for Approval
                    </span>
                    <span wire:loading wire:target="saveAndSubmit" class="flex items-center">
                        <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Submitting...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Client-side calculation for instant feedback
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
        
        // Sum all row totals
        document.querySelectorAll('[id^="total-"]').forEach(function(totalSpan) {
            const value = totalSpan.textContent.replace(/[^0-9]/g, '');
            grandTotal += parseInt(value) || 0;
        });
        
        // Update grand total display
        const grandTotalSpan = document.getElementById('grand-total');
        if (grandTotalSpan) {
            grandTotalSpan.textContent = new Intl.NumberFormat('id-ID').format(grandTotal);
        }
    }
    
    // Recalculate totals when page loads or items are added/removed
    document.addEventListener('DOMContentLoaded', function() {
        calculateGrandTotal();
    });
    
    // Recalculate totals after Livewire updates
    document.addEventListener('livewire:navigated', function() {
        calculateGrandTotal();
    });
    
    // Recalculate after any Livewire DOM update (when items added/removed)
    Livewire.hook('morph.updated', ({ el, component }) => {
        setTimeout(() => calculateGrandTotal(), 100); // Small delay to ensure DOM is updated
    });
    
    // Listen for specific Livewire events
    window.addEventListener('item-added', function() {
        setTimeout(() => calculateGrandTotal(), 100);
        // Show toast notification
        if (typeof window.notify === 'function') {
            window.notify('Item added successfully', 'success');
        }
    });
    
    window.addEventListener('item-removed', function() {
        setTimeout(() => calculateGrandTotal(), 100);
        // Show toast notification
        if (typeof window.notify === 'function') {
            window.notify('Item removed successfully', 'success');
        }
    });
    
    // Listen for Livewire events globally
    document.addEventListener('livewire:init', function () {
        Livewire.on('notify', function(data) {
            if (typeof window.notify === 'function') {
                window.notify(data.message, data.type || 'info', data.duration || 5000);
            }
        });
    });
    
    // Auto-validate fields on blur for better UX
    document.addEventListener('blur', function(e) {
        if (e.target.hasAttribute('wire:model') || e.target.hasAttribute('wire:model.blur')) {
            const fieldName = e.target.getAttribute('wire:model') || e.target.getAttribute('wire:model.blur');
            if (fieldName && typeof @this !== 'undefined') {
                // Extract field name without array indices for validation
                const baseField = fieldName.split('.')[0];
                @this.validateField && @this.validateField(baseField);
            }
        }
    }, true);
</script>