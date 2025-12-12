<div>
    {{-- Header --}}
    <div class="px-4 py-5 sm:px-6 lg:px-8 bg-white border-b border-gray-100">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                {{-- Back Button --}}
                <a href="{{ route('purchasing.admin.tasks') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Tasks
                </a>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="px-4 py-5 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Task Details --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Task Overview Card --}}
                <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    {{-- Task Type Badge --}}
                                    @if(str_contains($this->task->taskable_type, 'PurchaseRequest'))
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            Purchase Request
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                            Stock Request
                                        </span>
                                    @endif

                                    {{-- Status Badge --}}
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $this->getStatusBadgeColor($this->task->status) }}">
                                        {{ $this->getStatusLabel($this->task->status) }}
                                    </span>

                                    {{-- SLA Violation Badge --}}
                                    @if($this->task->hasExceededFollowupSla() || $this->task->hasExceededCompletionSla())
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            SLA Exceeded
                                        </span>
                                    @endif
                                </div>

                                <h2 class="text-xl font-semibold text-gray-900 mt-3">
                                    {{ $this->task->taskable->pr_number ?? $this->task->taskable->st_number ?? 'N/A' }}
                                </h2>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">
                        {{-- Task Information Grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            {{-- Business Unit --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Business Unit</label>
                                <p class="text-base text-gray-900">{{ $this->task->businessUnit->name }}</p>
                            </div>

                            {{-- Department --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Department</label>
                                <p class="text-base text-gray-900">{{ $this->task->department->name }}</p>
                            </div>

                            {{-- Assigned Admin --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Assigned To</label>
                                <p class="text-base text-gray-900">
                                    @if($this->task->assigned_admin_id)
                                        {{ $this->task->assignedAdmin->name }}
                                    @else
                                        <span class="text-amber-600">Unassigned</span>
                                    @endif
                                </p>
                            </div>

                            {{-- Entered Date --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Entered Date</label>
                                <p class="text-base text-gray-900">{{ $this->task->entered_at->format('d M Y, H:i') }}</p>
                            </div>

                            {{-- Started Date --}}
                            @if($this->task->started_at)
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Started Date</label>
                                    <p class="text-base text-gray-900">{{ $this->task->started_at->format('d M Y, H:i') }}</p>
                                </div>
                            @endif

                            {{-- Completed Date --}}
                            @if($this->task->completed_at)
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Completed Date</label>
                                    <p class="text-base text-gray-900">{{ $this->task->completed_at->format('d M Y, H:i') }}</p>
                                </div>
                            @endif

                            {{-- Follow-up Time --}}
                            @if($this->task->followup_time_minutes !== null)
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Follow-up Time</label>
                                    <p class="text-base text-gray-900">
                                        {{ floor($this->task->followup_time_minutes / 60) }}h {{ $this->task->followup_time_minutes % 60 }}m
                                    </p>
                                </div>
                            @endif

                            {{-- Completion Time --}}
                            @if($this->task->completion_time_minutes !== null)
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Completion Time</label>
                                    <p class="text-base text-gray-900">
                                        {{ floor($this->task->completion_time_minutes / 60) }}h {{ $this->task->completion_time_minutes % 60 }}m
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- Price Information --}}
                        <div class="pt-6 border-t border-gray-100">
                            <h3 class="text-base font-semibold text-gray-900 mb-4">Price Information</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                {{-- Estimated Price --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Estimated Price</label>
                                    <p class="text-lg font-semibold text-gray-900">
                                        Rp {{ number_format($this->task->estimated_total_price, 0, ',', '.') }}
                                    </p>
                                </div>

                                {{-- Realized Price --}}
                                @if($this->task->realized_total_price !== null)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500 mb-1">Realized Price</label>
                                        <p class="text-lg font-semibold text-gray-900">
                                            Rp {{ number_format($this->task->realized_total_price, 0, ',', '.') }}
                                        </p>
                                    </div>
                                @endif

                                {{-- Savings Amount --}}
                                @if($this->task->savings_amount !== null)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500 mb-1">Savings Amount</label>
                                        <p class="text-lg font-semibold {{ $this->task->savings_amount >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                            Rp {{ number_format($this->task->savings_amount, 0, ',', '.') }}
                                        </p>
                                    </div>
                                @endif

                                {{-- Savings Percentage --}}
                                @if($this->task->savings_percentage !== null)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500 mb-1">Savings Percentage</label>
                                        <p class="text-lg font-semibold {{ $this->task->savings_percentage >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                            {{ number_format($this->task->savings_percentage, 2) }}%
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- SLA Status --}}
                        @if($this->task->status !== 'done')
                            <div class="pt-6 border-t border-gray-100">
                                <h3 class="text-base font-semibold text-gray-900 mb-4">SLA Status</h3>
                                @if($this->task->hasExceededFollowupSla())
                                    <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-red-800">
                                                    Follow-up SLA Exceeded
                                                </p>
                                                <p class="mt-1 text-sm text-red-700">
                                                    This task has exceeded the follow-up time target. Please start working on it immediately.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($this->task->hasExceededCompletionSla())
                                    <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-red-800">
                                                    Completion SLA Exceeded
                                                </p>
                                                <p class="mt-1 text-sm text-red-700">
                                                    This task has exceeded the completion time target. Please complete it as soon as possible.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="bg-emerald-50 border-l-4 border-emerald-400 p-4 rounded">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-emerald-800">
                                                    Within SLA Target
                                                </p>
                                                <p class="mt-1 text-sm text-emerald-700">
                                                    This task is currently within the SLA target time.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Notes --}}
                        @if($this->task->notes)
                            <div class="pt-6 border-t border-gray-100">
                                <h3 class="text-base font-semibold text-gray-900 mb-2">Notes</h3>
                                <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $this->task->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Activity Timeline --}}
                <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900">Activity Timeline</h3>
                    </div>
                    <div class="p-6">
                        @if($this->task->activities->count() > 0)
                            <div class="flow-root">
                                <ul role="list" class="-mb-8">
                                    @foreach($this->task->activities as $activity)
                                        <li>
                                            <div class="relative pb-8">
                                                @if(!$loop->last)
                                                    <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                                @endif
                                                <div class="relative flex space-x-3">
                                                    <div>
                                                        <span class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center ring-8 ring-white">
                                                            <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            </svg>
                                                        </span>
                                                    </div>
                                                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                        <div>
                                                            <p class="text-sm text-gray-900">
                                                                {{ $activity->description }}
                                                                @if($activity->causer)
                                                                    <span class="font-medium text-gray-900">by {{ $activity->causer->name }}</span>
                                                                @endif
                                                            </p>
                                                            @if($activity->properties->count() > 0)
                                                                <div class="mt-1 text-xs text-gray-500">
                                                                    @foreach($activity->properties as $key => $value)
                                                                        <div>{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ is_numeric($value) ? number_format($value, 2) : $value }}</div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                                            <time datetime="{{ $activity->created_at->toIso8601String() }}">
                                                                {{ $activity->created_at->format('d M Y, H:i') }}
                                                            </time>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 text-center py-4">No activity recorded yet</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right Column: Actions --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl border border-gray-100 overflow-hidden sticky top-6">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900">Actions</h3>
                    </div>
                    <div class="p-5 space-y-3">
                        {{-- Claim Task Button --}}
                        @if($this->task->assigned_admin_id === null && $this->task->status === 'pending_followup')
                            <button 
                                wire:click="claimTask"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
                                </svg>
                                Claim Task
                            </button>
                            <p class="text-xs text-gray-500 text-center">
                                Claim this task to start working on it
                            </p>
                        @endif

                        {{-- Start Task Button --}}
                        @if($this->task->status === 'pending_followup' && $this->task->assigned_admin_id === Auth::id())
                            <button 
                                wire:click="startTask"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Start Task
                            </button>
                            <p class="text-xs text-gray-500 text-center">
                                Begin working on this task
                            </p>
                        @endif

                        {{-- Complete Task Button --}}
                        @if($this->task->status === 'in_progress' && $this->task->assigned_admin_id === Auth::id())
                            <button 
                                wire:click="openCompleteModal"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-medium">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Complete Task
                            </button>
                            <p class="text-xs text-gray-500 text-center">
                                Mark this task as completed
                            </p>
                        @endif

                        {{-- View Source Document --}}
                        <div class="pt-3 border-t border-gray-100">
                            @if(str_contains($this->task->taskable_type, 'PurchaseRequest'))
                                <a 
                                    href="{{ route('purchase-requests.show', $this->task->taskable_id) }}"
                                    target="_blank"
                                    class="w-full inline-flex items-center justify-center px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors border border-gray-300">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    View Purchase Request
                                </a>
                            @else
                                <a 
                                    href="{{ route('stock-requests.show', $this->task->taskable_id) }}"
                                    target="_blank"
                                    class="w-full inline-flex items-center justify-center px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors border border-gray-300">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    View Stock Request
                                </a>
                            @endif
                        </div>

                        {{-- Task Status Info --}}
                        @if($this->task->status === 'done')
                            <div class="pt-3 border-t border-gray-100">
                                <div class="flex items-center justify-center text-sm text-emerald-600">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Task Completed
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Complete Task Modal --}}
    @if($showCompleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Background overlay --}}
                <div 
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                    aria-hidden="true"
                    wire:click="closeCompleteModal"></div>

                {{-- Center modal --}}
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                {{-- Modal panel --}}
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Complete Task
                                </h3>
                                <div class="mt-4 space-y-4">
                                    {{-- Realized Price Input --}}
                                    <div>
                                        <label for="realizedTotalPrice" class="block text-sm font-medium text-gray-700 mb-1">
                                            Realized Total Price <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                                Rp
                                            </span>
                                            <input 
                                                type="number" 
                                                id="realizedTotalPrice"
                                                wire:model="realizedTotalPrice"
                                                step="0.01"
                                                min="0.01"
                                                class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                                placeholder="0.00">
                                        </div>
                                        @error('realizedTotalPrice')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                        <p class="mt-1 text-xs text-gray-500">
                                            Estimated: Rp {{ number_format($this->task->estimated_total_price, 0, ',', '.') }}
                                        </p>
                                    </div>

                                    {{-- Notes Input --}}
                                    <div>
                                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                                            Notes (Optional)
                                        </label>
                                        <textarea 
                                            id="notes"
                                            wire:model="notes"
                                            rows="3"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                            placeholder="Add any notes about this task completion..."></textarea>
                                        @error('notes')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            type="button"
                            wire:click="completeTask"
                            class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Complete Task
                        </button>
                        <button 
                            type="button"
                            wire:click="closeCompleteModal"
                            class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
