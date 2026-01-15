<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Activity Type</h1>
                <p class="text-sm text-gray-600 mt-1">Update activity type: {{ $activityType->name }}</p>
            </div>
            <a href="{{ route('admin.activity-types.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <form action="{{ route('admin.activity-types.update', $activityType) }}" method="POST" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name', $activityType->name) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="e.g., Meeting"
                           required>
                    @error('name')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Code -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                        Code <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="code" 
                           id="code" 
                           value="{{ old('code', $activityType->code) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 uppercase"
                           placeholder="e.g., MEETING"
                           maxlength="20"
                           required>
                    <p class="text-xs text-gray-500 mt-1">Short code for the activity type (max 20 characters)</p>
                    @error('code')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Color -->
                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700 mb-1">
                        Color <span class="text-red-500">*</span>
                    </label>
                    <select name="color" 
                            id="color" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                            required>
                        <option value="blue" {{ old('color', $activityType->color) == 'blue' ? 'selected' : '' }}>Blue</option>
                        <option value="green" {{ old('color', $activityType->color) == 'green' ? 'selected' : '' }}>Green</option>
                        <option value="purple" {{ old('color', $activityType->color) == 'purple' ? 'selected' : '' }}>Purple</option>
                        <option value="pink" {{ old('color', $activityType->color) == 'pink' ? 'selected' : '' }}>Pink</option>
                        <option value="yellow" {{ old('color', $activityType->color) == 'yellow' ? 'selected' : '' }}>Yellow</option>
                        <option value="red" {{ old('color', $activityType->color) == 'red' ? 'selected' : '' }}>Red</option>
                        <option value="indigo" {{ old('color', $activityType->color) == 'indigo' ? 'selected' : '' }}>Indigo</option>
                        <option value="gray" {{ old('color', $activityType->color) == 'gray' ? 'selected' : '' }}>Gray</option>
                        <option value="amber" {{ old('color', $activityType->color) == 'amber' ? 'selected' : '' }}>Amber</option>
                        <option value="emerald" {{ old('color', $activityType->color) == 'emerald' ? 'selected' : '' }}>Emerald</option>
                        <option value="cyan" {{ old('color', $activityType->color) == 'cyan' ? 'selected' : '' }}>Cyan</option>
                        <option value="rose" {{ old('color', $activityType->color) == 'rose' ? 'selected' : '' }}>Rose</option>
                    </select>
                    @error('color')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sort Order -->
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">
                        Sort Order
                    </label>
                    <input type="number" 
                           name="sort_order" 
                           id="sort_order" 
                           value="{{ old('sort_order', $activityType->sort_order) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                           min="0">
                    <p class="text-xs text-gray-500 mt-1">Lower numbers appear first in dropdown</p>
                    @error('sort_order')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Is Active -->
                <div class="flex items-center">
                    <input type="checkbox" 
                           name="is_active" 
                           id="is_active" 
                           value="1"
                           {{ old('is_active', $activityType->is_active) ? 'checked' : '' }}
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">
                        Active (visible in task forms)
                    </label>
                </div>

                <!-- Usage Info -->
                @php
                    $taskCount = $activityType->employeeTasks()->count();
                    $subActivityCount = $activityType->subActivities()->count();
                @endphp
                @if($taskCount > 0 || $subActivityCount > 0)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-blue-800">Activity Type in use</p>
                                <p class="text-sm text-blue-700 mt-1">
                                    This activity type has {{ $subActivityCount }} sub-activities and is used by {{ $taskCount }} task(s).
                                    @if(!$activityType->is_active)
                                        <br><span class="font-medium">Note:</span> Deactivating will hide this from new task forms but preserve existing tasks.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Submit -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.activity-types.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Update Activity Type
                    </button>
                </div>
            </form>
        </div>

        <!-- Sub-Activities Section -->
        @if($activityType->subActivities->count() > 0 || true)
            <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Sub-Activities</h3>
                    <a href="{{ route('admin.sub-activities.create', ['activity_type' => $activityType->id]) }}" 
                       class="inline-flex items-center px-3 py-1.5 text-sm text-indigo-600 hover:text-indigo-900">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Sub-Activity
                    </a>
                </div>
                <div class="p-6">
                    @if($activityType->subActivities->count() > 0)
                        <div class="space-y-2">
                            @foreach($activityType->subActivities()->ordered()->get() as $subActivity)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-sm text-gray-500">{{ $subActivity->sort_order }}</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-700">
                                            {{ $subActivity->code }}
                                        </span>
                                        <span class="text-sm font-medium text-gray-900">{{ $subActivity->name }}</span>
                                        @if(!$subActivity->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Inactive
                                            </span>
                                        @endif
                                    </div>
                                    <a href="{{ route('admin.sub-activities.edit', $subActivity) }}" 
                                       class="text-sm text-indigo-600 hover:text-indigo-900">
                                        Edit
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 text-center py-4">
                            No sub-activities yet. 
                            <a href="{{ route('admin.sub-activities.create', ['activity_type' => $activityType->id]) }}" class="text-indigo-600 hover:text-indigo-900">Add one</a>
                        </p>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
