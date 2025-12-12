<div>
    <div wire:loading.flex wire:target="handleBusinessUnitSwitch,switchTab,previousPage,nextPage,gotoPage,searchTerm,statusFilter,dateFrom,dateTo" class="fixed inset-0 bg-white/80 backdrop-blur-sm z-50 items-center justify-center">
        <div class="flex flex-col items-center space-y-6">
            <div class="relative">
                <div class="w-16 h-16 border-4 border-indigo-200 rounded-full"></div>
                <div class="absolute top-0 left-0 w-16 h-16 border-4 border-indigo-500 rounded-full border-t-transparent animate-spin"></div>
            </div>
            <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-700 mb-1">Loading Data</h3>
                <p class="text-sm text-gray-400">Please wait...</p>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-100">
        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
            <button 
                wire:click="switchTab('all')"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none {{ $activeTab === 'all' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                All Requests
            </button>
            <button 
                wire:click="switchTab('purchase')"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none {{ $activeTab === 'purchase' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Purchase Requests
            </button>
            <button 
                wire:click="switchTab('stock')"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none {{ $activeTab === 'stock' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Stock Requests
            </button>
        </nav>
    </div>

    {{-- Search & Filters --}}
    <div class="px-6 py-4">
        <div class="flex items-center gap-3 flex-wrap">
            {{-- Search --}}
            <div class="relative flex-1 min-w-[12.5rem] max-w-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="searchTerm"
                    placeholder="Search number or purpose..."
                    class="block w-full pl-10 pr-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg focus:outline-none focus:border-gray-300 transition-colors placeholder-gray-400"
                >
            </div>

            {{-- Status Filter --}}
            <select 
                wire:model.live="statusFilter"
                class="px-3 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg focus:outline-none focus:border-gray-300 transition-colors">
                <option value="">All Status</option>
                <option value="draft">Draft</option>
                <option value="submitted">Submitted</option>
                <option value="in_approval">In Approval</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="voided">Voided</option>
            </select>

            {{-- Date From --}}
            <input 
                type="date" 
                wire:model.live="dateFrom"
                class="px-3 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg focus:outline-none focus:border-gray-300 transition-colors"
            >

            {{-- Date To --}}
            <input 
                type="date" 
                wire:model.live="dateTo"
                class="px-3 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg focus:outline-none focus:border-gray-300 transition-colors"
            >
        </div>
    </div>

    {{-- Results Table --}}
    @if(count($requests) > 0)
    <div class="bg-white overflow-hidden" wire:loading.class="opacity-50">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">DEPT</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">NUMBER</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">USED FOR</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">DATE</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">STATUS</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50">
                    @foreach($requests as $request)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600">{{ $request['department'] }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-mono font-medium text-gray-700">{{ $request['number'] }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $request['items_count'] ?? 0 }} items</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600 max-w-md">{{ Str::limit($request['purpose'], 60) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-600">{{ $request['created_at']->format('d M Y') }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $request['created_at']->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusLabels = ['draft'=>'Draft','submitted'=>'Submitted','in_approval'=>'In Approval','approved'=>'Approved','rejected'=>'Rejected','voided'=>'Voided'];
                                $statusLabel = $statusLabels[$request['status']] ?? 'Unknown';
                            @endphp
                            <span class="text-sm text-gray-600">{{ $statusLabel }}</span>
                            @if(isset($request['approval_progress']))
                                <div class="text-xs text-gray-400 mt-0.5">{{ $request['approval_progress'] }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ $request['show_route'] }}" wire:navigate class="text-indigo-500 hover:text-indigo-600 text-sm font-medium transition-colors">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-gray-100">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-400">
                    Showing {{ (($currentPage - 1) * 15) + 1 }} to {{ min($currentPage * 15, $total) }} of {{ $total }} results
                </p>
                @if($lastPage > 1)
                <nav class="flex items-center gap-1">
                    @if($currentPage <= 1)
                        <span class="px-3 py-2 text-sm text-gray-300 cursor-not-allowed select-none">← Previous</span>
                    @else
                        <button wire:click="previousPage" class="px-3 py-2 text-sm text-gray-400 hover:text-gray-600 transition-colors">← Previous</button>
                    @endif

                    <div class="flex items-center gap-1 mx-2">
                        @for($page = 1; $page <= $lastPage; $page++)
                            @if($page == $currentPage)
                                <span class="w-8 h-8 flex items-center justify-center text-sm font-medium text-indigo-600 bg-indigo-50 rounded-md">{{ $page }}</span>
                            @else
                                <button wire:click="gotoPage({{ $page }})" class="w-8 h-8 flex items-center justify-center text-sm text-gray-500 hover:bg-gray-100 rounded-md transition-colors">{{ $page }}</button>
                            @endif
                        @endfor
                    </div>

                    @if($currentPage >= $lastPage)
                        <span class="px-3 py-2 text-sm text-gray-300 cursor-not-allowed select-none">Next →</span>
                    @else
                        <button wire:click="nextPage" class="px-3 py-2 text-sm text-gray-400 hover:text-gray-600 transition-colors">Next →</button>
                    @endif
                </nav>
                @endif
            </div>
        </div>
    </div>
    @else
    <div class="bg-white">
        <div class="text-center py-16">
            <div class="w-16 h-16 mx-auto bg-gray-50 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3 class="text-base font-medium text-gray-600 mb-2">No Requests Found</h3>
            <p class="text-sm text-gray-400">Try adjusting your filters or search term.</p>
        </div>
    </div>
    @endif
</div>
