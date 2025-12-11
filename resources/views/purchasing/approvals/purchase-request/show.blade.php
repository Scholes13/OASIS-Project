@php
    use Illuminate\Support\Facades\Auth;
    
    // Approval status styling
    $approvalStatusStyles = [
        'pending' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'icon' => 'clock'],
        'approved' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'icon' => 'check'],
        'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => 'x'],
    ];
    $approvalStyle = $approvalStatusStyles[$approval->status] ?? $approvalStatusStyles['pending'];
    
    // PR status styling
    $prStatusStyles = [
        'draft' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => 'Draft'],
        'submitted' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Submitted'],
        'in_approval' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'In Approval'],
        'approved' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => 'Approved'],
        'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Rejected'],
    ];
    $prStyle = $prStatusStyles[$approval->purchaseRequest->status] ?? $prStatusStyles['draft'];
@endphp

<x-app-layout>
    <div class="min-h-screen bg-white">
        <div class="w-full">
            <!-- Header -->
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('approvals.index') }}" 
                           class="inline-flex items-center text-gray-500 hover:text-gray-700 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </a>
                        <div>
                            <div class="flex items-center space-x-3">
                                <h1 class="text-xl font-semibold text-gray-900">{{ $approval->purchaseRequest->pr_number }}</h1>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $prStyle['bg'] }} {{ $prStyle['text'] }}">
                                    {{ $prStyle['label'] }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 mt-0.5">{{ $approval->purchaseRequest->businessUnit?->name ?? 'N/A' }} • {{ $approval->purchaseRequest->department?->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('purchase-requests.show', $approval->purchaseRequest) }}" 
                           class="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            View PR Details
                        </a>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            @if(!$canApprove && $approval->status === 'pending')
            <div class="mx-6 mt-6">
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-amber-800">Waiting for Previous Approver</h3>
                            <p class="text-sm text-amber-700 mt-1">
                                This approval is not yet your turn. Please wait for the previous approver to complete their review first.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($approval->status !== 'pending')
            <div class="mx-6 mt-6">
                <div class="p-4 {{ $approval->status === 'approved' ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200' }} border rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 {{ $approval->status === 'approved' ? 'text-emerald-500' : 'text-red-500' }} mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($approval->status === 'approved')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            @endif
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium {{ $approval->status === 'approved' ? 'text-emerald-800' : 'text-red-800' }}">
                                You Have Already {{ ucfirst($approval->status) }}
                            </h3>
                            <p class="text-sm {{ $approval->status === 'approved' ? 'text-emerald-700' : 'text-red-700' }} mt-1">
                                You have {{ $approval->status }} this request on {{ $approval->responded_at ? $approval->responded_at->format('F j, Y \a\t H:i') : 'N/A' }}.
                                @if($approval->notes)
                                    <br><span class="font-medium">Notes:</span> {{ $approval->notes }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Content Grid -->
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Request Details -->
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <h2 class="text-base font-semibold text-gray-900">Request Information</h2>
                            </div>
                            <div class="px-6 py-4">
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Requested By</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $approval->purchaseRequest->user->name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Department</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $approval->purchaseRequest->department->name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Date of Request</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $approval->purchaseRequest->date_of_request ? \Carbon\Carbon::parse($approval->purchaseRequest->date_of_request)->format('d/m/Y') : 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Expected Date</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $approval->purchaseRequest->designated_date ? \Carbon\Carbon::parse($approval->purchaseRequest->designated_date)->format('d/m/Y') : 'Not specified' }}</dd>
                                    </div>
                                </dl>
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Purpose / Used For</dt>
                                    <dd class="mt-2 text-sm text-gray-900">{{ $approval->purchaseRequest->used_for }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                                <h2 class="text-base font-semibold text-gray-900">Items</h2>
                                <span class="text-sm text-gray-500">{{ $approval->purchaseRequest->items->count() }} {{ Str::plural('item', $approval->purchaseRequest->items->count()) }}</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expense Dept</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        @foreach($approval->purchaseRequest->items as $index => $item)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-4 text-sm text-gray-500">{{ $index + 1 }}</td>
                                                <td class="px-4 py-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ $item->item_name }}</div>
                                                    @if($item->brand_name)
                                                        <div class="text-xs text-gray-500 mt-0.5">{{ $item->brand_name }}</div>
                                                    @endif
                                                    @if($item->item_description)
                                                        <div class="text-xs text-gray-400 mt-1">{{ $item->item_description }}</div>
                                                    @endif
                                                    @if($item->supplier_name)
                                                        <div class="text-xs text-gray-400 mt-0.5">Supplier: {{ $item->supplier_name }}</div>
                                                    @endif
                                                    @if($item->image_path)
                                                        <div class="mt-2">
                                                            <img src="{{ Storage::url($item->image_path) }}" 
                                                                 alt="{{ $item->item_name }}" 
                                                                 class="w-16 h-16 object-cover rounded border border-gray-200">
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-4 text-sm text-gray-900">
                                                    {{ $item->expenseDepartment?->name ?? 'N/A' }}
                                                </td>
                                                <td class="px-4 py-4 text-sm text-gray-900 text-right whitespace-nowrap">
                                                    {{ number_format($item->quantity, 0) }} {{ $item->unit }}
                                                </td>
                                                <td class="px-4 py-4 text-sm text-gray-900 text-right whitespace-nowrap">
                                                    {{ $item->currency }} {{ number_format($item->unit_price, 0, ',', '.') }}
                                                </td>
                                                <td class="px-4 py-4 text-sm font-medium text-gray-900 text-right whitespace-nowrap">
                                                    {{ $item->currency }} {{ number_format($item->total_price, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <td colspan="5" class="px-4 py-4 text-sm font-semibold text-gray-900 text-right">
                                                Total Amount
                                            </td>
                                            <td class="px-4 py-4 text-sm font-semibold text-indigo-600 text-right whitespace-nowrap">
                                                {{ $approval->purchaseRequest->currency ?? 'IDR' }} {{ number_format($approval->purchaseRequest->total_amount, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Purchase Request Document -->
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <h2 class="text-base font-semibold text-gray-900">Purchase Request Document</h2>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-sm text-gray-600 mb-4">View the complete purchase request document in PDF format</p>
                                <a href="{{ route('purchase-requests.pdf', $approval->purchaseRequest) }}" 
                                   target="_blank"
                                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    View PDF Document
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Your Action Required -->
                        @if($canApprove && $approval->status === 'pending')
                        <div class="border border-indigo-200 rounded-lg overflow-hidden bg-indigo-50">
                            <div class="px-6 py-4 bg-indigo-100 border-b border-indigo-200">
                                <h3 class="text-base font-semibold text-indigo-900">Your Action Required</h3>
                            </div>
                            <form action="{{ route('approvals.process', $approval) }}" method="POST" class="px-6 py-4 space-y-4">
                                @csrf
                                
                                <div>
                                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                        Notes (Optional)
                                    </label>
                                    <textarea 
                                        id="notes" 
                                        name="notes" 
                                        rows="3" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Add any comments or notes..."></textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <button 
                                        type="submit" 
                                        name="action" 
                                        value="approve"
                                        style="background-color: #10b981 !important; color: #ffffff !important;"
                                        class="inline-flex justify-center items-center px-4 py-2.5 text-sm font-semibold rounded-lg hover:opacity-90 focus:ring-4 focus:ring-emerald-200 transition-all shadow-sm border-0"
                                        onmouseover="this.style.backgroundColor='#059669'"
                                        onmouseout="this.style.backgroundColor='#10b981'">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Approve
                                    </button>
                                    <button 
                                        type="submit" 
                                        name="action" 
                                        value="reject"
                                        style="background-color: #ef4444 !important; color: #ffffff !important;"
                                        class="inline-flex justify-center items-center px-4 py-2.5 text-sm font-semibold rounded-lg hover:opacity-90 focus:ring-4 focus:ring-red-200 transition-all shadow-sm border-0"
                                        onmouseover="this.style.backgroundColor='#dc2626'"
                                        onmouseout="this.style.backgroundColor='#ef4444'">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Reject
                                    </button>
                                </div>
                            </form>
                        </div>
                        @endif

                        <!-- Approval Progress -->
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <h3 class="text-base font-semibold text-gray-900">Approval Progress</h3>
                            </div>
                            <div class="px-6 py-4">
                                <div class="flow-root">
                                    <ul class="space-y-4">
                                        <!-- Requestor -->
                                        <li class="relative">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="ml-3 flex-1">
                                                    <div class="text-sm font-medium text-gray-900">{{ $approval->purchaseRequest->user->name }}</div>
                                                    <div class="text-xs text-gray-500">Requestor · Submitted</div>
                                                </div>
                                                <div class="ml-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                        Submitted
                                                    </span>
                                                </div>
                                            </div>
                                        </li>

                                        <!-- Approvers -->
                                        @foreach($approval->purchaseRequest->approvals()->orderBy('step_order')->get() as $step)
                                        <li class="relative">
                                            @if(!$loop->last)
                                                <div class="absolute left-4 top-8 -ml-px h-full w-0.5 bg-gray-200"></div>
                                            @endif
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0">
                                                    @if($step->status === 'approved')
                                                        <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                            </svg>
                                                        </div>
                                                    @elseif($step->status === 'rejected')
                                                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                        </div>
                                                    @elseif($step->id === $approval->id)
                                                        <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center ring-2 ring-amber-400">
                                                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                        </div>
                                                    @else
                                                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="ml-3 flex-1">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $step->approver->name }}
                                                        @if($step->id === $approval->id)
                                                            <span class="text-amber-600">(You)</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        Step {{ $step->step_order }} · {{ ucfirst($step->approval_type) }}
                                                    </div>
                                                    @if($step->notes)
                                                        <div class="mt-1 text-xs text-gray-600 italic">
                                                            "{{ $step->notes }}"
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="ml-2">
                                                    @if($step->status === 'approved')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                                                            Approved
                                                        </span>
                                                    @elseif($step->status === 'rejected')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                            Rejected
                                                        </span>
                                                    @elseif($step->id === $approval->id)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                                            Current
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                            Pending
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <h3 class="text-base font-semibold text-gray-900">Timeline</h3>
                            </div>
                            <div class="px-6 py-4">
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Created</span>
                                        <span class="text-gray-900">{{ $approval->purchaseRequest->created_at ? $approval->purchaseRequest->created_at->format('M d, Y H:i') : 'N/A' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Submitted</span>
                                        <span class="text-gray-900">{{ $approval->purchaseRequest->submitted_at ? $approval->purchaseRequest->submitted_at->format('M d, Y H:i') : 'N/A' }}</span>
                                    </div>
                                    @if($approval->responded_at)
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Your Response</span>
                                        <span class="text-gray-900">{{ $approval->responded_at ? $approval->responded_at->format('M d, Y H:i') : 'N/A' }}</span>
                                    </div>
                                    @endif
                                    @if($approval->due_date)
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Due Date</span>
                                        <span class="text-gray-900">{{ \Carbon\Carbon::parse($approval->due_date)->format('M d, Y') }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
