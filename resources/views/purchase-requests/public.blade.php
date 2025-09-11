<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PR Verification - {{ $purchaseRequest->pr_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Purchase Request Verification</h1>
                <p class="mt-2 text-gray-600">This document has been digitally verified</p>
            </div>

            <!-- Verification Badge -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <div>
                        @if($verificationData['type'] === 'requestor')
                            <p class="text-green-800 font-medium">Verified Request Creator</p>
                            <p class="text-green-700 text-sm">
                                Created by {{ $verificationData['verified_by']->name }} on {{ $verificationData['verified_at']->format('d F Y, H:i') }}
                            </p>
                        @else
                            <p class="text-green-800 font-medium">Verified {{ $verificationData['role'] }}</p>
                            <p class="text-green-700 text-sm">
                                Approved by {{ $verificationData['verified_by']->name }} on {{ $verificationData['verified_at']->format('d F Y, H:i') }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Purchase Request Details -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-900">Purchase Request Details</h2>
                        <span class="text-2xl font-bold text-indigo-600">{{ $purchaseRequest->pr_number }}</span>
                    </div>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Requestor</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $purchaseRequest->user->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Department</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $purchaseRequest->department->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Business Unit</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $purchaseRequest->businessUnit->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Request Date</label>
                            <p class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($purchaseRequest->date_of_request)->format('d F Y') }}</p>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700">Purpose</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $purchaseRequest->keperluan }}</p>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700">Used For</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $purchaseRequest->used_for }}</p>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Items</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($purchaseRequest->items as $item)
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
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $item->supplier_name ?: '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $item->quantity }} {{ $item->unit }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $item->currency }} {{ number_format($item->unit_price, 0) }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item->currency }} {{ number_format($item->quantity * $item->unit_price, 0) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-right text-sm font-medium text-gray-900">Grand Total:</td>
                                <td class="px-6 py-4 text-sm font-bold text-indigo-600">{{ $purchaseRequest->currency }} {{ number_format($purchaseRequest->total_amount, 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Approval Workflow -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Approval Workflow</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-4">
                        <!-- Requestor -->
                        <div class="flex items-center {{ $verificationData['type'] === 'requestor' ? 'bg-green-50 -mx-2 px-2 py-2 rounded-lg' : '' }}">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $purchaseRequest->user->name }}
                                    @if($verificationData['type'] === 'requestor')
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Verified Creator
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500">Requestor</div>
                                <div class="text-xs text-gray-400">{{ $purchaseRequest->submitted_at ? $purchaseRequest->submitted_at->format('d/m/Y H:i') : 'N/A' }}</div>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Submitted
                                </span>
                            </div>
                        </div>

                        <!-- Approval Steps -->
                        @foreach($purchaseRequest->approvals->sortBy('step_order') as $step)
                        <div class="flex items-center {{ (isset($verificationData['approval']) && $step->id === $verificationData['approval']->id) ? 'bg-green-50 -mx-2 px-2 py-2 rounded-lg' : '' }}">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 {{ $step->status === 'approved' ? 'bg-green-100' : ($step->status === 'rejected' ? 'bg-red-100' : 'bg-gray-100') }} rounded-full flex items-center justify-center">
                                    @if($step->status === 'approved')
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    @elseif($step->status === 'rejected')
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    @else
                                        <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                    @endif
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $step->approver->name }}
                                    @if(isset($verificationData['approval']) && $step->id === $verificationData['approval']->id)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Verified Approver
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $step->approval_type ?? 'Approver')) }}</div>
                                @if($step->responded_at)
                                    <div class="text-xs text-gray-400">{{ $step->responded_at->format('d/m/Y H:i') }}</div>
                                @endif
                                @if($step->notes)
                                    <div class="text-xs text-gray-600 mt-1 italic">"{{ $step->notes }}"</div>
                                @endif
                            </div>
                            <div class="flex-shrink-0">
                                @if($step->status === 'approved')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Approved
                                    </span>
                                @elseif($step->status === 'rejected')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Rejected
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Pending
                                    </span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Verification Footer -->
            <div class="text-center text-sm text-gray-500">
                <p>This document was verified on {{ now()->format('d F Y, H:i') }}</p>
                <p class="mt-1">Digital verification ensures authenticity and prevents tampering</p>
            </div>
        </div>
    </div>
</body>
</html>