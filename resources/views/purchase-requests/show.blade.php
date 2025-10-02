@php
    use Illuminate\Support\Facades\Auth;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Purchase Request: {{ $purchaseRequest->pr_number }}</h1>
                <p class="text-sm text-gray-600 mt-1">Created by {{ $purchaseRequest->user->name }} on {{ $purchaseRequest->created_at->format('M d, Y') }}</p>
            </div>
            <div class="flex items-center space-x-3">
                {{-- Debug Info --}}
                @if(config('app.debug'))
                    <span class="text-xs text-gray-500">
                        Status: {{ $purchaseRequest->status }} | 
                        Owner: {{ $purchaseRequest->user_id }} | 
                        Current: {{ Auth::id() }} |
                        Can Edit: {{ $purchaseRequest->canBeEdited() ? 'YES' : 'NO' }}
                    </span>
                @endif
                
                @if($purchaseRequest->canBeEdited() && $purchaseRequest->user_id === Auth::id())
                    <a href="{{ route('purchase-requests.edit', $purchaseRequest) }}" 
                       wire:navigate
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </a>
                @endif
                
                @if($purchaseRequest->canBeSubmitted() && $purchaseRequest->user_id === Auth::id())
                    <form method="POST" action="{{ route('purchase-requests.submit', $purchaseRequest) }}" class="inline">
                        @csrf
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                                onclick="return confirm('Are you sure you want to submit this purchase request for approval?')">
                            <svg class="w-4 h-4 mr-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Submit for Approval
                        </button>
                    </form>
                @endif

                @if($purchaseRequest->status === 'rejected' && $purchaseRequest->user_id === Auth::id())
                    <form method="POST" action="{{ route('purchase-requests.resubmit', $purchaseRequest) }}" class="inline">
                        @csrf
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors duration-200"
                                onclick="return confirm('This will reset the approval workflow and resubmit for approval. Continue?')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Resubmit for Approval
                        </button>
                    </form>
                @endif
                
                <!-- PDF Actions -->
                <a href="{{ route('purchase-requests.pdf', $purchaseRequest) }}" 
                   target="_blank"
                   class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-lg text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    View PDF
                </a>
                
                <a href="{{ route('purchase-requests.download-pdf', $purchaseRequest) }}" 
                   class="inline-flex items-center px-4 py-2 border border-green-300 rounded-lg text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download PDF
                </a>
                
                <a href="{{ route('purchase-requests.index') }}" 
                   wire:navigate
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to List
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
                <a href="{{ route('purchase-requests.index') }}" wire:navigate class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">
                    Purchase Requests
                </a>
            </div>
        </li>
        <li class="flex">
            <div class="flex items-center">
                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="ml-4 text-sm font-medium text-gray-500">{{ $purchaseRequest->pr_number }}</span>
            </div>
        </li>
    </x-slot>

    <!-- Purchase Request Details -->
    <div class="w-full space-y-6">
        <!-- Action Buttons Section -->
        @if($purchaseRequest->status === 'rejected' && $purchaseRequest->user_id === Auth::id())
            <div class="bg-orange-50 border-l-4 border-orange-400 p-6 rounded-lg">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-lg font-medium text-orange-800">
                            This Purchase Request Was Rejected
                        </h3>
                        <p class="mt-2 text-sm text-orange-700">
                            You can edit the request and resubmit it for approval. The approval workflow will be completely reset.
                        </p>
                        <div class="mt-4 flex items-center space-x-3">
                            <a href="{{ route('purchase-requests.edit', $purchaseRequest) }}" 
                               class="inline-flex items-center px-4 py-2 border border-orange-300 rounded-lg text-sm font-medium text-orange-700 bg-white hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit Request
                            </a>
                            <form method="POST" action="{{ route('purchase-requests.resubmit', $purchaseRequest) }}" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors duration-200"
                                        onclick="return confirm('This will reset the approval workflow and resubmit for approval. Continue?')">
                                    <svg class="w-4 h-4 mr-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Resubmit for Approval
                                </button>
                            </form>
                        </div>
                        
                        {{-- Debug Info --}}
                        @if(config('app.debug'))
                            <div class="mt-3 text-xs text-orange-600 font-mono bg-orange-100 p-2 rounded">
                                Debug: Status={{ $purchaseRequest->status }} | Owner={{ $purchaseRequest->user_id }} | Current={{ Auth::id() }} | CanEdit={{ $purchaseRequest->canBeEdited() ? 'YES' : 'NO' }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
        
        <!-- PR Header Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Purchase Request Information</h3>
                    @php
                        $statusConfig = [
                            'draft' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => 'Draft'],
                            'submitted' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Submitted'],
                            'in_approval' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'In Approval'],
                            'approved' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Approved'],
                            'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Rejected'],
                            'voided' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => 'Voided'],
                        ];
                        $config = $statusConfig[$purchaseRequest->status] ?? $statusConfig['draft'];
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $config['bg'] }} {{ $config['text'] }}">
                        {{ $config['label'] }}
                    </span>
                </div>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">PR Number</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $purchaseRequest->pr_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Requestor</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $purchaseRequest->user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Department</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $purchaseRequest->department->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Date of Request</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $purchaseRequest->date_of_request->format('M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Total Amount</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ $purchaseRequest->currency }} {{ number_format($purchaseRequest->total_amount, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $purchaseRequest->created_at->format('M d, Y H:i') }}</dd>
                    </div>
                </div>
                
                <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Purpose / Requirements</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $purchaseRequest->keperluan }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Detailed Description</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $purchaseRequest->used_for }}</dd>
                    </div>
                </div>
                
                <!-- PDF Export Section -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">📄 Export Options</h4>
                            <p class="text-sm text-gray-500">Download or view this purchase request as PDF document</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <a href="{{ route('purchase-requests.pdf', $purchaseRequest) }}" 
                               target="_blank"
                               class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-lg text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                View PDF
                            </a>
                            
                            <a href="{{ route('purchase-requests.download-pdf', $purchaseRequest) }}" 
                               class="inline-flex items-center px-4 py-2 border border-green-300 rounded-lg text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PR Items -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Purchase Items</h3>
                <p class="text-sm text-gray-600 mt-1">{{ $purchaseRequest->items->count() }} item(s) in this purchase request</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expense Dept</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($purchaseRequest->items as $item)
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $item->item_order }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $item->item_name }}</div>
                                    @if($item->brand_name)
                                        <div class="text-sm text-gray-500">Brand: {{ $item->brand_name }}</div>
                                    @endif
                                    @if($item->item_description)
                                        <div class="text-sm text-gray-500 mt-1">{{ $item->item_description }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $item->supplier_name ?: 'Not specified' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($item->quantity, 2) }} {{ $item->unit }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $item->currency }} {{ number_format($item->unit_price, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $item->currency }} {{ number_format($item->total_price, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $item->expenseDepartment->name ?? 'N/A' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                Total Amount:
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                {{ $purchaseRequest->currency }} {{ number_format($purchaseRequest->total_amount, 2) }}
                            </td>
                            <td class="px-6 py-4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Approval Status -->
        @if($purchaseRequest->approvals->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Approval Status</h3>
                    <p class="text-sm text-gray-600 mt-1">Track the approval progress of this purchase request</p>
                </div>
                
                <div class="p-6">
                    <div class="flow-root">
                        <ul class="-mb-8">
                            @foreach($purchaseRequest->approvals as $index => $approval)
                                <li>
                                    <div class="relative pb-8">
                                        @if(!$loop->last)
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                @php
                                                    $iconConfig = [
                                                        'pending' => ['bg' => 'bg-gray-500', 'icon' => 'clock'],
                                                        'approved' => ['bg' => 'bg-green-500', 'icon' => 'check'],
                                                        'rejected' => ['bg' => 'bg-red-500', 'icon' => 'x'],
                                                    ];
                                                    $config = $iconConfig[$approval->status] ?? $iconConfig['pending'];
                                                @endphp
                                                <span class="h-8 w-8 rounded-full {{ $config['bg'] }} flex items-center justify-center ring-8 ring-white">
                                                    @if($config['icon'] === 'check')
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    @elseif($config['icon'] === 'x')
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    @else
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm text-gray-500">
                                                        Step {{ $approval->step_order }}: 
                                                        <span class="font-medium text-gray-900">{{ $approval->approver->name ?? 'Unknown Approver' }}</span>
                                                        @if($approval->status === 'approved')
                                                            approved this request
                                                        @elseif($approval->status === 'rejected')
                                                            rejected this request
                                                        @else
                                                            is reviewing this request
                                                        @endif
                                                    </p>
                                                    @if($approval->notes)
                                                        <p class="text-sm text-gray-700 mt-1">{{ $approval->notes }}</p>
                                                    @endif
                                                </div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                    @if($approval->responded_at)
                                                        <time>{{ $approval->responded_at->format('M d, Y H:i') }}</time>
                                                    @elseif($approval->assigned_at)
                                                        <time>Assigned {{ $approval->assigned_at->format('M d, Y') }}</time>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Action Buttons for Special Cases -->
        @if($purchaseRequest->canBeVoided() && ($purchaseRequest->user_id === Auth::id() || in_array(session('current_user_role'), ['admin', 'manager'])))
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Danger Zone</h3>
                    
                    <form method="POST" action="{{ route('purchase-requests.void', $purchaseRequest) }}" class="inline">
                        @csrf
                        <div class="mb-4">
                            <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">Reason for voiding</label>
                            <textarea name="reason" id="reason" rows="3" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      placeholder="Please provide a reason for voiding this purchase request..."></textarea>
                        </div>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-red-300 rounded-lg text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
                                onclick="return confirm('Are you sure you want to void this purchase request? This action cannot be undone.')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Void Purchase Request
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>