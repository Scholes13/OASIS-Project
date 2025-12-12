<div>
    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white p-6 rounded-xl border border-gray-100">
            <p class="text-sm text-gray-500">Total Completed</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $statistics['total_completed'] }}</p>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-100">
            <p class="text-sm text-gray-500">Avg Follow-up Time</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">
                {{ $statistics['avg_followup_time'] ? round($statistics['avg_followup_time'] / 60, 1) : '0' }} hrs
            </p>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-100">
            <p class="text-sm text-gray-500">Avg Completion Time</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">
                {{ $statistics['avg_completion_time'] ? round($statistics['avg_completion_time'] / 60, 1) : '0' }} hrs
            </p>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-100">
            <p class="text-sm text-gray-500">Total Savings</p>
            <p class="text-2xl font-bold text-emerald-600 mt-2">
                Rp {{ number_format($statistics['total_savings'], 0, ',', '.') }}
            </p>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-100">
            <p class="text-sm text-gray-500">Avg Savings %</p>
            <p class="text-2xl font-bold text-emerald-600 mt-2">
                {{ $statistics['avg_savings_percentage'] ? number_format($statistics['avg_savings_percentage'], 1) : '0' }}%
            </p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        {{-- Header --}}
        <div class="px-5 py-4 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">My Task History</h3>
                    <p class="text-sm text-gray-500 mt-1">All tasks you have handled</p>
                </div>
                <button wire:click="resetFilters" class="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Reset Filters
                </button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="px-5 py-4 bg-gray-50 border-b border-gray-100">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Date From --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" wire:model.live="dateFrom" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                {{-- Date To --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" wire:model.live="dateTo" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                {{-- Status Filter --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                    <select wire:model.live="statusFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="all">All Statuses</option>
                        <option value="pending_followup">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="done">Completed</option>
                    </select>
                </div>

                {{-- Type Filter --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                    <select wire:model.live="typeFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="all">All Types</option>
                        <option value="App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest">Purchase Request</option>
                        <option value="App\Models\Modules\Purchasing\StockRequest\StockRequest">Stock Request</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task ID</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Unit</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entered</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Started</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Follow-up Time</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Time</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estimated Price</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Realized Price</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Savings</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($tasks as $task)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                #{{ $task->id }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if(str_contains($task->taskable_type, 'PurchaseRequest'))
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">PR</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">ST</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $task->businessUnit->name ?? 'N/A' }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm">
                                @if($task->status === 'pending_followup')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Pending</span>
                                @elseif($task->status === 'in_progress')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">In Progress</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Completed</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $task->entered_at->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $task->started_at ? $task->started_at->format('Y-m-d H:i') : '-' }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $task->completed_at ? $task->completed_at->format('Y-m-d H:i') : '-' }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $task->followup_time_minutes ? round($task->followup_time_minutes / 60, 1) . ' hrs' : '-' }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $task->completion_time_minutes ? round($task->completion_time_minutes / 60, 1) . ' hrs' : '-' }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                Rp {{ number_format($task->estimated_total_price, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $task->realized_total_price ? 'Rp ' . number_format($task->realized_total_price, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm">
                                @if($task->savings_amount !== null)
                                    <div class="flex flex-col">
                                        <span class="font-medium {{ $task->savings_amount >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                            Rp {{ number_format($task->savings_amount, 0, ',', '.') }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            ({{ number_format($task->savings_percentage, 1) }}%)
                                        </span>
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-5 py-8 text-center text-sm text-gray-500">
                                No task history found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($tasks->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $tasks->links() }}
            </div>
        @endif
    </div>
</div>
