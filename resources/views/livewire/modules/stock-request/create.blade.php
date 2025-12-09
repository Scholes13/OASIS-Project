{{-- HOSTING ENVIRONMENT FIX: Defensive property initialization --}}
@php
    // CRITICAL: Initialize all variables with fallbacks for hosting environment
    $submission_date = $this->submission_date ?? now()->format('d/m/Y');
    $user_name = $this->user_name ?? 'User';
    $department_name = $this->department_name ?? 'Department';
    $department_code = $this->department_code ?? 'DEPT';
    $items = $this->items ?? [];
    $isEdit = $this->isEdit ?? false;
    $isLoading = $this->isLoading ?? false;
    $isRejected = $this->isRejected ?? false;
    $purpose = $this->purpose ?? '';
    $request_date = $this->request_date ?? '';
    $expected_date = $this->expected_date ?? '';
    $notes = $this->notes ?? '';
@endphp

{{-- CRITICAL: Single root element for Livewire --}}
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

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-medium text-red-800">
                        <ul class="mt-2 space-y-1 text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Alert for rejected status --}}
    @if($isRejected)
    <div class="bg-orange-50 border border-orange-200 rounded-md p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-orange-800">Editing Rejected Stock Request</h3>
                <p class="mt-1 text-sm text-orange-700">
                    This stock request was rejected. After editing, you must resubmit it from the detail page.
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Basic Information Form -->
    <div class="bg-white border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Stock Request Information</h3>
        </div>
        
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <!-- Purpose Field -->
                <div class="sm:col-span-2">
                    <label for="purpose" class="block text-sm font-medium text-gray-900 mb-2">
                        Purpose <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        id="purpose"
                        wire:model.blur="purpose"
                        rows="4"
                        class="w-full px-3 py-2 border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Describe the purpose of this stock request..."
                        maxlength="1000"
                    ></textarea>
                    @error('purpose')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Request Date -->
                <div>
                    <label for="request_date" class="block text-sm font-medium text-gray-900 mb-2">
                        Request Date <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="date"
                        id="request_date"
                        wire:model.blur="request_date"
                        class="w-full px-3 py-2 border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                    />
                    @error('request_date')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Expected Delivery Date -->
                <div>
                    <label for="expected_date" class="block text-sm font-medium text-gray-900 mb-2">
                        Expected Delivery Date
                    </label>
                    <input 
                        type="date"
                        id="expected_date"
                        wire:model.blur="expected_date"
                        min="{{ date('Y-m-d') }}"
                        class="w-full px-3 py-2 border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                    />
                    @error('expected_date')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Auto-filled Request Information -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">Request Information</label>
                    <div class="space-y-2">
                        <div class="flex justify-between py-2 px-3 bg-gray-50 border border-gray-200">
                            <span class="text-sm text-gray-600">Submission Date:</span>
                            <span class="text-sm text-gray-900">{{ $submission_date }}</span>
                        </div>
                        <div class="flex justify-between py-2 px-3 bg-gray-50 border border-gray-200">
                            <span class="text-sm text-gray-600">Requested By:</span>
                            <span class="text-sm text-gray-900">{{ $user_name }}</span>
                        </div>
                        <div class="flex justify-between py-2 px-3 bg-gray-50 border border-gray-200">
                            <span class="text-sm text-gray-600">Department:</span>
                            <span class="text-sm text-gray-900">{{ $department_name }} ({{ $department_code }})</span>
                        </div>
                    </div>
                </div>

                <!-- Notes Field -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-900 mb-2">
                        Additional Notes
                    </label>
                    <textarea 
                        id="notes"
                        wire:model.blur="notes"
                        rows="6"
                        class="w-full px-3 py-2 border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Any additional information..."
                    ></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Container -->
    <div class="bg-white border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Stock Items</h3>
                
                <!-- Add Item Button -->
                <button 
                    wire:click="addItem" 
                    type="button"
                    onclick="this.disabled=true; setTimeout(() => this.disabled=false, 500);"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Item
                </button>
            </div>
        </div>

        <!-- Items Table -->
        <div class="p-6">
            @error('items')
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-sm text-red-600">{{ $message }}</p>
                </div>
            @enderror

            <div class="overflow-x-auto -mx-6 px-6">
                <div class="min-w-full inline-block align-middle">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">NO</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-48">Item Name</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32 min-w-32">QTY</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-36 min-w-36">Unit</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-32">Item Code</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-48">Specifications</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-32">Notes</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider min-w-32">Image</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @if(count($items ?? []) > 0)
                                @foreach($items as $index => $item)
                                    <tr wire:key="item-row-{{ $index }}" class="hover:bg-gray-50">
                                        <!-- NO -->
                                        <td class="px-3 py-3 text-sm text-gray-900 text-center font-medium">
                                            {{ $index + 1 }}
                                        </td>

                                        <!-- Item Name -->
                                        <td class="px-3 py-3">
                                            <input 
                                                type="text"
                                                wire:model.blur="items.{{ $index }}.item_name"
                                                class="w-full px-2 py-1.5 text-sm border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error('items.' . $index . '.item_name') border-red-300 @enderror"
                                                placeholder="Enter item name"
                                            />
                                            @error('items.' . $index . '.item_name')
                                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                        </td>

                                        <!-- Quantity -->
                                        <td class="px-3 py-3">
                                            <input 
                                                type="number"
                                                wire:model.blur="items.{{ $index }}.quantity"
                                                min="1"
                                                class="w-full px-2 py-1.5 text-sm text-center border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none @error('items.' . $index . '.quantity') border-red-300 @enderror"
                                            />
                                            @error('items.' . $index . '.quantity')
                                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                        </td>

                                        <!-- Unit -->
                                        <td class="px-3 py-3">
                                            <select 
                                                wire:model.blur="items.{{ $index }}.unit"
                                                class="w-full px-2 py-1.5 text-sm border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error('items.' . $index . '.unit') border-red-300 @enderror"
                                            >
                                                <option value="pcs">pcs</option>
                                                <option value="box">box</option>
                                                <option value="kg">kg</option>
                                                <option value="liter">liter</option>
                                                <option value="meter">meter</option>
                                                <option value="set">set</option>
                                                <option value="pack">pack</option>
                                                <option value="unit">unit</option>
                                            </select>
                                            @error('items.' . $index . '.unit')
                                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                        </td>

                                        <!-- Item Code -->
                                        <td class="px-3 py-3">
                                            <input 
                                                type="text"
                                                wire:model.blur="items.{{ $index }}.item_code"
                                                class="w-full px-2 py-1.5 text-sm border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="e.g., SKU-001"
                                            />
                                        </td>

                                        <!-- Specifications -->
                                        <td class="px-3 py-3">
                                            <textarea 
                                                wire:model.blur="items.{{ $index }}.specifications"
                                                rows="2"
                                                class="w-full px-2 py-1.5 text-sm border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Specifications..."
                                            ></textarea>
                                        </td>

                                        <!-- Item Notes -->
                                        <td class="px-3 py-3">
                                            <input 
                                                type="text"
                                                wire:model.blur="items.{{ $index }}.notes"
                                                class="w-full px-2 py-1.5 text-sm border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Notes..."
                                            />
                                        </td>

                                        <!-- Image Upload -->
                                        <td class="px-3 py-3 text-center">
                                            <div class="flex items-center justify-center">
                                                <label class="cursor-pointer inline-flex items-center justify-center w-16 h-16 border-2 border-dashed border-gray-300 hover:border-blue-500 transition-colors">
                                                    @if(isset($item['image_path']) && $item['image_path'])
                                                        <img src="{{ asset('storage/' . $item['image_path']) }}" class="w-full h-full object-cover" alt="Item image">
                                                    @else
                                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                        </svg>
                                                    @endif
                                                    <input 
                                                        type="file"
                                                        wire:model="itemImages.{{ $index }}"
                                                        accept="image/*"
                                                        class="hidden"
                                                    />
                                                </label>
                                            </div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-3 py-3 text-center">
                                            @if(count($items) > 1)
                                            <button 
                                                wire:click="removeItem({{ $index }})"
                                                type="button"
                                                onclick="this.disabled=true; setTimeout(() => this.disabled=false, 500);"
                                                class="inline-flex items-center p-1 text-red-600 hover:text-red-800 focus:outline-none">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500">
                                        No items added yet. Click "Add Item" to start.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-between bg-white border border-gray-200 px-6 py-4 rounded-lg">
        <a 
            href="{{ route('stock-requests.index') }}"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
            Cancel
        </a>

        <button 
            type="button"
            wire:click="submitStockRequest"
            wire:loading.attr="disabled"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <span wire:loading.remove wire:target="submitStockRequest">
                {{ $isRejected ? 'Save Changes (Rejected)' : ($isEdit ? 'Update Stock Request' : 'Submit Stock Request') }}
            </span>
            <span wire:loading wire:target="submitStockRequest" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            </span>
        </button>
    </div>
</div>
{{-- END: Single root element for Livewire --}}
