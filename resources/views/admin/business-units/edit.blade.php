<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Business Unit') }} - {{ $businessUnit->name }}
            </h2>
            <a href="{{ route('admin.business-units.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('admin.business-units.update', $businessUnit) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')
                
                <!-- Basic Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
                        <p class="mt-1 text-sm text-gray-600">Update the basic details for the business unit.</p>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Code -->
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700">Code *</label>
                                <input type="text" 
                                       name="code" 
                                       id="code"
                                       value="{{ old('code', $businessUnit->code) }}"
                                       required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('code') border-red-300 @enderror">
                                @error('code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Name *</label>
                                <input type="text" 
                                       name="name" 
                                       id="name"
                                       value="{{ old('name', $businessUnit->name) }}"
                                       required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-300 @enderror">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" 
                                      id="description"
                                      rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-300 @enderror">{{ old('description', $businessUnit->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Logo Upload -->
                        <div>
                            <label for="logo" class="block text-sm font-medium text-gray-700 mb-2">Business Unit Logo</label>
                            
                            <!-- Current Logo Preview -->
                            @if($businessUnit->logo)
                                <div class="mb-4 flex items-start space-x-4">
                                    <div class="flex-shrink-0">
                                        <img src="{{ asset('storage/' . $businessUnit->logo) }}" 
                                             alt="{{ $businessUnit->name }} Logo" 
                                             class="h-20 w-auto object-contain border border-gray-200 rounded-lg p-2 bg-white">
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-600 mb-2">Current logo</p>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" 
                                                   name="remove_logo" 
                                                   value="1"
                                                   class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                                            <span class="ml-2 text-sm text-red-600">Remove current logo</span>
                                        </label>
                                    </div>
                                </div>
                            @endif

                            <!-- File Upload Input -->
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-indigo-400 transition-colors duration-200">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="logo" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                            <span>Upload a file</span>
                                            <input id="logo" 
                                                   name="logo" 
                                                   type="file" 
                                                   accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                                                   class="sr-only"
                                                   onchange="previewLogo(this)">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, SVG up to 2MB</p>
                                </div>
                            </div>
                            
                            <!-- Preview New Logo -->
                            <div id="logo-preview" class="mt-4 hidden">
                                <p class="text-sm font-medium text-gray-700 mb-2">New logo preview:</p>
                                <img id="preview-image" 
                                     src="" 
                                     alt="Logo Preview" 
                                     class="h-20 w-auto object-contain border border-gray-200 rounded-lg p-2 bg-white">
                            </div>

                            @error('logo')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Parent Business Unit -->
                        <div>
                            <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent Business Unit</label>
                            <select name="parent_id" 
                                    id="parent_id" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('parent_id') border-red-300 @enderror">
                                <option value="">Select Parent (Optional)</option>
                                @foreach($parentBusinessUnits as $parent)
                                    <option value="{{ $parent->id }}" {{ old('parent_id', $businessUnit->parent_id) == $parent->id ? 'selected' : '' }}>
                                        {{ $parent->name }} ({{ $parent->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Contact Information</h3>
                        <p class="mt-1 text-sm text-gray-600">Update contact details for the business unit.</p>
                    </div>
                    <div class="p-6 space-y-6">
                        <!-- Address -->
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                            <textarea name="address" 
                                      id="address"
                                      rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('address') border-red-300 @enderror">{{ old('address', $businessUnit->address) }}</textarea>
                            @error('address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                <input type="text" 
                                       name="phone" 
                                       id="phone"
                                       value="{{ old('phone', $businessUnit->phone) }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('phone') border-red-300 @enderror">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" 
                                       name="email" 
                                       id="email"
                                       value="{{ old('email', $businessUnit->email) }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email') border-red-300 @enderror">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Existing Departments -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Departments</h3>
                        <p class="mt-1 text-sm text-gray-600">Current departments in this business unit.</p>
                    </div>
                    <div class="p-6">
                        @if($businessUnit->departments->count() > 0)
                            <div class="space-y-4">
                                @foreach($businessUnit->departments as $department)
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <h4 class="text-md font-medium text-gray-900">{{ $department->name }} ({{ $department->code }})</h4>
                                                @if($department->description)
                                                    <p class="text-sm text-gray-600">{{ $department->description }}</p>
                                                @endif
                                            </div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $department->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $department->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                        
                                        <!-- Positions -->
                                        @if($department->positions->count() > 0)
                                            <div class="mt-3">
                                                <h5 class="text-sm font-medium text-gray-700 mb-2">Positions:</h5>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($department->positions as $position)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            {{ $position->name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Users Count -->
                                        <div class="mt-3">
                                            <span class="text-sm text-gray-500">
                                                {{ $department->users->count() }} users assigned
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-4">No departments found. You can add departments after creating the business unit.</p>
                        @endif
                    </div>
                </div>

                <!-- Status -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="is_active" 
                                   id="is_active"
                                   value="1"
                                   {{ old('is_active', $businessUnit->is_active) ? 'checked' : '' }}
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('admin.business-units.index') }}" 
                               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition-colors duration-200">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md transition-colors duration-200">
                                <i class="fas fa-save mr-2"></i>Update Business Unit
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function previewLogo(input) {
            const preview = document.getElementById('logo-preview');
            const previewImage = document.getElementById('preview-image');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.classList.add('hidden');
            }
        }
    </script>
    @endpush
</x-app-layout>