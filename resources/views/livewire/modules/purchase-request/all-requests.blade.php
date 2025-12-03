<div class="min-h-screen bg-white">
    <div class="w-full">
        <!-- Simple Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">All Purchase Requests</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage all purchase requests in {{ $currentBusinessUnitName }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">Last updated: {{ now()->format('d M Y, H:i') }} (GMT+7)</span>
                    <button type="button" wire:click="$refresh" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.class="animate-spin" wire:target="$refresh">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span wire:loading.remove wire:target="$refresh">Refresh</span>
                        <span wire:loading wire:target="$refresh">Loading...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Overview - Category Filter Cards -->
        <div class="border-b border-gray-200 px-6 py-8">
            <div style="display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 1.5rem;">
                @foreach($categories as $cat)
                <!-- {{ $cat['name'] }} -->
                <button type="button" wire:click="filterByCategory({{ $cat['id'] }})"
                   class="relative flex items-center px-6 py-5 rounded-lg border border-gray-200 transition-all duration-200 hover:shadow-md hover:border-indigo-300 hover:bg-indigo-50 cursor-pointer text-left {{ $category == $cat['id'] ? 'bg-indigo-50 ring-2 ring-indigo-500 border-indigo-500' : 'bg-white' }}">
                    <svg class="absolute top-4 right-4 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <div class="w-1 h-16 bg-indigo-600 rounded-full" style="margin-right: 1rem;"></div>
                    <div>
                        <p class="text-3xl font-semibold text-indigo-600">{{ $categoryStats[$cat['id']] ?? 0 }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ $cat['name'] }}</p>
                    </div>
                </button>
                @endforeach

                <!-- Total Documents -->
                <button type="button" wire:click="showAllDocuments"
                   class="relative flex items-center px-6 py-5 rounded-lg border border-gray-200 transition-all duration-200 hover:shadow-md hover:border-indigo-300 hover:bg-indigo-50 cursor-pointer text-left {{ $showAll ? 'bg-indigo-50 ring-2 ring-indigo-500 border-indigo-500' : 'bg-white' }}">
                    <svg class="absolute top-4 right-4 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <div class="w-1 h-16 bg-indigo-600 rounded-full" style="margin-right: 1rem;"></div>
                    <div>
                        <p class="text-3xl font-semibold text-indigo-600">{{ $totalPRs }}</p>
                        <p class="text-sm text-gray-500 mt-1">Total documents</p>
                    </div>
                </button>
            </div>
        </div>

        <!-- All Documents Table -->
        <div class="px-6 py-4">
            @if($purchaseRequests->count() > 0)
                <table class="min-w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dept</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. PR</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Used For</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requestor</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($purchaseRequests as $pr)
                        @php
                            $totalApprovals = $pr->approvals->count();
                            $completedApprovals = $pr->approvals->whereIn('status', ['approved'])->count();
                            
                            $statusLabel = match($pr->status) {
                                'draft' => 'Draft',
                                'submitted' => 'Submitted',
                                'in_approval' => 'Pending document',
                                'approved' => 'Completed',
                                'rejected' => 'Rejected',
                                'voided' => 'Voided',
                                default => ucfirst($pr->status)
                            };
                        @endphp
                        <tr wire:key="pr-{{ $pr->id }}" class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ route('purchase-requests.show', $pr) }}'">
                            <td class="px-3 py-4">
                                <div class="text-sm text-gray-900">{{ $pr->department->code ?? 'N/A' }}</div>
                            </td>
                            <td class="px-3 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $pr->pr_number }}</div>
                            </td>
                            <td class="px-3 py-4">
                                <div class="text-sm text-gray-700">{{ $pr->category->name ?? '-' }}</div>
                            </td>
                            <td class="px-3 py-4">
                                <div class="text-sm text-gray-700 max-w-xs truncate" title="{{ $pr->used_for }}">
                                    {{ Str::limit($pr->used_for, 40) }}
                                </div>
                            </td>
                            <td class="px-3 py-4">
                                <div class="text-sm text-gray-900">{{ $pr->user->name ?? 'Unknown' }}</div>
                            </td>
                            <td class="px-3 py-4">
                                <div class="text-sm text-gray-700">{{ $statusLabel }}</div>
                                @if($totalApprovals > 0)
                                    <div class="text-xs text-gray-400 mt-0.5">{{ $completedApprovals }}/{{ $totalApprovals }} done</div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                @if($purchaseRequests->hasPages())
                    <div class="px-3 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500">
                                Showing {{ $purchaseRequests->firstItem() }} to {{ $purchaseRequests->lastItem() }} of {{ $purchaseRequests->total() }} results
                            </div>
                            <div class="flex items-center gap-2">
                                @if($purchaseRequests->onFirstPage())
                                    <span class="px-3 py-1 text-sm text-gray-400 cursor-not-allowed">&lt;</span>
                                @else
                                    <button wire:click="previousPage" class="px-3 py-1 text-sm text-gray-700 hover:bg-gray-100 rounded">&lt;</button>
                                @endif

                                @foreach($purchaseRequests->getUrlRange(1, $purchaseRequests->lastPage()) as $page => $url)
                                    @if($page == $purchaseRequests->currentPage())
                                        <span class="px-3 py-1 text-sm font-medium text-white bg-indigo-600 rounded">{{ $page }}</span>
                                    @else
                                        <button wire:click="gotoPage({{ $page }})" class="px-3 py-1 text-sm text-gray-700 hover:bg-gray-100 rounded">{{ $page }}</button>
                                    @endif
                                @endforeach

                                @if($purchaseRequests->hasMorePages())
                                    <button wire:click="nextPage" class="px-3 py-1 text-sm text-gray-700 hover:bg-gray-100 rounded">&gt;</button>
                                @else
                                    <span class="px-3 py-1 text-sm text-gray-400 cursor-not-allowed">&gt;</span>
                                @endif
                            </div>
                        </div>
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
                    <p class="text-sm text-gray-500">
                        @if($category)
                            No purchase requests found for this category. 
                            <button wire:click="clearFilter" class="text-indigo-600 hover:text-indigo-800 font-medium">Clear filter</button>
                        @else
                            No purchase requests have been created in this business unit yet.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
