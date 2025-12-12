<div wire:init="loadData">
    {{-- Header --}}
    <div class="px-4 py-5 sm:px-6 lg:px-8 bg-white border-b border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Task List</h1>
                <p class="mt-1 text-sm text-gray-500">View and manage your procurement follow-up tasks</p>
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

            {{-- Filters --}}
            <div class="p-5 bg-gray-50 border-b border-gray-100">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Date From Filter --}}
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">
                            Date From
                        </label>
                        <input 
                            type="date" 
                            id="date_from"
                            wire:model.live="filters.date_from"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>

                    {{-- Date To Filter --}}
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">
                            Date To
                        </label>
                        <input 
                            type="date" 
                            id="date_to"
                            wire:model.live="filters.date_to"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>

                    {{-- Type Filter --}}
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                            Type
                        </label>
                        <select 
                            id="type"
                            wire:model.live="filters.type"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">All Types</option>
                            <option value="App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest">Purchase Request</option>
                            <option value="App\Models\Modules\Purchasing\StockRequest\StockRequest">Stock Request</option>
                        </select>
                    </div>

                    {{-- Reset Filters Button --}}
                    <div class="flex items-end">
                        <button 
                            wire:click="resetFilters"
                            class="w-full inline-flex items-center justify-center px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors border border-gray-300">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Reset Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Task Cards Grid --}}
    <div class="px-4 py-5 sm:px-6 lg:px-8">
        {{-- Debug Info (remove in production) --}}
        @if(config('app.debug'))
            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-sm">
                <strong>Debug Info:</strong><br>
                Ready to Load: {{ $readyToLoad ? 'YES' : 'NO' }}<br>
                Active BU ID: {{ $activeBusinessUnitId ?? 'NULL' }}<br>
                Session BU ID: {{ session('current_business_unit_id') ?? 'NULL' }}<br>
                User ID: {{ auth()->id() }}<br>
                Active Tab: {{ $activeTab }}<br>
                Tasks Count: {{ $this->tasks->count() }}<br>
                @if(method_exists($this->tasks, 'total'))
                    Tasks Total: {{ $this->tasks->total() }}
                @endif
            </div>
        @endif
        
        @if($this->tasks->count() > 0)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($this->tasks as $task)
                    <div wire:key="task-{{ $task->id }}" class="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                        {{-- Card Header --}}
                        <div class="p-5 border-b border-gray-100">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    {{-- Task Type Badge --}}
                                    @if($task->taskable_type === 'App\\Models\\Modules\\Purchasing\\PurchaseRequest\\PurchaseRequest')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            PR
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                            ST
                                        </span>
                                    @endif

                                    {{-- Task Number --}}
                                    <h3 class="text-base font-semibold text-gray-900 mt-2 truncate">
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
                        <div class="p-5 space-y-3">
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
                                                {{ floor($task->completion_time_minutes / 60) }}h {{ $task->completion_time_minutes % 60 }}m
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
                                        wire:target="claimTask({{ $task->id }})"
                                        class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                        <svg wire:loading.remove wire:target="claimTask({{ $task->id }})" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        <svg wire:loading wire:target="claimTask({{ $task->id }})" class="animate-spin w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span wire:loading.remove wire:target="claimTask({{ $task->id }})">Claim Task</span>
                                        <span wire:loading wire:target="claimTask({{ $task->id }})">Claiming...</span>
                                    </button>
                                @elseif($task->status === 'pending_followup' && $task->assigned_admin_id === Auth::id())
                                    {{-- Start Task Button --}}
                                    <button 
                                        wire:click="startTask({{ $task->id }})"
                                        class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Start Task
                                    </button>
                                @endif

                                {{-- View Details Button --}}
                                <a 
                                    href="{{ route('purchasing.admin.tasks.show', $task->id) }}"
                                    class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors border border-gray-300">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
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
</div>
