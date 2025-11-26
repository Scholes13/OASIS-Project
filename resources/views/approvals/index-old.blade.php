<x-app-layout>
<div class="min-h-screen bg-gray-50">
    <div class="w-full">
        <!-- Compact Header -->
        <div class="bg-white border-b border-gray-200 px-6 py-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Approvals</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage your purchase request approvals</p>
                </div>
                <button type="button" onclick="window.location.reload()" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Update
                </button>
            </div>
        </div>

        <div class="px-6">
            <!-- Compact Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <!-- Pending -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-sm transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm text-gray-600 mb-1">Pending</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $pendingApprovals->count() }}</p>
                        </div>
                        <div class="w-10 h-10 bg-yellow-50 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Approved -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-sm transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm text-gray-600 mb-1">Approved</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $approvalStats['total_approved'] ?? 0 }}</p>
                        </div>
                        <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Rejected -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-sm transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm text-gray-600 mb-1">Rejected</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $approvalStats['total_rejected'] ?? 0 }}</p>
                        </div>
                        <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Approval Rate -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-sm transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm text-gray-600 mb-1">Approval Rate</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $approvalStats['approval_rate'] ?? 0 }}%</p>
                        </div>
                        <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Simplified Tabs with Compact Design -->
            <div class="bg-white rounded-lg border border-gray-200 mb-4">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px" aria-label="Tabs">
                        <a href="{{ route('approvals.index', ['tab' => 'pending']) }}" 
                           class="group inline-flex items-center px-6 py-3 border-b-2 font-medium text-sm transition-colors {{ $tab === 'pending' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            <svg class="w-4 h-4 mr-2 {{ $tab === 'pending' ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Pending Approvals
                            @if($pendingApprovals->count() > 0)
                                <span class="ml-2 py-0.5 px-2 rounded-full text-xs font-medium {{ $tab === 'pending' ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-600' }}">{{ $pendingApprovals->count() }}</span>
                            @endif
                        </a>
                        <a href="{{ route('approvals.index', ['tab' => 'history']) }}" 
                           class="group inline-flex items-center px-6 py-3 border-b-2 font-medium text-sm transition-colors {{ $tab === 'history' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            <svg class="w-4 h-4 mr-2 {{ $tab === 'history' ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Approval History
                            @if($approvalHistory->total() > 0)
                                <span class="ml-2 py-0.5 px-2 rounded-full text-xs font-medium {{ $tab === 'history' ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-600' }}">{{ $approvalHistory->total() }}</span>
                            @endif
                        </a>
                    </nav>
                </div>

                <!-- Tab Content with Minimalist Design -->
                @if($tab === 'pending')
                    <!-- Pending Approvals Tab -->
                    @if($pendingApprovals->isEmpty())
                        <!-- Minimal Empty State -->
                        <div class="p-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-base font-medium text-gray-900 mb-1">No pending approvals</h3>
                            <p class="text-sm text-gray-500">You're all caught up! No purchase requests waiting for approval.</p>
                        </div>
                    @else
                        <!-- Compact List with Hover States -->
                        <div class="divide-y divide-gray-200">
                            @foreach($pendingApprovals as $approval)
                            <div class="p-4 hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ route('approvals.show', $approval->id) }}'">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <h3 class="text-sm font-semibold text-gray-900">{{ $approval->purchaseRequest->pr_number }}</h3>
                                            @if($approval->due_date->isPast())
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                    Overdue
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-500">To: {{ implode(', ', array_slice(explode(',', $approval->purchaseRequest->items->pluck('item_name')->take(2)->implode(', ')), 0, 50)) }}@if($approval->purchaseRequest->items->count() > 2), +{{ $approval->purchaseRequest->items->count() - 2 }} more@endif</p>
                                    </div>
                                    <div class="text-right ml-4 flex-shrink-0">
                                        <p class="text-sm font-semibold text-gray-900">{{ $approval->purchaseRequest->currency }} {{ number_format($approval->purchaseRequest->total_amount, 0) }}</p>
                                        <p class="text-xs text-gray-500">{{ $approval->purchaseRequest->department->name }}</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between text-xs text-gray-500">
                                    <div class="flex items-center gap-4">
                                        <span class="flex items-center">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            {{ $approval->purchaseRequest->user->name }}
                                        </span>
                                        <span class="flex items-center">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            {{ \Carbon\Carbon::parse($approval->purchaseRequest->date_of_request)->format('d M Y') }}
                                        </span>
                                    </div>
                                    <span class="text-gray-400">Assigned {{ $approval->assigned_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                @elseif($tab === 'history')
                    <!-- Approval History Tab -->
                    @if($approvalHistory->isEmpty())
                        <!-- Minimal Empty State -->
                        <div class="p-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <h3 class="text-base font-medium text-gray-900 mb-1">No approval history</h3>
                            <p class="text-sm text-gray-500">You haven't processed any approvals yet.</p>
                        </div>
                    @else
                        <!-- Compact History List -->
                        <div class="divide-y divide-gray-200">
                            @foreach($approvalHistory as $approval)
                            <div class="p-4 hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ route('approvals.show', $approval->id) }}'">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <h3 class="text-sm font-semibold text-gray-900">{{ $approval->purchaseRequest->pr_number }}</h3>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $approval->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($approval->status) }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 truncate">{{ $approval->purchaseRequest->keperluan }}</p>
                                    </div>
                                    <div class="text-right ml-4 flex-shrink-0">
                                        <p class="text-sm font-semibold text-gray-900">{{ $approval->purchaseRequest->currency }} {{ number_format($approval->purchaseRequest->total_amount, 0) }}</p>
                                        <p class="text-xs text-gray-500">{{ $approval->purchaseRequest->department->name }}</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between text-xs text-gray-500">
                                    <div class="flex items-center gap-4">
                                        <span class="flex items-center">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            {{ $approval->purchaseRequest->user->name }}
                                        </span>
                                        <span class="flex items-center">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ $approval->responded_at->format('d M Y, H:i') }}
                                        </span>
                                    </div>
                                    <span class="text-gray-400">Processed {{ $approval->responded_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            <!-- Pagination (Outside white box, minimalist style) -->
            @if($tab === 'pending' && $pendingApprovals->hasPages())
            <div class="mt-4 flex items-center justify-between px-4 py-3">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-medium">{{ $pendingApprovals->firstItem() ?? 0 }}</span> to <span class="font-medium">{{ $pendingApprovals->lastItem() ?? 0 }}</span> of <span class="font-medium">{{ $pendingApprovals->total() }}</span>
                </div>
                <div>
                    {{ $pendingApprovals->appends(['tab' => 'pending'])->links() }}
                </div>
            </div>
            @endif

            @if($tab === 'history' && $approvalHistory->hasPages())
            <div class="mt-4 flex items-center justify-between px-4 py-3">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-medium">{{ $approvalHistory->firstItem() ?? 0 }}</span> to <span class="font-medium">{{ $approvalHistory->lastItem() ?? 0 }}</span> of <span class="font-medium">{{ $approvalHistory->total() }}</span>
                </div>
                <div>
                    {{ $approvalHistory->appends(['tab' => 'history'])->links() }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
</x-app-layout>