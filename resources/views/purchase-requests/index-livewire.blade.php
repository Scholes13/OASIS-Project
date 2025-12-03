@php
    use Illuminate\Support\Facades\Auth;
@endphp

<x-app-layout>
    <!-- Clean header style matching All Requests page -->
    <div class="min-h-screen bg-white">
        <div class="w-full">
            <!-- Header -->
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">My History</h1>
                        <p class="text-sm text-gray-500 mt-0.5">{{ session('current_business_unit_name') }}</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="{{ url()->current() }}" class="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Refresh
                        </a>
                        <a href="{{ route('purchase-requests.create') }}" 
                           wire:navigate
                           class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create New PR
                        </a>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="px-6 py-4">
                <!-- Livewire My History Component -->
                <livewire:modules.purchase-request.my-history />
            </div>
        </div>
    </div>
</x-app-layout>
