<div class="w-full space-y-6">
    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="bg-green-50 border border-green-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('info'))
        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-800">{{ session('info') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Validation Errors Summary -->
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- PR Creation Form -->
    <div class="fluid-card">
        <div class="border-b border-gray-200" style="padding-bottom: clamp(0.5rem, 1vw, 0.75rem);">
            <h3 class="fluid-text-lg font-semibold text-gray-900">Create Purchase Request</h3>
            <p class="fluid-text-sm text-gray-600 mt-1">Create a complete Purchase Request for {{ session('current_business_unit_name') }}</p>
        </div>
        
        <div class="fluid-form-spacing" style="padding-top: clamp(0.75rem, 1.5vw, 1rem);">
            <!-- Auto-populated Information Display -->
            <div class="bg-gray-50 rounded-lg fluid-spacing-sm">
                <h4 class="fluid-text-base font-medium text-gray-700 mb-2">Request Information</h4>
                <div class="fluid-grid-4">
                    <div>
                        <label class="block fluid-text-xs font-medium text-gray-700 mb-1">PR Number</label>
                        <p class="fluid-text-xs text-gray-500 font-mono">auto-generated</p>
                    </div>
                    <div>
                        <label class="block fluid-text-xs font-medium text-gray-700 mb-1">Request Date</label>
                        <p class="fluid-text-xs text-gray-500">auto-generated</p>
                    </div>
                    <div>
                        <label class="block fluid-text-xs font-medium text-gray-700 mb-1">Business Unit</label>
                        <p class="fluid-text-xs text-gray-900 font-medium">{{ session('current_business_unit_code') }}</p>
                    </div>
                    <div>
                        <label class="block fluid-text-xs font-medium text-gray-700 mb-1">Department</label>
                        <p class="fluid-text-xs text-gray-900 font-medium">{{ $department_code }}</p>
                    </div>
                    <div>
                        <label class="block fluid-text-xs font-medium text-gray-700 mb-1">Requested By</label>
                        <p class="fluid-text-xs text-gray-900">{{ $user_name }}</p>
                    </div>
                    <div>
                        <label class="block fluid-text-xs font-medium text-gray-700 mb-1">Currency</label>
                        <div class="relative">
                            <select wire:model="currency" class="fluid-input pr-8 appearance-none bg-white">
                                <option value="IDR">IDR</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic Information -->
            <div class="fluid-grid-2">
                <!-- Keperluan -->
                <div>
                    <label for="purpose" class="block fluid-text-xs font-medium text-gray-700 mb-1">
                        Purpose (Keperluan) <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        wire:model="purpose" 
                        id="purpose"
                        rows="2"
                        class="fluid-input resize-none"
                        placeholder="Office equipment purchase to support daily operations..."
                        maxlength="500"
                    ></textarea>
                    @error('purpose') 
                        <p class="mt-1 fluid-text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Used For -->
                <div>
                    <label for="used_for" class="block fluid-text-xs font-medium text-gray-700 mb-1">
                        Used For (Digunakan Untuk) <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        wire:model="used_for" 
                        id="used_for"
                        rows="2"
                        class="fluid-input resize-none"
                        placeholder="Detailed description of what the items will be used for..."
                        maxlength="1000"
                    ></textarea>
                    @error('used_for') 
                        <p class="mt-1 fluid-text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Items Section -->
            <div class="border-t" style="padding-top: clamp(0.5rem, 1vw, 0.75rem);">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="fluid-text-base font-medium text-gray-700">Items ({{ count($items) }})</h4>
                    <button 
                        wire:click="addItem" 
                        type="button"
                        class="fluid-button bg-indigo-600 hover:bg-indigo-700 text-white border-0">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Item
                    </button>
                </div>

                <!-- Items Table -->
                <div class="table-responsive bg-white rounded border border-gray-200 shadow-sm">
                    <table class="table-fixed-columns w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 50px; min-width: 50px;">No</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 200px;">Item Name *</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 120px;">Brand</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 120px;">Supplier</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 80px; min-width: 80px;">Qty *</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 100px; min-width: 100px;">Unit *</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 120px; min-width: 120px;">Price *</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 80px; min-width: 80px;">Curr</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 120px; min-width: 120px;">Total</th>
                                <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 60px; min-width: 60px;">Act</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @if(count($items) > 0)
                                @foreach($items as $index => $item)
                                <tr wire:key="item-{{ $index }}" class="hover:bg-gray-50">
                                    <!-- No -->
                                    <td class="px-2 py-3 whitespace-nowrap text-sm text-gray-900 text-center font-medium">
                                        {{ $index + 1 }}
                                    </td>
                                    
                                    <!-- Item Name -->
                                    <td class="px-3 py-3">
                                        <input type="text" wire:model="items.{{ $index }}.item_name" 
                                               placeholder="Enter detailed item name..."
                                               class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2">
                                        @error("items.{$index}.item_name") 
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    
                                    <!-- Brand -->
                                    <td class="px-3 py-3">
                                        <input type="text" wire:model="items.{{ $index }}.brand_name" 
                                               placeholder="Brand name"
                                               class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2">
                                    </td>
                                    
                                    <!-- Supplier -->
                                    <td class="px-3 py-3">
                                        <input type="text" wire:model="items.{{ $index }}.supplier_name" 
                                               placeholder="Supplier name"
                                               class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2">
                                    </td>
                                    
                                    <!-- Quantity -->
                                    <td class="px-3 py-3">
                                        <input type="number" 
                                               step="0.01" 
                                               min="0.01"
                                               wire:model.live="items.{{ $index }}.quantity" 
                                               placeholder="1"
                                               class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 px-2 py-2 text-center font-medium">
                                        @error("items.{$index}.quantity") 
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    
                                    <!-- Unit -->
                                    <td class="px-3 py-3">
                                        <select wire:model="items.{{ $index }}.unit" 
                                                class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 px-2 py-2 bg-white">
                                            <option value="pcs">pcs</option>
                                            <option value="unit">unit</option>
                                            <option value="set">set</option>
                                            <option value="box">box</option>
                                            <option value="pack">pack</option>
                                            <option value="kg">kg</option>
                                            <option value="liter">liter</option>
                                            <option value="meter">meter</option>
                                            <option value="roll">roll</option>
                                            <option value="sheet">sheet</option>
                                        </select>
                                        @error("items.{$index}.unit") 
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    
                                    <!-- Unit Price -->
                                    <td class="px-3 py-3">
                                        <input type="text" wire:model.live="items.{{ $index }}.unit_price" 
                                               placeholder="100,000"
                                               class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 px-2 py-2 text-right"
                                               x-data="{ 
                                                   formatNumber(value) {
                                                       let num = value.toString().replace(/[^\d]/g, '');
                                                       return num.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                                   }
                                               }"
                                               x-on:input="
                                                   $event.target.value = formatNumber($event.target.value);
                                                   $wire.set('items.{{ $index }}.unit_price', $event.target.value.replace(/,/g, ''));
                                               ">
                                        @error("items.{$index}.unit_price") 
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    
                                    <!-- Currency -->
                                    <td class="px-3 py-3">
                                        <select wire:model="items.{{ $index }}.currency" 
                                                class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 px-2 py-2 bg-white">
                                            <option value="IDR">IDR</option>
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                        </select>
                                    </td>
                                    
                                    <!-- Total Price -->
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-medium bg-gray-50">
                                        {{ number_format(($item['quantity'] ?? 0) * (str_replace(',', '', $item['unit_price'] ?? 0)), 0) }}
                                    </td>
                                    
                                    <!-- Action -->
                                    <td class="px-2 py-3 text-center">
                                        @if(count($items) > 1)
                                            <button 
                                                wire:click="removeItem({{ $index }})" 
                                                type="button"
                                                class="text-red-600 hover:text-red-800 p-1 rounded-md hover:bg-red-50 transition-colors"
                                                title="Remove item">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                
                                <!-- Description Row -->
                                <tr wire:key="desc-{{ $index }}" class="bg-gray-25 border-t border-gray-100">
                                    <td class="px-2 py-3"></td>
                                    <td colspan="9" class="px-3 py-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Description:</label>
                                            <textarea wire:model="items.{{ $index }}.item_description" 
                                                      rows="2"
                                                      placeholder="Optional detailed item description, specifications, or notes..."
                                                      class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 resize-y px-3 py-2"></textarea>
                                        </div>
                                    </td>
                                </tr>

                                @endforeach
                            @else
                                <tr>
                                    <td colspan="10" class="px-6 py-4 text-center text-gray-500">
                                        No items added yet. Click "Add Item" to get started.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Items Cards -->
                <div class="sm:hidden space-y-4">
                    @if(count($items) > 0)
                        @foreach($items as $index => $item)
                        <div wire:key="mobile-item-{{ $index }}" class="bg-white rounded-lg border border-gray-200 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h5 class="text-sm font-medium text-gray-900">Item {{ $index + 1 }}</h5>
                                @if(count($items) > 1)
                                    <button 
                                        wire:click="removeItem({{ $index }})" 
                                        type="button"
                                        class="text-red-600 hover:text-red-800 p-1"
                                        title="Remove item">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                            
                            <div class="space-y-3">
                                <!-- Item Name -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Item Name *</label>
                                    <input type="text" wire:model="items.{{ $index }}.item_name" 
                                           placeholder="Enter item name"
                                           class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                    @error("items.{$index}.item_name") 
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <!-- Brand & Supplier -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Brand</label>
                                        <input type="text" wire:model="items.{{ $index }}.brand_name" 
                                               placeholder="Brand"
                                               class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Supplier</label>
                                        <input type="text" wire:model="items.{{ $index }}.supplier_name" 
                                               placeholder="Supplier"
                                               class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                </div>
                                
                                <!-- Quantity, Unit, Price -->
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Qty *</label>
                                        <input type="number" 
                                               step="0.01" 
                                               min="0.01"
                                               wire:model.live="items.{{ $index }}.quantity" 
                                               placeholder="10"
                                               class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 text-center font-medium">
                                        @error("items.{$index}.quantity") 
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Unit *</label>
                                        <div class="relative">
                                            <select wire:model="items.{{ $index }}.unit" 
                                                    class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 pl-3 pr-10 py-2 appearance-none bg-white">
                                                <option value="pcs">pcs</option>
                                                <option value="unit">unit</option>
                                                <option value="set">set</option>
                                                <option value="box">box</option>
                                                <option value="pack">pack</option>
                                                <option value="kg">kg</option>
                                                <option value="liter">liter</option>
                                                <option value="meter">meter</option>
                                                <option value="roll">roll</option>
                                                <option value="sheet">sheet</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        @error("items.{$index}.unit") 
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Unit Price *</label>
                                        <input type="text" wire:model.live="items.{{ $index }}.unit_price" 
                                               placeholder="10,000,000"
                                               class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 text-right"
                                               x-data="{ 
                                                   formatNumber(value) {
                                                       let num = value.toString().replace(/[^\d]/g, '');
                                                       return num.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                                   }
                                               }"
                                               x-on:input="
                                                   $event.target.value = formatNumber($event.target.value);
                                                   $wire.set('items.{{ $index }}.unit_price', $event.target.value.replace(/,/g, ''));
                                               ">
                                        @error("items.{$index}.unit_price") 
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                
                                <!-- Currency -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Currency</label>
                                    <div class="relative">
                                        <select wire:model="items.{{ $index }}.currency" 
                                                class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 pl-3 pr-10 py-2 appearance-none bg-white">
                                            <option value="IDR">IDR</option>
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
                                    <textarea wire:model="items.{{ $index }}.item_description" 
                                              rows="3"
                                              placeholder="Optional detailed item description, specifications, or notes..."
                                              class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 resize-y px-3 py-2 min-h-[80px]"></textarea>
                                </div>
                                
                                <!-- Item Total -->
                                <div class="bg-gray-50 rounded p-2 text-right">
                                    <span class="text-sm font-medium text-gray-900">
                                        Total: {{ $currency }} {{ number_format(($item['quantity'] ?? 0) * (str_replace(',', '', $item['unit_price'] ?? 0)), 0) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
                            <p class="text-gray-500">No items added yet. Click "Add Item" to get started.</p>
                        </div>
                    @endif
                </div>

                <!-- Total Amount -->
                <div class="bg-indigo-50 rounded-lg p-5 mt-6 -mx-4">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-medium text-gray-900">Grand Total:</span>
                        <span class="text-2xl font-bold text-indigo-600">{{ $currency }} {{ number_format($totalAmount, 0) }}</span>
                    </div>
                </div>
            </div>

            <!-- Approval Section -->
            <div class="border-t pt-6">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Approval Settings</h4>
                
                <!-- Approval Flow Selection -->
                <div class="space-y-4 mb-6">
                    <div class="flex items-center space-x-4">
                        <input type="radio" 
                               wire:model.live="approvalFlow" 
                               value="automatic" 
                               id="approval-automatic"
                               name="approval_flow"
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                        <label for="approval-automatic" class="text-sm font-medium text-gray-700 cursor-pointer">
                            Automatic Approval (Default)
                        </label>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <input type="radio" 
                               wire:model.live="approvalFlow" 
                               value="custom" 
                               id="approval-custom"
                               name="approval_flow"
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
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
                <div class="space-y-4"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100">
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
                        <select wire:model.live="customApprovalLayers" 
                                class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="1">1 Layer</option>
                            <option value="2">2 Layers</option>
                            <option value="3">3 Layers</option>
                            <option value="4">4 Layers</option>
                            <option value="5">5 Layers</option>
                        </select>
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
                            <div class="border border-gray-200 rounded-lg p-4" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform translate-y-2"
                                 x-transition:enter-end="opacity-100 transform translate-y-0">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Approval Layer {{ $i }}
                                </label>
                                <select wire:model.live="customApprovers.{{ $i }}" 
                                        class="w-full text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 @error('customApprovers.'.$i) border-red-300 @enderror">
                                    <option value="">Select Approver...</option>
                                    @foreach($availableApprovers as $approver)
                                        <option value="{{ $approver['id'] }}">
                                            {{ $approver['name'] }} - {{ ucfirst(str_replace('_', ' ', $approver['role'])) }} ({{ $approver['department'] }})
                                        </option>
                                    @endforeach
                                </select>
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

    <!-- Submit Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-6 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                <p><strong>Ready to submit?</strong> Your PR will be created with a unique number and sent for approval.</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <button 
                    wire:click="saveDraft" 
                    wire:loading.attr="disabled"
                    type="button"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span wire:loading.remove wire:target="saveDraft">Save as Draft</span>
                    <span wire:loading wire:target="saveDraft">Saving...</span>
                </button>

                <button 
                    wire:click="submitPurchaseRequest" 
                    wire:loading.attr="disabled"
                    type="button"
                    class="inline-flex items-center px-6 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span wire:loading.remove wire:target="submitPurchaseRequest" class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Submit for Approval
                    </span>
                    <span wire:loading wire:target="submitPurchaseRequest" class="flex items-center">
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