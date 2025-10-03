<x-app-layout>
<div class="min-h-screen bg-gray-50 py-8">
    <div class="w-full">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Approvals</h1>
            <p class="mt-2 text-gray-600">Manage your purchase request approvals</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
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
                            <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $pendingApprovals->count() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
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
                            <dt class="text-sm font-medium text-gray-500 truncate">Approved</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $approvalStats['total_approved'] ?? 0 }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
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
                            <dt class="text-sm font-medium text-gray-500 truncate">Rejected</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $approvalStats['total_rejected'] ?? 0 }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Approval Rate</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $approvalStats['approval_rate'] ?? 0 }}%</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <a href="{{ route('approvals.index', ['tab' => 'pending']) }}" 
                       class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm {{ $tab === 'pending' ? 'border-indigo-500 text-indigo-600' : '' }}">
                        Pending Approvals
                        @if($pendingApprovals->count() > 0)
                            <span class="bg-indigo-100 text-indigo-600 ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium">{{ $pendingApprovals->count() }}</span>
                        @endif
                    </a>
                    <a href="{{ route('approvals.index', ['tab' => 'history']) }}" 
                       class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm {{ $tab === 'history' ? 'border-indigo-500 text-indigo-600' : '' }}">
                        Approval History
                        @if($approvalHistory->count() > 0)
                            <span class="bg-gray-100 text-gray-600 ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium">{{ $approvalHistory->count() }}</span>
                        @endif
                    </a>
                </nav>
            </div>
        </div>

        <!-- Tab Content -->
        @if($tab === 'pending')
            <!-- Pending Approvals Tab -->
            @if($pendingApprovals->isEmpty())
                <!-- Empty State -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Pending Approvals</h3>
                    <p class="text-gray-500">You don't have any purchase requests waiting for approval at the moment.</p>
                </div>
            @else
                <!-- Pending Approvals List -->
                <div class="space-y-6">
                    @foreach($pendingApprovals as $approval)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $approval->purchaseRequest->pr_number }}</h3>
                                    <p class="text-sm text-gray-600">Requested by {{ $approval->purchaseRequest->user->name }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-indigo-600">{{ $approval->purchaseRequest->currency }} {{ number_format($approval->purchaseRequest->total_amount, 0) }}</div>
                                    <div class="text-sm text-gray-500">Total Amount</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Department</label>
                                    <p class="text-sm text-gray-900">{{ $approval->purchaseRequest->department->name }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Request Date</label>
                                    <p class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($approval->purchaseRequest->date_of_request)->format('d/m/Y') }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</label>
                                    <p class="text-sm {{ $approval->due_date->isPast() ? 'text-red-600 font-medium' : 'text-gray-900' }}">
                                        {{ $approval->due_date->format('d/m/Y') }}
                                        @if($approval->due_date->isPast())
                                            <span class="text-xs">(Overdue)</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Purpose</label>
                                <p class="text-sm text-gray-900">{{ $approval->purchaseRequest->keperluan }}</p>
                            </div>

                            <!-- Items Preview -->
                            <div class="mb-4">
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Items ({{ $approval->purchaseRequest->items->count() }})</label>
                                <div class="space-y-1">
                                    @foreach($approval->purchaseRequest->items->take(3) as $item)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-900">{{ $item->item_name }}</span>
                                        <span class="text-gray-600">{{ $item->quantity }} {{ $item->unit }} × {{ $item->currency }} {{ number_format($item->unit_price, 0) }}</span>
                                    </div>
                                    @endforeach
                                    @if($approval->purchaseRequest->items->count() > 3)
                                    <div class="text-sm text-gray-500">
                                        ... and {{ $approval->purchaseRequest->items->count() - 3 }} more items
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                <div class="flex items-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Assigned {{ $approval->assigned_at->diffForHumans() }}
                                </div>
                                <div class="flex space-x-3">
                                    <a href="{{ route('approvals.show', $approval->id) }}" 
                                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Review & Approve
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Pagination Links for Pending Approvals -->
                @if($pendingApprovals->hasPages())
                <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <span class="font-medium">{{ $pendingApprovals->firstItem() ?? 0 }}</span>
                            to <span class="font-medium">{{ $pendingApprovals->lastItem() ?? 0 }}</span>
                            of <span class="font-medium">{{ $pendingApprovals->total() }}</span> pending approvals
                        </div>
                        <div>
                            {{ $pendingApprovals->appends(['tab' => 'pending'])->links() }}
                        </div>
                    </div>
                </div>
                @endif
            @endif

        @elseif($tab === 'history')
            <!-- Approval History Tab -->
            @if($approvalHistory->isEmpty())
                <!-- Empty State -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Approval History</h3>
                    <p class="text-gray-500">You haven't processed any approvals yet.</p>
                </div>
            @else
                <!-- Approval History List -->
                <div class="space-y-6">
                    @foreach($approvalHistory as $approval)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <div class="flex items-center space-x-3">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $approval->purchaseRequest->pr_number }}</h3>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $approval->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($approval->status) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">Requested by {{ $approval->purchaseRequest->user->name }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-gray-900">{{ $approval->purchaseRequest->currency }} {{ number_format($approval->purchaseRequest->total_amount, 0) }}</div>
                                    <div class="text-sm text-gray-500">Total Amount</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Department</label>
                                    <p class="text-sm text-gray-900">{{ $approval->purchaseRequest->department->name }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Request Date</label>
                                    <p class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($approval->purchaseRequest->date_of_request)->format('d/m/Y') }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Responded Date</label>
                                    <p class="text-sm text-gray-900">{{ $approval->responded_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Response Time</label>
                                    <p class="text-sm text-gray-900">{{ $approval->assigned_at->diffInHours($approval->responded_at) }}h</p>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Purpose</label>
                                <p class="text-sm text-gray-900">{{ $approval->purchaseRequest->keperluan }}</p>
                            </div>

                            @if($approval->notes)
                            <div class="mb-4">
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Your Notes</label>
                                <p class="text-sm text-gray-900 bg-gray-50 rounded-md p-3">{{ $approval->notes }}</p>
                            </div>
                            @endif

                            <!-- Items Preview -->
                            <div class="mb-4">
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Items ({{ $approval->purchaseRequest->items->count() }})</label>
                                <div class="space-y-1">
                                    @foreach($approval->purchaseRequest->items->take(2) as $item)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-900">{{ $item->item_name }}</span>
                                        <span class="text-gray-600">{{ $item->quantity }} {{ $item->unit }} × {{ $item->currency }} {{ number_format($item->unit_price, 0) }}</span>
                                    </div>
                                    @endforeach
                                    @if($approval->purchaseRequest->items->count() > 2)
                                    <div class="text-sm text-gray-500">
                                        ... and {{ $approval->purchaseRequest->items->count() - 2 }} more items
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                <div class="flex items-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Processed {{ $approval->responded_at->diffForHumans() }}
                                </div>
                                <div class="flex space-x-3">
                                    <a href="{{ route('approvals.show', $approval->id) }}" 
                                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Pagination Links for Approval History -->
                @if($approvalHistory->hasPages())
                <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <span class="font-medium">{{ $approvalHistory->firstItem() ?? 0 }}</span>
                            to <span class="font-medium">{{ $approvalHistory->lastItem() ?? 0 }}</span>
                            of <span class="font-medium">{{ $approvalHistory->total() }}</span> completed approvals
                        </div>
                        <div>
                            {{ $approvalHistory->appends(['tab' => 'history'])->links() }}
                        </div>
                    </div>
                </div>
                @endif
            @endif
        @endif
    </div>
</div>
</x-app-layout>