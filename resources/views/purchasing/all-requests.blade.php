<x-app-layout>
    <x-slot name="title">All Requests - Purchasing</x-slot>

    <div class="h-full bg-white overflow-auto">
        <div class="w-full">
            <div class="border-b border-gray-200 px-6 py-4">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">All Requests</h1>
                    <p class="text-sm text-gray-500 mt-0.5">View all Purchase Requests and Stock Requests in {{ session('current_business_unit_name') }}</p>
                </div>
            </div>
            <livewire:modules.purchasing.all-requests />
        </div>
    </div>
</x-app-layout>
