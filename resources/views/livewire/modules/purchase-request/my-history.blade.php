<div wire:init="loadData">
    <!-- Loading Overlay for Business Unit Switch -->
    <div wire:loading.flex wire:target="handleBusinessUnitSwitch" 
         class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 items-center justify-center">
        <div class="bg-white rounded-xl p-8 shadow-2xl text-center">
            <svg class="animate-spin h-12 w-12 text-indigo-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-lg font-semibold text-gray-900">Switching Business Unit...</p>
            <p class="text-sm text-gray-500 mt-1">Loading your purchase requests</p>
        </div>
    </div>

    @if($readyToLoad)
    <!-- Combined History List -->
    <div class="w-full space-y-6">
        @if($allItems->count() > 0)
            <div class="bg-white border-t border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Purchase Request History</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $allItems->count() }} total items ({{ $purchaseRequests->count() }} completed PRs, {{ $reservations->count() }} reservations)</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PR Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount/Info</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($allItems as $item)
                                <tr class="hover:bg-gray-50 transition-colors duration-200" wire:key="item-{{ $item['type'] }}-{{ $item['data']->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 font-mono">{{ $item['pr_number'] }}</div>
                                        <div class="text-sm text-gray-500">
                                            @if($item['type'] === 'purchase_request')
                                                {{ $item['data']->items->count() }} items
                                            @else
                                                Reserved number
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $item['purpose'] }}">
                                            {{ $item['purpose'] }}
                                        </div>
                                        @if($item['description'])
                                            <div class="text-sm text-gray-500 max-w-xs truncate" title="{{ $item['description'] }}">
                                                {{ Str::limit($item['description'], 50) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $item['department']->name ?? 'N/A' }}</div>
                                        <div class="text-sm text-gray-500">{{ $item['department']->code ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusLabels = [
                                                'draft' => 'Draft',
                                                'submitted' => 'Submitted',
                                                'in_approval' => 'In Approval',
                                                'approved' => 'Approved',
                                                'rejected' => 'Rejected',
                                                'voided' => 'Voided',
                                                'reserved' => 'Reserved',
                                                'used' => 'Used',
                                            ];
                                            $statusLabel = $statusLabels[$item['status']] ?? 'Unknown';
                                            
                                            // Calculate approval progress for PRs
                                            $totalApprovals = 0;
                                            $completedApprovals = 0;
                                            if ($item['type'] === 'purchase_request' && $item['data']->approvals) {
                                                $totalApprovals = $item['data']->approvals->count();
                                                $completedApprovals = $item['data']->approvals->whereIn('status', ['approved', 'rejected'])->count();
                                            }
                                        @endphp
                                        <div class="text-sm text-gray-700">{{ $statusLabel }}</div>
                                        @if($item['type'] === 'purchase_request' && $totalApprovals > 0)
                                            <div class="text-xs text-gray-400 mt-0.5">{{ $completedApprovals }}/{{ $totalApprovals }} done</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($item['type'] === 'purchase_request')
                                            <div class="text-sm text-gray-900 font-medium">
                                                {{ $item['data']->currency }} {{ number_format($item['data']->total_amount, 2) }}
                                            </div>
                                        @else
                                            <div class="text-sm text-gray-500">
                                                @if($item['status'] === 'reserved')
                                                    {{ $item['data']->getDaysSinceReserved() }} days reserved
                                                @else
                                                    Number {{ $item['status'] }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($item['date'])->format('M d, Y') }}</div>
                                        <div class="text-sm text-gray-500">{{ $item['created_at']->format('H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            @if($item['type'] === 'purchase_request')
                                                <a href="{{ route('purchase-requests.show', $item['data']) }}" 
                                                   wire:navigate
                                                   class="text-indigo-600 hover:text-indigo-900 transition-colors duration-200">
                                                    View
                                                </a>
                                                @if($item['data']->canBeEdited())
                                                    <a href="{{ route('purchase-requests.edit', $item['data']) }}" 
                                                       wire:navigate
                                                       class="text-green-600 hover:text-green-900 transition-colors duration-200">
                                                        Edit
                                                    </a>
                                                @endif
                                            @else
                                                @if($item['status'] === 'reserved')
                                                    <a href="{{ route('pr-numbers.continue', $item['data']) }}" 
                                                       class="text-indigo-600 hover:text-indigo-900 transition-colors duration-200">
                                                        Continue
                                                    </a>
                                                    <button wire:click="openVoidModal('{{ $item['data']->id }}', '{{ $item['data']->pr_number }}')" 
                                                            class="text-red-600 hover:text-red-900 transition-colors duration-200">
                                                        Void
                                                    </button>
                                                @elseif($item['status'] === 'used' && $item['data']->purchaseRequest)
                                                    <a href="{{ route('purchase-requests.show', $item['data']->purchaseRequest) }}" 
                                                       class="text-indigo-600 hover:text-indigo-900 transition-colors duration-200">
                                                        View PR
                                                    </a>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Section -->
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
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Purchase Request History</h3>
                    <p class="text-gray-500 mb-6">You haven't created any purchase requests or reserved any numbers yet. Get started by creating your first one.</p>
                    <a href="{{ route('purchase-requests.create') }}" 
                       wire:navigate
                       class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create Your First PR
                    </a>
                </div>
            </div>
        @endif
    </div>
    @else
    {{-- Loading Skeleton --}}
    <div class="w-full space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-pulse">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="h-6 bg-gray-200 rounded w-1/3 mb-2"></div>
                <div class="h-4 bg-gray-100 rounded w-1/4"></div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @for($i = 0; $i < 7; $i++)
                            <th class="px-6 py-3">
                                <div class="h-4 bg-gray-200 rounded w-20"></div>
                            </th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @for($row = 0; $row < 5; $row++)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="h-4 bg-gray-200 rounded w-24 mb-1"></div>
                                <div class="h-3 bg-gray-100 rounded w-16"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="h-4 bg-gray-200 rounded w-32 mb-1"></div>
                                <div class="h-3 bg-gray-100 rounded w-24"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="h-4 bg-gray-200 rounded w-20 mb-1"></div>
                                <div class="h-3 bg-gray-100 rounded w-12"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="h-5 bg-gray-200 rounded-full w-16"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="h-4 bg-gray-200 rounded w-24"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="h-4 bg-gray-200 rounded w-20 mb-1"></div>
                                <div class="h-3 bg-gray-100 rounded w-12"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="h-4 bg-gray-200 rounded w-16"></div>
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
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
