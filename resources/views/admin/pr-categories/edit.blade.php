<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit PR Category</h1>
                <p class="text-sm text-gray-600 mt-1">Update category: {{ $prCategory->name }}</p>
            </div>
            <a href="{{ route('admin.pr-categories.index') }}" 
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
            <form action="{{ route('admin.pr-categories.update', $prCategory) }}" method="POST" class="p-6 space-y-6">
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
                           value="{{ old('name', $prCategory->name) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="e.g., Operational"
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
                           value="{{ old('code', $prCategory->code) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 uppercase"
                           placeholder="e.g., OPS"
                           maxlength="20"
                           required>
                    <p class="text-xs text-gray-500 mt-1">Short code for the category (max 20 characters)</p>
                    @error('code')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Description
                    </label>
                    <textarea name="description" 
                              id="description" 
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                              placeholder="Brief description of this category...">{{ old('description', $prCategory->description) }}</textarea>
                    @error('description')
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
                        <option value="blue" {{ old('color', $prCategory->color) == 'blue' ? 'selected' : '' }}>Blue</option>
                        <option value="green" {{ old('color', $prCategory->color) == 'green' ? 'selected' : '' }}>Green</option>
                        <option value="purple" {{ old('color', $prCategory->color) == 'purple' ? 'selected' : '' }}>Purple</option>
                        <option value="pink" {{ old('color', $prCategory->color) == 'pink' ? 'selected' : '' }}>Pink</option>
                        <option value="yellow" {{ old('color', $prCategory->color) == 'yellow' ? 'selected' : '' }}>Yellow</option>
                        <option value="red" {{ old('color', $prCategory->color) == 'red' ? 'selected' : '' }}>Red</option>
                        <option value="indigo" {{ old('color', $prCategory->color) == 'indigo' ? 'selected' : '' }}>Indigo</option>
                        <option value="gray" {{ old('color', $prCategory->color) == 'gray' ? 'selected' : '' }}>Gray</option>
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
                           value="{{ old('sort_order', $prCategory->sort_order) }}"
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
                           {{ old('is_active', $prCategory->is_active) ? 'checked' : '' }}
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">
                        Active (visible in dropdown)
                    </label>
                </div>

                <!-- Usage Info -->
                @if($prCategory->purchaseRequests()->count() > 0)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-yellow-800">Category in use</p>
                                <p class="text-sm text-yellow-700 mt-1">
                                    This category is used by {{ $prCategory->purchaseRequests()->count() }} purchase request(s).
                                    Changes will affect existing records.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Submit -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.pr-categories.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
