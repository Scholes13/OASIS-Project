@php
    use Illuminate\Support\Facades\Auth;
    $pr = $approval->purchaseRequest;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Review Purchase Request</h1>
                <p class="text-sm text-gray-600 mt-1">{{ $pr->pr_number }} - Step {{ $approval->step_order }} Approval</p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                    Pending Approval
                </span>
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
                </a>
            </div>
        </li>
        <li class="flex">
            <div class="flex items-center">
                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                <a href="{{ route('approvals.index') }}" wire:navigate class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">
                    Approvals
                </a>
            </div>
        </li>
        <li class="flex">
            <div class="flex items-center">
                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="ml-4 text-sm font-medium text-gray-500">{{ $pr->pr_number }}</span>
            </div>
        </li>
    </x-slot>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content - PR Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- PR Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Purchase Request Information</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">PR Number</label>
                            <p class="text-sm text-gray-900 font-mono">{{ $pr->pr_number }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Request Date</label>
                            <p class="text-sm text-gray-900">{{ $pr->date_of_request->format('F j, Y') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Requestor</label>
                            <p class="text-sm text-gray-900">{{ $pr->user->name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500">{{ $pr->user->email ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <p class="text-sm text-gray-900">{{ $pr->department->name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500">{{ $pr->department->code ?? 'N/A' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Purpose (Keperluan)</label>
                            <p class="text-sm text-gray-900">{{ $pr->keperluan ?: 'Not specified' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Used For</label>
                            <p class="text-sm text-gray-900">{{ $pr->used_for ?: 'Not specified' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PR Items -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Items Requested</h3>
                    <p class="text-sm text-gray-600 mt-1">{{ $pr->items->count() }} item(s) in this request</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($pr->items as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->item_order }}</td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $item->item_name }}</div>
                                        @if($item->item_description)
                                            <div class="text-sm text-gray-500">{{ Str::limit($item->item_description, 50) }}</div>
                                        @endif
                                        @if($item->expenseDepartment)
                                            <div class="text-xs text-blue-600">Expense: {{ $item->expenseDepartment->code }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->brand_name ?: '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->supplier_name ?: '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($item->quantity, 2) }} {{ $item->unit }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">
                                        {{ $item->currency }} {{ number_format($item->unit_price, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono font-medium">
                                        {{ $item->currency }} {{ number_format($item->total_price, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-right text-sm font-medium text-gray-900">Total Amount:</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 font-mono">
                                    {{ $pr->currency }} {{ number_format($pr->total_amount, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Approval History -->
            @if($pr->approvals->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Approval Workflow</h3>
                        <p class="text-sm text-gray-600 mt-1">Approval progress and history</p>
                    </div>
                    <div class="p-6">
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                @foreach($pr->approvals as $prApproval)
                                    <li>
                                        <div class="relative pb-8">
                                            @if(!$loop->last)
                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 
                                                    {{ $prApproval->status === 'approved' ? 'bg-green-500' : 
                                                       ($prApproval->status === 'rejected' ? 'bg-red-500' : 'bg-gray-200') }}" 
                                                    aria-hidden="true"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white
                                                        {{ $prApproval->status === 'approved' ? 'bg-green-500' : 
                                                           ($prApproval->status === 'rejected' ? 'bg-red-500' : 
                                                            ($prApproval->id === $approval->id ? 'bg-orange-500' : 'bg-gray-400')) }}">
                                                        @if($prApproval->status === 'approved')
                                                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                            </svg>
                                                        @elseif($prApproval->status === 'rejected')
                                                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                        @elseif($prApproval->id === $approval->id)
                                                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                        @else
                                                            <span class="h-2 w-2 bg-white rounded-full"></span>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-900">
                                                            <span class="font-medium">Step {{ $prApproval->step_order }}</span>
                                                            - {{ $prApproval->approver->name ?? 'Unknown Approver' }}
                                                        </p>
                                                        <p class="text-sm text-gray-500">
                                                            {{ $prApproval->approver->email ?? 'N/A' }}
                                                        </p>
                                                        @if($prApproval->notes)
                                                            <p class="text-sm text-gray-600 mt-1 italic">
                                                                "{{ $prApproval->notes }}"
                                                            </p>
                                                        @endif
                                                        @if($prApproval->id === $approval->id)
                                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800 mt-1">
                                                                Your Turn
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        @if($prApproval->responded_at)
                                                            <p>{{ ucfirst($prApproval->status) }}</p>
                                                            <p>{{ $prApproval->responded_at->format('M j, Y H:i') }}</p>
                                                        @elseif($prApproval->id === $approval->id)
                                                            <p class="text-orange-600 font-medium">Pending</p>
                                                            <p>Assigned {{ $prApproval->assigned_at->format('M j, Y') }}</p>
                                                        @else
                                                            <p class="text-gray-400">Waiting</p>
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
        </div>

        <!-- Sidebar - Action Panel -->
        <div class="lg:col-span-1">
            <div class="sticky top-6">
                <!-- Approval Actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Approval Actions</h3>
                        <p class="text-sm text-gray-600 mt-1">Review and take action on this request</p>
                    </div>
                    <div class="p-6">
                        <form id="approvalForm" class="space-y-4">
                            @csrf
                            <input type="hidden" name="approval_id" value="{{ $approval->id }}">
                            
                            <!-- Notes -->
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                    Comments <span class="text-gray-400">(optional)</span>
                                </label>
                                <textarea 
                                    id="notes" 
                                    name="notes" 
                                    rows="4" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                                    placeholder="Add your comments or feedback..."></textarea>
                            </div>

                            <!-- Action Buttons -->
                            <div class="grid grid-cols-2 gap-3">
                                <button 
                                    type="button" 
                                    onclick="submitAction('approve')"
                                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Approve
                                </button>
                                <button 
                                    type="button" 
                                    onclick="submitAction('reject')"
                                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-red-50 hover:text-red-700 hover:border-red-300 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Reject
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- PR Summary -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Summary</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Items</span>
                            <span class="text-sm font-medium text-gray-900">{{ $pr->items->count() }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Amount</span>
                            <span class="text-sm font-medium text-gray-900">{{ $pr->currency }} {{ number_format($pr->total_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Approval Step</span>
                            <span class="text-sm font-medium text-gray-900">{{ $approval->step_order }} of {{ $pr->approvals->count() }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Assigned Date</span>
                            <span class="text-sm font-medium text-gray-900">{{ $approval->assigned_at->format('M j, Y') }}</span>
                        </div>
                        @if($approval->due_date)
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Due Date</span>
                                <span class="text-sm font-medium {{ $approval->due_date->isPast() ? 'text-red-600' : 'text-gray-900' }}">
                                    {{ $approval->due_date->format('M j, Y') }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                    </div>
                    <div class="p-6 space-y-3">
                        <a href="{{ route('purchase-requests.show', $pr) }}" 
                           wire:navigate
                           class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            View Full Details
                        </a>
                        <a href="{{ route('approvals.index') }}" 
                           wire:navigate
                           class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                            Back to Approvals
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function submitAction(action) {
            const form = document.getElementById('approvalForm');
            const formData = new FormData(form);
            const notes = document.getElementById('notes').value;
            
            if (action === 'reject' && !notes.trim()) {
                alert('Please provide comments when rejecting a request.');
                return;
            }
            
            // Disable buttons to prevent double submission
            const buttons = form.querySelectorAll('button');
            buttons.forEach(btn => {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            });
            
            // Show loading state
            const actionBtn = event.target;
            const originalText = actionBtn.innerHTML;
            actionBtn.innerHTML = action === 'approve' ? 
                '<svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Approving...' :
                '<svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Rejecting...';
            
            // Submit the form
            fetch(`{{ route('approvals.process') }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': formData.get('_token'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    approval_id: formData.get('approval_id'),
                    action: action,
                    notes: notes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const message = action === 'approve' ? 'Request approved successfully!' : 'Request rejected successfully!';
                    
                    // Create and show toast notification
                    const toast = document.createElement('div');
                    toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-transform duration-300 translate-x-full';
                    toast.textContent = message;
                    document.body.appendChild(toast);
                    
                    // Animate in
                    setTimeout(() => toast.classList.remove('translate-x-full'), 100);
                    
                    // Redirect after short delay
                    setTimeout(() => {
                        window.location.href = "{{ route('approvals.index') }}";
                    }, 1500);
                } else {
                    // Show error message
                    alert(data.message || 'An error occurred. Please try again.');
                    
                    // Re-enable buttons
                    buttons.forEach(btn => {
                        btn.disabled = false;
                        btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    });
                    actionBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                
                // Re-enable buttons
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                });
                actionBtn.innerHTML = originalText;
            });
        }
    </script>
    @endpush
</x-app-layout>