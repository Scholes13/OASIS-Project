<x-app-layout>
    <x-slot name="title">All Requests - Purchasing</x-slot>

    <div class="container mx-auto px-4 py-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">All Requests</h1>
            <p class="mt-1 text-sm text-gray-600">View all Purchase Requests and Stock Requests in {{ session('current_business_unit_name') }}</p>
        </div>

        {{-- Livewire Component --}}
        <livewire:modules.purchasing.all-requests />
    </div>
</x-app-layout>
