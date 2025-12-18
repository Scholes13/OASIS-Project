<div wire:init="loadData">
    {{-- Loading Overlay --}}
    <div wire:loading.flex wire:target="switchTab,previousPage,nextPage,gotoPage,filters" class="fixed inset-0 bg-white/80 backdrop-blur-sm z-50 items-center justify-center">
        <div class="flex flex-col items-center space-y-6">
            <div class="relative">
                <div class="w-16 h-16 border-4 border-indigo-200 rounded-full"></div>
                <div class="absolute top-0 left-0 w-16 h-16 border-4 border-indigo-500 rounded-full border-t-transparent animate-spin"></div>
            </div>
            <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-700 mb-1">Loading Data</h3>
                <p class="text-sm text-gray-400">Please wait...</p>
            </div>
        </div>
    </div>

    {{-- Tabs and Filters --}}
    <div class="px-4 py-5 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            {{-- Tabs --}}
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px" aria-label="Tabs">
                    <button 
                        wire:click="switchTab('pending')"
                        class="flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'pending' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <span class="flex items-center justify-center">
                            Pending
                            @if($this->tabCounts['pending'] > 0)
                                <span class="ml-2 py-0.5 px-2 rounded-full text-xs {{ $activeTab === 'pending' ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $this->tabCounts['pending'] }}
                                </span>
                            @endif
                        </span>
                    </button>
                    <button 
                        wire:click="switchTab('in_progress')"
                        class="flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'in_progress' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <span class="flex items-center justify-center">
                            In Progress
                            @if($this->tabCounts['in_progress'] > 0)
                                <span class="ml-2 py-0.5 px-2 rounded-full text-xs {{ $activeTab === 'in_progress' ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $this->tabCounts['in_progress'] }}
                                </span>
                            @endif
                        </span>
                    </button>
                    <button 
                        wire:click="switchTab('completed')"
                        class="flex-1 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'completed' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <span class="flex items-center justify-center">
                            Completed
                            @if($this->tabCounts['completed'] > 0)
                                <span class="ml-2 py-0.5 px-2 rounded-full text-xs {{ $activeTab === 'completed' ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $this->tabCounts['completed'] }}
                                </span>
                            @endif
                        </span>
                    </button>
                </nav>
            </div>

            {{-- Filters - Compact Style --}}
            <div class="px-5 py-3 border-b border-gray-100">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-3 flex-wrap">
                        {{-- Type Filter --}}
                        <select 
                            wire:model.live="filters.type"
                            class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 bg-white focus:outline-none focus:border-gray-300 min-w-[140px]">
                            <option value="">All Types</option>
                            <option value="App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest">Purchase Request</option>
                            <option value="App\Models\Modules\Purchasing\StockRequest\StockRequest">Stock Request</option>
                        </select>

                        {{-- Date Preset Filter --}}
                        <select 
                            wire:model.live="datePreset"
                            class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 bg-white focus:outline-none focus:border-gray-300 min-w-[140px]">
                            <option value="all">All Dates</option>
                            <option value="today">Today</option>
                            <option value="last_30_days">Last 30 Days</option>
                            <option value="last_3_months">Last 3 Months</option>
                            <option value="last_6_months">Last 6 Months</option>
                        </select>

                        {{-- Reset Filters --}}
                        @if($this->hasActiveFilters() || $datePreset !== 'all' || !empty($search))
                            <button 
                                wire:click="resetFilters"
                                class="px-3 py-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                Reset Filters
                            </button>
                        @endif
                    </div>

                    {{-- Search Box --}}
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search number..."
                            class="pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 bg-white focus:outline-none focus:border-gray-300 w-48">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Task Cards Grid --}}
    <div class="px-4 py-5 sm:px-6 lg:px-8">
        {{-- Loading Skeleton --}}
        @if(!$readyToLoad)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @for($i = 0; $i < 6; $i++)
                    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden animate-pulse">
                        <div class="p-5 border-b border-gray-100">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="h-6 w-16 bg-gray-200 rounded-full mb-2"></div>
                                    <div class="h-5 w-32 bg-gray-200 rounded mt-2"></div>
                                </div>
                                <div class="h-6 w-16 bg-gray-200 rounded-full"></div>
                            </div>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="h-4 w-48 bg-gray-200 rounded"></div>
                            <div class="h-4 w-32 bg-gray-200 rounded"></div>
                            <div class="h-4 w-40 bg-gray-200 rounded"></div>
                        </div>
                        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100">
                            <div class="flex gap-2">
                                <div class="h-9 flex-1 bg-gray-200 rounded-lg"></div>
                                <div class="h-9 flex-1 bg-gray-200 rounded-lg"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        @elseif($this->tasks->count() > 0)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 auto-rows-fr">
                @foreach($this->tasks as $task)
                    <div wire:key="task-{{ $task->id }}" class="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-md transition-shadow flex flex-col">
                        {{-- Card Header --}}
                        <div class="px-5 py-3 border-b border-gray-100">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    {{-- Document Number with mono font --}}
                                    <h3 class="text-base font-semibold text-gray-900 font-mono truncate">
                                        {{ $task->taskable->pr_number ?? $task->taskable->st_number ?? 'N/A' }}
                                    </h3>
                                </div>

                                {{-- Status Badge --}}
                                <div class="ml-2 flex flex-col gap-1">
                                    @if($task->status === 'pending_followup')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                            Pending
                                        </span>
                                    @elseif($task->status === 'in_progress')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                            In Progress
                                        </span>
                                    @elseif($task->status === 'done')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                            Completed
                                        </span>
                                    @endif

                                    {{-- SLA Violation Indicator --}}
                                    @if($task->hasExceededFollowupSla() || $task->hasExceededCompletionSla())
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            SLA
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Card Body --}}
                        <div class="p-5 space-y-3 flex-1">
                            {{-- Department --}}
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <span class="truncate">{{ $task->department->name ?? 'N/A' }}</span>
                            </div>

                            {{-- Estimated Price --}}
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Rp {{ number_format($task->estimated_total_price, 0, ',', '.') }}</span>
                            </div>

                            {{-- Entered Date --}}
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>{{ $task->entered_at->format('d M Y, H:i') }}</span>
                            </div>

                            {{-- Assignment Info --}}
                            @if($task->assigned_admin_id)
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span class="truncate">{{ $task->assignedAdmin->name }}</span>
                                </div>
                            @else
                                <div class="flex items-center text-sm text-amber-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <span>Unassigned</span>
                                </div>
                            @endif

                            {{-- Completed Task Additional Info --}}
                            @if($task->status === 'done')
                                <div class="pt-3 border-t border-gray-100 space-y-2">
                                    {{-- Realized Price --}}
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">Realized Price:</span>
                                        <span class="font-medium text-gray-900">Rp {{ number_format($task->realized_total_price, 0, ',', '.') }}</span>
                                    </div>

                                    {{-- Savings --}}
                                    @if($task->savings_amount !== null)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-500">Savings:</span>
                                            <span class="font-medium {{ $task->savings_amount >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                                Rp {{ number_format($task->savings_amount, 0, ',', '.') }}
                                                ({{ number_format($task->savings_percentage, 1) }}%)
                                            </span>
                                        </div>
                                    @endif

                                    {{-- Completion Time --}}
                                    @if($task->completion_time_minutes !== null)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-500">Completion Time:</span>
                                            <span class="font-medium text-gray-900">
                                                @if($task->completion_time_minutes >= 60)
                                                    {{ round($task->completion_time_minutes / 60, 1) }} hrs
                                                @elseif($task->completion_time_minutes >= 1)
                                                    {{ round($task->completion_time_minutes) }} min
                                                @else
                                                    {{ max(1, round($task->completion_time_minutes * 60)) }} sec
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Card Footer with Actions --}}
                        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100">
                            <div class="flex items-center justify-between gap-2">
                                @if($task->assigned_admin_id === null && $activeTab === 'pending')
                                    {{-- Claim Task Button --}}
                                    <button 
                                        wire:click="claimTask({{ $task->id }})"
                                        wire:loading.attr="disabled"
                                        class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                        Claim Task
                                    </button>
                                @elseif($task->status === 'pending_followup' && $task->assigned_admin_id === Auth::id())
                                    {{-- Start Task Button --}}
                                    <button 
                                        wire:click="startTask({{ $task->id }})"
                                        wire:loading.attr="disabled"
                                        class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                        Start Task
                                    </button>
                                @elseif($task->status === 'in_progress' && $task->assigned_admin_id === Auth::id())
                                    {{-- Complete Task Button --}}
                                    <button 
                                        wire:click="openCompleteModal({{ $task->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="openCompleteModal({{ $task->id }})"
                                        class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                        Complete Task
                                    </button>
                                @endif

                                {{-- View Details Button --}}
                                <a 
                                    href="{{ route('purchasing.admin.tasks.show', $task->id) }}"
                                    class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors border border-gray-300">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $this->tasks->links() }}
            </div>
        @else
            {{-- Empty State --}}
            <div class="bg-white rounded-xl border border-gray-100 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No tasks found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if($activeTab === 'pending')
                        There are no pending tasks at the moment.
                    @elseif($activeTab === 'in_progress')
                        You don't have any tasks in progress.
                    @else
                        You haven't completed any tasks yet.
                    @endif
                </p>
                @if($this->hasActiveFilters())
                    <button 
                        wire:click="resetFilters"
                        class="mt-4 inline-flex items-center px-4 py-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Clear filters to see all tasks
                    </button>
                @endif
            </div>
        @endif
    </div>

    {{-- Complete Task Modal with Item-Level Realization --}}
    @if($showCompleteModal)
        <div 
            class="fixed inset-0 z-50 overflow-y-auto" 
            aria-labelledby="modal-title" 
            role="dialog" 
            aria-modal="true"
            x-data="{
                items: @js($completingTaskItems),
                realizations: @js($itemRealizations),
                
                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(Math.round(num));
                },
                
                calculateItemTotal(index) {
                    const quantity = this.items[index]?.quantity || 0;
                    const unitPrice = parseFloat(this.realizations[index]?.realized_unit_price) || 0;
                    return quantity * unitPrice;
                },
                
                calculateGrandTotal() {
                    let total = 0;
                    for (let i = 0; i < Object.keys(this.items).length; i++) {
                        total += this.calculateItemTotal(i);
                    }
                    return total;
                },
                
                calculateItemSavings(index) {
                    const estimated = this.items[index]?.estimated_total_price || 0;
                    const realized = this.calculateItemTotal(index);
                    return estimated - realized;
                },
                
                calculateItemSavingsPercentage(index) {
                    const estimated = this.items[index]?.estimated_total_price || 0;
                    if (estimated <= 0) return 0;
                    const savings = this.calculateItemSavings(index);
                    return (savings / estimated) * 100;
                },
                
                calculateGrandSavings() {
                    let totalEstimated = 0;
                    let totalRealized = 0;
                    for (let i = 0; i < Object.keys(this.items).length; i++) {
                        totalEstimated += this.items[i]?.estimated_total_price || 0;
                        totalRealized += this.calculateItemTotal(i);
                    }
                    return totalEstimated - totalRealized;
                },
                
                calculateGrandSavingsPercentage() {
                    let totalEstimated = 0;
                    for (let i = 0; i < Object.keys(this.items).length; i++) {
                        totalEstimated += this.items[i]?.estimated_total_price || 0;
                    }
                    if (totalEstimated <= 0) return 0;
                    return (this.calculateGrandSavings() / totalEstimated) * 100;
                },
                
                updateRealization(index, field, value) {
                    if (field === 'realized_unit_price') {
                        const numValue = parseFloat(value) || 0;
                        this.realizations[index].realized_unit_price = numValue;
                        this.realizations[index].realized_total_price = this.calculateItemTotal(index);
                        // Force Alpine to re-render immediately
                        this.$nextTick(() => {
                            // Sync with Livewire after UI update
                            $wire.updateItemRealization(index, field, value);
                        });
                    } else if (field === 'realized_supplier') {
                        this.realizations[index].realized_supplier = value;
                        // Sync with Livewire
                        $wire.updateItemRealization(index, field, value);
                    }
                }
            }"
        >
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeCompleteModal"></div>

                {{-- Modal panel - wider for items table --}}
                <div class="relative bg-white rounded-lg text-left shadow-xl transform transition-all w-full max-w-7xl max-h-[90vh] flex flex-col">
                    {{-- Modal Header --}}
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg leading-6 font-semibold text-gray-900">
                                Complete Task - Item Realization
                            </h3>
                            <button 
                                type="button" 
                                wire:click="closeCompleteModal"
                                class="text-gray-400 hover:text-gray-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Enter the realized prices for each item. Prices are pre-filled with estimated values.
                        </p>
                    </div>

                    {{-- Modal Body - Scrollable --}}
                    <div class="flex-1 overflow-y-auto px-6 py-4">
                        @if(count($completingTaskItems) > 0)
                            {{-- Items Table with Horizontal Scroll --}}
                            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                <table class="w-full divide-y divide-gray-200" style="table-layout: fixed; min-width: 1100px;">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[140px]">
                                                Item
                                            </th>
                                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-[60px]">
                                                Qty
                                            </th>
                                            <th scope="col" class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-[110px]">
                                                Est. Unit
                                            </th>
                                            <th scope="col" class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-[120px]">
                                                Est. Total
                                            </th>
                                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-[200px]">
                                                Realized Unit <span class="text-red-500">*</span>
                                            </th>
                                            <th scope="col" class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-[120px]">
                                                Realized Total
                                            </th>
                                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-[260px]">
                                                Supplier
                                            </th>
                                            <th scope="col" class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-[130px]">
                                                Savings
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        @foreach($completingTaskItems as $index => $item)
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                {{-- Item Name - Max 2 lines --}}
                                                <td class="px-3 py-3">
                                                    <div class="text-sm font-medium text-gray-900 leading-tight overflow-hidden" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; max-height: 2.5rem;" title="{{ $item['name'] }}">
                                                        {{ $item['name'] }}
                                                    </div>
                                                </td>
                                                
                                                {{-- Quantity & Unit --}}
                                                <td class="px-3 py-3 text-center">
                                                    <div class="text-sm text-gray-900">{{ number_format($item['quantity'], 0) }}</div>
                                                    <div class="text-xs text-gray-500">{{ $item['unit'] }}</div>
                                                </td>
                                                
                                                {{-- Estimated Unit Price --}}
                                                <td class="px-3 py-3 text-right">
                                                    <div class="text-sm text-gray-600 whitespace-nowrap">Rp {{ number_format($item['estimated_unit_price'], 0, ',', '.') }}</div>
                                                </td>
                                                
                                                {{-- Estimated Total Price --}}
                                                <td class="px-3 py-3 text-right">
                                                    <div class="text-sm font-medium text-gray-900 whitespace-nowrap">Rp {{ number_format($item['estimated_total_price'], 0, ',', '.') }}</div>
                                                </td>
                                                
                                                {{-- Realized Unit Price Input --}}
                                                <td class="px-3 py-3">
                                                    <div class="relative">
                                                        <div class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                                                            <span class="text-gray-400 text-xs">Rp</span>
                                                        </div>
                                                        <input 
                                                            type="number" 
                                                            x-model.number="realizations[{{ $index }}].realized_unit_price"
                                                            @input.debounce.300ms="$wire.updateItemRealization({{ $index }}, 'realized_unit_price', $event.target.value)"
                                                            class="block w-full pl-7 pr-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-right"
                                                            min="0"
                                                            step="any"
                                                            placeholder="0">
                                                    </div>
                                                    @error("itemRealizations.{$index}.realized_unit_price")
                                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                    @enderror
                                                </td>
                                                
                                                {{-- Realized Total (Calculated) --}}
                                                <td class="px-3 py-3 text-right">
                                                    <div class="text-sm font-medium text-gray-900 whitespace-nowrap" x-text="'Rp ' + formatNumber(calculateItemTotal({{ $index }}))"></div>
                                                </td>
                                                
                                                {{-- Realized Supplier Input --}}
                                                <td class="px-3 py-3">
                                                    <input 
                                                        type="text" 
                                                        x-model="realizations[{{ $index }}].realized_supplier"
                                                        @input.debounce.300ms="$wire.updateItemRealization({{ $index }}, 'realized_supplier', $event.target.value)"
                                                        class="block w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                                        placeholder="{{ $item['original_supplier'] ?: 'Supplier' }}">
                                                </td>
                                                
                                                {{-- Savings (Calculated with Color Coding) --}}
                                                <td class="px-3 py-3 text-right">
                                                    <div 
                                                        class="flex flex-col items-end"
                                                        x-bind:class="{
                                                            'text-emerald-600': calculateItemSavings({{ $index }}) >= 0,
                                                            'text-red-600': calculateItemSavings({{ $index }}) < 0
                                                        }"
                                                    >
                                                        <span class="text-sm font-medium whitespace-nowrap" x-text="'Rp ' + formatNumber(Math.abs(calculateItemSavings({{ $index }})))"></span>
                                                        <span 
                                                            class="text-xs whitespace-nowrap"
                                                            x-text="'(' + (calculateItemSavings({{ $index }}) >= 0 ? '+' : '-') + Math.abs(calculateItemSavingsPercentage({{ $index }})).toFixed(1) + '%)'">
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    {{-- Grand Total Footer --}}
                                    <tfoot class="bg-gray-50">
                                        <tr class="border-t-2 border-gray-300">
                                            <td colspan="3" class="px-3 py-3 text-right">
                                                <span class="text-sm font-semibold text-gray-700">Grand Total:</span>
                                            </td>
                                            <td class="px-3 py-3 text-right">
                                                <span class="text-sm font-bold text-gray-900">
                                                    Rp {{ number_format(collect($completingTaskItems)->sum('estimated_total_price'), 0, ',', '.') }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-3"></td>
                                            <td class="px-3 py-3 text-right">
                                                <span class="text-sm font-bold text-gray-900" x-text="'Rp ' + formatNumber(calculateGrandTotal())"></span>
                                            </td>
                                            <td class="px-3 py-3"></td>
                                            <td class="px-3 py-3 text-right">
                                                <div 
                                                    x-bind:class="{
                                                        'text-emerald-600': calculateGrandSavings() >= 0,
                                                        'text-red-600': calculateGrandSavings() < 0
                                                    }"
                                                >
                                                    <span class="text-sm font-bold" x-text="'Rp ' + formatNumber(Math.abs(calculateGrandSavings()))"></span>
                                                    <span 
                                                        class="text-xs block"
                                                        x-text="'(' + (calculateGrandSavings() >= 0 ? '+' : '-') + Math.abs(calculateGrandSavingsPercentage()).toFixed(1) + '%)'">
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            {{-- No Items Message --}}
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No items to realize</h3>
                                <p class="mt-1 text-sm text-gray-500">This task has no associated items.</p>
                            </div>
                        @endif

                        {{-- Notes Section --}}
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <label for="completionNotes" class="block text-sm font-medium text-gray-700">
                                Notes (Optional)
                            </label>
                            <textarea 
                                wire:model="completionNotes"
                                id="completionNotes"
                                rows="2"
                                class="mt-1 block w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                                placeholder="Add any notes about this task completion..."></textarea>
                            @error('completionNotes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            <span class="text-red-500">*</span> Required fields
                        </div>
                        <div class="flex gap-3">
                            <button 
                                type="button"
                                wire:click="closeCompleteModal"
                                wire:loading.attr="disabled"
                                wire:target="completeTask,completeTaskWithItems"
                                class="inline-flex justify-center rounded-lg border border-gray-300 px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 transition-colors">
                                Cancel
                            </button>
                            <button 
                                type="button"
                                wire:click="completeTaskWithItems"
                                class="inline-flex items-center justify-center rounded-lg border border-transparent px-4 py-2 bg-emerald-600 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
                                Complete Task
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
