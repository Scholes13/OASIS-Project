<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PR Verification - {{ $purchaseRequest->pr_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        .token-hash {
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 min-h-screen">
    <div class="min-h-screen py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Company Header -->
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 mb-8 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0121 12c0 6.627-5.373 12-12 12S-3 18.627-3 12 1.373 0 9 0c2.125 0 4.108.556 5.828 1.531"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold text-white">WNS Purchase Request</h1>
                                    <p class="text-blue-100 text-sm">Digital Verification System</p>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="inline-flex items-center space-x-2 bg-white/20 backdrop-blur-sm rounded-full px-4 py-2">
                                <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                <span class="text-white text-sm font-medium">Verified</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- PR Number Banner -->
                <div class="bg-gradient-to-r from-slate-50 to-blue-50 px-8 py-4 border-b border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-600">Purchase Request Number</p>
                            <p class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                                {{ $purchaseRequest->pr_number }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-slate-600">Verification Status</p>
                            <p class="text-lg font-semibold text-green-600 flex items-center justify-end mt-1">
                                <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Authenticated
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Verification Badge -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-2xl shadow-lg mb-6 overflow-hidden">
                <div class="flex items-start p-6">
                    <div class="flex-shrink-0">
                        <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-emerald-500 rounded-2xl flex items-center justify-center shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0121 12c0 6.627-5.373 12-12 12S-3 18.627-3 12 1.373 0 9 0c2.125 0 4.108.556 5.828 1.531"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 flex-1">
                        @if($verificationData['type'] === 'requestor')
                            <h3 class="text-xl font-bold text-green-900 mb-1">✓ Verified Request Creator</h3>
                            <p class="text-green-800 font-medium mb-2">
                                Created by <span class="font-bold">{{ $verificationData['verified_by']->name }}</span>
                            </p>
                            <p class="text-green-700 text-sm flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $verificationData['verified_at']->format('l, d F Y \a\t H:i') }} WIB
                            </p>
                        @else
                            <h3 class="text-xl font-bold text-green-900 mb-1">✓ Verified {{ $verificationData['role'] }}</h3>
                            <p class="text-green-800 font-medium mb-2">
                                Approved by <span class="font-bold">{{ $verificationData['verified_by']->name }}</span>
                            </p>
                            <p class="text-green-700 text-sm flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $verificationData['verified_at']->format('l, d F Y \a\t H:i') }} WIB
                            </p>
                        @endif
                        <div class="mt-4 pt-4 border-t border-green-200">
                            <p class="text-xs font-semibold text-green-800 mb-2 uppercase tracking-wide">Digital Signature Hash</p>
                            <div class="bg-white/70 backdrop-blur-sm rounded-lg px-4 py-3 border border-green-200">
                                <p class="token-hash text-xs text-green-900 font-mono leading-relaxed">
                                    {{ request()->get('token') }}
                                </p>
                            </div>
                            <p class="text-xs text-green-600 mt-2 flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                SHA-256 cryptographic verification ensures document authenticity
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchase Request Details -->
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 mb-6 overflow-hidden">
                <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-6 py-5 border-b border-slate-200">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900">Purchase Request Information</h2>
                    </div>
                </div>
                <div class="px-6 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Requestor</label>
                            <p class="text-base font-semibold text-slate-900">{{ $purchaseRequest->user->name }}</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Department</label>
                            <p class="text-base font-semibold text-slate-900">{{ $purchaseRequest->department->name }}</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Business Unit</label>
                            <p class="text-base font-semibold text-slate-900">{{ $purchaseRequest->businessUnit->name }}</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Request Date</label>
                            <p class="text-base font-semibold text-slate-900">{{ \Carbon\Carbon::parse($purchaseRequest->date_of_request)->format('d F Y') }}</p>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 rounded-xl p-4 border border-blue-200 mb-4">
                        <label class="block text-xs font-bold text-blue-900 uppercase tracking-wider mb-2">Purpose</label>
                        <p class="text-sm text-blue-900 leading-relaxed">{{ $purchaseRequest->keperluan }}</p>
                    </div>
                    
                    <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-200">
                        <label class="block text-xs font-bold text-indigo-900 uppercase tracking-wider mb-2">Used For</label>
                        <p class="text-sm text-indigo-900 leading-relaxed">{{ $purchaseRequest->used_for }}</p>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 mb-6 overflow-hidden">
                <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-6 py-5 border-b border-slate-200">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900">Items Requested</h2>
                    </div>
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
                        <tfoot class="bg-gradient-to-r from-blue-50 to-indigo-50">
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-right text-base font-bold text-slate-900">Grand Total:</td>
                                <td class="px-6 py-4 text-lg font-black bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">{{ $purchaseRequest->currency }} {{ number_format($purchaseRequest->total_amount, 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Approval Workflow -->
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 mb-6 overflow-hidden">
                <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-6 py-5 border-b border-slate-200">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900">Approval Workflow Status</h2>
                    </div>
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
            <div class="bg-gradient-to-r from-slate-800 via-slate-900 to-slate-800 rounded-2xl shadow-2xl p-8 text-center">
                <div class="flex items-center justify-center mb-4">
                    <svg class="w-12 h-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0121 12c0 6.627-5.373 12-12 12S-3 18.627-3 12 1.373 0 9 0c2.125 0 4.108.556 5.828 1.531"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Document Authenticity Verified</h3>
                <p class="text-slate-300 text-sm mb-4">
                    This document was cryptographically verified on {{ now()->format('l, d F Y \a\t H:i') }} WIB
                </p>
                <div class="border-t border-slate-700 pt-4 mt-4">
                    <p class="text-slate-400 text-xs leading-relaxed max-w-2xl mx-auto">
                        <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        This digital verification system uses SHA-256 cryptographic hashing to ensure document authenticity and prevent unauthorized modifications. 
                        The verification token is unique to this specific approval and cannot be replicated or forged.
                    </p>
                </div>
                <div class="mt-6">
                    <p class="text-slate-500 text-xs">
                        © {{ now()->year }} WNS Purchase Request System • Powered by Digital Signature Technology
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>