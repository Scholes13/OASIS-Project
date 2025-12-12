<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Performance Metrics</h1>
            <p class="mt-1 text-sm text-gray-500">Track your procurement efficiency and savings achievements</p>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-6">
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

            <!-- Average Savings Percentage -->
            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500">Avg Savings %</p>
                        <p class="mt-2 text-3xl font-bold text-emerald-600">
                            @if($this->readyToLoad)
                                {{ number_format($this->averageSavingsPercentage, 1) }}%
                            @else
                                <span class="inline-block w-16 h-8 bg-gray-200 animate-pulse rounded"></span>
                            @endif
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Savings Trend Chart -->
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Savings Trend</h3>
                    <p class="mt-1 text-sm text-gray-500">Average savings percentage over time</p>
                </div>
                <div class="p-6">
                    @if($this->readyToLoad)
                        @if(count($this->savingsTrendData['labels']) > 0)
                            <div class="relative" style="height: 20rem;">
                                <canvas id="savingsTrendChart"></canvas>
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

            <!-- Tasks Per Month Chart -->
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-900">Tasks Completed</h3>
                    <p class="mt-1 text-sm text-gray-500">Number of tasks completed per month</p>
                </div>
                <div class="p-6">
                    @if($this->readyToLoad)
                        @if(count($this->tasksPerMonthData['labels']) > 0)
                            <div class="relative" style="height: 20rem;">
                                <canvas id="tasksPerMonthChart"></canvas>
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
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('livewire:initialized', () => {
        let savingsTrendChart = null;
        let tasksPerMonthChart = null;

        function initCharts() {
            // Destroy existing charts if they exist
            if (savingsTrendChart) {
                savingsTrendChart.destroy();
            }
            if (tasksPerMonthChart) {
                tasksPerMonthChart.destroy();
            }

            // Savings Trend Chart
            const savingsTrendCanvas = document.getElementById('savingsTrendChart');
            if (savingsTrendCanvas) {
                const savingsTrendData = @json($this->savingsTrendData);
                
                if (savingsTrendData.labels.length > 0) {
                    const ctx = savingsTrendCanvas.getContext('2d');
                    savingsTrendChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: savingsTrendData.labels,
                            datasets: [{
                                label: 'Savings %',
                                data: savingsTrendData.data,
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
                                            return 'Savings: ' + context.parsed.y.toFixed(1) + '%';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return value + '%';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // Tasks Per Month Chart
            const tasksPerMonthCanvas = document.getElementById('tasksPerMonthChart');
            if (tasksPerMonthCanvas) {
                const tasksPerMonthData = @json($this->tasksPerMonthData);
                
                if (tasksPerMonthData.labels.length > 0) {
                    const ctx = tasksPerMonthCanvas.getContext('2d');
                    tasksPerMonthChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: tasksPerMonthData.labels,
                            datasets: [{
                                label: 'Tasks',
                                data: tasksPerMonthData.data,
                                backgroundColor: 'rgba(99, 102, 241, 0.8)',
                                borderColor: 'rgb(99, 102, 241)',
                                borderWidth: 1,
                                borderRadius: 6,
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
                                            return 'Tasks: ' + context.parsed.y;
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
