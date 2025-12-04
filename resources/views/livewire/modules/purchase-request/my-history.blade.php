<div class="min-h-screen bg-white" wire:init="loadData">
    {{-- Skeleton Loader - for initial lazy load --}}
    @if(!$readyToLoad)
    <div class="w-full">
        <!-- Header skeleton -->
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="h-8 w-48 bg-gray-200 rounded animate-pulse"></div>
                    <div class="h-4 w-64 bg-gray-100 rounded mt-2 animate-pulse"></div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="h-4 w-40 bg-gray-100 rounded animate-pulse"></div>
                    <div class="h-9 w-24 bg-gray-200 rounded-md animate-pulse"></div>
                </div>
            </div>
        </div>
        
        <!-- Stats skeleton -->
        <div class="border-b border-gray-200 px-6 py-8">
            <div class="grid grid-cols-5 gap-6">
                @for($i = 0; $i < 5; $i++)
                <div class="relative flex items-center px-6 py-5 rounded-lg border border-gray-200 bg-white animate-pulse">
                    <div class="w-1 h-16 bg-gray-200 rounded-full mr-4"></div>
                    <div>
                        <div class="h-8 w-12 bg-gray-200 rounded"></div>
                        <div class="h-4 w-20 bg-gray-100 rounded mt-2"></div>
                    </div>
                </div>
                @endfor
            </div>
        </div>
        
        <!-- Table skeleton -->
        <div class="px-6 py-4">
            <div class="mb-4">
                <div class="h-10 w-80 bg-gray-100 rounded-md animate-pulse"></div>
            </div>
            <div class="animate-pulse space-y-3">
                <div class="h-10 bg-gray-100 rounded"></div>
                <div class="h-14 bg-gray-50 rounded"></div>
                <div class="h-14 bg-gray-50 rounded"></div>
                <div class="h-14 bg-gray-50 rounded"></div>
                <div class="h-14 bg-gray-50 rounded"></div>
                <div class="h-14 bg-gray-50 rounded"></div>
            </div>
        </div>
    </div>
    @else
    {{-- Main Content --}}
    <div class="w-full">
        {{-- Loading Overlay - Same style as Dashboard (search excluded for smoother UX) --}}
        <div wire:loading.flex 
             wire:target="gotoPage, setFilter, clearFilter, $refresh, handleBusinessUnitSwitch"
             class="fixed inset-0 bg-white/80 backdrop-blur-sm z-50 items-center justify-center">
            <div class="flex flex-col items-center space-y-6">
                <div class="relative">
                    <div class="w-16 h-16 border-4 border-indigo-100 rounded-full"></div>
                    <div class="absolute top-0 left-0 w-16 h-16 border-4 border-indigo-500 rounded-full border-t-transparent animate-spin"></div>
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">Loading My History</h3>
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
                    <div class="h-full bg-gradient-to-r from-indigo-400 via-indigo-500 to-indigo-400 rounded-full animate-pulse" style="width: 100%;"></div>
                </div>
            </div>
        </div>

        <!-- Sub Header with Last Updated & Refresh -->
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Your purchase requests in {{ $businessUnitName }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">Last updated: {{ now()->format('d M Y, H:i') }} (GMT+7)</span>
                    <button type="button" 
                            wire:click="$refresh" 
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-wait"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 disabled:opacity-50">
                        <svg class="w-4 h-4 mr-2 transition-transform duration-200" 
                             wire:loading.class="animate-spin" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span wire:loading.remove wire:target="$refresh">Refresh</span>
                        <span wire:loading wire:target="$refresh" class="text-indigo-600">Refreshing...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Overview - Filter Cards -->
        <div class="border-b border-gray-200 px-6 py-8">
            <div class="grid grid-cols-5 gap-6">
                <!-- Pending Documents -->
                <button type="button" wire:click="setFilter('pending')" 
                   wire:loading.class="opacity-50 scale-95"
                   wire:target="setFilter('pending')"
                   class="relative flex items-center px-6 py-5 rounded-lg border border-gray-200 transition-all duration-200 hover:shadow-md hover:border-indigo-300 hover:bg-indigo-50 cursor-pointer text-left w-full transform {{ $filter === 'pending' ? 'bg-indigo-50 ring-2 ring-indigo-500 border-indigo-500' : 'bg-white' }}">
                    <svg class="absolute top-4 right-4 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <div class="w-1 h-16 bg-indigo-600 rounded-full" style="margin-right: 1rem;"></div>
                    <div>
                        <p class="text-3xl font-semibold text-indigo-600">{{ $pendingCount }}</p>
                        <p class="text-sm text-gray-500 mt-1">Pending</p>
                    </div>
                </button>

                <!-- Approved Documents -->
                <button type="button" wire:click="setFilter('approved')" 
                   wire:loading.class="opacity-50 scale-95"
                   wire:target="setFilter('approved')"
                   class="relative flex items-center px-6 py-5 rounded-lg border border-gray-200 transition-all duration-200 hover:shadow-md hover:border-indigo-300 hover:bg-indigo-50 cursor-pointer text-left w-full transform {{ $filter === 'approved' ? 'bg-indigo-50 ring-2 ring-indigo-500 border-indigo-500' : 'bg-white' }}">
                    <svg class="absolute top-4 right-4 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <div class="w-1 h-16 bg-indigo-600 rounded-full" style="margin-right: 1rem;"></div>
                    <div>
                        <p class="text-3xl font-semibold text-indigo-600">{{ $approvedCount }}</p>
                        <p class="text-sm text-gray-500 mt-1">Completed</p>
                    </div>
                </button>

                <!-- Rejected Documents -->
                <button type="button" wire:click="setFilter('rejected')" 
                   wire:loading.class="opacity-50 scale-95"
                   wire:target="setFilter('rejected')"
                   class="relative flex items-center px-6 py-5 rounded-lg border border-gray-200 transition-all duration-200 hover:shadow-md hover:border-indigo-300 hover:bg-indigo-50 cursor-pointer text-left w-full transform {{ $filter === 'rejected' ? 'bg-indigo-50 ring-2 ring-indigo-500 border-indigo-500' : 'bg-white' }}">
                    <svg class="absolute top-4 right-4 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <div class="w-1 h-16 bg-indigo-600 rounded-full" style="margin-right: 1rem;"></div>
                    <div>
                        <p class="text-3xl font-semibold text-indigo-600">{{ $rejectedCount }}</p>
                        <p class="text-sm text-gray-500 mt-1">Rejected</p>
                    </div>
                </button>

                <!-- Reserved Numbers (Display Only - No Filter) -->
                <div class="relative flex items-center px-6 py-5 rounded-lg border border-gray-200 bg-white text-left w-full">
                    <div class="w-1 h-16 bg-indigo-600 rounded-full" style="margin-right: 1rem;"></div>
                    <div>
                        <p class="text-3xl font-semibold text-indigo-600">{{ $reservedCount }}</p>
                        <p class="text-sm text-gray-500 mt-1">Reserved</p>
                    </div>
                </div>

                <!-- Total Documents (Clear Filter when clicked) -->
                <button type="button" wire:click="clearFilter" 
                   wire:loading.class="opacity-50 scale-95"
                   wire:target="clearFilter"
                   class="relative flex items-center px-6 py-5 rounded-lg border border-gray-200 transition-all duration-200 hover:shadow-md hover:border-indigo-300 hover:bg-indigo-50 cursor-pointer text-left w-full transform {{ $filter ? 'bg-white' : 'bg-white' }}">
                    <svg class="absolute top-4 right-4 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <div class="w-1 h-16 bg-indigo-600 rounded-full" style="margin-right: 1rem;"></div>
                    <div>
                        <p class="text-3xl font-semibold text-indigo-600">{{ $totalPRs }}</p>
                        <p class="text-sm text-gray-500 mt-1">Total PRs</p>
                    </div>
                </button>
            </div>
        </div>

        <!-- All Documents Table -->
        <div class="px-6 py-4">
            <!-- Search Box -->
            <div class="mb-4 flex items-center justify-between">
                <div class="relative w-80">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" 
                           wire:model.live.debounce.150ms="search" 
                           placeholder="Search PR number or description..." 
                           class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-all duration-200">
                    @if($search)
                    <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3 flex items-center hover:scale-110 transition-transform">
                        <svg class="h-4 w-4 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    @endif
                </div>
                @if($search || $filter)
                <button type="button" wire:click="clearFilter" 
                        wire:loading.class="opacity-50"
                        class="text-sm text-indigo-600 hover:text-indigo-800 font-medium transition-colors duration-200 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Clear all filters
                </button>
                @endif
            </div>

            {{-- Reserved Numbers Section --}}
            @if($reservations->count() > 0 && !$filter)
            <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-yellow-800 mb-3">Reserved PR Numbers ({{ $reservations->count() }})</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($reservations as $reservation)
                    <div class="inline-flex items-center bg-white border border-yellow-300 rounded-lg px-3 py-2">
                        <span class="text-sm font-mono font-medium text-gray-900">{{ $reservation->pr_number }}</span>
                        <div class="ml-3 flex items-center gap-2">
                            <a href="{{ route('pr-numbers.continue', $reservation) }}" 
                               class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                Continue
                            </a>
                            <button wire:click="openVoidModal('{{ $reservation->id }}', '{{ $reservation->pr_number }}')" 
                                    class="text-xs text-red-600 hover:text-red-800 font-medium">
                                Void
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($purchaseRequests->count() > 0)
                <table class="min-w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dept</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. PR</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Used For</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($purchaseRequests as $pr)
                        @php
                            $totalApprovals = $pr->approvals->count();
                            $completedApprovals = $pr->approvals->whereIn('status', ['approved'])->count();
                        @endphp
                        <tr wire:key="pr-{{ $pr->id }}" class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-3 py-4">
                                <div class="text-sm text-gray-900">{{ $pr->department->code ?? 'N/A' }}</div>
                            </td>
                            <td class="px-3 py-4">
                                <div class="text-sm font-medium text-gray-900 font-mono">{{ $pr->pr_number }}</div>
                                <div class="text-xs text-gray-500">{{ $pr->items->count() }} items</div>
                            </td>
                            <td class="px-3 py-4">
                                <div class="text-sm text-gray-700 max-w-xs truncate" title="{{ $pr->used_for }}">
                                    {{ Str::limit($pr->used_for, 40) }}
                                </div>
                            </td>
                            <td class="px-3 py-4">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $pr->currency ?? 'IDR' }} {{ number_format($pr->total_amount ?? 0, 0) }}
                                </div>
                            </td>
                            <td class="px-3 py-4">
                                <div class="text-sm text-gray-900">{{ $pr->created_at->format('d M Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $pr->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-3 py-4">
                                @if($pr->status === 'in_approval' || $pr->status === 'submitted')
                                    <div class="text-sm text-blue-600 font-medium">Pending document</div>
                                    @if($totalApprovals > 0)
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $completedApprovals }}/{{ $totalApprovals }} done</div>
                                    @endif
                                @elseif($pr->status === 'approved')
                                    <div class="text-sm text-green-600 font-medium">Completed</div>
                                    @if($totalApprovals > 0)
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $completedApprovals }}/{{ $totalApprovals }} done</div>
                                    @endif
                                @elseif($pr->status === 'rejected')
                                    <div class="text-sm text-red-600 font-medium">Rejected</div>
                                @elseif($pr->status === 'voided')
                                    <div class="text-sm text-gray-500 font-medium">Voided</div>
                                @elseif($pr->status === 'draft')
                                    <div class="text-sm text-yellow-600 font-medium">Draft</div>
                                @else
                                    <div class="text-sm text-gray-700">{{ ucfirst($pr->status) }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-4">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('purchase-requests.show', $pr) }}" 
                                       class="text-sm text-indigo-600 hover:text-indigo-900 font-medium transition-colors duration-200">
                                        View
                                    </a>
                                    @if($pr->canBeEdited())
                                        <a href="{{ route('purchase-requests.edit', $pr) }}" 
                                           class="text-sm text-green-600 hover:text-green-900 font-medium transition-colors duration-200">
                                            Edit
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Pagination -->
                @if($purchaseRequests->hasPages())
                <div class="mt-6 flex items-center justify-between border-t border-gray-100 pt-4">
                    <div class="text-sm text-gray-500">
                        Showing {{ $purchaseRequests->firstItem() }} to {{ $purchaseRequests->lastItem() }} of {{ $purchaseRequests->total() }} results
                    </div>
                    <nav class="flex items-center space-x-1">
                        {{-- Previous Page Link --}}
                        @if ($purchaseRequests->onFirstPage())
                            <span class="px-3 py-2 text-sm text-gray-300 cursor-not-allowed">&larr; Previous</span>
                        @else
                            <button type="button" 
                                    wire:click="gotoPage({{ $purchaseRequests->currentPage() - 1 }})" 
                                    wire:loading.class="opacity-50"
                                    class="px-3 py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition-all duration-200">&larr; Previous</button>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($purchaseRequests->getUrlRange(1, $purchaseRequests->lastPage()) as $page => $url)
                            @if ($page == $purchaseRequests->currentPage())
                                <span class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-md shadow-sm">{{ $page }}</span>
                            @else
                                <button type="button" 
                                        wire:click="gotoPage({{ $page }})" 
                                        wire:loading.class="opacity-50"
                                        class="px-3 py-1.5 text-sm text-gray-700 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition-all duration-200">{{ $page }}</button>
                            @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($purchaseRequests->hasMorePages())
                            <button type="button" 
                                    wire:click="gotoPage({{ $purchaseRequests->currentPage() + 1 }})" 
                                    wire:loading.class="opacity-50"
                                    class="px-3 py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition-all duration-200">Next &rarr;</button>
                        @else
                            <span class="px-3 py-2 text-sm text-gray-300 cursor-not-allowed">Next &rarr;</span>
                        @endif
                    </nav>
                </div>
                @endif
            @else
                <!-- Empty State -->
                <div class="py-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-base font-medium text-gray-900 mb-1">No Purchase Requests</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        @if($search)
                            No results for "{{ $search }}". 
                            <button type="button" wire:click="$set('search', '')" class="text-indigo-600 hover:text-indigo-800 font-medium">Clear search</button>
                        @elseif($filter)
                            No {{ $filter }} purchase requests found. 
                            <button type="button" wire:click="clearFilter" class="text-indigo-600 hover:text-indigo-800 font-medium">Clear filter</button>
                        @else
                            You haven't created any purchase requests yet.
                        @endif
                    </p>
                    @if(!$search && !$filter)
                    <a href="{{ route('purchase-requests.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create Your First PR
                    </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Void Modal -->
    @if($showVoidModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Void PR Number</h3>
                    <button wire:click="closeVoidModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <p class="text-sm text-gray-500 mb-4">
                    Are you sure you want to void PR Number <span class="font-semibold text-gray-900">{{ $voidPrNumber }}</span>? 
                    This action cannot be undone.
                </p>
                
                <div class="mb-4">
                    <label for="voidReason" class="block text-sm font-medium text-gray-700 mb-1">Reason for voiding <span class="text-red-500">*</span></label>
                    <textarea wire:model="voidReason" 
                              id="voidReason"
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('voidReason') border-red-500 @enderror"
                              placeholder="Please provide a reason for voiding this PR number..."></textarea>
                    @error('voidReason')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button wire:click="closeVoidModal" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </button>
                    <button wire:click="voidReservation"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50">
                        <span wire:loading.remove wire:target="voidReservation">Void Number</span>
                        <span wire:loading wire:target="voidReservation">Voiding...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
