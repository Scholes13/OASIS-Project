<div class="min-h-screen bg-gray-50 py-8">
    <div class="w-full px-4 sm:px-6 lg:px-8 space-y-8">

        <!-- Enterprise Header Section -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="bg-white/10 p-3 rounded-lg backdrop-blur-sm">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Create Purchase Request</h1>
                        <p class="text-blue-100">Professional Request Management System</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-blue-100">{{ $department_name ?? 'Department' }}</div>
                    <div class="text-sm text-blue-200">{{ $user_name ?? 'User' }}</div>
                    <div class="text-xs text-blue-300">{{ $submission_date ?? now()->format('d M Y') }}</div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            
            <!-- Form Section -->
            <div class="xl:col-span-2 space-y-8">
                
                <!-- Basic Information Section -->
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                    <div class="flex items-center mb-6">
                        <div class="bg-blue-100 p-2 rounded-lg mr-3">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Title -->
                        <div class="md:col-span-2">
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                Request Title <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="title"
                                wire:model.blur="title"
                                placeholder="Enter request title..."
                                class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white transition-colors duration-200"
                                maxlength="255">
                            @error('title')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @endif
                        </div>

                        <!-- Business Unit -->
                        <div>
                            <label for="business_unit_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Business Unit <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select 
                                    id="business_unit_id"
                                    wire:model.live="business_unit_id"
                                    class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white appearance-none cursor-pointer"
                                    required>
                                    <option value="">Select Business Unit</option>
                                    @foreach($businessUnits as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->code }})</option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>
                            @error('business_unit_id')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @endif
                        </div>

                        <!-- Department -->
                        <div>
                            <label for="department_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Department <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select 
                                    id="department_id"
                                    wire:model.blur="department_id"
                                    class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white appearance-none cursor-pointer"
                                    required>
                                    <option value="">Select Department</option>
                                    @if($business_unit_id && isset($departments))
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->code }})</option>
                                        @endforeach
                                    @endif
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>
                            @error('department_id')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @endif
                        </div>

                        <!-- Request Date -->
                        <div>
                            <label for="request_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Request Date <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="date" 
                                id="request_date"
                                wire:model.blur="request_date"
                                class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                required>
                            @error('request_date')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @endif
                        </div>

                        <!-- Expected Date -->
                        <div>
                            <label for="expected_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Expected Date
                            </label>
                            <input 
                                type="date" 
                                id="expected_date"
                                wire:model.blur="expected_date"
                                class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                            @error('expected_date')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @endif
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Description
                            </label>
                            <div class="relative">
                                <textarea 
                                    id="description"
                                    wire:model.blur="description"
                                    placeholder="Enter detailed description..."
                                    rows="4"
                                    maxlength="1000"
                                    class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white resize-none"
                                    oninput="updateCharacterCount('description', 'description-count', 1000)"></textarea>
                                <div class="flex justify-between items-center mt-1">
                                    <div></div>
                                    <span class="text-xs text-gray-500">
                                        <span id="description-count">0</span>/1000 characters
                                    </span>
                                </div>
                            </div>
                            @error('description')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Purpose & Usage Section -->
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                    <div class="flex items-center mb-6">
                        <div class="bg-green-100 p-2 rounded-lg mr-3">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Purpose & Usage Details</h3>
                    </div>

                    <div class="space-y-6">
                        <!-- Purpose -->
                        <div>
                            <label for="purpose" class="block text-sm font-medium text-gray-700 mb-2">
                                Purpose <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <textarea 
                                    id="purpose"
                                    wire:model.blur="purpose"
                                    placeholder="What is the purpose of this request?"
                                    rows="3"
                                    maxlength="500"
                                    class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white resize-none"
                                    oninput="updateCharacterCount('purpose', 'purpose-count', 500)"></textarea>
                                <div class="flex justify-between items-center mt-1">
                                    @if($purpose && strlen($purpose) >= 3)
                                        <div class="flex items-center text-green-600">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="text-xs">Valid</span>
                                        </div>
                                    @else
                                        <div class="text-xs text-gray-400">Minimum 3 characters required</div>
                                    @endif
                                    <span class="text-xs text-gray-500">
                                        <span id="purpose-count">0</span>/500 characters
                                    </span>
                                </div>
                            </div>
                            @error('purpose')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @endif
                        </div>

                        <!-- Used For -->
                        <div>
                            <label for="used_for" class="block text-sm font-medium text-gray-700 mb-2">
                                Usage Details <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <textarea 
                                    id="used_for"
                                    wire:model.blur="used_for"
                                    placeholder="How will these items be used? Include specific details..."
                                    rows="4"
                                    maxlength="1000"
                                    class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white resize-none"
                                    oninput="updateCharacterCount('used_for', 'used-for-count', 1000)"></textarea>
                                <div class="flex justify-between items-center mt-1">
                                    @if($used_for && strlen($used_for) >= 10)
                                        <div class="flex items-center text-green-600">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="text-xs">Valid</span>
                                        </div>
                                    @else
                                        <div class="text-xs text-gray-400">Minimum 10 characters required</div>
                                    @endif
                                    <span class="text-xs text-gray-500">
                                        <span id="used-for-count">0</span>/1000 characters
                                    </span>
                                </div>
                            </div>
                            @error('used_for')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Items Management Section -->
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="bg-purple-100 p-2 rounded-lg mr-3">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Items Management</h3>
                        </div>
                        <button 
                            wire:click="addItem" 
                            type="button"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Item
                        </button>
                    </div>

                    <!-- Items Table -->
                    @if(count($items) > 0)
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="pr-form-table w-full text-sm">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-3 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">#</th>
                                        <th class="px-4 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-48">Item Name</th>
                                        <th class="px-4 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-32 col-brand">Brand</th>
                                        <th class="px-4 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-32 col-supplier">Supplier</th>
                                        <th class="px-4 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-20">Qty</th>
                                        <th class="px-4 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-32">Unit</th>
                                        <th class="px-4 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-32">Unit Price</th>
                                        <th class="px-4 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-32">Total</th>
                                        <th class="px-4 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($items as $index => $item)
                                        <tr class="hover:bg-gray-50/50 transition-colors duration-150">
                                            <!-- Row Number -->
                                            <td class="px-3 py-4 text-sm font-medium text-gray-900 text-center">
                                                {{ $index + 1 }}
                                            </td>
                                            
                                            <!-- Item Name -->
                                            <td class="px-4 py-4">
                                                <input 
                                                    type="text" 
                                                    wire:model.blur="items.{{ $index }}.item_name"
                                                    placeholder="Item name..."
                                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                                    maxlength="255">
                                                @error("items.{$index}.item_name")
                                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                                @enderror
                                            </td>

                                            <!-- Brand -->
                                            <td class="px-4 py-4 col-brand">
                                                <input 
                                                    type="text" 
                                                    wire:model.blur="items.{{ $index }}.brand"
                                                    placeholder="Brand..."
                                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                                    maxlength="255">
                                            </td>

                                            <!-- Supplier -->
                                            <td class="px-4 py-4 col-supplier">
                                                <input 
                                                    type="text" 
                                                    wire:model.blur="items.{{ $index }}.supplier_name"
                                                    placeholder="Supplier..."
                                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                                    maxlength="255">
                                            </td>

                                            <!-- Quantity -->
                                            <td class="px-4 py-4">
                                                <input 
                                                    type="number" 
                                                    wire:model.blur="items.{{ $index }}.quantity"
                                                    placeholder="0"
                                                    min="0.01"
                                                    step="0.01"
                                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 no-spinner"
                                                    oninput="calculateRowTotal({{ $index }})">
                                                @error("items.{$index}.quantity")
                                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                                @enderror
                                            </td>

                                            <!-- Unit -->
                                            <td class="px-4 py-4">
                                                <input 
                                                    type="text" 
                                                    wire:model.blur="items.{{ $index }}.unit"
                                                    placeholder="pcs, kg, m..."
                                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                                    maxlength="50">
                                                @error("items.{$index}.unit")
                                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                                @enderror
                                            </td>

                                            <!-- Unit Price -->
                                            <td class="px-4 py-4">
                                                <input 
                                                    type="text" 
                                                    wire:model.blur="items.{{ $index }}.unit_price"
                                                    placeholder="0"
                                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                                    oninput="this.value = this.value.replace(/[^0-9]/g, ''); calculateRowTotal({{ $index }})">
                                                @error("items.{$index}.unit_price")
                                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                                @enderror
                                            </td>

                                            <!-- Total -->
                                            <td class="px-4 py-4">
                                                <span id="row-total-{{ $index }}" class="text-sm font-medium text-gray-900">Rp 0</span>
                                            </td>

                                            <!-- Remove Action -->
                                            <td class="px-4 py-4 text-center">
                                                @if(count($items) > 1)
                                                    <button 
                                                        wire:click="removeItem({{ $index }})" 
                                                        type="button"
                                                        class="inline-flex items-center justify-center w-8 h-8 text-red-600 hover:bg-red-50 rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                                        title="Remove item">
                                                        <span class="text-lg font-bold">×</span>
                                                    </button>
                                                @else
                                                    <span class="text-gray-300 text-xs">Required</span>
                                                @endif
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

                    <!-- Grand Total -->
                    @if(count($items) > 0)
                        <div class="mt-6 flex justify-end">
                            <div class="bg-gray-50 rounded-lg px-6 py-4 min-w-64">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-600">Total Items:</span>
                                    <span id="total-items" class="text-sm font-bold text-gray-900">{{ count($items) }}</span>
                                </div>
                                <div class="flex justify-between items-center border-t border-gray-200 pt-2">
                                    <span class="text-base font-semibold text-gray-900">Grand Total:</span>
                                    <span id="grand-total-table" class="text-base font-bold text-blue-600">Rp 0</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Submit Section -->
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <button 
                                type="button"
                                class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200">
                                Save as Draft
                            </button>
                        </div>
                        <div class="flex items-center space-x-4">
                            <button 
                                wire:click="submitRequest" 
                                type="button"
                                wire:loading.attr="disabled"
                                class="btn-primary px-8 py-3 text-white font-medium rounded-lg hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50">
                                <span wire:loading.remove>Submit Request</span>
                                <span wire:loading class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
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

            <!-- Sidebar -->
            <div class="xl:col-span-1 space-y-8">
                
                <!-- Request Summary Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-amber-50 to-yellow-100 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center space-x-3">
                            <div class="bg-amber-500 p-2 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900">Request Summary</h4>
                        </div>
                    </div>

                    <div class="px-6 py-4 space-y-4">
                        <!-- Total Items -->
                        <div class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="text-sm font-medium text-gray-600">Total Items</span>
                            <span id="total-items-sidebar" class="text-sm font-semibold text-gray-900">{{ count($items) }}</span>
                        </div>

                        <!-- Grand Total -->
                        <div class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="text-sm font-medium text-gray-600">Grand Total</span>
                            <span id="grand-total-sidebar" class="text-sm font-bold text-blue-600">Rp 0</span>
                        </div>

                        <!-- Request Date -->
                        <div class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="text-sm font-medium text-gray-600">Request Date</span>
                            <span class="text-sm font-semibold text-gray-900">
                                @if($request_date)
                                    {{ \Carbon\Carbon::parse($request_date)->format('d M Y') }}
                                @else
                                    <span class="text-gray-400 text-xs">Not set</span>
                                @endif
                            </span>
                        </div>

                        <!-- Expected Delivery -->
                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm font-medium text-gray-600">Expected Delivery</span>
                            <span class="text-sm font-semibold text-gray-900">
                                @if($expected_date)
                                    {{ \Carbon\Carbon::parse($expected_date)->format('d M Y') }}
                                @else
                                    <span class="text-gray-400 text-xs">Not specified</span>
                                @endif
                            </span>
                        </div>

                        <!-- Status -->
                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm font-medium text-gray-600">Status</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L10 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                Draft
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Validation Checklist -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-100 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center space-x-3">
                            <div class="bg-green-500 p-2 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900">Validation Checklist</h4>
                        </div>
                    </div>

                    <div class="px-6 py-4 space-y-3">
                        <div class="flex items-center space-x-2">
                            @if($title)
                                <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            @else
                                <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                            <span class="{{ $title ? 'text-green-600' : 'text-gray-400' }}">Title</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($business_unit_id)
                                <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            @else
                                <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                            <span class="{{ $business_unit_id ? 'text-green-600' : 'text-gray-400' }}">Business Unit</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($department_id)
                                <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            @else
                                <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                            <span class="{{ $department_id ? 'text-green-600' : 'text-gray-400' }}">Department</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($purpose && strlen($purpose) >= 3)
                                <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            @else
                                <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                            <span class="{{ $purpose && strlen($purpose) >= 3 ? 'text-green-600' : 'text-gray-400' }}">Purpose</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($used_for && strlen($used_for) >= 10)
                                <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            @else
                                <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                            <span class="{{ $used_for && strlen($used_for) >= 10 ? 'text-green-600' : 'text-gray-400' }}">Usage Details</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if(count($items) > 0)
                                <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            @else
                                <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                            <span class="{{ count($items) > 0 ? 'text-green-600' : 'text-gray-400' }}">At least one item</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Enterprise-level calculation and interaction functions
    function calculateRowTotal(index) {
        const qtyInput = document.querySelector(`input[wire\\:model\\.blur="items.${index}.quantity"]`);
        const priceInput = document.querySelector(`input[wire\\:model\\.blur="items.${index}.unit_price"]`);
        const totalSpan = document.getElementById(`row-total-${index}`);
        
        if (qtyInput && priceInput && totalSpan) {
            const qty = parseInt(qtyInput.value.replace(/[^0-9]/g, '')) || 0;
            const price = parseInt(priceInput.value.replace(/[^0-9]/g, '')) || 0;
            const total = qty * price;
            
            totalSpan.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
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
        
        // Update multiple total displays
        const grandTotalElements = [
            document.getElementById('grand-total'),
            document.getElementById('grand-total-table'), 
            document.getElementById('grand-total-sidebar')
        ];
        
        grandTotalElements.forEach(element => {
            if (element) {
                element.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal);
            }
        });
        
        // Update item counts
        const totalItemsElements = [
            document.getElementById('total-items'),
            document.getElementById('total-items-sidebar')
        ];
        
        totalItemsElements.forEach(element => {
            if (element) {
                element.textContent = totalSpans.length;
            }
        });
    }

    // Character counting for textareas
    function updateCharacterCount(textareaId, counterId, maxLength) {
        const textarea = document.getElementById(textareaId);
        const counter = document.getElementById(counterId);
        
        if (textarea && counter) {
            const currentLength = textarea.value.length;
            counter.textContent = currentLength;
            
            // Visual feedback for character limits
            if (currentLength > maxLength * 0.9) {
                counter.classList.add('text-amber-500');
                counter.classList.remove('text-gray-500');
            } else if (currentLength > maxLength * 0.95) {
                counter.classList.add('text-red-500');
                counter.classList.remove('text-amber-500', 'text-gray-500');
            } else {
                counter.classList.add('text-gray-500');
                counter.classList.remove('text-amber-500', 'text-red-500');
            }
        }
    }

    // Initialize everything on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateGrandTotal();
        
        // Set up character counters
        const textareas = [
            { id: 'description', counterId: 'description-count', maxLength: 1000 },
            { id: 'purpose', counterId: 'purpose-count', maxLength: 500 },
            { id: 'used_for', counterId: 'used-for-count', maxLength: 1000 }
        ];

        textareas.forEach(({ id, counterId, maxLength }) => {
            const textarea = document.getElementById(id);
            if (textarea) {
                textarea.addEventListener('input', () => {
                    updateCharacterCount(id, counterId, maxLength);
                });
                // Initialize count
                updateCharacterCount(id, counterId, maxLength);
            }
        });
    });

    // Update calculations after Livewire updates
    document.addEventListener('livewire:navigated', function() {
        setTimeout(() => {
            updateGrandTotal();
        }, 100);
    });

    // Livewire hook for after component updates
    Livewire.on('component-updated', () => {
        setTimeout(() => {
            updateGrandTotal();
        }, 100);
    });
    </script>

    @push('styles')
    <style>
    /* Enterprise-grade styling enhancements */
    .no-spinner::-webkit-outer-spin-button,
    .no-spinner::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .no-spinner {
        -moz-appearance: textfield;
    }

    /* Enhanced gradient backgrounds */
    .gradient-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .gradient-sidebar {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .gradient-success {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    /* Smooth transitions and hover effects */
    .transition-all {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .hover-scale:hover {
        transform: scale(1.02);
    }

    /* Professional table styling */
    .pr-form-table th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: linear-gradient(to bottom, #f8fafc, #e2e8f0);
    }

    .pr-form-table tbody tr:hover {
        background: linear-gradient(to right, #f8fafc, #ffffff);
        transform: scale(1.001);
        transition: all 0.2s ease;
    }

    /* Loading state improvements */
    .loading-overlay {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(4px);
        border-radius: 0.75rem;
    }

    /* Form focus states */
    .form-input:focus {
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        border-color: #3b82f6;
    }

    /* Professional button styles */
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        box-shadow: 0 4px 15px 0 rgba(102, 126, 234, 0.4);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        box-shadow: 0 6px 20px 0 rgba(102, 126, 234, 0.6);
        transform: translateY(-1px);
    }

    /* Advanced animations */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .animate-slide-in-up {
        animation: slideInUp 0.6s ease-out forwards;
    }

    .animate-fade-in-scale {
        animation: fadeInScale 0.4s ease-out forwards;
    }

    /* Responsive enhancements */
    @media (max-width: 1024px) {
        .pr-form-table .col-supplier {
            display: none !important;
        }
    }

    @media (max-width: 768px) {
        .pr-form-table .col-brand {
            display: none !important;
        }
    }

    /* Custom scrollbars */
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    </style>
    @endpush
</div>