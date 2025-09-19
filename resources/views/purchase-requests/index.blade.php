@php
    use Illuminate\Support\Facades\Auth;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">My Purchase Requests</h1>
                <p class="text-sm text-gray-600 mt-1">Manage your purchase requests for {{ session('current_business_unit_name') }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('purchase-requests.create') }}" 
                   wire:navigate
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create New PR
                </a>
            </div>
        </div>
    </x-slot>

    <x-slot name="breadcrumbs">
        <li class="flex">
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" wire:navigate class="text-gray-400 hover:text-gray-500">
                    <svg class="flex-shrink-0 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    <span class="sr-only">Dashboard</span>
                </a>
            </div>
        </li>
        <li class="flex">
            <div class="flex items-center">
                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="ml-4 text-sm font-medium text-gray-500">Purchase Requests</span>
            </div>
        </li>
    </x-slot>

    <!-- Combined History List -->
    <div class="w-full">
        @php
            // Combine and sort all items by date
            $allItems = collect();
            
            // Add purchase requests
            foreach($purchaseRequests as $pr) {
                $allItems->push([
                    'type' => 'purchase_request',
                    'data' => $pr,
                    'sort_date' => $pr->created_at,
                    'pr_number' => $pr->pr_number,
                    'status' => $pr->status,
                    'purpose' => $pr->keperluan,
                    'description' => $pr->used_for,
                    'department' => $pr->department,
                    'user' => $pr->user,
                    'date' => $pr->date_of_request,
                    'created_at' => $pr->created_at
                ]);
            }
            
            // Add reservations
            foreach($reservations as $reservation) {
                $allItems->push([
                    'type' => 'reservation',
                    'data' => $reservation,
                    'sort_date' => $reservation->reserved_at,
                    'pr_number' => $reservation->pr_number,
                    'status' => $reservation->status,
                    'purpose' => $reservation->purpose,
                    'description' => $reservation->description,
                    'department' => $reservation->department,
                    'user' => $reservation->user,
                    'date' => $reservation->reserved_at->toDateString(),
                    'created_at' => $reservation->reserved_at
                ]);
            }
            
            // Sort by date (newest first)
            $allItems = $allItems->sortByDesc('sort_date');
        @endphp

        @if($allItems->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Purchase Request History</h3>
                    <p class="text-sm text-gray-600 mt-1">{{ $allItems->count() }} total items ({{ $purchaseRequests->count() }} completed PRs, {{ $reservations->count() }} reservations)</p>
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
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
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
                                            $statusConfig = [
                                                // PR statuses
                                                'draft' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => 'Draft'],
                                                'submitted' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Submitted'],
                                                'in_approval' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'In Approval'],
                                                'approved' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Approved'],
                                                'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Rejected'],
                                                'voided' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => 'Voided'],
                                                // Reservation statuses
                                                'reserved' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'Reserved'],
                                                'used' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Used'],
                                            ];
                                            $config = $statusConfig[$item['status']] ?? $statusConfig['draft'];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $config['bg'] }} {{ $config['text'] }}">
                                            {{ $config['label'] }}
                                        </span>
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
                                                    <button onclick="openVoidModal('{{ $item['data']->id }}', '{{ $item['data']->pr_number }}')" 
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

    <!-- Void Modal -->
    <div id="voidModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Void PR Number</h3>
                    <button onclick="closeVoidModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="voidForm" method="POST">
                    @csrf
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-3">
                            Are you sure you want to void PR number <span id="voidPrNumber" class="font-mono font-medium"></span>?
                        </p>
                        <label for="void_reason" class="block text-sm font-medium text-gray-700 mb-2">
                            Reason for voiding <span class="text-red-500">*</span>
                        </label>
                        <textarea id="void_reason" name="reason" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                  placeholder="Please provide a reason for voiding this PR number..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeVoidModal()" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Void Number
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openVoidModal(reservationId, prNumber) {
            document.getElementById('voidPrNumber').textContent = prNumber;
            document.getElementById('voidForm').action = `/pr-numbers/${reservationId}/void`;
            document.getElementById('voidModal').classList.remove('hidden');
        }

        function closeVoidModal() {
            document.getElementById('voidModal').classList.add('hidden');
            document.getElementById('void_reason').value = '';
        }

        // Close modal when clicking outside
        document.getElementById('voidModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVoidModal();
            }
        });
    </script>
</x-app-layout>