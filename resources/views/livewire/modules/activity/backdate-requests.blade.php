<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Backdate Requests</h1>
            <p class="mt-1 text-sm text-gray-600">Request permission to enter tasks with older dates</p>
        </div>

        <!-- Active Permission Alert -->
        @if($activePermission && $activePermission->isActive())
            <div class="mb-6 bg-emerald-50 border border-emerald-200 rounded-lg p-4" x-data="{
                grantedUntil: '{{ $activePermission->granted_until->toIso8601String() }}',
                countdown: '',
                updateCountdown() {
                    const now = new Date();
                    const end = new Date(this.grantedUntil);
                    const diff = end - now;
                    
                    if (diff <= 0) {
                        this.countdown = 'Expired';
                        return;
                    }
                    
                    const hours = Math.floor(diff / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                    
                    this.countdown = `${hours}h ${minutes}m ${seconds}s`;
                }
            }" x-init="
                updateCountdown();
                setInterval(() => updateCountdown(), 1000);
            ">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-emerald-800">Active Backdate Permission</h3>
                        <div class="mt-2 text-sm text-emerald-700">
                            <p>You can backdate tasks up to <strong>{{ $activePermission->requested_date->format('d M Y') }}</strong></p>
                            <p class="mt-1">Time remaining: <strong x-text="countdown"></strong></p>
                            <p class="mt-1 text-xs">Expires at: {{ $activePermission->granted_until->format('d M Y H:i:s') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Request Button -->
        <div class="mb-6">
            <button 
                wire:click="openRequestModal"
                @if($hasPendingRequest) disabled @endif
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Request Backdate Access
            </button>
            @if($hasPendingRequest)
                <p class="mt-2 text-sm text-amber-600">You have a pending request. Please wait for approval.</p>
            @endif
        </div>

        <!-- Requests List -->
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Request History</h3>
            </div>

            @if($requests->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested Date</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach($requests as $request)
                                <tr class="hover:bg-gray-50 transition-colors cursor-pointer" wire:click="viewDetail({{ $request->id }})">
                                    <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $request->requested_date->format('d M Y') }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-900">
                                        <div class="max-w-xs truncate">{{ $request->reason }}</div>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        @if($request->status === 'pending')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Pending
                                            </span>
                                        @elseif($request->status === 'approved')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Approved
                                            </span>
                                        @elseif($request->status === 'rejected')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                Rejected
                                            </span>
                                        @elseif($request->status === 'expired')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Expired
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $request->created_at->format('d M Y H:i') }}
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap text-sm">
                                        <button 
                                            wire:click.stop="viewDetail({{ $request->id }})"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $requests->links() }}
                </div>
            @else
                <div class="px-5 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No requests yet</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by requesting backdate access.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Request Form Modal -->
    @if($showRequestModal)
        <div class="fixed inset-0 z-[9999] overflow-y-auto" x-data="{ show: @entangle('showRequestModal') }">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div 
                    class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    wire:click="closeRequestModal"
                ></div>

                <!-- Modal panel -->
                <div 
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative z-[10000]"
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <form wire:submit.prevent="submitRequest">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                                        Request Backdate Access
                                    </h3>
                                    <p class="mt-2 text-sm text-gray-600">
                                        Request permission to enter tasks with older dates. Your department head will review and approve your request.
                                    </p>
                                    <div class="mt-4 space-y-4">
                                        <!-- Reason -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Reason <span class="text-red-500">*</span>
                                            </label>
                                            <textarea 
                                                wire:model="reason"
                                                rows="4"
                                                placeholder="Explain why you need backdate access..."
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            ></textarea>
                                            @error('reason')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                            <p class="mt-1 text-xs text-gray-500">Minimum 10 characters, maximum 500 characters</p>
                                        </div>

                                        <!-- Info Box -->
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                            <div class="flex">
                                                <div class="flex-shrink-0">
                                                    <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm text-blue-700">
                                                        Once approved, you'll be able to enter tasks with dates older than yesterday. The permission will be valid until the end of the approval day.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button 
                                type="submit"
                                class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Submit Request
                            </button>
                            <button 
                                type="button"
                                wire:click="closeRequestModal"
                                class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Request Detail Modal -->
    @if($showDetailModal && $selectedRequest)
        <div class="fixed inset-0 z-[9999] overflow-y-auto" x-data="{ show: @entangle('showDetailModal') }">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div 
                    class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    wire:click="closeDetailModal"
                ></div>

                <!-- Modal panel -->
                <div 
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full relative z-[10000]"
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <div class="sm:flex sm:items-start">
                            <div class="w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Request Details
                                </h3>

                                <div class="space-y-4">
                                    <!-- Status -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        @if($selectedRequest->status === 'pending')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                                Pending
                                            </span>
                                        @elseif($selectedRequest->status === 'approved')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                                Approved
                                            </span>
                                        @elseif($selectedRequest->status === 'rejected')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                                Rejected
                                            </span>
                                        @elseif($selectedRequest->status === 'expired')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                                Expired
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Requested Date -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Requested Date</label>
                                        <p class="text-sm text-gray-900">{{ $selectedRequest->requested_date->format('d M Y') }}</p>
                                    </div>

                                    <!-- Reason -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                                        <p class="text-sm text-gray-900">{{ $selectedRequest->reason }}</p>
                                    </div>

                                    <!-- Submitted -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Submitted</label>
                                        <p class="text-sm text-gray-900">{{ $selectedRequest->created_at->format('d M Y H:i:s') }}</p>
                                    </div>

                                    @if($selectedRequest->status === 'approved')
                                        <!-- Approved By -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Approved By</label>
                                            <p class="text-sm text-gray-900">{{ $selectedRequest->approver->name ?? 'N/A' }}</p>
                                        </div>

                                        <!-- Approved At -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Approved At</label>
                                            <p class="text-sm text-gray-900">{{ $selectedRequest->approved_at?->format('d M Y H:i:s') ?? 'N/A' }}</p>
                                        </div>

                                        <!-- Granted Until -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Granted Until</label>
                                            <p class="text-sm text-gray-900">{{ $selectedRequest->granted_until?->format('d M Y H:i:s') ?? 'N/A' }}</p>
                                        </div>
                                    @endif

                                    @if($selectedRequest->status === 'rejected')
                                        <!-- Rejected By -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Rejected By</label>
                                            <p class="text-sm text-gray-900">{{ $selectedRequest->rejector->name ?? 'N/A' }}</p>
                                        </div>

                                        <!-- Rejected At -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Rejected At</label>
                                            <p class="text-sm text-gray-900">{{ $selectedRequest->rejected_at?->format('d M Y H:i:s') ?? 'N/A' }}</p>
                                        </div>

                                        <!-- Rejection Reason -->
                                        @if($selectedRequest->rejection_reason)
                                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                                <label class="block text-sm font-medium text-red-800 mb-1">Rejection Reason</label>
                                                <p class="text-sm text-red-700">{{ $selectedRequest->rejection_reason }}</p>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            type="button"
                            wire:click="closeDetailModal"
                            class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
