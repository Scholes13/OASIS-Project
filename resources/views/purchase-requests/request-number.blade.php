<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Request PR Number</h1>
                <p class="text-sm text-gray-600 mt-1">Step 1: Generate your Purchase Request number for {{ session('current_business_unit_name') }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('purchase-requests.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-500">
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
                <a href="{{ route('purchase-requests.index') }}" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">
                    Purchase Requests
                </a>
            </div>
        </li>
        <li class="flex">
            <div class="flex items-center">
                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="ml-4 text-sm font-medium text-gray-500">Request Number</span>
            </div>
        </li>
    </x-slot>

    <!-- Step Progress Indicator -->
    <div class="mx-2 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between max-w-md mx-auto">
                <!-- Step 1 - Current -->
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-indigo-600 text-white rounded-full text-sm font-medium">
                        1
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-indigo-600">Step 1</p>
                        <p class="text-xs text-gray-600">Request PR Number</p>
                    </div>
                </div>
                
                <!-- Connector -->
                <div class="flex-1 mx-4">
                    <div class="h-0.5 bg-gray-200"></div>
                </div>
                
                <!-- Step 2 - Next -->
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 text-gray-600 rounded-full text-sm font-medium">
                        2
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-600">Step 2</p>
                        <p class="text-xs text-gray-500">Create PR Form</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PR Number Request Form -->
    <div class="mx-2">
        <livewire:purchase-requests.request-number />
    </div>
</x-app-layout>