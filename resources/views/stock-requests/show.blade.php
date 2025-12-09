<x-app-layout>
    <x-slot name="title">Stock Request Details</x-slot>
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex items-center justify-between">
        <a 
            href="{{ route('stock-requests.index') }}"
            class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900"
        >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Stock Requests
        </a>

        <div class="flex gap-2">
            @if($stockRequest->status === 'rejected')
            <form action="{{ route('stock-requests.resubmit', $stockRequest->id) }}" method="POST" class="inline">
                @csrf
                <button 
                    type="submit"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                    onclick="return confirm('Are you sure you want to resubmit this stock request?')"
                >
                    Resubmit for Review
                </button>
            </form>
            @endif

            @if($stockRequest->status === 'draft' || $stockRequest->status === 'rejected')
            <a 
                href="{{ route('stock-requests.edit', $stockRequest->id) }}"
                class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors"
            >
                Edit
            </a>
            @endif

            @if($stockRequest->status === 'submitted' || $stockRequest->status === 'approved')
            <form action="{{ route('stock-requests.void', $stockRequest->id) }}" method="POST" class="inline">
                @csrf
                <button 
                    type="submit"
                    class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                    onclick="return confirm('Are you sure you want to void this stock request?')"
                >
                    Void
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Alert for Rejected Status --}}
    @if($stockRequest->status === 'rejected')
    <div class="mb-6 bg-orange-50 border border-orange-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-orange-800">Stock Request Rejected</h3>
                <div class="mt-2 text-sm text-orange-700">
                    <p>This stock request was rejected. You can edit and resubmit it.</p>
                    @if($stockRequest->rejected_at)
                    <p class="mt-1">Rejected on: {{ $stockRequest->rejected_at->format('d M Y H:i') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Main Card --}}
    <div class="bg-white rounded-lg shadow-sm">
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $stockRequest->st_number }}</h1>
                    <p class="mt-1 text-sm text-gray-600">Stock Request Details</p>
                </div>
                @php
                    $statusColors = [
                        'draft' => 'bg-gray-100 text-gray-800',
                        'submitted' => 'bg-blue-100 text-blue-800',
                        'approved' => 'bg-green-100 text-green-800',
                        'rejected' => 'bg-red-100 text-red-800',
                        'voided' => 'bg-gray-100 text-gray-500',
                    ];
                @endphp
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $statusColors[$stockRequest->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst($stockRequest->status) }}
                </span>
            </div>
        </div>

        {{-- Request Information --}}
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Request Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-600">Requested By</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $stockRequest->user->name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Business Unit</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $stockRequest->businessUnit->name }} ({{ $stockRequest->businessUnit->code }})</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Department</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $stockRequest->department->name }} ({{ $stockRequest->department->code }})</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Request Date</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $stockRequest->date_of_request->format('d M Y') }}</p>
                </div>
                @if($stockRequest->expected_date)
                <div>
                    <label class="block text-sm font-medium text-gray-600">Expected Delivery Date</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $stockRequest->expected_date->format('d M Y') }}</p>
                </div>
                @endif
                @if($stockRequest->submitted_at)
                <div>
                    <label class="block text-sm font-medium text-gray-600">Submitted At</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $stockRequest->submitted_at->format('d M Y H:i') }}</p>
                </div>
                @endif
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-600">Purpose</label>
                <p class="mt-1 text-sm text-gray-900">{{ $stockRequest->purpose }}</p>
            </div>

            @if($stockRequest->notes)
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-600">Additional Notes</label>
                <p class="mt-1 text-sm text-gray-900">{{ $stockRequest->notes }}</p>
            </div>
            @endif
        </div>

        {{-- Items Section --}}
        <div class="px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Stock Items ({{ $stockRequest->items->count() }})</h2>
            <div class="space-y-4">
                @foreach($stockRequest->items as $item)
                <div class="border border-gray-200 rounded-lg p-4" wire:key="item-{{ $item->id }}">
                    <div class="flex items-start gap-4">
                        {{-- Item Image --}}
                        @if($item->image_path)
                        <div class="flex-shrink-0">
                            <img 
                                src="{{ asset('storage/' . $item->image_path) }}" 
                                alt="{{ $item->item_name }}"
                                class="w-20 h-20 object-cover rounded-lg"
                            />
                        </div>
                        @endif

                        {{-- Item Details --}}
                        <div class="flex-1">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $item->item_order }}. {{ $item->item_name }}</h3>
                                    @if($item->item_code)
                                    <p class="text-xs text-gray-500 mt-1">Code: {{ $item->item_code }}</p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-gray-900">{{ $item->quantity }} {{ $item->unit }}</p>
                                </div>
                            </div>

                            @if($item->specifications)
                            <div class="mt-2">
                                <p class="text-sm text-gray-600"><span class="font-medium">Specifications:</span> {{ $item->specifications }}</p>
                            </div>
                            @endif

                            @if($item->notes)
                            <div class="mt-2">
                                <p class="text-sm text-gray-600"><span class="font-medium">Notes:</span> {{ $item->notes }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Activity Log --}}
        @if($stockRequest->activities->count() > 0)
        <div class="px-6 py-4 border-t border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Activity Log</h2>
            <div class="space-y-3">
                @foreach($stockRequest->activities as $activity)
                <div class="flex items-start gap-3 text-sm">
                    <div class="flex-shrink-0 w-2 h-2 mt-1.5 rounded-full bg-blue-500"></div>
                    <div class="flex-1">
                        <p class="text-gray-900">
                            <span class="font-medium">{{ $activity->causer->name ?? 'System' }}</span>
                            {{ $activity->description }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $activity->created_at->format('d M Y H:i') }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
</x-app-layout>
