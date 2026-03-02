<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Create Activity Type</h1>
                <p class="text-sm text-gray-600 mt-1">Add a new activity type for task categorization</p>
            </div>
            <a href="{{ route('admin.activity-types.index') }}" 
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
            <form action="{{ route('admin.activity-types.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary"
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
                           value="{{ old('code') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary uppercase"
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
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary"
                            required>
                        <option value="blue" {{ old('color') == 'blue' ? 'selected' : '' }}>Blue</option>
                        <option value="green" {{ old('color') == 'green' ? 'selected' : '' }}>Green</option>
                        <option value="purple" {{ old('color') == 'purple' ? 'selected' : '' }}>Purple</option>
                        <option value="pink" {{ old('color') == 'pink' ? 'selected' : '' }}>Pink</option>
                        <option value="yellow" {{ old('color') == 'yellow' ? 'selected' : '' }}>Yellow</option>
                        <option value="red" {{ old('color') == 'red' ? 'selected' : '' }}>Red</option>
                        <option value="indigo" {{ old('color') == 'indigo' ? 'selected' : '' }}>Indigo</option>
                        <option value="gray" {{ old('color') == 'gray' ? 'selected' : '' }}>Gray</option>
                        <option value="amber" {{ old('color') == 'amber' ? 'selected' : '' }}>Amber</option>
                        <option value="emerald" {{ old('color') == 'emerald' ? 'selected' : '' }}>Emerald</option>
                        <option value="cyan" {{ old('color') == 'cyan' ? 'selected' : '' }}>Cyan</option>
                        <option value="rose" {{ old('color') == 'rose' ? 'selected' : '' }}>Rose</option>
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
                           value="{{ old('sort_order', 0) }}"
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
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">
                        Active (visible in task forms)
                    </label>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.activity-types.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Create Activity Type
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
