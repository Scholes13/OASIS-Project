<div>
    {{-- Header --}}
    <div class="px-4 py-5 sm:px-6 lg:px-8 bg-white border-b border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Performance Metrics</h1>
                <p class="mt-1 text-sm text-gray-500">Track your procurement efficiency and savings achievements</p>
            </div>
        </div>
    </div>

    {{-- Performance Metrics Cards --}}
    <div class="px-4 py-5 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-6">
            {{-- Total Tasks Completed --}}
            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500">Total Tasks</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ number_format($this->totalTasksCompleted) }}
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

            {{-- Average Follow-up Time --}}
            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500">Avg Follow-up</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ $this->formatTime($this->averageFollowupTime) }}
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

            {{-- Average Completion Time --}}
            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500">Avg Completion</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ $this->formatTime($this->averageCompletionTime) }}
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

            {{-- Total Savings --}}
            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500">Total Savings</p>
                        <p class="mt-2 text-2xl font-bold text-emerald-600">
                            {{ $this->formatCurrency($this->totalSavings) }}
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

            {{-- Average Savings Percentage --}}
            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500">Avg Savings %</p>
                        <p class="mt-2 text-3xl font-bold text-emerald-600">
                            {{ number_format($this->averageSavingsPercentage, 1) }}%
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
    </div>

    {{-- Charts Section --}}
    <div class="px-4 py-5 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Savings Trend Chart --}}
            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-gray-900">Savings Trend</h3>
                    <p class="text-sm text-gray-500 mt-1">Average savings percentage over time</p>
                </div>
                
                @if(count($this->savingsTrendData['labels']) > 0)
                    <div class="h-56">
                        <canvas id="savingsTrendChart"></canvas>
                    </div>
                @else
                    <div class="h-56 flex items-center justify-center text-gray-400">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <p class="mt-2 text-sm">No data available yet</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Department Breakdown --}}
            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-gray-900">Department Breakdown</h3>
                    <p class="text-sm text-gray-500 mt-1">Tasks completed by department</p>
                </div>
                
                @if($this->departmentBreakdown->count() > 0)
                    <div class="grid grid-cols-2 gap-6">
                        {{-- Chart on the left --}}
                        <div class="flex items-center justify-center">
                            <div class="w-56 h-56">
                                <canvas id="departmentChart"></canvas>
                            </div>
                        </div>
                        
                        {{-- List on the right --}}
                        <div class="space-y-3 overflow-y-auto max-h-56">
                            @foreach($this->departmentBreakdown as $index => $dept)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center flex-1 min-w-0">
                                        <div class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ ['rgb(99, 102, 241)', 'rgb(16, 185, 129)', 'rgb(251, 146, 60)', 'rgb(168, 85, 247)', 'rgb(236, 72, 153)', 'rgb(59, 130, 246)', 'rgb(245, 158, 11)', 'rgb(239, 68, 68)', 'rgb(20, 184, 166)', 'rgb(217, 70, 239)'][$index % 10] }}"></div>
                                        <span class="ml-3 text-sm text-gray-700 truncate">{{ $dept['department'] }}</span>
                                    </div>
                                    <div class="ml-4 flex items-center gap-2 flex-shrink-0">
                                        <span class="text-sm font-semibold text-gray-900">{{ $dept['count'] }}</span>
                                        <span class="text-xs text-gray-500">({{ $dept['percentage'] }}%)</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="h-56 flex items-center justify-center text-gray-400">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <p class="mt-2 text-sm">No data available yet</p>
                        </div>
                    </div>
                @endif
            </div>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Savings Trend Chart
        const savingsTrendCanvas = document.getElementById('savingsTrendChart');
        if (savingsTrendCanvas) {
            const ctx = savingsTrendCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @js($this->savingsTrendData['labels']),
                    datasets: [{
                        label: 'Avg Savings %',
                        data: @js($this->savingsTrendData['data']),
                        borderColor: 'rgb(99, 102, 241)',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
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
                            },
                            grid: {
                                display: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Department Breakdown Pie Chart
        const departmentCanvas = document.getElementById('departmentChart');
        if (departmentCanvas) {
            const deptData = @js($this->departmentBreakdown->toArray());
            const labels = deptData.map(d => d.department);
            const data = deptData.map(d => d.count);
            const total = data.reduce((a, b) => a + b, 0);
            
            const colors = [
                'rgb(99, 102, 241)',   // Indigo
                'rgb(16, 185, 129)',   // Emerald
                'rgb(251, 146, 60)',   // Orange
                'rgb(168, 85, 247)',   // Purple
                'rgb(236, 72, 153)',   // Pink
                'rgb(59, 130, 246)',   // Blue
                'rgb(245, 158, 11)',   // Amber
                'rgb(239, 68, 68)',    // Red
                'rgb(20, 184, 166)',   // Teal
                'rgb(217, 70, 239)',   // Fuchsia
            ];

            const ctx = departmentCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed;
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return context.label + ': ' + value + ' tasks (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        }
    });
</script>
@endpush
