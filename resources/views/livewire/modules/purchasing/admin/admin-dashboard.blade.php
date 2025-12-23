<div wire:init="loadData">
    <!-- Date Filter Section -->
    <div class="mb-6 flex flex-col lg:flex-row lg:items-end gap-4">
        <div class="flex-1 max-w-xs">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Date Range Filter</label>
            <select wire:model.live="datePreset" 
                    wire:loading.class="opacity-50"
                    wire:target="datePreset"
                    class="w-full rounded-lg border-gray-200 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-opacity duration-200">
                <option value="today">Today</option>
                <option value="this_week">This Week</option>
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
                <option value="this_quarter">This Quarter</option>
                <option value="this_year">This Year</option>
                <option value="all_time">All Time</option>
            </select>
        </div>

        <div class="ml-auto text-sm text-gray-500">
            <span class="font-medium">{{ $this->getDateRangeLabel() }}</span>
        </div>
    </div>

    @if(!$readyToLoad)
    {{-- Skeleton Loader --}}
    <div class="space-y-6 animate-pulse">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-6">
            @for($i = 0; $i < 3; $i++)
            <div class="bg-white rounded-xl p-5 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-11 h-11 bg-gray-200 rounded-xl"></div>
                    <div class="ml-4 flex-1">
                        <div class="h-4 bg-gray-200 rounded w-24 mb-2"></div>
                        <div class="h-7 bg-gray-300 rounded w-16"></div>
                    </div>
                </div>
            </div>
            @endfor
        </div>
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-gray-100 p-5 h-80"></div>
            <div class="bg-white rounded-xl border border-gray-100 p-5 h-80"></div>
        </div>
    </div>
    @else
    {{-- Dashboard Content --}}
    <div wire:key="dashboard-content">
        <!-- Loading Overlay -->
        <div wire:loading.flex 
             wire:target="switchBusinessUnit,handleBusinessUnitSwitch,datePreset" 
             class="fixed inset-0 bg-white/80 backdrop-blur-sm z-50 items-center justify-center">
            <div class="flex flex-col items-center space-y-4">
                <div class="w-12 h-12 border-4 border-blue-500 rounded-full border-t-transparent animate-spin"></div>
                <p class="text-sm text-gray-600">Updating Dashboard...</p>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <!-- Total Completed (Period) -->
            <div class="bg-white rounded-xl p-5 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Period Completed</p>
                        <p class="text-xl font-bold text-gray-900">{{ $this->totalTasksCompleted }}</p>
                    </div>
                    <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Avg Follow-up Time -->
            <div class="bg-white rounded-xl p-5 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Avg Follow-up</p>
                        <p class="text-xl font-bold text-gray-900">{{ $this->formatTime($this->averageFollowupTime) }}</p>
                    </div>
                    <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Avg Completion Time -->
            <div class="bg-white rounded-xl p-5 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Avg Completion</p>
                        <p class="text-xl font-bold text-gray-900">{{ $this->formatTime($this->averageCompletionTime) }}</p>
                    </div>
                    <div class="w-10 h-10 bg-cyan-50 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Savings -->
            <div class="bg-white rounded-xl p-5 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Savings</p>
                        <p class="text-xl font-bold text-emerald-600">{{ $this->formatCurrency($this->totalSavings) }}</p>
                    </div>
                    <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-1">Avg {{ number_format($this->averageSavingsPercentage, 1) }}% per task</p>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <!-- Recent Tasks -->
            <div class="bg-white rounded-xl border border-gray-100">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Recent Tasks</h3>
                        <p class="text-sm text-gray-400 mt-0.5">Tasks requiring attention</p>
                    </div>
                    <a href="{{ route('purchasing.admin.tasks') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                        View All →
                    </a>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse($this->recentTasks as $task)
                    <div class="px-5 py-4 hover:bg-gray-50/50 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ $task->taskable?->pr_number ?? $task->taskable?->st_number ?? 'N/A' }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $task->taskable?->requester?->name ?? 'Unknown' }} · {{ $task->department?->name ?? 'No Dept' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($task->status === 'pending_followup')
                                    @if($task->assigned_admin_id === null)
                                        @if($isPurchasingAdmin && !$isManagement)
                                        <button wire:click="claimTask({{ $task->id }})" 
                                                class="px-2.5 py-1 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-md hover:bg-indigo-100 transition-colors">
                                            Claim
                                        </button>
                                        @else
                                        <span class="px-2.5 py-1 text-xs font-medium text-gray-500 bg-gray-50 rounded-md">
                                            Unclaimed
                                        </span>
                                        @endif
                                    @else
                                        @if($isPurchasingAdmin && !$isManagement && $task->assigned_admin_id === auth()->id())
                                        <button wire:click="startTask({{ $task->id }})" 
                                                class="px-2.5 py-1 text-xs font-medium text-blue-600 bg-blue-50 rounded-md hover:bg-blue-100 transition-colors">
                                            Start
                                        </button>
                                        @else
                                        <span class="px-2.5 py-1 text-xs font-medium text-blue-500 bg-blue-50 rounded-md">
                                            Assigned
                                        </span>
                                        @endif
                                    @endif
                                @elseif($task->status === 'in_progress')
                                    <span class="px-2.5 py-1 text-xs font-medium text-amber-600 bg-amber-50 rounded-md">
                                        In Progress
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-12 text-center">
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500">No pending tasks</p>
                        <p class="text-xs text-gray-400 mt-1">All caught up!</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Department Breakdown -->
            <div class="bg-white rounded-xl border border-gray-100">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Tasks by Department</h3>
                    <p class="text-sm text-gray-400 mt-0.5">Completed tasks distribution</p>
                </div>
                <div class="p-5">
                    @forelse($this->departmentBreakdown as $dept)
                    <div class="mb-4 last:mb-0">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-700">{{ $dept['department'] }}</span>
                            <span class="text-sm font-medium text-gray-900">{{ $dept['count'] }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-indigo-500 h-2 rounded-full transition-all duration-300" 
                                 style="width: {{ $dept['percentage'] }}%"></div>
                        </div>
                    </div>
                    @empty
                    <div class="py-8 text-center">
                        <p class="text-sm text-gray-500">No data available</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
