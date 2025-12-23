<div wire:init="loadData">
    {{-- Loading Overlay --}}
    @if(!$readyToLoad)
        <div class="flex items-center justify-center min-h-[400px]">
            <div class="text-center">
                <svg class="animate-spin h-10 w-10 text-indigo-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-gray-500 text-sm">Loading task history...</p>
            </div>
        </div>
    @else
    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white p-6 rounded-xl border border-gray-100">
            <p class="text-sm text-gray-500">Total Completed</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $statistics['total_completed'] }}</p>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-100">
            <p class="text-sm text-gray-500">Avg Follow-up Time</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">
                @if(($statistics['avg_followup_time'] ?? 0) >= 60)
                    {{ round($statistics['avg_followup_time'] / 60, 1) }} hrs
                @elseif(($statistics['avg_followup_time'] ?? 0) >= 1)
                    {{ round($statistics['avg_followup_time']) }} min
                @else
                    {{ max(1, round(($statistics['avg_followup_time'] ?? 0) * 60)) }} sec
                @endif
            </p>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-100">
            <p class="text-sm text-gray-500">Avg Completion Time</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">
                @if(($statistics['avg_completion_time'] ?? 0) >= 60)
                    {{ round($statistics['avg_completion_time'] / 60, 1) }} hrs
                @elseif(($statistics['avg_completion_time'] ?? 0) >= 1)
                    {{ round($statistics['avg_completion_time']) }} min
                @else
                    {{ max(1, round(($statistics['avg_completion_time'] ?? 0) * 60)) }} sec
                @endif
            </p>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-100">
            <p class="text-sm text-gray-500">Total Savings</p>
            <p class="text-2xl font-bold text-emerald-600 mt-2">
                Rp {{ number_format($statistics['total_savings'] ?? 0, 0, ',', '.') }}
            </p>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-100">
            <p class="text-sm text-gray-500">Avg Savings %</p>
            <p class="text-2xl font-bold text-emerald-600 mt-2">
                {{ ($statistics['avg_savings_percentage'] ?? 0) ? number_format($statistics['avg_savings_percentage'], 1) : '0' }}%
            </p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        {{-- Header --}}
        <div class="px-5 py-4 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Task History</h3>
                    <p class="text-sm text-gray-500 mt-1">All completed tasks by purchasing admins</p>
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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
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

                {{-- Admin Filter --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Admin</label>
                    <select wire:model.live="adminFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="all">All Admins</option>
                        @foreach($adminList as $admin)
                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                        @endforeach
                    </select>
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
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Unit</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entered</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Follow-up</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estimated</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Realized</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Savings</th>
                        <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($tasks as $task)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-4 whitespace-nowrap text-sm font-medium text-gray-900 font-mono">
                                {{ $task->taskable->pr_number ?? $task->taskable->st_number ?? 'N/A' }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $task->businessUnit->code ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $task->assignedAdmin->name ?? 'N/A' }}
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
                                @if($task->followup_time_minutes !== null)
                                    @if($task->followup_time_minutes >= 60)
                                        {{ round($task->followup_time_minutes / 60, 1) }} hrs
                                    @elseif($task->followup_time_minutes >= 1)
                                        {{ round($task->followup_time_minutes) }} min
                                    @else
                                        {{ max(1, round($task->followup_time_minutes * 60)) }} sec
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($task->completion_time_minutes !== null)
                                    @if($task->completion_time_minutes >= 60)
                                        {{ round($task->completion_time_minutes / 60, 1) }} hrs
                                    @elseif($task->completion_time_minutes >= 1)
                                        {{ round($task->completion_time_minutes) }} min
                                    @else
                                        {{ max(1, round($task->completion_time_minutes * 60)) }} sec
                                    @endif
                                @else
                                    -
                                @endif
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
                            <td class="px-5 py-4 whitespace-nowrap text-sm text-center">
                                @if($task->status === 'done')
                                    <button 
                                        wire:click="openDetailModal({{ $task->id }})"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center px-3 py-1.5 text-sm text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors font-medium">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Detail
                                    </button>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-5 py-8 text-center text-sm text-gray-500">
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
    @endif

    {{-- Item Realization Detail Modal --}}
    @if($showDetailModal)
        <div 
            class="fixed inset-0 z-50 overflow-y-auto" 
            aria-labelledby="detail-modal-title" 
            role="dialog" 
            aria-modal="true"
        >
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDetailModal"></div>

                <div class="relative bg-white rounded-lg text-left shadow-xl transform transition-all w-full max-w-6xl max-h-[90vh] flex flex-col">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg leading-6 font-semibold text-gray-900" id="detail-modal-title">
                                Item Realization Details
                            </h3>
                            <button type="button" wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-4">
                        @if(count($detailItems) > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item Name</th>
                                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Est. Total</th>
                                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Real. Total</th>
                                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Savings</th>
                                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        @php $grandSavings = 0; @endphp
                                        @foreach($detailItems as $item)
                                            @php $grandSavings += $item['savings_amount']; @endphp
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 py-3 text-sm text-gray-900">{{ $item['item_name'] }}</td>
                                                <td class="px-3 py-3 text-sm text-gray-900 text-center">{{ number_format($item['quantity'], 0) }} {{ $item['unit'] }}</td>
                                                <td class="px-3 py-3 text-sm text-gray-900 text-right">Rp {{ number_format($item['estimated_total_price'], 0, ',', '.') }}</td>
                                                <td class="px-3 py-3 text-sm text-gray-900 text-right">Rp {{ number_format($item['realized_total_price'], 0, ',', '.') }}</td>
                                                <td class="px-3 py-3 text-sm text-right">
                                                    <span class="{{ $item['savings_amount'] >= 0 ? 'text-emerald-600' : 'text-red-600' }} font-medium">
                                                        Rp {{ number_format($item['savings_amount'], 0, ',', '.') }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-3 text-sm text-gray-900">{{ $item['realized_supplier'] ?? $item['original_supplier'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr class="font-semibold">
                                            <td colspan="4" class="px-3 py-3 text-sm text-gray-900 text-right">Total Savings:</td>
                                            <td class="px-3 py-3 text-sm text-right {{ $grandSavings >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                                Rp {{ number_format($grandSavings, 0, ',', '.') }}
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <p class="text-sm text-gray-500">No item details available for this task.</p>
                            </div>
                        @endif
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex justify-end">
                            <button type="button" wire:click="closeDetailModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
