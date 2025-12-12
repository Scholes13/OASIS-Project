<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Department Report</h1>
            @if($this->department)
                <p class="mt-1 text-sm text-gray-500">Performance metrics for {{ $this->department->name }}</p>
            @else
                <p class="mt-1 text-sm text-gray-500">Department performance overview</p>
            @endif
        </div>

        @if(!$this->departmentId)
            <!-- No Department Message -->
            <div class="bg-white rounded-xl border border-gray-100 p-12">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No Department Assigned</h3>
                    <p class="mt-1 text-sm text-gray-500">You need to be assigned to a department to view this report.</p>
                </div>
            </div>
        @else
            <!-- Summary Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Tasks Completed -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500">Total Tasks</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900">
                                @if($this->readyToLoad)
                                    {{ number_format($this->totalTasksCompleted) }}
                                @else
                                    <span class="inline-block w-16 h-8 bg-gray-200 animate-pulse rounded"></span>
                                @endif
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Savings -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500">Total Savings</p>
                            <p class="mt-2 text-2xl font-bold text-emerald-600">
                                @if($this->readyToLoad)
                                    {{ $this->formatCurrency($this->totalSavings) }}
                                @else
                                    <span class="inline-block w-24 h-8 bg-gray-200 animate-pulse rounded"></span>
                                @endif
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Follow-up Time -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500">Avg Follow-up</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900">
                                @if($this->readyToLoad)
                                    {{ $this->formatTime($this->averageFollowupTime) }}
                                @else
                                    <span class="inline-block w-16 h-8 bg-gray-200 animate-pulse rounded"></span>
                                @endif
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Completion Time -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500">Avg Completion</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900">
                                @if($this->readyToLoad)
                                    {{ $this->formatTime($this->averageCompletionTime) }}
                                @else
                                    <span class="inline-block w-16 h-8 bg-gray-200 animate-pulse rounded"></span>
                                @endif
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Savings Breakdown -->
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Savings Breakdown</h3>
                    <p class="mt-1 text-sm text-gray-500">Total savings by request type</p>
                </div>
                <div class="p-6">
                    @if($this->readyToLoad)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Purchase Request Savings -->
                            <div class="bg-indigo-50 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-indigo-900">Purchase Requests</p>
                                        <p class="mt-1 text-2xl font-bold text-indigo-600">
                                            {{ $this->formatCurrency($this->savingsBreakdown['purchase_request']) }}
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Stock Request Savings -->
                            <div class="bg-emerald-50 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-emerald-900">Stock Requests</p>
                                        <p class="mt-1 text-2xl font-bold text-emerald-600">
                                            {{ $this->formatCurrency($this->savingsBreakdown['stock_request']) }}
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="h-24 bg-gray-200 animate-pulse rounded-lg"></div>
                            <div class="h-24 bg-gray-200 animate-pulse rounded-lg"></div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Admin Performance Comparison -->
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Admin Performance Comparison</h3>
                    <p class="mt-1 text-sm text-gray-500">Individual performance metrics for purchasing admins</p>
                </div>
                <div class="overflow-x-auto">
                    @if($this->readyToLoad)
                        @if($this->adminPerformance->count() > 0)
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tasks</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Savings</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Savings %</th>
                                        <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Follow-up</th>
                                        <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Completion</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    @foreach($this->adminPerformance as $admin)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                                        <span class="text-sm font-medium text-indigo-600">{{ substr($admin['name'], 0, 1) }}</span>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-sm font-medium text-gray-900">{{ $admin['name'] }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-900">{{ number_format($admin['tasks_completed']) }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm font-medium text-emerald-600">{{ $this->formatCurrency($admin['total_savings']) }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                                    {{ number_format($admin['avg_savings_percentage'], 1) }}%
                                                </span>
                                            </td>
                                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $this->formatTime($admin['avg_followup_time']) }}
                                            </td>
                                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $this->formatTime($admin['avg_completion_time']) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="p-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">No purchasing admins found</p>
                                <p class="text-xs text-gray-400">Assign purchasing admins to see performance data</p>
                            </div>
                        @endif
                    @else
                        <div class="p-12">
                            <div class="animate-pulse space-y-4">
                                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                                <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Department Trend Chart -->
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Department Savings Trend</h3>
                    <p class="mt-1 text-sm text-gray-500">Total savings over time</p>
                </div>
                <div class="p-6">
                    @if($this->readyToLoad)
                        @if(count($this->departmentTrendData['labels']) > 0)
                            <div class="relative" style="height: 20rem;">
                                <canvas id="departmentTrendChart"></canvas>
                            </div>
                        @else
                            <div class="flex items-center justify-center" style="height: 20rem;">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No data available</p>
                                    <p class="text-xs text-gray-400">Complete tasks to see trends</p>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="flex items-center justify-center" style="height: 20rem;">
                            <div class="animate-pulse">
                                <div class="h-48 w-full bg-gray-200 rounded"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('livewire:initialized', () => {
        let departmentTrendChart = null;

        function initCharts() {
            // Destroy existing chart if it exists
            if (departmentTrendChart) {
                departmentTrendChart.destroy();
            }

            // Department Trend Chart
            const departmentTrendCanvas = document.getElementById('departmentTrendChart');
            if (departmentTrendCanvas) {
                const departmentTrendData = @json($this->departmentTrendData);
                
                if (departmentTrendData.labels.length > 0) {
                    const ctx = departmentTrendCanvas.getContext('2d');
                    departmentTrendChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: departmentTrendData.labels,
                            datasets: [{
                                label: 'Total Savings (Rp)',
                                data: departmentTrendData.data,
                                borderColor: 'rgb(16, 185, 129)',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.4,
                                fill: true,
                                pointRadius: 4,
                                pointHoverRadius: 6,
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
                                            return 'Savings: Rp ' + context.parsed.y.toLocaleString('id-ID');
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return 'Rp ' + value.toLocaleString('id-ID');
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }

        // Initialize charts on load
        initCharts();

        // Reinitialize charts when data changes
        Livewire.on('business-unit-switched', () => {
            setTimeout(initCharts, 100);
        });

        Livewire.on('task-completed', () => {
            setTimeout(initCharts, 100);
        });

        Livewire.on('refresh-metrics', () => {
            setTimeout(initCharts, 100);
        });
    });
</script>
@endpush
