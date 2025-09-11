<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Business Unit Details') }} - {{ $businessUnit->name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('admin.business-units.edit', $businessUnit) }}" 
                   class="bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
                <a href="{{ route('admin.business-units.index') }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="w-full space-y-6">
            
            <!-- Basic Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Code</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $businessUnit->code }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $businessUnit->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $businessUnit->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $businessUnit->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </dd>
                        </div>
                        @if($businessUnit->description)
                            <div class="md:col-span-2 lg:col-span-3">
                                <dt class="text-sm font-medium text-gray-500">Description</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $businessUnit->description }}</dd>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            @if($businessUnit->address || $businessUnit->phone || $businessUnit->email)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Contact Information</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @if($businessUnit->address)
                                <div class="md:col-span-2 lg:col-span-3">
                                    <dt class="text-sm font-medium text-gray-500">Address</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $businessUnit->address }}</dd>
                                </div>
                            @endif
                            @if($businessUnit->phone)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $businessUnit->phone }}</dd>
                                </div>
                            @endif
                            @if($businessUnit->email)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $businessUnit->email }}</dd>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Statistics -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Statistics</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="text-center">
                            <dt class="text-sm font-medium text-gray-500">Total Departments</dt>
                            <dd class="mt-1 text-2xl font-semibold text-indigo-600">{{ $stats['total_departments'] ?? 0 }}</dd>
                        </div>
                        <div class="text-center">
                            <dt class="text-sm font-medium text-gray-500">Active Departments</dt>
                            <dd class="mt-1 text-2xl font-semibold text-green-600">{{ $stats['active_departments'] ?? 0 }}</dd>
                        </div>
                        <div class="text-center">
                            <dt class="text-sm font-medium text-gray-500">Total Users</dt>
                            <dd class="mt-1 text-2xl font-semibold text-blue-600">{{ $stats['total_users'] ?? 0 }}</dd>
                        </div>
                        <div class="text-center">
                            <dt class="text-sm font-medium text-gray-500">Active Users</dt>
                            <dd class="mt-1 text-2xl font-semibold text-purple-600">{{ $stats['active_users'] ?? 0 }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hierarchy -->
            @if($businessUnit->parent || $businessUnit->children->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Business Unit Hierarchy</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if($businessUnit->parent)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Parent Business Unit</dt>
                                    <dd class="mt-1">
                                        <a href="{{ route('admin.business-units.show', $businessUnit->parent) }}" 
                                           class="text-indigo-600 hover:text-indigo-900">
                                            {{ $businessUnit->parent->name }} ({{ $businessUnit->parent->code }})
                                        </a>
                                    </dd>
                                </div>
                            @endif
                            
                            @if($businessUnit->children->count() > 0)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Child Business Units</dt>
                                    <dd class="mt-1 space-y-1">
                                        @foreach($businessUnit->children as $child)
                                            <div>
                                                <a href="{{ route('admin.business-units.show', $child) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900">
                                                    {{ $child->name }} ({{ $child->code }})
                                                </a>
                                            </div>
                                        @endforeach
                                    </dd>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Departments -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Departments</h3>
                        <span class="text-sm text-gray-500">{{ $businessUnit->departments->count() }} departments</span>
                    </div>
                </div>
                <div class="p-6">
                    @if($businessUnit->departments->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($businessUnit->departments as $department)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h4 class="text-md font-medium text-gray-900">{{ $department->name }}</h4>
                                            <p class="text-sm text-gray-500">{{ $department->code }}</p>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $department->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $department->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    
                                    @if($department->description)
                                        <p class="text-sm text-gray-600 mb-3">{{ $department->description }}</p>
                                    @endif
                                    
                                    <!-- Positions -->
                                    @if($department->positions->count() > 0)
                                        <div class="mb-3">
                                            <h5 class="text-xs font-medium text-gray-700 mb-1">Positions:</h5>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($department->positions as $position)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                        {{ $position->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Users Count -->
                                    <div class="text-xs text-gray-500">
                                        {{ $department->users->count() }} users assigned
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">No departments found.</p>
                    @endif
                </div>
            </div>

            <!-- Recent Users -->
            @if($businessUnit->users->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">Users</h3>
                            <span class="text-sm text-gray-500">{{ $businessUnit->users->count() }} users</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($businessUnit->users->take(10) as $userAssignment)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8">
                                                        <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                                            <span class="text-xs font-medium text-indigo-800">
                                                                {{ strtoupper(substr($userAssignment->user->name, 0, 2)) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="ml-3">
                                                        <div class="text-sm font-medium text-gray-900">{{ $userAssignment->user->name }}</div>
                                                        <div class="text-sm text-gray-500">{{ $userAssignment->user->email }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $userAssignment->department->name ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $userAssignment->position->name ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ ucfirst($userAssignment->role) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $userAssignment->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $userAssignment->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($businessUnit->users->count() > 10)
                            <div class="mt-4 text-center">
                                <p class="text-sm text-gray-500">Showing 10 of {{ $businessUnit->users->count() }} users</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>