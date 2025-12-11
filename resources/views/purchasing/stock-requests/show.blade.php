@php
    use Illuminate\Support\Facades\Auth;
    
    // Status styling
    $statusStyles = [
        'draft' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'icon' => 'edit', 'label' => 'Draft'],
        'submitted' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'icon' => 'clock', 'label' => 'Submitted'],
        'in_approval' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'icon' => 'clock', 'label' => 'In Approval'],
        'approved' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'icon' => 'check', 'label' => 'Approved'],
        'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => 'x', 'label' => 'Rejected'],
        'voided' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-500', 'icon' => 'ban', 'label' => 'Voided'],
    ];
    $currentStyle = $statusStyles[$stockRequest->status] ?? $statusStyles['draft'];
    
    // Check permissions
    $user = Auth::user();
    $isOwner = $stockRequest->user_id === $user->id;
    $accessLevel = $user->getAccessLevel();
    $isAdmin = in_array($accessLevel, ['super_admin', 'executive', 'general_manager']);
    $canEdit = $stockRequest->isEditable() && $isOwner;
    $canVoid = $stockRequest->canBeVoided() && ($isOwner || $isAdmin);
    $canMarkOffline = in_array($stockRequest->status, ['submitted', 'in_approval']) && $isOwner;
    $canResubmit = $stockRequest->status === 'rejected' && $isOwner;
@endphp

