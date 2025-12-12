<div wire:init="loadData">
    {{-- Header --}}
    <div class="px-4 py-5 sm:px-6 lg:px-8 bg-white border-b border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Purchasing Admin Dashboard</h1>
                <p class="mt-1 text-sm text-gray-500">Manage procurement follow-up tasks and track performance</p>
            </div>
        </div>
    </div>

    {{-- Task Count Cards --}}
    <div class="px-4 py-5 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Pending Tasks --}}
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Pending Follow-up
                                </dt>
                                <dd class="text-2xl font-bold text-gray-900 mt-1">
                                    {{ $this->taskCounts['pending'] }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- In Progress Tasks --}}
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    In Progress
                                </dt>
                                <dd class="text-2xl font-bold text-gray-900 mt-1">
                                    {{ $this->taskCounts['in_progress'] }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Completed Tasks --}}
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Completed
                                </dt>
                                <dd class="text-2xl font-bold text-gray-900 mt-1">
                                    {{ $this->taskCounts['done'] }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Tasks with Quick Actions --}}
    <div class="px-4 py-5 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Recent Tasks</h3>
                <p class="mt-1 text-sm text-gray-500">Quick actions for your pending and in-progress tasks</p>
            </div>
            
            <div class="divide-y divide-gray-100">
                @forelse($this->recentTasks as $task)
                    <div wire:key="task-{{ $task->id }}" class="p-5 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                {{-- Task Type Badge --}}
                                <div class="flex items-center gap-2 mb-2">
                                    @if($task->taskable_type === 'App\\Models\\Modules\\Purchasing\\PurchaseRequest\\PurchaseRequest')
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
                                    @if($task->status === 'pending_followup')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                            Pending
                                        </span>
                                    @elseif($task->status === 'in_progress')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                            In Progress
                                        </span>
                                    @endif
                                </div>

                                {{-- Task Details --}}
                                <div class="mt-2">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $task->taskable->pr_number ?? $task->taskable->st_number ?? 'N/A' }}
                                    </p>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Estimated: Rp {{ number_format($task->estimated_total_price, 0, ',', '.') }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        Entered {{ $task->entered_at->diffForHumans() }}
                                    </p>
                                </div>

                                {{-- Assignment Info --}}
                                @if($task->assigned_admin_id)
                                    <p class="text-xs text-gray-500 mt-2">
                                        Assigned to: {{ $task->assignedAdmin->name }}
                                    </p>
                                @else
                                    <p class="text-xs text-amber-600 mt-2">
                                        Unassigned - Available to claim
                                    </p>
                                @endif
                            </div>

                            {{-- Quick Action Buttons --}}
                            <div class="ml-4 flex-shrink-0 flex flex-col gap-2">
                                @if($task->assigned_admin_id === null)
                                    {{-- Claim Task Button --}}
                                    <button 
                                        wire:click="claimTask({{ $task->id }})"
                                        class="inline-flex items-center px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Claim Task
                                    </button>
                                @elseif($task->status === 'pending_followup' && $task->assigned_admin_id === Auth::id())
                                    {{-- Start Task Button --}}
                                    <button 
                                        wire:click="startTask({{ $task->id }})"
                                        class="inline-flex items-center px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
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
                                    class="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No tasks available</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            There are no pending or in-progress tasks at the moment.
                        </p>
                    </div>
                @endforelse
            </div>

            {{-- View All Tasks Link --}}
            @if($this->recentTasks->count() > 0)
                <div class="px-5 py-4 bg-gray-50 border-t border-gray-100">
                    <a href="{{ route('purchasing.admin.tasks') }}" 
                       class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                        View all tasks →
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="px-4 py-5 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Task List --}}
            <a href="{{ route('purchasing.admin.tasks') }}" 
               class="bg-white rounded-xl border border-gray-100 p-6 hover:border-indigo-200 hover:shadow-md transition-all">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-base font-semibold text-gray-900">Task List</h3>
                        <p class="text-sm text-gray-500 mt-1">View and manage all tasks</p>
                    </div>
                </div>
            </a>

            {{-- Performance Metrics --}}
            <a href="{{ route('purchasing.admin.performance-metrics') }}" 
               class="bg-white rounded-xl border border-gray-100 p-6 hover:border-indigo-200 hover:shadow-md transition-all">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-base font-semibold text-gray-900">My Performance</h3>
                        <p class="text-sm text-gray-500 mt-1">View your metrics and savings</p>
                    </div>
                </div>
            </a>

            {{-- Department Report --}}
            <a href="{{ route('purchasing.admin.department-report') }}" 
               class="bg-white rounded-xl border border-gray-100 p-6 hover:border-indigo-200 hover:shadow-md transition-all">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-base font-semibold text-gray-900">Department Report</h3>
                        <p class="text-sm text-gray-500 mt-1">View team performance</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
