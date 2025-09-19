<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">My PR Numbers</h1>
                <p class="text-sm text-gray-600 mt-1">Track your reserved PR numbers for {{ session('current_business_unit_name') }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('purchase-requests.create') }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Request New Number
                </a>
            </div>
        </div>
    </x-slot>

    <x-slot name="breadcrumbs">
        <li class="flex">
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-500">
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
                <a href="{{ route('purchase-requests.index') }}" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">
                    Purchase Requests
                </a>
            </div>
        </li>
        <li class="flex">
            <div class="flex items-center">
                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="ml-4 text-sm font-medium text-gray-500">My Numbers</span>
            </div>
        </li>
    </x-slot>

    <div class="w-full">
        <!-- Status Summary Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Reserved</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $reservations->where('status', 'reserved')->count() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Used</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $reservations->where('status', 'used')->count() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Voided</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $reservations->where('status', 'voided')->count() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reservations Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">PR Number Reservations</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    All your reserved PR numbers and their current status.
                </p>
            </div>

            @if($reservations->count() > 0)
                <ul class="divide-y divide-gray-200">
                    @foreach($reservations as $reservation)
                        <li class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $reservation->getStatusBadgeColor() }}">
                                            {{ $reservation->getStatusDisplayText() }}
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <p class="text-sm font-medium text-gray-900 font-mono">{{ $reservation->pr_number }}</p>
                                            @if($reservation->status === 'used' && $reservation->purchaseRequest)
                                                <a href="{{ route('purchase-requests.show', $reservation->purchaseRequest) }}" 
                                                   class="ml-2 text-indigo-600 hover:text-indigo-500 text-xs">
                                                    View PR
                                                </a>
                                            @endif
                                        </div>
                                        <div class="mt-1">
                                            <p class="text-sm text-gray-600">{{ Str::limit($reservation->purpose, 60) }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                Reserved {{ $reservation->reserved_at->diffForHumans() }}
                                                @if($reservation->status === 'used')
                                                    • Used {{ $reservation->used_at->diffForHumans() }}
                                                @elseif($reservation->status === 'voided')
                                                    • Voided {{ $reservation->voided_at->diffForHumans() }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2">
                                    @if($reservation->status === 'reserved')
                                        <!-- Continue to Form -->
                                        <a href="{{ route('pr-numbers.continue', $reservation) }}" 
                                           class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            Create PR
                                        </a>

                                        <!-- Void Button -->
                                        <button onclick="openVoidModal('{{ $reservation->id }}', '{{ $reservation->pr_number }}')" 
                                                class="inline-flex items-center px-3 py-1.5 border border-red-300 text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            Void
                                        </button>
                                    @elseif($reservation->status === 'voided')
                                        <span class="text-xs text-gray-500">
                                            Reason: {{ Str::limit($reservation->void_reason, 30) }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if($reservation->status === 'reserved' && $reservation->getDaysSinceReserved() > 7)
                                <div class="mt-2 px-4 py-2 bg-amber-50 border border-amber-200 rounded-md">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-4 w-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-2">
                                            <p class="text-xs text-amber-700">
                                                This number has been reserved for {{ $reservation->getDaysSinceReserved() }} days. Consider creating the PR or voiding if no longer needed.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <!-- Pagination -->
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $reservations->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No PR numbers reserved</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by requesting your first PR number.</p>
                    <div class="mt-6">
                        <a href="{{ route('purchase-requests.create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Request PR Number
                        </a>
                    </div>
                </div>
            @endif
        </div>
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