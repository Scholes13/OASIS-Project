<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Backdate Approvals</h1>
            <p class="mt-1 text-sm text-gray-600">Review and manage backdate permission requests from your team</p>
        </div>

        <!-- Stats Card -->
        <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending Requests</p>
                    <p class="text-3xl font-bold text-indigo-600 mt-1">{{ $pendingCount }}</p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 mb-6">
            <div class="flex items-center gap-4">
                <label class="text-sm font-medium text-gray-700">Filter by Status:</label>
                <div class="flex gap-2">
                    <button wire:click="$set('statusFilter', 'pending')" 
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $statusFilter === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        Pending
                    </button>
                    <button wire:click="$set('statusFilter', 'approved')" 
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $statusFilter === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        Approved
                    </button>
                    <button wire:click="$set('statusFilter', 'rejected')" 
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $statusFilter === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        Rejected
                    </button>
                    <button wire:click="$set('statusFilter', 'all')" 
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $statusFilter === 'all' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        All
                    </button>
                </div>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            @if($requests->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requester</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested Date</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach($requests as $request)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center text-xs font-medium text-indigo-700">
                                                {{ substr($request->user->name, 0, 2) }}
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-900">{{ $request->user->name }}</p>
                                                <p class="text-xs text-gray-500">{{ $request->user->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">{{ $request->department->name }}</span>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900">{{ $request->requested_date->format('d M Y') }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="text-sm text-gray-900 max-w-xs truncate" title="{{ $request->reason }}">
                                            {{ $request->reason }}
                                        </p>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        @if($request->status === 'pending')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                </svg>
                                                Pending
                                            </span>
                                        @elseif($request->status === 'approved')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                                Approved
                                            </span>
                                        @elseif($request->status === 'rejected')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                </svg>
                                                Rejected
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                                {{ ucfirst($request->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-500">{{ $request->created_at->format('d M Y H:i') }}</span>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        @if($request->status === 'pending')
                                            <div class="flex items-center gap-2">
                                                <button wire:click="approveRequest({{ $request->id }})" 
                                                        class="inline-flex items-center px-3 py-1.5 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors">
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Approve
                                                </button>
                                                <button wire:click="openRejectModal({{ $request->id }})" 
                                                        class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Reject
                                                </button>
                                            </div>
                                        @elseif($request->status === 'approved')
                                            <div class="text-sm text-gray-500">
                                                <p>Approved by {{ $request->approver->name }}</p>
                                                <p class="text-xs">{{ $request->approved_at->format('d M Y H:i') }}</p>
                                                @if($request->granted_until)
                                                    <p class="text-xs text-emerald-600 font-medium">Valid until: {{ $request->granted_until->format('d M Y H:i') }}</p>
                                                @endif
                                            </div>
                                        @elseif($request->status === 'rejected')
                                            <div class="text-sm text-gray-500">
                                                <p>Rejected by {{ $request->rejector->name }}</p>
                                                <p class="text-xs">{{ $request->rejected_at->format('d M Y H:i') }}</p>
                                                @if($request->rejection_reason)
                                                    <p class="text-xs text-red-600 mt-1 max-w-xs truncate" title="{{ $request->rejection_reason }}">
                                                        Reason: {{ $request->rejection_reason }}
                                                    </p>
                                                @endif
                                            </div>
                                        @endif
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
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No requests found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($statusFilter === 'pending')
                            There are no pending backdate requests at the moment.
                        @else
                            No requests match the selected filter.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Reject Modal -->
    @if($showRejectModal && $selectedRequest)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showRejectModal') }" x-show="show" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$wire.closeRejectModal()"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-6 pt-5 pb-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-medium text-gray-900">Reject Backdate Request</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        You are about to reject the backdate request from <strong>{{ $selectedRequest->user->name }}</strong> for date <strong>{{ $selectedRequest->requested_date->format('d M Y') }}</strong>.
                                    </p>
                                    <p class="text-sm text-gray-500 mt-2">
                                        <strong>Reason:</strong> {{ $selectedRequest->reason }}
                                    </p>
                                </div>

                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason <span class="text-red-600">*</span></label>
                                    <textarea wire:model="rejection_reason" 
                                              rows="4" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                              placeholder="Please provide a clear reason for rejecting this request..."></textarea>
                                    @error('rejection_reason')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex flex-row-reverse gap-3">
                        <button wire:click="rejectRequest" 
                                type="button" 
                                class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                            Reject Request
                        </button>
                        <button wire:click="closeRejectModal" 
                                type="button" 
                                class="inline-flex items-center px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
