<div>
    <!-- Business Units Monitor Info -->
    @if(count($businessUnits) > 1)
        <div class="mb-6 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl shadow-sm border border-indigo-200 p-4 lg:p-6">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Switch Business Unit View</h3>
                    <p class="text-sm text-gray-600 mb-3">
                        You have access to <strong>{{ count($businessUnits) }} business units</strong>. 
                        Click to switch view:
                    </p>
                    <div class="flex flex-wrap gap-2 mb-2">
                        @foreach($businessUnits as $bu)
                            @php
                                $isActive = $bu['id'] === $activeBusinessUnitId;
                                $isParent = $bu['parent_id'] === null;
                            @endphp
                            <button 
                                type="button"
                                wire:click="switchBusinessUnit({{ $bu['id'] }})"
                                wire:loading.attr="disabled"
                                wire:target="switchBusinessUnit"
                                class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200 transform hover:scale-105 
                                    {{ $isActive 
                                        ? ($isParent 
                                            ? 'bg-gradient-to-r from-indigo-600 to-indigo-700 text-white shadow-lg ring-4 ring-indigo-200' 
                                            : 'bg-gradient-to-r from-purple-600 to-purple-700 text-white shadow-lg ring-4 ring-purple-200')
                                        : 'bg-white text-gray-700 border-2 border-gray-300 hover:border-indigo-400 hover:shadow-md' }}">
                                @if($isParent)
                                    <svg class="w-4 h-4 mr-1.5 {{ $isActive ? 'text-white' : 'text-indigo-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                    </svg>
                                @endif
                                <span class="font-bold">{{ $bu['code'] }}</span>
                                <span class="mx-1.5">-</span>
                                <span>{{ $bu['name'] }}</span>
                                @if($isActive)
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 italic flex items-center">
                        <svg class="w-3.5 h-3.5 inline mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Currently viewing: <strong class="ml-1 text-indigo-700">{{ collect($businessUnits)->firstWhere('id', $activeBusinessUnitId)['name'] ?? 'N/A' }}</strong>
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Date Filter Section -->
    <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6">
        <div class="flex flex-col lg:flex-row lg:items-end gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range Filter</label>
                <select wire:model.live="dateFilter" 
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="today">Today</option>
                    <option value="this_week">This Week</option>
                    <option value="this_month">This Month</option>
                    <option value="last_30_days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>

            @if($customRange)
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" 
                           wire:model="startDate" 
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" 
                           wire:model="endDate" 
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <button wire:click="applyCustomDateRange" 
                            type="button"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 font-medium">
                        Apply Filter
                    </button>
                </div>
            @endif

            <div class="text-sm text-gray-600">
                <strong>Period:</strong> {{ date('M j, Y', strtotime($startDate)) }} - {{ date('M j, Y', strtotime($endDate)) }}
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:gap-6 mb-6">
        <!-- Loading Overlay -->
        <div wire:loading.flex wire:target="switchBusinessUnit,updatedDateFilter,applyCustomDateRange" 
             class="col-span-full fixed inset-0 bg-gray-900 bg-opacity-50 z-50 items-center justify-center">
            <div class="bg-white rounded-xl shadow-2xl p-6 flex items-center space-x-4">
                <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-lg font-semibold text-gray-700">Switching business unit...</span>
            </div>
        </div>
        
        <!-- Active PRs Card -->
        <div class="group bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-lg hover:border-blue-300 transition-all duration-200 transform hover:scale-[1.02]">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl flex items-center justify-center group-hover:from-blue-200 group-hover:to-blue-300 transition-all duration-200">
                        <svg class="w-5 h-5 lg:w-6 lg:h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 flex-1 min-w-0">
                    <h3 class="text-sm font-medium text-gray-900 truncate">Active Purchase Requests</h3>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900" wire:loading.class="opacity-50">
                        {{ $stats['active_prs'] ?? 0 }}
                    </p>
                    <p class="text-xs text-gray-500">{{ $stats['draft_prs'] ?? 0 }} drafts</p>
                </div>
            </div>
        </div>

        <!-- Pending Approvals Card -->
        <div class="group bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-lg hover:border-orange-300 transition-all duration-200 transform hover:scale-[1.02]">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-orange-100 to-orange-200 rounded-xl flex items-center justify-center group-hover:from-orange-200 group-hover:to-orange-300 transition-all duration-200">
                        <svg class="w-5 h-5 lg:w-6 lg:h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 flex-1 min-w-0">
                    <h3 class="text-sm font-medium text-gray-900 truncate">Pending Approvals</h3>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900" wire:loading.class="opacity-50">
                        {{ $stats['pending_approvals'] ?? 0 }}
                    </p>
                    <p class="text-xs text-gray-500">
                        @if(($stats['overdue_approvals'] ?? 0) > 0)
                            <span class="text-red-600 font-semibold">{{ $stats['overdue_approvals'] }} overdue</span>
                        @else
                            Awaiting your review
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Period PRs Card -->
        <div class="group bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-lg hover:border-green-300 transition-all duration-200 transform hover:scale-[1.02]">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-green-100 to-green-200 rounded-xl flex items-center justify-center group-hover:from-green-200 group-hover:to-green-300 transition-all duration-200">
                        <svg class="w-5 h-5 lg:w-6 lg:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 flex-1 min-w-0">
                    <h3 class="text-sm font-medium text-gray-900 truncate">Selected Period</h3>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900" wire:loading.class="opacity-50">
                        {{ $stats['period_prs'] ?? 0 }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ $stats['approved_prs'] ?? 0 }} approved, {{ $stats['rejected_prs'] ?? 0 }} rejected
                    </p>
                </div>
            </div>
        </div>

        <!-- Total Amount Card -->
        <div class="group bg-white rounded-xl shadow-sm border border-gray-200 p-4 lg:p-6 hover:shadow-lg hover:border-purple-300 transition-all duration-200 transform hover:scale-[1.02]">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-purple-100 to-purple-200 rounded-xl flex items-center justify-center group-hover:from-purple-200 group-hover:to-purple-300 transition-all duration-200">
                        <svg class="w-5 h-5 lg:w-6 lg:h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4 flex-1 min-w-0">
                    <h3 class="text-sm font-medium text-gray-900 truncate">Total Amount</h3>
                    <p class="text-2xl lg:text-3xl font-bold text-gray-900" wire:loading.class="opacity-50">
                        Rp {{ number_format($stats['total_amount'] ?? 0, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-500">Selected period</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2 mb-6">
        <!-- Daily Trend Chart -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
            <div class="px-4 py-4 lg:px-6 lg:py-5 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Daily PR Trend</h3>
                <p class="text-sm text-gray-500 mt-1">Purchase requests created per day</p>
            </div>
            <div class="p-4 lg:p-6">
                <canvas id="dailyTrendChart" height="250"></canvas>
            </div>
        </div>

        <!-- Status Distribution Chart -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
            <div class="px-4 py-4 lg:px-6 lg:py-5 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Status Distribution</h3>
                <p class="text-sm text-gray-500 mt-1">PRs grouped by status</p>
            </div>
            <div class="p-4 lg:p-6">
                <canvas id="statusChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity and Quick Actions -->
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
            <div class="px-4 py-4 lg:px-6 lg:py-5 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
            </div>
            <div class="p-4 lg:p-6">
                <div class="flow-root">
                    @if(count($recentActivities) > 0)
                        <ul class="-mb-8">
                            @foreach($recentActivities as $index => $activity)
                                <li>
                                    <div class="relative {{ $index < count($recentActivities) - 1 ? 'pb-8' : '' }}">
                                        @if($index < count($recentActivities) - 1)
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-{{ $activity['color'] }}-500 flex items-center justify-center ring-8 ring-white">
                                                    @if($activity['icon'] === 'check')
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    @elseif($activity['icon'] === 'x')
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    @elseif($activity['icon'] === 'clock')
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    @else
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                        </svg>
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm text-gray-500">{!! $activity['message'] !!}</p>
                                                </div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                    <time>{{ \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() }}</time>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No recent activity</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
            <div class="px-4 py-4 lg:px-6 lg:py-5 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
            </div>
            <div class="p-4 lg:p-6">
                <div class="space-y-3">
                    <a href="{{ route('pr-numbers.index') }}" class="group block p-4 rounded-xl border border-gray-200 hover:border-indigo-300 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-blue-50 transition-all duration-200 transform hover:scale-[1.02]">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-gradient-to-br from-indigo-100 to-indigo-200 rounded-xl flex items-center justify-center group-hover:from-indigo-200 group-hover:to-indigo-300 transition-colors duration-200">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-medium text-gray-900 group-hover:text-indigo-600 transition-colors duration-200">Request PR Number</h4>
                                <p class="text-xs text-gray-500">Start by requesting a PR number first</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('purchase-requests.index') }}" class="group block p-4 rounded-xl border border-gray-200 hover:border-blue-300 hover:bg-gradient-to-r hover:from-blue-50 hover:to-cyan-50 transition-all duration-200 transform hover:scale-[1.02]">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl flex items-center justify-center group-hover:from-blue-200 group-hover:to-blue-300 transition-colors duration-200">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-medium text-gray-900 group-hover:text-blue-600 transition-colors duration-200">View All PRs</h4>
                                <p class="text-xs text-gray-500">Browse your purchase requests</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('approvals.index') }}" class="group block p-4 rounded-xl border border-gray-200 hover:border-green-300 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 transition-all duration-200 transform hover:scale-[1.02]">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-100 to-green-200 rounded-xl flex items-center justify-center group-hover:from-green-200 group-hover:to-green-300 transition-colors duration-200">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-medium text-gray-900 group-hover:text-green-600 transition-colors duration-200">Review Approvals</h4>
                                <p class="text-xs text-gray-500">Check pending approvals</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('livewire:init', () => {
            initializeCharts(@json($chartData));
        });

        Livewire.on('chartDataUpdated', (event) => {
            initializeCharts(event.chartData);
        });

        function initializeCharts(chartData) {
            console.log('=== CHART INITIALIZATION DEBUG ===');
            console.log('Raw chartData:', chartData);
            console.log('chartData type:', typeof chartData);
            console.log('chartData.daily:', chartData?.daily);
            console.log('chartData.status:', chartData?.status);
            console.log('chartData JSON:', JSON.stringify(chartData, null, 2));
            
            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded!');
                return;
            }
            
            // Validate chartData
            if (!chartData || !chartData.daily || !chartData.status) {
                console.error('Invalid chart data:', chartData);
                return;
            }
            
            try {
                // Daily Trend Chart
                const dailyCtx = document.getElementById('dailyTrendChart');
                if (dailyCtx) {
                    // Destroy existing chart if it exists
                    if (window.dailyChart) {
                        window.dailyChart.destroy();
                    }

                    const dailyLabels = chartData.daily.map(item => {
                        const date = new Date(item.date);
                        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    });
                    const dailyCounts = chartData.daily.map(item => item.count);
                    
                    console.log('Daily chart labels:', dailyLabels);
                    console.log('Daily chart counts:', dailyCounts);
                    
                    window.dailyChart = new Chart(dailyCtx, {
                    type: 'line',
                    data: {
                        labels: dailyLabels,
                        datasets: [{
                            label: 'Purchase Requests',
                            data: dailyCounts,
                            borderColor: 'rgb(79, 70, 229)',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y + ' PR' + (context.parsed.y !== 1 ? 's' : '');
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
                
                console.log('Daily chart created successfully');
            } else {
                console.error('Daily chart canvas not found');
            }

            // Status Distribution Chart
            const statusCtx = document.getElementById('statusChart');
            if (statusCtx) {
                // Destroy existing chart if it exists (check if it's a Chart instance)
                if (window.statusChart && typeof window.statusChart.destroy === 'function') {
                    window.statusChart.destroy();
                }

                // Check if status data is not empty
                if (!chartData.status || Object.keys(chartData.status).length === 0) {
                    console.warn('Status chart: No data available');
                    return; // Exit early if no data
                }
                
                const statusLabels = Object.keys(chartData.status).map(status => {
                    return status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
                });
                const statusCounts = Object.values(chartData.status);
                
                console.log('Status chart data check:');
                console.log('  - chartData.status:', chartData.status);
                console.log('  - statusLabels:', statusLabels);
                console.log('  - statusCounts:', statusCounts);
                
                const statusColors = {
                    'draft': 'rgb(156, 163, 175)',
                    'submitted': 'rgb(59, 130, 246)',
                    'in_approval': 'rgb(251, 191, 36)',
                    'approved': 'rgb(34, 197, 94)',
                    'rejected': 'rgb(239, 68, 68)',
                    'voided': 'rgb(107, 114, 128)'
                };

                const backgroundColors = Object.keys(chartData.status).map(status => {
                    return statusColors[status] || 'rgb(156, 163, 175)';
                });

                window.statusChart = new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: statusCounts,
                            backgroundColor: backgroundColors,
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
                
                console.log('Status chart created successfully');
            } else {
                console.error('Status chart canvas not found');
            }
            } catch (error) {
                console.error('Error initializing charts:', error);
            }
        }
    </script>
    @endpush
</div>

