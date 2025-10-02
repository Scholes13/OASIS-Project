<x-app-layout>
<div class="min-h-screen bg-gray-50 py-8">
    <div class="w-full">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Purchase Request Approval</h1>
                    <p class="mt-2 text-gray-600">Review and process purchase request approval</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">PR Number</div>
                    <div class="text-2xl font-bold text-indigo-600">{{ $approval->purchaseRequest->pr_number }}</div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="text-green-700">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-red-700">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Request Information -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Request Information</h2>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Requestor</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $approval->purchaseRequest->user->name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Department</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $approval->purchaseRequest->department->name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Business Unit</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $approval->purchaseRequest->businessUnit->name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Request Date</label>
                                <p class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($approval->purchaseRequest->date_of_request)->format('d/m/Y') }}</p>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Purpose</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $approval->purchaseRequest->keperluan }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Used For</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $approval->purchaseRequest->used_for }}</p>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Items</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($approval->purchaseRequest->items as $item)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $item->item_name }}</div>
                                            @if($item->item_description)
                                                <div class="text-sm text-gray-500">{{ $item->item_description }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $item->brand_name ?: '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $item->quantity }} {{ $item->unit }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $item->currency }} {{ number_format($item->unit_price, 0) }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item->currency }} {{ number_format($item->quantity * $item->unit_price, 0) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-right text-sm font-medium text-gray-900">Grand Total:</td>
                                    <td class="px-6 py-4 text-sm font-bold text-indigo-600">{{ $approval->purchaseRequest->currency }} {{ number_format($approval->purchaseRequest->total_amount, 0) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Approval Workflow -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Approval Workflow</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div class="space-y-4">
                            <!-- Requestor -->
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3 flex-1">
                                    <div class="text-sm font-medium text-gray-900">{{ $approval->purchaseRequest->user->name }}</div>
                                    <div class="text-xs text-gray-500">Requestor</div>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Submitted
                                    </span>
                                </div>
                            </div>

                            <!-- Approval Steps -->
                            @foreach($approval->purchaseRequest->approvals->sortBy('step_order') as $step)
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 {{ $step->status === 'approved' ? 'bg-green-100' : ($step->status === 'rejected' ? 'bg-red-100' : ($step->id === $approval->id ? 'bg-yellow-100' : 'bg-gray-100')) }} rounded-full flex items-center justify-center">
                                        @if($step->status === 'approved')
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        @elseif($step->status === 'rejected')
                                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        @else
                                            <div class="w-2 h-2 {{ $step->id === $approval->id ? 'bg-yellow-600' : 'bg-gray-400' }} rounded-full"></div>
                                        @endif
                                    </div>
                                </div>
                                <div class="ml-3 flex-1">
                                    <div class="text-sm font-medium text-gray-900">{{ $step->approver->name }}</div>
                                    <div class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $step->approval_type ?? 'Approver')) }}</div>
                                    @if($step->responded_at)
                                        <div class="text-xs text-gray-400">{{ $step->responded_at->format('d/m/Y H:i') }}</div>
                                    @endif
                                </div>
                                <div class="flex-shrink-0">
                                    @if($step->status === 'approved')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Approved
                                        </span>
                                    @elseif($step->status === 'rejected')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Rejected
                                        </span>
                                    @elseif($step->id === $approval->id)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Current
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Pending
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Approval Actions -->
                @if($canApprove && $approval->status === 'pending')
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Your Action Required</h2>
                    </div>
                    <div class="px-6 py-4">
                        <form action="{{ route('approvals.process', $approval->id) }}" method="POST" class="space-y-4">
                            @csrf
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                                <textarea name="notes" rows="3" 
                                    class="w-full border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Add any comments or notes..."></textarea>
                            </div>

                            <div class="flex space-x-3">
                                <button type="submit" name="action" value="approved"
                                    class="flex-1 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Approve
                                </button>
                                
                                <button type="submit" name="action" value="rejected"
                                    class="flex-1 bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Reject
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                @endif

                <!-- View PDF Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Purchase Request Document</h2>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-sm text-gray-600 mb-4">View the complete purchase request document in PDF format</p>
                        
                        <a href="{{ route('purchase-requests.pdf', $approval->purchaseRequest->id) }}" 
                           target="_blank"
                           class="inline-flex items-center justify-center w-full px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            View PDF Document
                        </a>
                    </div>
                </div>

                <!-- QR Code Section -->
                @if($approval->status === 'approved')
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Digital Signature</h2>
                    </div>
                    <div class="px-6 py-4 text-center">
                        <div class="mb-4">
                            <img src="{{ route('approvals.qr-code', $approval->id) }}" 
                                 alt="QR Code for Approval Verification" 
                                 class="mx-auto w-32 h-32 border border-gray-200 rounded">
                        </div>
                        <p class="text-sm text-gray-600 mb-2">Scan QR code to verify this approval</p>
                        <p class="text-xs text-gray-500 mb-4">This QR code is unique to your approval and cannot be replicated</p>
                        
                        @php
                            $qrCodeService = new \App\Services\QrCodeService();
                            $publicUrl = $qrCodeService->generatePublicVerificationUrl($approval);
                        @endphp
                        
                        <a href="{{ $publicUrl }}" 
                           target="_blank"
                           class="inline-flex items-center justify-center w-full px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            View Public Verification
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
</x-app-layout>