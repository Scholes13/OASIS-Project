<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Purchasing Admin Configuration') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('admin.departments.show', $department) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    View Department
                </a>
                <a href="{{ route('admin.departments.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Departments
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="w-full">
            <!-- Department Info Card -->
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden mb-6">
                <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-blue-50">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $department->name }}</h3>
                            <p class="text-sm text-gray-600">{{ $department->businessUnit->name }} • Code: {{ $department->code }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Alert -->
            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Purchasing Admin Configuration</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Configure this department as a purchasing department and assign purchasing administrators who will manage procurement follow-up tasks for approved Purchase Requests and Stock Requests.</p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>Enable purchasing department to allow admin assignments</li>
                                <li>Assign users as purchasing admins to handle procurement tasks</li>
                                <li>Set a default admin for automatic task assignment</li>
                                <li>When no default admin is set, tasks will be available for manual claiming</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Livewire Component -->
            @livewire('admin.department-purchasing-config', ['department' => $department])
        </div>
    </div>
</x-app-layout>
