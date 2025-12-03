{{-- HOSTING ENVIRONMENT FIX: Defensive property initialization --}}
@php
    // CRITICAL: Initialize all variables with fallbacks for hosting environment
    // This prevents "Undefined variable" errors in strict hosting PHP configurations
    $submission_date = $this->submission_date ?? now()->format('d/m/Y');
    $user_name = $this->user_name ?? 'User';
    $department_name = $this->department_name ?? 'Department';
    $department_code = $this->department_code ?? 'DEPT';
    $items = $this->items ?? [];
    $customApprovalList = $this->customApprovalList ?? [];
    $availableApprovers = $availableApprovers ?? [];
    $isEdit = $this->isEdit ?? false;
    $isLoading = $this->isLoading ?? false;
    $currency = $this->currency ?? 'IDR';
    $purpose = $this->purpose ?? '';
    $used_for = $this->used_for ?? '';
    $expected_date = $this->expected_date ?? '';
@endphp

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
                                <li>ΓÇó {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Basic Information Form -->
    {{-- CRITICAL: This section MUST be visible --}}
    <div class="bg-white border border-gray-200" style="display: block !important; visibility: visible !important;">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Purchase Request Information</h3>
            @if(config('app.debug'))
            <p class="text-xs text-gray-500 mt-1">Debug: Section is rendering (user: {{ $user_name }}, dept: {{ $department_name }})</p>
            @endif
        </div>
        
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <!-- Purpose (Keperluan) -->
                <div class="sm:col-span-2">
                    <label for="purpose" class="block text-sm font-medium text-gray-900 mb-2">
                        Purpose / Requirements <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        wire:model.blur="purpose" 
                        id="purpose"
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Describe the purpose or requirements for this purchase request..."
                        maxlength="500"
                    ></textarea>
                    @error('purpose')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Used For (Digunakan untuk) -->
                <div class="sm:col-span-2">
                    <label for="used_for" class="block text-sm font-medium text-gray-900 mb-2">
                        Used For / Details <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        wire:model.blur="used_for" 
                        id="used_for"
                        rows="4"
                        class="w-full px-3 py-2 border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Provide detailed information about how these items will be used..."
                        maxlength="1000"
                    ></textarea>
                    @error('used_for')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Expected Date -->
                <div>
                    <label for="expected_date" class="block text-sm font-medium text-gray-900 mb-2">
                        Expected Delivery Date
                    </label>
                    <input 
                        wire:model.blur="expected_date" 
                        id="expected_date"
                        type="date"
                        min="{{ date('Y-m-d') }}"
                        class="w-full px-3 py-2 border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                    >
                    @error('expected_date')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Currency Selection -->
                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-900 mb-2">
                        Currency <span class="text-red-500">*</span>
                    </label>
                    <select 
                        wire:model.blur="currency" 
                        id="currency"
                        class="w-full px-3 py-2 border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        <option value="IDR">IDR - Indonesian Rupiah</option>
                        <option value="USD">USD - US Dollar</option>
                        <option value="EUR">EUR - Euro</option>
                        <option value="SGD">SGD - Singapore Dollar</option>
                    </select>
                    @error('currency')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Auto-filled Information -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">Request Information</label>
                    <div class="space-y-2">
                        <div class="flex justify-between py-2 px-3 bg-gray-50 border border-gray-200">
                            <span class="text-sm text-gray-600">Submission Date:</span>
                            <span class="text-sm text-gray-900">{{ $submission_date ?? now()->format('d/m/Y') }}</span>
                        </div>
                        <div class="flex justify-between py-2 px-3 bg-gray-50 border border-gray-200">
                            <span class="text-sm text-gray-600">Requested By:</span>
                            <span class="text-sm text-gray-900">{{ $user_name ?? 'Unknown User' }}</span>
                        </div>
                        <div class="flex justify-between py-2 px-3 bg-gray-50 border border-gray-200">
                            <span class="text-sm text-gray-600">Department:</span>
                            <span class="text-sm text-gray-900">{{ $department_name ?? 'Department not set' }} ({{ $department_code ?? 'N/A' }})</span>
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
                <h3 class="text-lg font-medium text-gray-900">Request Items</h3>
                
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
            <div class="overflow-x-auto -mx-6 px-6">
                <div class="min-w-full inline-block align-middle">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">NO</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-48">Item Name</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-32">Brand</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-40">Description</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-32">Supplier</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32 min-w-32">QTY</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-36 min-w-36">Unit</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Price</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Total</th>
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
                                                wire:model.blur="items.{{ $index }}.item_name" 
                                                type="text"
                                                class="w-full min-w-44 px-3 py-2 text-sm border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Enter item name"
                                                maxlength="255"
                                            >
                                            @error("items.{$index}.item_name") 
                                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                            @enderror
                                        </td>

                                        <!-- Brand -->
                                        <td class="px-3 py-3">
                                            <input 
                                                wire:model.blur="items.{{ $index }}.brand_name" 
                                                type="text"
                                                class="w-full min-w-28 px-3 py-2 text-sm border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Brand name"
                                                maxlength="255"
                                            >
                                            @error("items.{$index}.brand_name") 
                                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                            @enderror
                                        </td>

                                        <!-- Description -->
                                        <td class="px-3 py-3">
                                            <input 
                                                wire:model.blur="items.{{ $index }}.item_description" 
                                                type="text"
                                                class="w-full min-w-36 px-3 py-2 text-sm border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Description"
                                                maxlength="500"
                                            >
                                            @error("items.{$index}.item_description") 
                                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                            @enderror
                                        </td>

                                        <!-- Supplier -->
                                        <td class="px-3 py-3">
                                            <input 
                                                wire:model.blur="items.{{ $index }}.supplier_name" 
                                                type="text"
                                                class="w-full min-w-28 px-3 py-2 text-sm border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Supplier"
                                                maxlength="255"
                                            >
                                            @error("items.{$index}.supplier_name") 
                                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                            @enderror
                                        </td>

                                        <!-- Quantity -->
                                        <td class="px-3 py-3">
                                            <input 
                                                wire:model.blur="items.{{ $index }}.quantity" 
                                                type="number"
                                                step="1"
                                                min="1"
                                                value="{{ $item['quantity'] ?? 1 }}"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-center"
                                                placeholder="1"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, ''); calculateRowTotal({{ $index }});"
                                            >
                                            @error("items.{$index}.quantity") 
                                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                            @enderror
                                        </td>                                    <!-- Unit -->
                                    <td class="px-3 py-3">
                                        <select 
                                            wire:model.blur="items.{{ $index }}.unit" 
                                            class="w-full px-3 py-2 text-sm border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-center">
                                            <option value="">Unit</option>
                                            <option value="pcs">pcs</option>
                                            <option value="unit">unit</option>
                                            <option value="set">set</option>
                                            <option value="pack">pack</option>
                                            <option value="box">box</option>
                                            <option value="kg">kg</option>
                                            <option value="meter">meter</option>
                                            <option value="liter">liter</option>
                                        </select>
                                        @error("items.{$index}.unit") 
                                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </td>

                                        <!-- Price -->
                                        <td class="px-3 py-3">
                                            <input 
                                                wire:model.blur="items.{{ $index }}.unit_price" 
                                                type="text"
                                                inputmode="decimal"
                                                value="{{ $item['unit_price'] ?? 0 }}"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-right"
                                                placeholder="0"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, ''); calculateRowTotal({{ $index }});"
                                            >
                                            @error("items.{$index}.unit_price") 
                                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                            @enderror
                                        </td>                                    <!-- Total -->
                                    <td class="px-3 py-3 text-sm text-gray-900 font-semibold text-right bg-gray-50">
                                        @php
                                            $quantity = is_numeric($item['quantity'] ?? 0) ? intval($item['quantity']) : 0;
                                            $unitPrice = 0;
                                            if (isset($item['unit_price'])) {
                                                $cleanPrice = preg_replace('/[^0-9]/', '', $item['unit_price']);
                                                $unitPrice = is_numeric($cleanPrice) ? intval($cleanPrice) : 0;
                                            }
                                            $itemTotal = $quantity * $unitPrice;
                                        @endphp
                                        <span id="total-{{ $index }}">{{ number_format($itemTotal, 0, '', ',') }}</span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-2 py-2">
                                        @if(count($items ?? []) > 1)
                                            <button 
                                                wire:click="removeItem({{ $index }})" 
                                                type="button"
                                                onclick="this.closest('tr').style.opacity='0.5'; this.disabled=true;"
                                                class="text-red-600 hover:text-red-800 p-1 disabled:opacity-50 transition-all duration-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        @endif
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

            <!-- Total Summary -->
            @if(count($items ?? []) > 0)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <span class="text-lg font-medium text-gray-900">Total Amount:</span>
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
    <div class="bg-white border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Approval Workflow</h3>
        </div>

        <div class="p-6">
            <div class="space-y-6">
                <!-- Approval Setup Info - Single Clean Notice -->
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

                <!-- Approval Settings -->
                <div class="space-y-4">
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
                            <!-- Initialize first approval row if empty -->
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
                                                class="text-red-600 hover:text-red-800 p-2 hover:bg-red-50 transition-colors"
                                                title="Remove this approval step">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
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
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-between items-center pt-6 border-t border-gray-200">
        <button 
            wire:click="saveDraft" 
            type="button"
            onclick="showSavingState(this);"
            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
            {{ ($isEdit ?? false) ? 'Update Draft' : 'Save as Draft' }}
        </button>

        <div class="flex items-center space-x-3">
            <!-- Save Changes (for edit mode, keeps rejected status if PR was rejected) -->
            <button 
                wire:click="submitPurchaseRequest" 
                type="button"
                onclick="showSubmittingState(this);"
                class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                @if($isEdit ?? false)
                    @if($this->isRejected ?? false)
                        Save Changes (Rejected)
                    @else
                        Save Changes
                    @endif
                @else
                    Submit for Approval
                @endif
            </button>
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
    
    function showResubmittingState(button) {
        button.disabled = true;
        button.classList.add('opacity-75');
        button.innerHTML = '<svg class="w-5 h-5 mr-2 animate-spin inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Resubmitting...';
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
        
        // Sum all row totals
        document.querySelectorAll('[id^="total-"]').forEach(function(totalSpan) {
            const value = totalSpan.textContent.replace(/[^0-9]/g, '');
            const total = parseInt(value) || 0;
            if (total > 0) {
                grandTotal += total;
            }
        });
        
        // Update grand total display
        const grandTotalSpan = document.getElementById('grand-total');
        if (grandTotalSpan) {
            grandTotalSpan.textContent = new Intl.NumberFormat('id-ID').format(grandTotal);
        }
    }
    
    // Simple button disable function to prevent double clicks
    function preventDoubleClick(button) {
        button.disabled = true;
        button.classList.add('opacity-75');
        setTimeout(() => {
            button.disabled = false;
            button.classList.remove('opacity-75');
        }, 500);
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