<x-app-layout>
    <div class="min-h-screen bg-white" x-data="{ showVoidModal: false, showOfflineModal: false }">
        <div class="w-full">
            <!-- Header -->
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('stock-requests.index') }}" 
                           class="inline-flex items-center text-gray-500 hover:text-gray-700 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </a>
                        <div>
                            <div class="flex items-center space-x-3">
                                <h1 class="text-xl font-semibold text-gray-900">{{ $stockRequest->st_number }}</h1>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $currentStyle['bg'] }} {{ $currentStyle['text'] }}">
                                    @if($currentStyle['icon'] === 'check')
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    @elseif($currentStyle['icon'] === 'x')
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    @elseif($currentStyle['icon'] === 'clock')
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @elseif($currentStyle['icon'] === 'ban')
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                        </svg>
                                    @else
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    @endif
                                    {{ $currentStyle['label'] }}
                                </span>
                                @if($stockRequest->isOfflineApproved())
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                        </svg>
                                        Offline Approved
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 mt-0.5">{{ $stockRequest->businessUnit?->name ?? 'N/A' }} • {{ $stockRequest->department?->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-2">
                        @if($canEdit)
                            <a href="{{ route('stock-requests.edit', $stockRequest) }}" 
                               class="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit
                            </a>
                        @endif
                        
                        @if($canResubmit)
                            <form action="{{ route('stock-requests.resubmit', $stockRequest) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="inline-flex items-center px-3 py-1.5 text-sm text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50 rounded-md transition-colors">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Resubmit
                                </button>
                            </form>
                        @endif
                        
                        <a href="{{ route('stock-requests.pdf-public', $stockRequest) }}" 
                           target="_blank"
                           class="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Download PDF
                        </a>
                        
                        @if($canMarkOffline)
                            <button @click="showOfflineModal = true"
                                    class="inline-flex items-center px-3 py-1.5 text-sm text-purple-600 hover:text-purple-900 hover:bg-purple-50 rounded-md transition-colors">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                Mark Offline Approved
                            </button>
                        @endif
                        
                        @if($canVoid)
                            <button @click="showVoidModal = true"
                                    class="inline-flex items-center px-3 py-1.5 text-sm text-red-600 hover:text-red-900 hover:bg-red-50 rounded-md transition-colors">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                </svg>
                                Void
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            @if($stockRequest->status === 'rejected')
                <div class="mx-6 mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">This Stock Request was Rejected</h3>
                            <p class="text-sm text-red-700 mt-1">
                                You can edit this ST and resubmit it for approval using the "Resubmit" button above.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Session flash messages are automatically displayed as toast by layouts/app.blade.php -->

            <!-- Content Grid -->
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    <!-- Main Content (2/3) -->
                    <div class="xl:col-span-2 space-y-6">
                        <!-- Request Details Card -->
                        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-base font-semibold text-gray-900">Request Details</h3>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8">
                                    <div class="mb-6">
                                        <p class="text-sm font-medium text-gray-500">Requested By</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ $stockRequest->user?->name ?? 'N/A' }}</p>
                                    </div>
                                    <div class="mb-6">
                                        <p class="text-sm font-medium text-gray-500">Department</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ $stockRequest->department?->name ?? 'N/A' }} ({{ $stockRequest->department?->code ?? 'N/A' }})</p>
                                    </div>
                                    <div class="mb-6">
                                        <p class="text-sm font-medium text-gray-500">Date of Request</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ $stockRequest->date_of_request?->format('F j, Y') ?? 'N/A' }}</p>
                                    </div>
                                    <div class="mb-6">
                                        <p class="text-sm font-medium text-gray-500">Expected Date</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ $stockRequest->expected_date?->format('F j, Y') ?? 'Not specified' }}</p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <p class="text-sm font-medium text-gray-500">Purpose</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ $stockRequest->purpose ?? 'Not specified' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Items Table Card -->
                        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="text-base font-semibold text-gray-900">Items</h3>
                                <span class="text-sm text-gray-500">{{ $stockRequest->items->count() }} {{ Str::plural('item', $stockRequest->items->count()) }}</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        @forelse($stockRequest->items as $index => $item)
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                                                <td class="px-5 py-4">
                                                    <div class="text-sm text-gray-900">{{ $item->item_name }}</div>
                                                    @if($item->specifications)
                                                        <div class="text-sm text-gray-400 mt-1">{{ $item->specifications }}</div>
                                                    @endif
                                                </td>
                                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                    {{ number_format($item->quantity, 0) }} {{ $item->unit }}
                                                </td>
                                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                                    <span class="text-sm text-gray-500">IDR</span>
                                                    <span class="text-sm text-gray-900">{{ number_format($item->price ?? 0, 0, ',', '.') }}</span>
                                                </td>
                                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                                    <span class="text-sm text-gray-500">IDR</span>
                                                    <span class="text-sm text-gray-900">{{ number_format($item->total ?? 0, 0, ',', '.') }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500">
                                                    No items found
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <td colspan="4" class="px-5 py-4 text-right text-sm font-semibold text-gray-900">
                                                Total Amount
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap text-right">
                                                <span class="text-sm text-gray-900">IDR</span>
                                                <span class="text-base font-semibold text-gray-900">{{ number_format($stockRequest->items->sum('total') ?? 0, 0, ',', '.') }}</span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar (1/3) -->
                    <div class="space-y-6">
                        <!-- Approval Progress Card -->
                        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-base font-semibold text-gray-900">Approval Progress</h3>
                            </div>
                            <div class="p-5">
                                @if($stockRequest->approvals->count() > 0)
                                    <div class="space-y-0">
                                        @foreach($stockRequest->approvals as $approval)
                                            <div class="flex items-start gap-3 pb-6 last:pb-0">
                                                <!-- Step Indicator -->
                                                <div class="flex-shrink-0 relative">
                                                    @if($approval->status === 'approved')
                                                        <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"></path>
                                                            </svg>
                                                        </div>
                                                    @elseif($approval->status === 'rejected')
                                                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                        </div>
                                                    @elseif($approval->status === 'pending')
                                                        <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                        </div>
                                                    @else
                                                        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                                                            <div class="w-2 h-2 rounded-full bg-gray-400"></div>
                                                        </div>
                                                    @endif
                                                    
                                                    <!-- Connector Line -->
                                                    @if(!$loop->last)
                                                        <div class="absolute left-1/2 top-8 w-0.5 h-6 -translate-x-1/2 {{ $approval->status === 'approved' ? 'bg-emerald-200' : ($approval->status === 'rejected' ? 'bg-red-200' : 'bg-gray-200') }}"></div>
                                                    @endif
                                                </div>
                                                
                                                <!-- Content -->
                                                <div class="flex-1 min-w-0 pt-1">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <span class="text-sm font-medium text-gray-900 truncate">{{ $approval->approver?->name ?? 'Unknown' }}</span>
                                                        <span class="flex-shrink-0 text-xs px-2 py-0.5 rounded-full 
                                                            @if($approval->status === 'approved') bg-emerald-100 text-emerald-700
                                                            @elseif($approval->status === 'rejected') bg-red-100 text-red-700
                                                            @elseif($approval->status === 'pending') bg-amber-100 text-amber-700
                                                            @else bg-gray-100 text-gray-600 @endif">
                                                            {{ ucfirst($approval->status) }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-gray-500 mt-0.5">{{ $approval->approval_type ?? 'Approver' }} - Step {{ $approval->step_order }}</p>
                                                    @if($approval->responded_at)
                                                        <p class="text-xs text-gray-400 mt-1">{{ $approval->responded_at->format('M j, Y H:i') }}</p>
                                                    @endif
                                                    @if($approval->notes)
                                                        <p class="text-xs text-gray-600 mt-2 p-2 bg-gray-50 rounded">{{ $approval->notes }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-6">
                                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <p class="text-sm text-gray-500">No approval workflow</p>
                                        <p class="text-xs text-gray-400 mt-1">Submit this ST to start approval</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Timestamps Card -->
                        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-base font-semibold text-gray-900">Timeline</h3>
                            </div>
                            <div class="p-5 space-y-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Created</span>
                                    <span class="text-gray-900">{{ $stockRequest->created_at?->format('M j, Y H:i') ?? 'N/A' }}</span>
                                </div>
                                @if($stockRequest->submitted_at)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">Submitted</span>
                                        <span class="text-gray-900">{{ $stockRequest->submitted_at->format('M j, Y H:i') }}</span>
                                    </div>
                                @endif
                                @if($stockRequest->approved_at)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-emerald-600">Approved</span>
                                        <span class="text-gray-900">{{ $stockRequest->approved_at->format('M j, Y H:i') }}</span>
                                    </div>
                                @endif
                                @if($stockRequest->rejected_at)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-red-600">Rejected</span>
                                        <span class="text-gray-900">{{ $stockRequest->rejected_at->format('M j, Y H:i') }}</span>
                                    </div>
                                @endif
                                @if($stockRequest->voided_at)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">Voided</span>
                                        <span class="text-gray-900">{{ $stockRequest->voided_at->format('M j, Y H:i') }}</span>
                                    </div>
                                @endif
                                @if($stockRequest->last_modified_by && $stockRequest->lastModifiedBy)
                                    <div class="flex items-center justify-between text-sm pt-2 border-t border-gray-100">
                                        <span class="text-gray-500">Last Modified By</span>
                                        <span class="text-gray-900">{{ $stockRequest->lastModifiedBy->name }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Void Modal -->
        <template x-teleport="body">
            <div x-show="showVoidModal" 
                 x-cloak
                 class="fixed inset-0 z-[9999]"
                 aria-labelledby="void-modal-title" 
                 role="dialog" 
                 aria-modal="true">
                <!-- Backdrop -->
                <div x-show="showVoidModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
                     @click="showVoidModal = false"></div>

                <!-- Modal Panel - True Center -->
                <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
                    <div x-show="showVoidModal"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         style="width: 100%; max-width: 28rem;"
                         class="relative transform overflow-hidden rounded-xl bg-white shadow-xl transition-all">
                        <form action="{{ route('stock-requests.void', $stockRequest) }}" method="POST">
                            @csrf
                            <!-- Body -->
                            <div class="bg-white px-5 py-4">
                                <div class="flex items-start">
                                    <div style="width: 2.5rem; height: 2.5rem;" class="flex flex-shrink-0 items-center justify-center rounded-full bg-red-100">
                                        <svg style="width: 1.25rem; height: 1.25rem;" class="text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 style="font-size: 1rem;" class="font-semibold text-gray-900" id="void-modal-title">Void Stock Request</h3>
                                        <p style="font-size: 0.8125rem;" class="mt-1 text-gray-500">
                                            Are you sure you want to void <strong>{{ $stockRequest->st_number }}</strong>? This action cannot be undone.
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label for="void_reason" style="font-size: 0.8125rem;" class="block font-medium text-gray-700">Reason for voiding <span class="text-red-500">*</span></label>
                                    <textarea name="void_reason" id="void_reason" rows="2" required
                                              style="font-size: 0.8125rem;"
                                              class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                              placeholder="Please provide a reason for voiding this stock request..."></textarea>
                                </div>
                            </div>
                            
                            <!-- Footer -->
                            <div class="bg-gray-50 px-5 py-3 flex justify-end gap-2">
                                <button type="button" 
                                        @click="showVoidModal = false"
                                        style="font-size: 0.8125rem; padding: 0.5rem 1rem;"
                                        class="rounded-lg border border-gray-300 bg-white font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="submit"
                                        style="background-color: #dc2626 !important; color: #ffffff !important; font-size: 0.8125rem; padding: 0.5rem 1rem;"
                                        class="rounded-lg font-medium shadow-sm hover:opacity-90">
                                    Void Stock Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>

        <!-- Offline Approval Modal -->
        <template x-teleport="body">
            <div x-show="showOfflineModal" 
                 x-cloak
                 class="fixed inset-0 z-[9999]"
                 aria-labelledby="modal-title" 
                 role="dialog" 
                 aria-modal="true">
                <!-- Backdrop -->
                <div x-show="showOfflineModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
                     @click="showOfflineModal = false"></div>

                <!-- Modal Panel - True Center -->
                <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
                    <div x-show="showOfflineModal"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         style="width: 100%; max-width: 28rem;"
                             class="relative transform overflow-hidden rounded-xl bg-white shadow-xl transition-all mx-auto">
                            <form action="{{ route('stock-requests.mark-offline-approved', $stockRequest) }}" method="POST">
                                @csrf
                                <!-- Body -->
                                <div class="bg-white px-5 py-4">
                                    <div class="flex items-start">
                                        <div style="width: 2.5rem; height: 2.5rem;" class="flex flex-shrink-0 items-center justify-center rounded-full bg-purple-100">
                                            <svg style="width: 1.25rem; height: 1.25rem;" class="text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 style="font-size: 1rem;" class="font-semibold text-gray-900" id="modal-title">Mark as Offline Approved</h3>
                                            <p style="font-size: 0.8125rem;" class="mt-1 text-gray-500">
                                                Use this when the ST has been approved manually/offline (e.g., signed paper copy).
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 rounded-lg bg-amber-50 border border-amber-200 p-2.5">
                                        <div class="flex">
                                            <svg style="width: 1rem; height: 1rem;" class="flex-shrink-0 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                            <p style="font-size: 0.75rem;" class="ml-2 text-amber-700">
                                                <strong>Note:</strong> This will skip the digital approval workflow. The ST status will show as "Approved".
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <label for="offline_notes" style="font-size: 0.8125rem;" class="block font-medium text-gray-700">Notes (optional)</label>
                                        <textarea name="notes" id="offline_notes" rows="2"
                                                  style="font-size: 0.8125rem;"
                                                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                                  placeholder="Add any notes about the offline approval..."></textarea>
                                    </div>
                                </div>
                                
                                <!-- Footer -->
                                <div class="bg-gray-50 px-5 py-3 flex justify-end gap-2">
                                    <button type="button" 
                                            @click="showOfflineModal = false"
                                            style="font-size: 0.8125rem; padding: 0.5rem 1rem;"
                                            class="rounded-lg border border-gray-300 bg-white font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                            style="background-color: #9333ea !important; color: #ffffff !important; font-size: 0.8125rem; padding: 0.5rem 1rem;"
                                            class="rounded-lg font-medium shadow-sm hover:opacity-90">
                                        Confirm Offline Approval
                                    </button>
                                </div>
                            </form>
                        </div>
                </div>
            </div>
        </template>

    </div>
</x-app-layout>
