<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Purchase Request Statistics</h1>
                <p class="text-sm text-gray-600 mt-1">View and analyze purchase request data</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total PRs Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900">Total PRs</h3>
                        <p class="text-2xl font-bold text-gray-900">128</p>
                        <p class="text-xs text-gray-500">All time</p>
                    </div>
                </div>
            </div>

            <!-- Status Distribution Card -->
            <div class="col-span-1 lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">Purchase Request Status Distribution</h3>
                <div class="flex items-center justify-center h-48 bg-gray-50 rounded-lg">
                    <p class="text-gray-500">Charts will be implemented soon</p>
                </div>
            </div>
        </div>

        <!-- Department Statistics -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-medium text-gray-900 mb-4">PRs by Department</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total PRs</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rejected</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">In Process</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Information Technology (IT)</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">42</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">32</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">5</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">5</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Human Resources (HR)</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">36</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">28</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">4</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">4</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Finance (FIN)</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">30</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">25</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">3</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-center">
                <p class="text-gray-500 text-sm">Sample data - not actual statistics</p>
            </div>
        </div>
    </div>
</x-app-layout>