<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PR Verification - {{ $purchaseRequest->pr_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');
        
        * {
            box-sizing: border-box;
        }
        
        html {
            font-size: 100%; /* 16px base */
        }
        
        body {
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem; /* 12px */
            line-height: 1.4;
        }
        
        .token-hash {
            font-family: 'Courier New', monospace;
            word-break: break-all;
            font-size: 0.625rem; /* 10px */
        }
        
        @media (max-width: 640px) {
            html {
                font-size: 87.5%; /* 14px base on mobile */
            }
        }
        
        @media (min-width: 1024px) {
            html {
                font-size: 112.5%; /* 18px base on large screens */
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-6 sm:py-8">
        <div class="max-w-4xl mx-auto px-3 sm:px-4">
            <!-- Compact Header -->
            <div class="bg-white border border-gray-200 mb-4">
                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                    <p class="text-xs font-medium text-gray-900">
                        PR: <span class="font-bold">{{ $purchaseRequest->pr_number }}</span>
                    </p>
                </div>
                
                <div class="px-4 py-3 bg-green-50">
                    @if($verificationData['type'] === 'requestor')
                        <p class="text-xs text-gray-700">
                            Created by <strong>{{ $verificationData['verified_by']->name }}</strong>
                        </p>
                        <p class="text-xs text-gray-600 mt-0.5">
                            {{ $verificationData['verified_at']->format('d/m/Y H:i') }} WIB
                        </p>
                    @else
                        <p class="text-xs text-gray-700">
                            Approved by <strong>{{ $verificationData['verified_by']->name }}</strong>
                        </p>
                        <p class="text-xs text-gray-600 mt-0.5">
                            {{ $verificationData['verified_at']->format('d/m/Y H:i') }} WIB
                        </p>
                    @endif
                </div>
                
                <div class="px-4 py-3 border-t border-gray-200">
                    <p class="text-xs font-medium text-gray-500 mb-1">Digital Hash</p>
                    <div class="bg-gray-50 border border-gray-200 px-3 py-2">
                        <p class="token-hash text-xs text-gray-700 leading-relaxed">
                            {{ request()->get('token') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Purchase Request Details -->
            <div class="bg-white border border-gray-200 mb-4">
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-2">
                    <h2 class="text-xs font-semibold text-gray-900">PR Information</h2>
                </div>
                <div class="px-4 py-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-0.5">Requestor</label>
                            <p class="text-xs text-gray-900">{{ $purchaseRequest->user->name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-0.5">Department</label>
                            <p class="text-xs text-gray-900">{{ $purchaseRequest->department->name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-0.5">Business Unit</label>
                            <p class="text-xs text-gray-900">{{ $purchaseRequest->businessUnit->name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-0.5">Request Date</label>
                            <p class="text-xs text-gray-900">{{ \Carbon\Carbon::parse($purchaseRequest->date_of_request)->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-3 mb-3">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Purpose</label>
                        <p class="text-xs text-gray-900 leading-relaxed">{{ $purchaseRequest->keperluan }}</p>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-3">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Used For</label>
                        <p class="text-xs text-gray-900 leading-relaxed">{{ $purchaseRequest->used_for }}</p>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-white border border-gray-200 mb-4">
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-2">
                    <h2 class="text-xs font-semibold text-gray-900">Items</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Item</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Brand</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Supplier</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Qty</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Price</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($purchaseRequest->items as $item)
                            <tr>
                                <td class="px-3 py-2">
                                    <div>
                                        <div class="text-xs font-medium text-gray-900">{{ $item->item_name }}</div>
                                        @if($item->item_description)
                                            <div class="text-xs text-gray-500">{{ $item->item_description }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-900">{{ $item->brand_name ?: '-' }}</td>
                                <td class="px-3 py-2 text-xs text-gray-900">{{ $item->supplier_name ?: '-' }}</td>
                                <td class="px-3 py-2 text-xs text-gray-900">{{ $item->quantity }} {{ $item->unit }}</td>
                                <td class="px-3 py-2 text-xs text-gray-900">{{ $item->currency }} {{ number_format($item->unit_price, 0) }}</td>
                                <td class="px-3 py-2 text-xs font-medium text-gray-900">{{ $item->currency }} {{ number_format($item->quantity * $item->unit_price, 0) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="5" class="px-3 py-2 text-right text-xs font-semibold text-gray-900">Total:</td>
                                <td class="px-3 py-2 text-xs font-bold text-gray-900">{{ $purchaseRequest->currency }} {{ number_format($purchaseRequest->total_amount, 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Approval Workflow -->
            <div class="bg-white border border-gray-200 mb-4">
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-2">
                    <h2 class="text-xs font-semibold text-gray-900">Approval Status</h2>
                </div>
                <div class="px-4 py-3">
                    <div class="space-y-2">
                        <!-- Requestor -->
                        <div class="flex items-center py-2 {{ $verificationData['type'] === 'requestor' ? 'bg-green-50 -mx-2 px-2' : '' }}">
                            <div class="flex-1">
                                <div class="text-xs font-medium text-gray-900">
                                    {{ $purchaseRequest->user->name }}
                                    @if($verificationData['type'] === 'requestor')
                                        <span class="ml-1 text-xs font-medium text-green-700">✓</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500">Requestor • {{ $purchaseRequest->submitted_at ? $purchaseRequest->submitted_at->format('d/m/Y H:i') : 'N/A' }}</div>
                            </div>
                            <div>
                                <span class="inline-flex items-center px-2 py-0.5 text-xs text-green-700 bg-green-50 border border-green-200">
                                    Submitted
                                </span>
                            </div>
                        </div>

                        <!-- Approval Steps -->
                        @foreach($purchaseRequest->approvals->sortBy('step_order') as $step)
                        <div class="flex items-center py-2 border-t border-gray-100 {{ (isset($verificationData['approval']) && $step->id === $verificationData['approval']->id) ? 'bg-green-50 -mx-2 px-2' : '' }}">
                            <div class="flex-1">
                                <div class="text-xs font-medium text-gray-900">
                                    {{ $step->approver->name }}
                                    @if(isset($verificationData['approval']) && $step->id === $verificationData['approval']->id)
                                        <span class="ml-1 text-xs font-medium text-green-700">✓</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ ucfirst(str_replace('_', ' ', $step->approval_type ?? 'Approver')) }}
                                    @if($step->responded_at)
                                        • {{ $step->responded_at->format('d/m/Y H:i') }}
                                    @endif
                                </div>
                                @if($step->notes)
                                    <div class="text-xs text-gray-600 mt-0.5 italic">"{{ $step->notes }}"</div>
                                @endif
                            </div>
                            <div>
                                @if($step->status === 'approved')
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs text-green-700 bg-green-50 border border-green-200">
                                        Approved
                                    </span>
                                @elseif($step->status === 'rejected')
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs text-red-700 bg-red-50 border border-red-200">
                                        Rejected
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs text-gray-600 bg-gray-50 border border-gray-200">
                                        Pending
                                    </span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-white border border-gray-200 py-4">
                <div class="text-center border-b border-gray-200 pb-3 mb-3">
                    <p class="text-xs font-medium text-gray-700">
                        Document Verified: {{ now()->format('d/m/Y H:i') }} WIB
                    </p>
                </div>
                <div class="text-center px-4">
                    <p class="text-xs font-semibold text-gray-900 mb-1">
                        © {{ now()->year }} Werkudara Group
                    </p>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Digital Signed Verification System - This document is cryptographically authenticated using SHA-256 hashing technology to ensure integrity, prevent tampering, and guarantee approval authenticity. Each verification token is unique and non-replicable.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>