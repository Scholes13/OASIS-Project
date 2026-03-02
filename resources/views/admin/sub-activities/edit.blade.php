<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Sub-Activity</h1>
                <p class="text-sm text-gray-600 mt-1">Update sub-activity: {{ $subActivity->name }}</p>
            </div>
            <a href="{{ route('admin.sub-activities.index', ['activity_type' => $subActivity->activity_type_id]) }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <form action="{{ route('admin.sub-activities.update', $subActivity) }}" method="POST" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <!-- Activity Type -->
                <div>
                    <label for="activity_type_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Activity Type <span class="text-red-500">*</span>
                    </label>
                    <select name="activity_type_id" 
                            id="activity_type_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary"
                            required>
                        <option value="">Select Activity Type</option>
                        @foreach($activityTypes as $type)
                            <option value="{{ $type->id }}" {{ old('activity_type_id', $subActivity->activity_type_id) == $type->id ? 'selected' : '' }}>
                                {{ $type->name }} ({{ $type->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('activity_type_id')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name', $subActivity->name) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary"
                           placeholder="e.g., Meeting Client"
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
                           value="{{ old('code', $subActivity->code) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary uppercase"
                           placeholder="e.g., MTG-CLIENT"
                           maxlength="20"
                           required>
                    <p class="text-xs text-gray-500 mt-1">Short code for the sub-activity (max 20 characters, unique per activity type)</p>
                    @error('code')
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
                           value="{{ old('sort_order', $subActivity->sort_order) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary"
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
                           {{ old('is_active', $subActivity->is_active) ? 'checked' : '' }}
                           class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">
                        Active (visible in task forms)
                    </label>
                </div>

                <!-- Usage Info -->
                @php
                    $taskCount = $subActivity->employeeTasks()->count();
                @endphp
                @if($taskCount > 0)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-blue-800">Sub-Activity in use</p>
                                <p class="text-sm text-blue-700 mt-1">
                                    This sub-activity is used by {{ $taskCount }} task(s).
                                    @if(!$subActivity->is_active)
                                        <br><span class="font-medium">Note:</span> Deactivating will hide this from new task forms but preserve existing tasks.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Submit -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.sub-activities.index', ['activity_type' => $subActivity->activity_type_id]) }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Update Sub-Activity
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
