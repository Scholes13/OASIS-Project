<div wire:init="loadData">
    <!-- Date Filter Section - Clean Design -->
    <div class="mb-6 flex flex-col lg:flex-row lg:items-end gap-4">
        <div class="flex-1 max-w-xs">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Date Range Filter</label>
            <select wire:model.live="dateFilter" 
                    wire:loading.class="opacity-50"
                    wire:target="dateFilter"
                    class="w-full rounded-lg border-gray-200 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-opacity duration-200">
                <option value="today">Today</option>
                <option value="this_week">This Week</option>
                <option value="this_month">This Month</option>
                <option value="last_30_days">Last 30 Days</option>
                <option value="this_year">This Year</option>
                <option value="custom">Custom Range</option>
            </select>
        </div>

        @if($customRange)
            <div class="flex-1 max-w-xs">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Start Date</label>
                <input type="date" 
                       wire:model.blur="startDate"
                       wire:loading.class="opacity-50"
                       wire:target="applyCustomDateRange"
                       class="w-full rounded-lg border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-opacity duration-200">
            </div>

            <div class="flex-1 max-w-xs">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">End Date</label>
                <input type="date" 
                       wire:model.blur="endDate"
                       wire:loading.class="opacity-50"
                       wire:target="applyCustomDateRange"
                       class="w-full rounded-lg border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-opacity duration-200">
            </div>

            <div>
                <button wire:click="applyCustomDateRange" 
                        wire:loading.attr="disabled"
                        wire:target="applyCustomDateRange"
                        type="button"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="applyCustomDateRange">Apply Filter</span>
                    <span wire:loading wire:target="applyCustomDateRange" class="flex items-center">
                        <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Applying...
                    </span>
                </button>
            </div>
        @endif

        <div class="ml-auto text-sm text-gray-500" wire:ignore.self>
            <span class="font-medium">Period:</span> {{ date('M j, Y', strtotime($startDate)) }} - {{ date('M j, Y', strtotime($endDate)) }}
        </div>
    </div>

    {{-- Skeleton Loader - ONLY for initial lazy load (readyToLoad = false) --}}
    {{-- Orchestra pattern: isLoading is for filter changes (shows overlay instead) --}}
    @if(!$readyToLoad)
    <div class="space-y-6" wire:key="dashboard-skeleton">
        {{-- Stats Cards Skeleton --}}
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6 animate-pulse">
            @for($i = 0; $i < 4; $i++)
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
        {{-- Chart & Right Column Skeleton --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6 animate-pulse">
            <div class="bg-white rounded-xl border border-gray-100 p-5 flex flex-col" style="min-height: 380px;">
                <div class="h-6 bg-gray-200 rounded w-48 mb-2"></div>
                <div class="h-4 bg-gray-100 rounded w-32 mb-4"></div>
                <div class="flex-1 bg-gray-100 rounded flex items-center justify-center">
                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
            <div class="flex flex-col gap-4" style="min-height: 380px;">
                <div class="bg-white rounded-xl border border-gray-100 p-5">
                    <div class="h-5 bg-gray-200 rounded w-28 mb-4"></div>
                    <div class="flex gap-3">
                        @for($i = 0; $i < 3; $i++)
                        <div class="flex-1 bg-gray-100 rounded-xl p-4 flex flex-col items-center">
                            <div class="w-10 h-10 bg-gray-200 rounded-lg mb-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-16"></div>
                        </div>
                        @endfor
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-5 flex-1">
                    <div class="h-5 bg-gray-200 rounded w-36 mb-1"></div>
                    <div class="h-3 bg-gray-100 rounded w-48 mb-4"></div>
                    <div class="space-y-3">
                        @for($i = 0; $i < 2; $i++)
                        <div class="p-3 rounded-lg bg-gray-50">
                            <div class="flex justify-between mb-2">
                                <div class="h-4 bg-gray-200 rounded w-24"></div>
                                <div class="h-4 bg-gray-100 rounded w-20"></div>
                            </div>
                            <div class="flex gap-1 mb-2">
                                <div class="flex-1 h-1.5 bg-gray-200 rounded-full"></div>
                                <div class="flex-1 h-1.5 bg-gray-200 rounded-full"></div>
                                <div class="flex-1 h-1.5 bg-gray-100 rounded-full"></div>
                            </div>
                            <div class="h-3 bg-gray-100 rounded w-32"></div>
                        </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
        {{-- Activity Skeleton --}}
        <div class="bg-white rounded-xl border border-gray-100 p-5 animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-40 mb-4"></div>
            <div class="space-y-4">
                @for($i = 0; $i < 5; $i++)
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gray-200 rounded-full"></div>
                    <div class="flex-1">
                        <div class="h-4 bg-gray-200 rounded w-3/4 mb-1"></div>
                        <div class="h-3 bg-gray-100 rounded w-1/2"></div>
                    </div>
                </div>
                @endfor
            </div>
        </div>
    </div>
    @else
    {{-- Dashboard Content - shown when data is ready --}}
    <div wire:key="dashboard-content">
    <!-- Modern Loading Overlay for filter changes -->
    <div wire:loading.flex 
         wire:target="switchBusinessUnit,handleBusinessUnitSwitch,dateFilter,applyCustomDateRange" 
         class="fixed inset-0 bg-white/80 backdrop-blur-sm z-50 items-center justify-center"
         style="margin-left: -1rem; margin-right: -1rem; margin-top: -1rem;">
        <div class="flex flex-col items-center space-y-6">
            <div class="relative">
                <div class="w-16 h-16 border-4 border-blue-100 rounded-full"></div>
                <div class="absolute top-0 left-0 w-16 h-16 border-4 border-blue-500 rounded-full border-t-transparent animate-spin"></div>
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-800 mb-1">Updating Dashboard</h3>
                <p class="text-sm text-gray-500 flex items-center justify-center">
                    Please wait
                    <span class="inline-flex ml-1">
                        <span class="animate-bounce" style="animation-delay: 0ms">.</span>
                        <span class="animate-bounce" style="animation-delay: 150ms">.</span>
                        <span class="animate-bounce" style="animation-delay: 300ms">.</span>
                    </span>
                </p>
            </div>
            <div class="w-48 h-1 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-blue-400 via-blue-500 to-blue-400 rounded-full animate-shimmer" style="width: 100%; background-size: 200% 100%;"></div>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats - Clean Grid with Comparison -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        
        <!-- Active PRs Card -->
        <div class="group bg-white rounded-xl p-5 border border-gray-100 hover:shadow-md transition-all duration-200">
            <div class="flex items-start justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.25" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1 min-w-0">
                        <h3 class="text-sm font-medium text-gray-500">Active PRs</h3>
                        <div wire:loading wire:target="switchBusinessUnit,handleBusinessUnitSwitch" class="animate-pulse">
                            <div class="h-7 bg-gray-200 rounded w-16 mb-1"></div>
                        </div>
                        <div wire:loading.remove wire:target="switchBusinessUnit,handleBusinessUnitSwitch">
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['active_prs'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                {{-- Comparison Badge --}}
                @php
                    $comp = $comparisonStats['active_prs'] ?? ['trend' => 'neutral', 'percentage' => 0];
                @endphp
                @if($comp['trend'] !== 'neutral')
                <div class="flex items-center {{ $comp['trend'] === 'up' ? 'text-emerald-600' : 'text-red-500' }}">
                    @if($comp['trend'] === 'up')
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    @endif
                    <span class="text-xs font-semibold ml-0.5">{{ abs($comp['percentage']) }}%</span>
                </div>
                @endif
            </div>
            <p class="text-xs text-gray-400 mt-2">{{ $stats['draft_prs'] ?? 0 }} drafts · All time active</p>
        </div>

        <!-- Pending Approvals Card -->
        <div class="group bg-white rounded-xl p-5 border border-gray-100 hover:shadow-md transition-all duration-200">
            <div class="flex items-start justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-11 h-11 bg-orange-50 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.25" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1 min-w-0">
                        <h3 class="text-sm font-medium text-gray-500">Pending Approvals</h3>
                        <div wire:loading wire:target="switchBusinessUnit,handleBusinessUnitSwitch" class="animate-pulse">
                            <div class="h-7 bg-gray-200 rounded w-16 mb-1"></div>
                        </div>
                        <div wire:loading.remove wire:target="switchBusinessUnit,handleBusinessUnitSwitch">
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['pending_approvals'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                {{-- Comparison Badge --}}
                @php
                    $comp = $comparisonStats['pending_approvals'] ?? ['trend' => 'neutral', 'percentage' => 0];
                @endphp
                @if($comp['trend'] !== 'neutral')
                <div class="flex items-center {{ $comp['trend'] === 'up' ? 'text-red-500' : 'text-emerald-600' }}">
                    @if($comp['trend'] === 'up')
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    @endif
                    <span class="text-xs font-semibold ml-0.5">{{ abs($comp['percentage']) }}%</span>
                </div>
                @endif
            </div>
            @if(($stats['overdue_approvals'] ?? 0) > 0)
                <p class="text-xs text-red-500 font-medium mt-2">{{ $stats['overdue_approvals'] }} overdue</p>
            @endif
        </div>

        <!-- Period PRs Card -->
        <div class="group bg-white rounded-xl p-5 border border-gray-100 hover:shadow-md transition-all duration-200">
            <div class="flex items-start justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-11 h-11 bg-emerald-50 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.25" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1 min-w-0">
                        <h3 class="text-sm font-medium text-gray-500">Period PRs</h3>
                        <div wire:loading wire:target="switchBusinessUnit,handleBusinessUnitSwitch" class="animate-pulse">
                            <div class="h-7 bg-gray-200 rounded w-16 mb-1"></div>
                        </div>
                        <div wire:loading.remove wire:target="switchBusinessUnit,handleBusinessUnitSwitch">
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['period_prs'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                {{-- Comparison Badge --}}
                @php
                    $comp = $comparisonStats['period_prs'] ?? ['trend' => 'neutral', 'percentage' => 0];
                @endphp
                @if($comp['trend'] !== 'neutral')
                <div class="flex items-center {{ $comp['trend'] === 'up' ? 'text-emerald-600' : 'text-red-500' }}">
                    @if($comp['trend'] === 'up')
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    @endif
                    <span class="text-xs font-semibold ml-0.5">{{ abs($comp['percentage']) }}%</span>
                </div>
                @endif
            </div>
            <p class="text-xs text-gray-400 mt-2">
                <span class="text-emerald-600">{{ $stats['approved_prs'] ?? 0 }}</span> approved · 
                <span class="text-red-500">{{ $stats['rejected_prs'] ?? 0 }}</span> rejected
            </p>
        </div>

        <!-- Total Amount Card -->
        <div class="group bg-white rounded-xl p-5 border border-gray-100 hover:shadow-md transition-all duration-200">
            <div class="flex items-start justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-11 h-11 bg-purple-50 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.25" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1 min-w-0">
                        <h3 class="text-sm font-medium text-gray-500">Total Amount</h3>
                        <div wire:loading wire:target="switchBusinessUnit,handleBusinessUnitSwitch" class="animate-pulse">
                            <div class="h-7 bg-gray-200 rounded w-24 mb-1"></div>
                        </div>
                        <div wire:loading.remove wire:target="switchBusinessUnit,handleBusinessUnitSwitch">
                            <p class="text-xl font-bold text-gray-900">Rp {{ number_format($stats['total_amount'] ?? 0, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
                {{-- Comparison Badge --}}
                @php
                    $comp = $comparisonStats['total_amount'] ?? ['trend' => 'neutral', 'percentage' => 0];
                @endphp
                @if($comp['trend'] !== 'neutral')
                <div class="flex items-center {{ $comp['trend'] === 'up' ? 'text-emerald-600' : 'text-red-500' }}">
                    @if($comp['trend'] === 'up')
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    @endif
                    <span class="text-xs font-semibold ml-0.5">{{ abs($comp['percentage']) }}%</span>
                </div>
                @endif
            </div>
            <p class="text-xs text-gray-400 mt-2">
                @php
                    $periodLabel = match($dateFilter) {
                        'today' => 'Today',
                        'this_week' => 'This week',
                        'this_month' => 'This month',
                        'this_year' => 'This year',
                        'last_30_days' => 'Last 30 days',
                        'custom' => date('M j', strtotime($startDate)) . ' - ' . date('M j', strtotime($endDate)),
                        default => 'Selected period'
                    };
                @endphp
                {{ $periodLabel }}
            </p>
        </div>
    </div>

    <!-- Charts Section - Clean Design -->
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2 mb-6">
        <!-- Purchase Request Trend Chart -->
        <div class="bg-white rounded-xl border border-gray-100 hover:shadow-md transition-shadow duration-200 flex flex-col" style="min-height: 380px;">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Purchase Request Trend</h3>
                <p class="text-sm text-gray-400 mt-0.5">Requests created per day</p>
            </div>
            <div class="p-5 flex-1 flex items-center justify-center" wire:ignore>
                {{-- Canvas always present, JavaScript controls visibility --}}
                <canvas id="dailyTrendChart" class="w-full" style="height: 280px; {{ count($chartData['daily'] ?? []) == 0 ? 'display: none;' : '' }}"></canvas>
                
                {{-- Empty State - JavaScript controls visibility --}}
                <div id="chartEmptyState" class="flex flex-col items-center justify-center py-12" style="{{ count($chartData['daily'] ?? []) > 0 ? 'display: none;' : '' }}">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-500">No data for this period</p>
                    <p class="text-xs text-gray-400 mt-1">Create a PR to see trends</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Approval Progress -->
        <div class="flex flex-col gap-4" style="min-height: 380px;">
            <!-- Quick Actions - Horizontal Row -->
            <div class="bg-white rounded-xl border border-gray-100 hover:shadow-md transition-shadow duration-200">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Quick Actions</h3>
                </div>
                <div class="p-4">
                    <div class="flex gap-3">
                        {{-- Create New PR --}}
                        <a href="{{ route('purchase-requests.create') }}" 
                           class="group flex-1 flex flex-col items-center p-3 rounded-lg border border-gray-100 hover:border-indigo-200 hover:bg-indigo-50/50 transition-all duration-200">
                            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mb-2 group-hover:bg-indigo-200 group-hover:scale-105 transition-all duration-200">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-700 group-hover:text-indigo-600">Create PR</span>
                        </a>

                        {{-- View All PRs --}}
                        <a href="{{ route('purchase-requests.index') }}" 
                           class="group flex-1 flex flex-col items-center p-3 rounded-lg border border-gray-100 hover:border-blue-200 hover:bg-blue-50/50 transition-all duration-200">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mb-2 group-hover:bg-blue-200 group-hover:scale-105 transition-all duration-200">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-700 group-hover:text-blue-600">View PRs</span>
                        </a>

                        {{-- Review Approvals --}}
                        <a href="{{ route('approvals.index') }}" 
                           class="group flex-1 flex flex-col items-center p-3 rounded-lg border border-gray-100 hover:border-emerald-200 hover:bg-emerald-50/50 transition-all duration-200">
                            <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center mb-2 group-hover:bg-emerald-200 group-hover:scale-105 transition-all duration-200">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-700 group-hover:text-emerald-600">Approvals</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Approval Progress -->
            <div class="bg-white rounded-xl border border-gray-100 hover:shadow-md transition-shadow duration-200 flex-1 flex flex-col">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Approval Progress</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Your PRs awaiting approval</p>
                </div>
                <div class="p-4 flex-1 flex flex-col">
                    @if(count($approvalProgress) > 0)
                        <div class="space-y-3 flex-1 overflow-y-auto max-h-48">
                            @foreach($approvalProgress as $pr)
                                <a href="{{ route('purchase-requests.show', $pr['id']) }}" 
                                   class="block p-3 rounded-lg border border-gray-100 hover:border-indigo-200 hover:bg-gray-50 transition-all duration-200">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-900">{{ $pr['pr_number'] }}</span>
                                        <span class="text-xs text-gray-500">Rp {{ number_format($pr['total_amount'], 0, ',', '.') }}</span>
                                    </div>
                                    {{-- Progress Steps --}}
                                    <div class="flex items-center gap-1 mb-2">
                                        @foreach($pr['steps'] as $step)
                                            @if($step['status'] === 'approved')
                                                <div class="flex-1 h-1.5 rounded-full bg-emerald-500"></div>
                                            @elseif($step['status'] === 'rejected')
                                                <div class="flex-1 h-1.5 rounded-full bg-red-500"></div>
                                            @elseif($step['status'] === 'pending')
                                                <div class="flex-1 h-1.5 rounded-full bg-amber-400 animate-pulse"></div>
                                            @else
                                                <div class="flex-1 h-1.5 rounded-full bg-gray-200"></div>
                                            @endif
                                        @endforeach
                                    </div>
                                    {{-- Current Status --}}
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500">
                                            Step {{ $pr['completed_steps'] + 1 }} of {{ $pr['total_steps'] }}
                                        </span>
                                        @if($pr['current_step'])
                                            <span class="text-xs text-amber-600 font-medium">
                                                Waiting: {{ $pr['current_step']['approver_name'] }}
                                            </span>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        {{-- Empty State --}}
                        <div class="flex flex-col items-center justify-center flex-1 py-6">
                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500">No pending approvals</p>
                            <p class="text-xs text-gray-400 mt-1">Your submitted PRs will appear here</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Business Unit Distribution Chart - Only show for parent BU (multiple child BUs) -->
    @php
        $hasBuDistribution = !empty($chartData['buDistribution']) && count($chartData['buDistribution']) > 1;
    @endphp
    @if($hasBuDistribution)
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2 mb-6" id="buDistributionSection">
        <!-- Pie Chart - Amount Distribution -->
        <div class="bg-white rounded-xl border border-gray-100 hover:shadow-md transition-shadow duration-200">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Spending by Business Unit</h3>
                <p class="text-sm text-gray-400 mt-0.5">Distribution of approved PR amounts</p>
            </div>
            <div class="p-5">
                <canvas id="buDistributionChart" style="height: 280px;"></canvas>
            </div>
        </div>

        <!-- Table - Detailed Breakdown -->
        <div class="bg-white rounded-xl border border-gray-100 hover:shadow-md transition-shadow duration-200">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Detailed Breakdown</h3>
                <p class="text-sm text-gray-400 mt-0.5">Amount per business unit</p>
            </div>
            <div class="p-5">
                <div class="space-y-3">
                    @php
                        $colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899'];
                    @endphp
                    @foreach($chartData['buDistribution'] as $index => $bu)
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full" style="background-color: {{ $colors[$index % count($colors)] }}"></div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $bu['bu_code'] }}</p>
                                <p class="text-xs text-gray-500">{{ $bu['bu_name'] }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900">Rp {{ number_format($bu['amount'], 0, ',', '.') }}</p>
                            <p class="text-xs text-gray-500">{{ $bu['percentage'] }}% · {{ $bu['pr_count'] }} PRs</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <!-- Total -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600">Total</span>
                        <span class="text-base font-bold text-gray-900">
                            Rp {{ number_format(collect($chartData['buDistribution'])->sum('amount'), 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Task Summary Widget - Activity Module -->
    @if(($taskStats['total'] ?? 0) > 0 || count($taskStats['recent_tasks'] ?? []) > 0)
    <div class="bg-white rounded-xl border border-gray-100 hover:shadow-md transition-shadow duration-200 mb-6">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-900">My Tasks</h3>
                <p class="text-sm text-gray-400 mt-0.5">Activity tracking overview</p>
            </div>
            <a href="{{ route('activity.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                View All →
            </a>
        </div>
        <div class="p-5">
            <!-- Task Stats Grid -->
            <div class="grid grid-cols-4 gap-4 mb-5">
                <div class="text-center p-3 rounded-lg bg-gray-50">
                    <p class="text-2xl font-bold text-gray-900">{{ $taskStats['total'] ?? 0 }}</p>
                    <p class="text-xs text-gray-500 mt-1">Total Tasks</p>
                </div>
                <div class="text-center p-3 rounded-lg bg-blue-50">
                    <p class="text-2xl font-bold text-blue-600">{{ $taskStats['in_progress'] ?? 0 }}</p>
                    <p class="text-xs text-blue-600 mt-1">In Progress</p>
                </div>
                <div class="text-center p-3 rounded-lg bg-emerald-50">
                    <p class="text-2xl font-bold text-emerald-600">{{ $taskStats['completed'] ?? 0 }}</p>
                    <p class="text-xs text-emerald-600 mt-1">Completed</p>
                </div>
                <div class="text-center p-3 rounded-lg {{ ($taskStats['overdue'] ?? 0) > 0 ? 'bg-red-50' : 'bg-gray-50' }}">
                    <p class="text-2xl font-bold {{ ($taskStats['overdue'] ?? 0) > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $taskStats['overdue'] ?? 0 }}</p>
                    <p class="text-xs {{ ($taskStats['overdue'] ?? 0) > 0 ? 'text-red-600' : 'text-gray-500' }} mt-1">Overdue</p>
                </div>
            </div>

            <!-- Recent Tasks List -->
            @if(count($taskStats['recent_tasks'] ?? []) > 0)
            <div class="space-y-2">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Upcoming Tasks</p>
                @foreach($taskStats['recent_tasks'] as $task)
                <a href="{{ route('activity.show', $task['id']) }}" 
                   class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-indigo-200 hover:bg-gray-50 transition-all duration-200">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $task['activity_color'] }}"></div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $task['title'] }}</p>
                            <p class="text-xs text-gray-500">{{ $task['activity_type'] }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @if($task['is_overdue'])
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">
                                Overdue
                            </span>
                        @elseif($task['status'] === 'in_progress')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
                                In Progress
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                Planned
                            </span>
                        @endif
                        @if($task['due_date'])
                            <span class="text-xs text-gray-400">{{ $task['due_date'] }}</span>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>
            @else
            <div class="text-center py-4">
                <p class="text-sm text-gray-500">No upcoming tasks</p>
                <a href="{{ route('activity.create') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium mt-1 inline-block">
                    Create a task →
                </a>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Recent Activity - Full Width with Modern Design -->
    <div class="bg-white rounded-xl border border-gray-100 hover:shadow-md transition-shadow duration-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-900">Recent Activity</h3>
                <p class="text-sm text-gray-400 mt-0.5">Latest updates on your requests</p>
            </div>
            @if(count($recentActivities) > 0)
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                {{ count($recentActivities) }} {{ count($recentActivities) === 1 ? 'activity' : 'activities' }}
            </span>
            @endif
        </div>
        <div class="divide-y divide-gray-50">
            @if(count($recentActivities) > 0)
                @foreach($recentActivities as $index => $activity)
                    <div class="px-5 py-4 hover:bg-gray-50/50 transition-colors duration-150">
                        <div class="flex items-start gap-3">
                            {{-- Activity Icon - Fixed size, no stretch --}}
                            <div class="flex-shrink-0">
                                @php
                                    // Color mapping for icon backgrounds
                                    $iconStyles = match($activity['color'] ?? 'gray') {
                                        'green', 'emerald' => 'bg-emerald-100 text-emerald-600',
                                        'red' => 'bg-red-100 text-red-600',
                                        'orange', 'amber', 'yellow' => 'bg-amber-100 text-amber-600',
                                        'blue' => 'bg-blue-100 text-blue-600',
                                        'indigo', 'purple' => 'bg-indigo-100 text-indigo-600',
                                        default => 'bg-gray-100 text-gray-500',
                                    };
                                @endphp
                                <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $iconStyles }}">
                                    @if(($activity['icon'] ?? 'plus') === 'check')
                                        {{-- Approved --}}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    @elseif(($activity['icon'] ?? 'plus') === 'x')
                                        {{-- Rejected --}}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    @elseif(($activity['icon'] ?? 'plus') === 'clock')
                                        {{-- Pending/Waiting --}}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @elseif(($activity['icon'] ?? 'plus') === 'edit')
                                        {{-- Draft/Edit --}}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    @else
                                        {{-- Created/New (plus icon) --}}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    @endif
                                </div>
                            </div>
                            {{-- Activity Content --}}
                            <div class="flex-1 min-w-0 pt-0.5">
                                <p class="text-sm text-gray-700 leading-relaxed">{!! $activity['message'] !!}</p>
                                <time class="text-xs text-gray-400 mt-1 block">{{ \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() }}</time>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="px-5 py-12 text-center">
                    <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-600">No recent activity</p>
                    <p class="text-xs text-gray-400 mt-1">Your activities will appear here when you create or update requests</p>
                </div>
            @endif
        </div>
    </div>
    </div>{{-- End dashboard-content --}}
    @endif

    {{-- Lazy Load Chart.js only when dashboard is loaded --}}
    @push('scripts')
    @once
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
    @endonce
    <script>
        // Store chart data globally for re-initialization
        window.dashboardChartData = @json($chartData ?? ['daily' => [], 'status' => [], 'buDistribution' => []]);
        
        document.addEventListener('livewire:init', () => {
            console.log('📊 Initial chartData:', window.dashboardChartData);
            // Small delay to ensure DOM is ready after Livewire render
            setTimeout(() => initializeCharts(window.dashboardChartData), 200);
        });

        // Re-initialize charts after Livewire updates the DOM
        Livewire.hook('morph.updated', ({ el, component }) => {
            // Check if BU distribution section now exists in DOM
            const buSection = document.getElementById('buDistributionSection');
            const buCanvas = document.getElementById('buDistributionChart');
            if (buSection && buCanvas && !window.buDistributionChart && window.dashboardChartData?.buDistribution?.length > 1) {
                console.log('🔄 morph.updated: BU section found, initializing chart');
                setTimeout(() => initializeBuDistributionChart(window.dashboardChartData), 100);
            }
        });

        Livewire.on('chartDataUpdated', (event) => {
            console.log('📊 chartDataUpdated event received:', event);
            // Update global chart data
            window.dashboardChartData = event.chartData;
            // Delay to ensure DOM is updated
            setTimeout(() => initializeCharts(event.chartData), 150);
        });

        function initializeCharts(chartData) {
            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                setTimeout(() => initializeCharts(chartData), 100);
                return;
            }
            
            // Always try to initialize BU Distribution chart first (independent of daily data)
            initializeBuDistributionChart(chartData);
            
            const canvas = document.getElementById('dailyTrendChart');
            const emptyState = document.getElementById('chartEmptyState');
            
            // Handle empty daily data - show empty state for trend chart only
            if (!chartData || !chartData.daily || chartData.daily.length === 0) {
                if (canvas) canvas.style.display = 'none';
                if (emptyState) emptyState.style.display = 'flex';
                return;
            }
            
            // Canvas not found - might still be loading
            if (!canvas) {
                setTimeout(() => initializeCharts(chartData), 100);
                return;
            }
            
            try {
                // Hide empty state, show canvas
                if (emptyState) emptyState.style.display = 'none';
                canvas.style.display = 'block';
                
                // Destroy existing chart if it exists
                if (window.dailyChart) {
                    window.dailyChart.destroy();
                }

                const dailyLabels = chartData.daily.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                });
                const dailyCounts = chartData.daily.map(item => item.count);
                
                const dailyCtx = canvas.getContext('2d');
                window.dailyChart = new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: dailyLabels,
                        datasets: [{
                            label: 'Purchase Requests',
                            data: dailyCounts,
                            borderColor: 'rgb(99, 102, 241)',
                            backgroundColor: 'rgba(99, 102, 241, 0.08)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 2.5,
                            pointRadius: 4,
                            pointBackgroundColor: 'rgb(99, 102, 241)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: 'rgb(99, 102, 241)',
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(17, 24, 39, 0.95)',
                                titleColor: '#fff',
                                bodyColor: '#e5e7eb',
                                borderColor: 'rgba(99, 102, 241, 0.3)',
                                borderWidth: 1,
                                cornerRadius: 8,
                                padding: 12,
                                displayColors: false,
                                callbacks: {
                                    title: function(context) {
                                        return context[0].label;
                                    },
                                    label: function(context) {
                                        return context.parsed.y + ' Purchase Request' + (context.parsed.y !== 1 ? 's' : '');
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                border: { display: false },
                                grid: { display: false },
                                ticks: {
                                    color: '#9ca3af',
                                    font: { size: 11, weight: '500' },
                                    maxRotation: 0,
                                    padding: 8
                                }
                            },
                            y: {
                                border: { display: false },
                                grid: { 
                                    display: false
                                },
                                ticks: {
                                    color: '#9ca3af',
                                    font: { size: 11, weight: '500' },
                                    stepSize: 1,
                                    padding: 8
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });
            } catch (error) {
                // Silent error handling for production
            }
        }
        
        function initializeBuDistributionChart(chartData) {
            console.log('🎯 initializeBuDistributionChart called', chartData);
            
            // Skip if no distribution data or only 1 BU
            if (!chartData?.buDistribution || chartData.buDistribution.length < 2) {
                console.log('❌ No distribution data or less than 2 BUs');
                return;
            }
            
            // Try to find canvas, retry if not found (DOM might not be ready)
            const canvas = document.getElementById('buDistributionChart');
            console.log('📊 Canvas element:', canvas);
            console.log('📊 BU Distribution data:', chartData?.buDistribution);
            
            if (!canvas) {
                console.log('⏳ Canvas not found, retrying in 300ms...');
                setTimeout(() => initializeBuDistributionChart(chartData), 300);
                return;
            }
            
            console.log('✅ Proceeding to create chart with', chartData.buDistribution.length, 'BUs');
            
            try {
                // Destroy existing chart if it exists and is a valid Chart instance
                if (window.buDistributionChart && typeof window.buDistributionChart.destroy === 'function') {
                    window.buDistributionChart.destroy();
                }
                window.buDistributionChart = null;
                
                const labels = chartData.buDistribution.map(item => item.bu_code);
                const amounts = chartData.buDistribution.map(item => item.amount);
                const colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899'];
                
                const ctx = canvas.getContext('2d');
                window.buDistributionChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: amounts,
                            backgroundColor: colors.slice(0, labels.length),
                            borderColor: '#fff',
                            borderWidth: 3,
                            hoverBorderWidth: 4,
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    font: { size: 12, weight: '500' }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(17, 24, 39, 0.95)',
                                titleColor: '#fff',
                                bodyColor: '#e5e7eb',
                                borderColor: 'rgba(99, 102, 241, 0.3)',
                                borderWidth: 1,
                                cornerRadius: 8,
                                padding: 12,
                                callbacks: {
                                    label: function(context) {
                                        const item = chartData.buDistribution[context.dataIndex];
                                        const formatted = new Intl.NumberFormat('id-ID').format(item.amount);
                                        return [
                                            item.bu_name,
                                            'Rp ' + formatted,
                                            item.percentage + '% · ' + item.pr_count + ' PRs'
                                        ];
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('✅ BU Distribution chart created successfully');
            } catch (error) {
                console.error('❌ Error creating BU Distribution chart:', error);
            }
        }
    </script>
    @endpush
</div>