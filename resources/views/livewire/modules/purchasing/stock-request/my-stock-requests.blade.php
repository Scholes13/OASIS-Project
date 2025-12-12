<div>
    <div wire:loading.flex wire:target="handleBusinessUnitSwitch,gotoPage,previousPage,nextPage,setPage,performSearch" class="fixed inset-0 bg-white/80 backdrop-blur-sm z-50 items-center justify-center">
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

    <!-- Search Bar -->
    <div class="px-6 py-4">
        <form wire:submit="performSearch" class="max-w-md">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input 
                    type="text" 
                    wire:model="search"
                    placeholder="Search ST number or description..."
                    class="block w-full pl-10 pr-4 py-2.5 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-colors placeholder-gray-400"
                >
            </div>
        </form>
    </div>

    @if($stockRequests->count() > 0)
    <div class="bg-white overflow-hidden" wire:loading.class="opacity-50">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">DEPT</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">NO. ST</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">USED FOR</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">DATE</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">STATUS</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50">
                    @foreach($stockRequests as $st)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600">{{ $st->department->code ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-mono font-medium text-gray-700">{{ $st->st_number }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $st->items->count() }} items</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600 max-w-md">{{ Str::limit($st->used_for, 60) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-600">{{ $st->date_of_request->format('d M Y') }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $st->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusLabels = ['draft'=>'Draft','submitted'=>'Submitted','in_approval'=>'In Approval','approved'=>'Approved','rejected'=>'Rejected','voided'=>'Voided'];
                                $statusLabel = $statusLabels[$st->status] ?? 'Unknown';
                                $showProgress = in_array($st->status, ['submitted','in_approval','approved','rejected']);
                                $totalApprovals = $st->approvals->count();
                                $approvedCount = $st->approvals->where('status', 'approved')->count();
                            @endphp
                            <span class="text-sm text-gray-600">{{ $statusLabel }}</span>
                            @if($showProgress && $totalApprovals > 0)
                                <div class="text-xs text-gray-400 mt-0.5">{{ $approvedCount }}/{{ $totalApprovals }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('stock-requests.show', $st) }}" wire:navigate class="text-indigo-500 hover:text-indigo-600 text-sm font-medium transition-colors">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-100">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-400">
                    Showing {{ $stockRequests->firstItem() ?? 0 }} to {{ $stockRequests->lastItem() ?? 0 }} of {{ $stockRequests->total() }} results
                </p>
                @if($stockRequests->hasPages())
                <nav class="flex items-center gap-1">
                    @if($stockRequests->onFirstPage())
                        <span class="px-3 py-2 text-sm text-gray-300 cursor-not-allowed select-none">← Previous</span>
                    @else
                        <button wire:click="previousPage" class="px-3 py-2 text-sm text-gray-400 hover:text-gray-600 transition-colors">← Previous</button>
                    @endif

                    <div class="flex items-center gap-1 mx-2">
                        @foreach($stockRequests->getUrlRange(1, $stockRequests->lastPage()) as $page => $url)
                            @if($page == $stockRequests->currentPage())
                                <span class="w-8 h-8 flex items-center justify-center text-sm font-medium text-indigo-600 bg-indigo-50 rounded-md">{{ $page }}</span>
                            @else
                                <button wire:click="gotoPage({{ $page }})" class="w-8 h-8 flex items-center justify-center text-sm text-gray-500 hover:bg-gray-100 rounded-md transition-colors">{{ $page }}</button>
                            @endif
                        @endforeach
                    </div>

                    @if($stockRequests->hasMorePages())
                        <button wire:click="nextPage" class="px-3 py-2 text-sm text-gray-400 hover:text-gray-600 transition-colors">Next →</button>
                    @else
                        <span class="px-3 py-2 text-sm text-gray-300 cursor-not-allowed select-none">Next →</span>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
            <h3 class="text-base font-medium text-gray-600 mb-2">No Stock Request History</h3>
            <p class="text-sm text-gray-400 mb-6">You haven't created any stock requests yet.</p>
            <a href="{{ route('stock-requests.create') }}" wire:navigate class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-indigo-500 hover:bg-indigo-600 rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Create Your First ST
            </a>
        </div>
    </div>
    @endif
</div>
