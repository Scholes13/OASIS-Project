<x-app-layout>
    <div class="h-full bg-white overflow-auto">
        <div class="w-full">
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">My Purchase Requests</h1>
                        <p class="text-sm text-gray-500 mt-0.5">{{ session('current_business_unit_name') }}</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('purchase-requests.create') }}" wire:navigate
                           class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create New PR
                        </a>
                    </div>
                </div>
            </div>
            <livewire:modules.purchasing.purchase-request.my-purchase-requests />
        </div>
    </div>
</x-app-layout>
