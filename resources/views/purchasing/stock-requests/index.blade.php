@php
    use Illuminate\Support\Facades\Auth;
@endphp

<x-app-layout>
    <div class="min-h-screen bg-white">
        <div class="w-full">
            <!-- Header -->
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">My History</h1>
                        <p class="text-sm text-gray-500 mt-0.5">{{ session('current_business_unit_name') }}</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="{{ url()->current() }}" class="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Refresh
                        </a>
                        <a href="{{ route('stock-requests.create') }}" 
                           wire:navigate
                           class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create New Stock Request
                        </a>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="px-6 py-4 space-y-6">
                @php
                    $pending = $stockRequests->where('status', 'draft')->count() + $stockRequests->where('status', 'submitted')->count() + $stockRequests->where('status', 'in_approval')->count();
                    $completed = $stockRequests->where('status', 'approved')->count();
                    $rejected = $stockRequests->where('status', 'rejected')->count();
                    $reserved = $stockRequests->where('status', 'reserved')->count();
                    $total = $stockRequests->count();
                @endphp
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4 mb-6">
                    <div class="bg-white border border-gray-200 rounded-lg p-4 flex flex-col items-center">
                        <div class="text-2xl font-bold text-indigo-600">{{ $pending }}</div>
                        <div class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Pending</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4 flex flex-col items-center">
                        <div class="text-2xl font-bold text-green-600">{{ $completed }}</div>
                        <div class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Completed</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4 flex flex-col items-center">
                        <div class="text-2xl font-bold text-red-600">{{ $rejected }}</div>
                        <div class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Rejected</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4 flex flex-col items-center">
                        <div class="text-2xl font-bold text-yellow-600">{{ $reserved }}</div>
                        <div class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Reserved</div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4 flex flex-col items-center">
                        <div class="text-2xl font-bold text-gray-900">{{ $total }}</div>
                        <div class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Total SRs</div>
                    </div>
                </div>
                <div class="bg-white border-t border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dept</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. ST</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Used For</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($stockRequests as $sr)
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $sr->department->code ?? '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 font-mono">{{ $sr->st_number }}</div>
                                        <div class="text-sm text-gray-500">{{ $sr->items->count() }} items</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $sr->purpose }}">
                                            {{ $sr->purpose }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 font-medium">
                                            {{ $sr->currency ?? 'IDR' }} {{ number_format($sr->items->sum('total'), 0, '', ',') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $sr->date_of_request->format('d M Y') }}</div>
                                        <div class="text-sm text-gray-500">{{ $sr->created_at->format('H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusConfig = [
                                                'draft' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => 'Draft'],
                                                'submitted' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Submitted'],
                                                'in_approval' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'In Approval'],
                                                'approved' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Approved'],
                                                'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Rejected'],
                                                'voided' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => 'Voided'],
                                            ];
                                            $config = $statusConfig[$sr->status] ?? $statusConfig['draft'];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $config['bg'] }} {{ $config['text'] }}">
                                            {{ $config['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('stock-requests.show', $sr->id) }}" 
                                               wire:navigate
                                               class="text-indigo-600 hover:text-indigo-900 transition-colors duration-200">
                                                View
                                            </a>
                                            @if($sr->status === 'draft' || $sr->status === 'rejected')
                                                <a href="{{ route('stock-requests.edit', $sr->id) }}" 
                                                   wire:navigate
                                                   class="text-green-600 hover:text-green-900 transition-colors duration-200">
                                                    Edit
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="text-gray-400">
                                            <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                            </svg>
                                            <p class="mt-2 text-sm">No stock requests found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($stockRequests->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $stockRequests->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
