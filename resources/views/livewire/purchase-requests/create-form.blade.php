<div class="space-y-8">
    <!-- Form Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
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
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Purchase Items</h3>
                <p class="text-sm text-gray-600 mt-1">Add items to your purchase request</p>
            </div>
            <button 
                wire:click="addItem" 
                type="button"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add Item
            </button>
        </div>

        <div class="p-6">
            @if(count($items) > 0)
                <div class="space-y-6">
                    @foreach($items as $index => $item)
                        <div class="border border-gray-200 rounded-lg p-6 relative" wire:key="item-{{ $index }}">
                            <!-- Item Header -->
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-md font-semibold text-gray-900">Item #{{ $index + 1 }}</h4>
                                @if(count($items) > 1)
                                    <button 
                                        wire:click="removeItem({{ $index }})" 
                                        type="button"
                                        class="text-red-600 hover:text-red-800 p-1 rounded-full hover:bg-red-50 transition-colors duration-200"
                                        title="Remove item">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <!-- Item Name -->
                                <div class="sm:col-span-2 lg:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Item Name <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        wire:model.live="items.{{ $index }}.item_name" 
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors duration-200"
                                        placeholder="Enter item name"
                                        maxlength="255"
                                    >
                                    @error("items.{$index}.item_name") 
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Brand Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand Name</label>
                                    <input 
                                        wire:model.live="items.{{ $index }}.brand_name" 
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors duration-200"
                                        placeholder="Enter brand name"
                                        maxlength="255"
                                    >
                                    @error("items.{$index}.brand_name") 
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Supplier Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Supplier Name</label>
                                    <input 
                                        wire:model.live="items.{{ $index }}.supplier_name" 
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors duration-200"
                                        placeholder="Enter supplier name"
                                        maxlength="255"
                                    >
                                    @error("items.{$index}.supplier_name") 
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Item Description -->
                                <div class="sm:col-span-2 lg:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea 
                                        wire:model.live="items.{{ $index }}.item_description" 
                                        rows="2"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors duration-200"
                                        placeholder="Enter item description..."
                                        maxlength="1000"
                                    ></textarea>
                                    @error("items.{$index}.item_description") 
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Quantity -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Quantity <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        wire:model.live="items.{{ $index }}.quantity" 
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors duration-200"
                                        placeholder="0.00"
                                    >
                                    @error("items.{$index}.quantity") 
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Unit -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Unit <span class="text-red-500">*</span>
                                    </label>
                                    <select 
                                        wire:model.live="items.{{ $index }}.unit" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors duration-200"
                                    >
                                        <option value="pcs">pcs (pieces)</option>
                                        <option value="set">set</option>
                                        <option value="box">box</option>
                                        <option value="pack">pack</option>
                                        <option value="unit">unit</option>
                                        <option value="kg">kg (kilogram)</option>
                                        <option value="m">m (meter)</option>
                                        <option value="liter">liter</option>
                                        <option value="dozen">dozen</option>
                                        <option value="roll">roll</option>
                                    </select>
                                    @error("items.{$index}.unit") 
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Unit Price -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Unit Price <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-gray-500 text-sm">{{ $currency }}</span>
                                        <input 
                                            wire:model.live="items.{{ $index }}.unit_price" 
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-full pl-12 pr-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors duration-200"
                                            placeholder="0.00"
                                        >
                                    </div>
                                    @error("items.{$index}.unit_price") 
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Expense Department -->
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Expense Department <span class="text-red-500">*</span>
                                    </label>
                                    <select 
                                        wire:model.live="items.{{ $index }}.expense_department_id" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors duration-200"
                                    >
                                        <option value="">Select Department</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->name }} ({{ $department->code }})</option>
                                        @endforeach
                                    </select>
                                    @error("items.{$index}.expense_department_id") 
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Total Price Display -->
                                <div class="lg:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Price</label>
                                    <div class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-md">
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ $currency }} {{ number_format($item['total_price'] ?? 0, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Total Summary -->
                <div class="mt-6 bg-gray-50 rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-gray-900">Total Amount:</span>
                        <span class="text-xl font-bold text-indigo-600">
                            {{ $currency }} {{ number_format($totalAmount, 2) }}
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
                    <h3 class="text-sm font-medium text-gray-900 mb-1">No items added</h3>
                    <p class="text-sm text-gray-500 mb-4">Get started by adding your first item to the purchase request.</p>
                    <button 
                        wire:click="addItem" 
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add First Item
                    </button>
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