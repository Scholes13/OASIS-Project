{{-- HOSTING ENVIRONMENT FIX: Defensive property initialization --}}
@php
    use Illuminate\Support\Facades\Storage;
    
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
    $expected_date = $this->expected_date ?? '';
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
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-48">Specifications</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24 min-w-24">QTY</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32 min-w-32">Unit</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-40 min-w-40">Price</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-40 min-w-40">Total</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider min-w-24">Image</th>
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

                                        <!-- Specifications -->
                                        <td class="px-3 py-3">
                                            <textarea 
                                                wire:model.blur="items.{{ $index }}.specifications"
                                                rows="2"
                                                class="w-full px-2 py-1.5 text-sm border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Specifications..."
                                            ></textarea>
                                        </td>

                                        <!-- Quantity -->
                                        <td class="px-3 py-3">
                                            <input 
                                                type="number"
                                                wire:model.blur="items.{{ $index }}.quantity"
                                                min="1"
                                                oninput="calculateRowTotal({{ $index }});"
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

                                        <!-- Price -->
                                        <td class="px-3 py-3">
                                            <input 
                                                wire:model.blur="items.{{ $index }}.price"
                                                type="text"
                                                inputmode="decimal"
                                                class="w-full px-2 py-1.5 text-sm text-right border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 item-price @error('items.' . $index . '.price') border-red-300 @enderror"
                                                placeholder="0"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, ''); calculateRowTotal({{ $index }});"
                                            />
                                            @error('items.' . $index . '.price')
                                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                        </td>

                                        <!-- Total (calculated, read-only) -->
                                        <td class="px-3 py-3 text-sm text-gray-900 font-semibold text-right bg-gray-50" wire:ignore.self>
                                            @php
                                                $quantity = is_numeric($item['quantity'] ?? 0) ? intval($item['quantity']) : 0;
                                                $price = 0;
                                                if (isset($item['price'])) {
                                                    $cleanPrice = preg_replace('/[^0-9]/', '', $item['price']);
                                                    $price = is_numeric($cleanPrice) ? intval($cleanPrice) : 0;
                                                }
                                                $itemTotal = $quantity * $price;
                                            @endphp
                                            <span id="total-{{ $index }}" data-value="{{ $itemTotal }}">{{ number_format($itemTotal, 0, '', ',') }}</span>
                                        </td>

                                        <!-- Image Upload -->
                                        <td class="px-3 py-3 text-center">
                                            <div class="flex flex-col items-center gap-2">
                                                @if(isset($itemImages[$index]))
                                                    <!-- Preview uploaded image -->
                                                    <div class="relative">
                                                        <img src="{{ $itemImages[$index]->temporaryUrl() }}" 
                                                             alt="Item preview" 
                                                             class="w-16 h-16 object-cover rounded border border-gray-300">
                                                        <button 
                                                            wire:click="removeItemImage({{ $index }})"
                                                            type="button"
                                                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center hover:bg-red-600">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                @elseif(isset($item['image_path']) && $item['image_path'])
                                                    <!-- Show existing image from database -->
                                                    <div class="relative">
                                                        <img src="{{ Storage::url($item['image_path']) }}" 
                                                             alt="Item image" 
                                                             class="w-16 h-16 object-cover rounded border border-gray-300">
                                                        <button 
                                                            wire:click="removeItemImage({{ $index }})"
                                                            type="button"
                                                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center hover:bg-red-600">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                @else
                                                    <!-- Upload button -->
                                                    <label class="cursor-pointer">
                                                        <input 
                                                            type="file" 
                                                            wire:model="itemImages.{{ $index }}" 
                                                            accept="image/*"
                                                            class="hidden">
                                                        <div class="w-16 h-16 border-2 border-dashed border-gray-300 rounded flex items-center justify-center hover:border-blue-500 hover:bg-blue-50 transition-colors">
                                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                            </svg>
                                                        </div>
                                                    </label>
                                                @endif
                                                
                                                <!-- Loading indicator -->
                                                <div wire:loading wire:target="itemImages.{{ $index }}" class="text-xs text-blue-600">
                                                    Uploading...
                                                </div>
                                                
                                                @error("itemImages.{$index}") 
                                                    <p class="text-xs text-red-500 text-center">{{ $message }}</p>
                                                @enderror
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

            <!-- Grand Total Summary -->
            @if(count($items ?? []) > 0)
                <div class="mt-4 pt-4 border-t border-gray-200 px-6">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <span class="text-lg font-medium text-gray-900">Grand Total:</span>
                            <button 
                                wire:click="refreshTotals" 
                                type="button"
                                class="text-sm px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 transition-colors"
                                title="Refresh totals calculation">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Refresh
                            </button>
                        </div>
                        <span class="text-xl font-bold text-indigo-600" wire:ignore>
                            <span id="grand-total">0</span>
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Approval Workflow Section -->
    <div class="bg-white border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Approval Workflow</h3>
        </div>

        <div class="p-6">
            <div class="space-y-6">
                <!-- Approval Setup Info -->
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-sm text-blue-800">
                                Set up custom approval workflow by adding approvers and defining their tasks.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Custom Approvers List -->
                <div class="space-y-3">
                    @error('customApprovalList')
                        <div class="bg-red-50 border border-red-200 p-3">
                            <div class="flex">
                                <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-sm text-red-700">{{ $message }}</span>
                            </div>
                        </div>
                    @enderror
                    
                    @if(empty($customApprovalList))
                        @php $customApprovalList = [['approver_id' => '', 'task_type' => 'approval']]; @endphp
                    @endif
                    
                    @foreach($customApprovalList ?? [['approver_id' => '', 'task_type' => 'approval']] as $index => $approvalItem)
                        <div wire:key="approval-item-{{ $index }}" class="border border-gray-200 p-4 bg-gray-50">
                            <div class="flex items-start space-x-4">
                                <!-- Order Number -->
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-medium">
                                    {{ $index + 1 }}
                                </div>
                                
                                <!-- Form Fields -->
                                <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Approver Selection -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Select Approver <span class="text-red-500">*</span>
                                        </label>
                                        <select wire:model.live="customApprovalList.{{ $index }}.approver_id" 
                                                class="w-full px-3 py-2 text-sm border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error('customApprovalList.'.$index.'.approver_id') border-red-300 @enderror">
                                            <option value="">Choose approver...</option>
                                            @foreach(($availableApprovers ?? []) as $approver)
                                                <option value="{{ $approver['id'] ?? '' }}">
                                                    {{ ($approver['name'] ?? 'Unknown') }} - {{ ucfirst(str_replace('_', ' ', ($approver['role'] ?? 'staff'))) }} ({{ ($approver['department'] ?? 'No Department') }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('customApprovalList.'.$index.'.approver_id')
                                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    
                                    <!-- Task Type Selection -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Task Type <span class="text-red-500">*</span>
                                        </label>
                                        <select wire:model.live="customApprovalList.{{ $index }}.task_type" 
                                                class="w-full px-3 py-2 text-sm border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="approval">Approval (Full Review & Approve)</option>
                                            <option value="paraf">Paraf (Acknowledge & Sign)</option>
                                        </select>
                                        @error('customApprovalList.'.$index.'.task_type')
                                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                
                                <!-- Delete Button -->
                                @if(count($customApprovalList ?? []) > 1)
                                    <div class="flex-shrink-0">
                                        <button 
                                            wire:click="removeCustomApproval({{ $index }})" 
                                            type="button"
                                            class="text-red-600 hover:text-red-800 p-2 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Remove this approval step">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Task Type Description -->
                            <div class="mt-2 ml-12 text-xs text-gray-500">
                                @if(($approvalItem['task_type'] ?? 'approval') === 'approval')
                                    <p><strong>Approval:</strong> Full review process - approver will evaluate and decide to approve or reject the request.</p>
                                @else
                                    <p><strong>Paraf:</strong> Acknowledgment process - approver confirms awareness and provides signature/initial.</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    
                    <!-- Add Approval Button -->
                    <div class="text-center pt-2">
                        <button 
                            wire:click="addCustomApproval" 
                            type="button"
                            class="inline-flex items-center px-4 py-2 border-2 border-dashed border-gray-300 text-sm font-medium text-gray-700 hover:border-blue-400 hover:text-blue-600 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Another Approval Step
                        </button>
                    </div>
                    
                    <!-- Approval Flow Preview -->
                    @if(count($customApprovalList ?? []) > 0 && collect($customApprovalList ?? [])->pluck('approver_id')->filter()->count() > 0)
                        <div class="bg-blue-50 border border-blue-200 p-4 mt-4">
                            <h6 class="text-sm font-medium text-blue-900 mb-2">Approval Flow Preview:</h6>
                            <div class="flex flex-wrap items-center space-x-2 text-sm text-blue-800">
                                @foreach($customApprovalList ?? [] as $index => $approvalItem)
                                    @if($approvalItem['approver_id'] ?? false)
                                        @php
                                            $selectedApprover = collect($availableApprovers ?? [])->firstWhere('id', $approvalItem['approver_id']);
                                        @endphp
                                        @if($selectedApprover)
                                            <div class="flex items-center bg-white px-2 py-1 border border-blue-200 mb-1">
                                                <span class="text-blue-600 font-medium mr-2">{{ $index + 1 }}.</span>
                                                <span>{{ $selectedApprover['name'] }}</span>
                                                <span class="text-xs text-blue-600 ml-1">({{ ucfirst($approvalItem['task_type'] ?? 'approval') }})</span>
                                            </div>
                                            @if($index < count($customApprovalList ?? []) - 1 && isset($customApprovalList[$index + 1]['approver_id']) && $customApprovalList[$index + 1]['approver_id'])
                                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                                </svg>
                                            @endif
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
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

<script>
// Calculate row total (qty * price)
function calculateRowTotal(index) {
    const qtyInput = document.querySelector(`input[wire\\:model\\.blur="items.${index}.quantity"]`);
    const priceInput = document.querySelector(`input[wire\\:model\\.blur="items.${index}.price"]`);
    const totalSpan = document.getElementById(`total-${index}`);
    
    if (!qtyInput || !priceInput || !totalSpan) return;
    
    const qty = parseInt(qtyInput.value) || 0;
    const price = parseInt(priceInput.value.replace(/[^0-9]/g, '')) || 0;
    const total = qty * price;
    
    // Update display
    totalSpan.textContent = new Intl.NumberFormat('id-ID').format(total);
    totalSpan.setAttribute('data-value', total);
    
    // Recalculate grand total
    updateGrandTotal();
}

// Update grand total display
function updateGrandTotal() {
    let grandTotal = 0;
    document.querySelectorAll('[id^="total-"]').forEach((el) => {
        const value = parseInt(el.getAttribute('data-value')) || 0;
        grandTotal += value;
    });
    
    const grandTotalEl = document.getElementById('grand-total');
    if (grandTotalEl) {
        grandTotalEl.textContent = new Intl.NumberFormat('id-ID').format(grandTotal);
    }
}

// Initialize all totals on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[id^="total-"]').forEach((el) => {
        const itemIndex = el.id.replace('total-', '');
        calculateRowTotal(itemIndex);
    });
    updateGrandTotal();
});

// Recalculate totals after Livewire updates
document.addEventListener('livewire:update', function() {
    setTimeout(() => {
        document.querySelectorAll('[id^="total-"]').forEach((el) => {
            const itemIndex = el.id.replace('total-', '');
            calculateRowTotal(itemIndex);
        });
        updateGrandTotal();
    }, 100);
});
</script>
