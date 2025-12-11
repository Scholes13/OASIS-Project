<x-app-layout>
<div class="min-h-screen bg-white">
    <div class="w-full">
        <!-- Simple Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Approvals</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage your purchase request approvals</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">Last updated: {{ now()->format('d M Y, H:i') }} (GMT+7)</span>
                    <button type="button" onclick="window.location.reload()" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Update
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="border-b border-gray-200 px-6 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Pending Documents -->
                <a href="{{ route('approvals.index', ['filter' => 'pending']) }}" 
                   class="relative flex items-center px-6 py-5 rounded-lg border border-gray-200 transition-all duration-200 hover:shadow-md hover:border-indigo-300 hover:bg-indigo-50 cursor-pointer {{ request('filter') === 'pending' ? 'bg-indigo-50 ring-2 ring-indigo-500 border-indigo-500' : 'bg-white' }}">
                    <svg class="absolute top-4 right-4 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <div class="w-1 h-16 bg-indigo-600 rounded-full" style="margin-right: 1rem;"></div>
                    <div>
                        <p class="text-3xl font-semibold text-indigo-600">{{ $pendingApprovals->count() }}</p>
                        <p class="text-sm text-gray-500 mt-1">Pending documents</p>
                    </div>
                </a>

                <!-- Approved Documents -->
                <a href="{{ route('approvals.index', ['filter' => 'approved']) }}" 
                   class="relative flex items-center px-6 py-5 rounded-lg border border-gray-200 transition-all duration-200 hover:shadow-md hover:border-indigo-300 hover:bg-indigo-50 cursor-pointer {{ request('filter') === 'approved' ? 'bg-indigo-50 ring-2 ring-indigo-500 border-indigo-500' : 'bg-white' }}">
                    <svg class="absolute top-4 right-4 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <div class="w-1 h-16 bg-indigo-600 rounded-full" style="margin-right: 1rem;"></div>
                    <div>
                        <p class="text-3xl font-semibold text-indigo-600">{{ $approvalStats['total_approved'] ?? 0 }}</p>
                        <p class="text-sm text-gray-500 mt-1">Approved</p>
                    </div>
                </a>

                <!-- Rejected Documents -->
                <a href="{{ route('approvals.index', ['filter' => 'rejected']) }}" 
                   class="relative flex items-center px-6 py-5 rounded-lg border border-gray-200 transition-all duration-200 hover:shadow-md hover:border-indigo-300 hover:bg-indigo-50 cursor-pointer {{ request('filter') === 'rejected' ? 'bg-indigo-50 ring-2 ring-indigo-500 border-indigo-500' : 'bg-white' }}">
                    <svg class="absolute top-4 right-4 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <div class="w-1 h-16 bg-indigo-600 rounded-full" style="margin-right: 1rem;"></div>
                    <div>
                        <p class="text-3xl font-semibold text-indigo-600">{{ $approvalStats['total_rejected'] ?? 0 }}</p>
                        <p class="text-sm text-gray-500 mt-1">Rejected</p>
                    </div>
                </a>

                <!-- Total Documents -->
                <a href="{{ route('approvals.index') }}" 
                   class="relative flex items-center px-6 py-5 rounded-lg border border-gray-200 transition-all duration-200 hover:shadow-md hover:border-indigo-300 hover:bg-indigo-50 cursor-pointer bg-white">
                    <svg class="absolute top-4 right-4 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <div class="w-1 h-16 bg-indigo-600 rounded-full" style="margin-right: 1rem;"></div>
                    <div>
                        <p class="text-3xl font-semibold text-indigo-600">{{ $pendingApprovals->count() + ($approvalStats['total_approved'] ?? 0) + ($approvalStats['total_rejected'] ?? 0) }}</p>
                        <p class="text-sm text-gray-500 mt-1">Total documents</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- All Documents Table -->
        <div class="px-6 py-4">
            @if($filteredApprovals->isEmpty())
                <div class="py-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-base font-medium text-gray-900 mb-1">No approvals found</h3>
                    <p class="text-sm text-gray-500">
                        @if($filter)
                            No {{ $filter }} approvals found. <a href="{{ route('approvals.index') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Clear filter</a>
                        @else
                            You don't have any approval requests yet.
                        @endif
                    </p>
                </div>
            @else
                <table class="min-w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document Name</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Modified</th>
                            <th scope="col" class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($filteredApprovals as $item)
                        @php
                            $pr = $item['pr'];
                            $approval = $item['approval'];
                            
                            if ($item['type'] === 'pending') {
                                $approvals = $item['approvals'];
                                $totalApprovals = $pr->approvals->count();
                                $completedApprovals = $pr->approvals->where('status', 'approved')->count();
                                $userApproval = $approvals->where('approver_id', auth()->id())->first() ?? $approval;
                                $clickUrl = route('approvals.show', $userApproval->id);
                            } else {
                                $clickUrl = route('approvals.show', $approval->id);
                                $totalApprovals = $pr->approvals->count();
                                $completedApprovals = $pr->approvals->where('status', 'approved')->count();
                            }
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location='{{ $clickUrl }}'">
                            <td class="px-3 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $pr->pr_number }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    @if($item['type'] === 'pending')
                                        To: {{ $item['approvals']->pluck('approver.name')->implode(', ') }}
                                    @else
                                        To: {{ $approval->approver->name }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-4">
                                @if($item['type'] === 'pending')
                                    <div class="text-sm text-gray-700">Pending document</div>
                                    <div class="text-xs text-gray-400 mt-0.5">{{ $completedApprovals }}/{{ $totalApprovals }} done</div>
                                @else
                                    <div class="text-sm text-gray-700">
                                        @if($pr->status === 'voided')
                                            Voided
                                        @elseif($approval->status === 'approved')
                                            Completed
                                        @else
                                            Rejected
                                        @endif
                                    </div>
                                    @if($pr->status === 'approved')
                                    <div class="text-xs text-gray-400 mt-0.5">{{ $completedApprovals }}/{{ $totalApprovals }} done</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 py-4">
                                <div class="text-sm text-gray-900">{{ $item['date'] ? $item['date']->format('d M Y, H:i') : '-' }}</div>
                            </td>
                            <td class="px-3 py-4 text-right">
                                <div class="text-sm font-medium text-gray-900">{{ $pr->currency }} {{ number_format($pr->total_amount, 0) }}</div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
