<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Sub-Activities</h1>
                <p class="text-sm text-gray-600 mt-1">Manage sub-activities for activity types</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.activity-types.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Activity Types
                </a>
                <a href="{{ route('admin.sub-activities.create', request()->only('activity_type')) }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Sub-Activity
                </a>
            </div>
        </div>
    </x-slot>

    <div class="w-full">
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <form method="GET" action="{{ route('admin.sub-activities.index') }}" class="flex flex-wrap gap-4">
                <div class="w-48">
                    <select name="activity_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary">
                        <option value="">All Activity Types</option>
                        @foreach($activityTypes as $type)
                            <option value="{{ $type->id }}" {{ request('activity_type') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Search by name or code..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary">
                </div>
                <div class="w-40">
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'status', 'activity_type']))
                        <a href="{{ route('admin.sub-activities.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tasks</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($subActivities as $subActivity)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $subActivity->sort_order }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $colorClasses = [
                                            'blue' => 'bg-blue-100 text-blue-800',
                                            'green' => 'bg-green-100 text-green-800',
                                            'purple' => 'bg-purple-100 text-purple-800',
                                            'pink' => 'bg-pink-100 text-pink-800',
                                            'yellow' => 'bg-yellow-100 text-yellow-800',
                                            'red' => 'bg-red-100 text-red-800',
                                            'gray' => 'bg-gray-100 text-gray-800',
                                            'indigo' => 'bg-blue-100 text-blue-800',
                                            'amber' => 'bg-amber-100 text-amber-800',
                                            'emerald' => 'bg-emerald-100 text-emerald-800',
                                            'cyan' => 'bg-cyan-100 text-cyan-800',
                                            'rose' => 'bg-rose-100 text-rose-800',
                                        ];
                                        $colorClass = $colorClasses[$subActivity->activityType->color ?? 'gray'] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
                                        {{ $subActivity->activityType->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $subActivity->code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $subActivity->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $subActivity->employee_tasks_count }} tasks
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($subActivity->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('admin.sub-activities.edit', $subActivity) }}" 
                                           class="text-primary hover:text-primary">
                                            Edit
                                        </a>
                                        @if($subActivity->employee_tasks_count === 0)
                                            <form action="{{ route('admin.sub-activities.destroy', $subActivity) }}" 
                                                  method="POST" 
                                                  class="inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this sub-activity?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    No sub-activities found. <a href="{{ route('admin.sub-activities.create', request()->only('activity_type')) }}" class="text-primary hover:text-primary">Create one</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($subActivities->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $subActivities->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
