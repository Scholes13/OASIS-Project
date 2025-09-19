<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Create New Business Unit') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">Add a new business unit with departments and positions</p>
            </div>
            <div class="flex-shrink-0">
                <a href="{{ route('admin.business-units.index') }}" 
                   class="inline-flex items-center bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="pb-6">
        <div class="w-full">
            <form action="{{ route('admin.business-units.store') }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Business Unit Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900">Business Unit Information</h3>
                        <p class="text-gray-600 text-sm mt-1">Enter all required information for the new business unit</p>
                    </div>
                    
                    <div class="p-6">
                        <!-- Basic Information Section -->
                        <div class="mb-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Basic Information</h4>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                                <!-- Code -->
                                <div>
                                    <label for="code" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Business Unit Code <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           name="code" 
                                           id="code"
                                           value="{{ old('code') }}"
                                           required
                                           placeholder="e.g., IT, HR, FIN"
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 @error('code') border-red-300 @enderror">
                                    @error('code')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-500">Short code to identify this business unit</p>
                                </div>

                                <!-- Name -->
                                <div>
                                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Business Unit Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           name="name" 
                                           id="name"
                                           value="{{ old('name') }}"
                                           required
                                           placeholder="e.g., Information Technology"
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 @error('name') border-red-300 @enderror">
                                    @error('name')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-500">Full name of the business unit</p>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mb-6">
                                <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Description
                                </label>
                                <textarea name="description" 
                                          id="description"
                                          rows="4"
                                          placeholder="Brief description of the business unit's purpose and responsibilities..."
                                          class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 @error('description') border-red-300 @enderror">{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Management Section -->
                        <div class="mb-6 pb-6 border-b border-gray-200">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Management</h4>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Parent Business Unit -->
                                <div>
                                    <label for="parent_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Parent Business Unit
                                    </label>
                                    <select name="parent_id" 
                                            id="parent_id" 
                                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 @error('parent_id') border-red-300 @enderror">
                                        <option value="">Select Parent (Optional)</option>
                                        @foreach($parentBusinessUnits as $parent)
                                            <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                                {{ $parent->name }} ({{ $parent->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('parent_id')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-500">Choose a parent business unit if this is a subsidiary</p>
                                </div>

                                <!-- General Manager -->
                                <div>
                                    <label for="manager_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                        General Manager
                                    </label>
                                    <select name="manager_id" 
                                            id="manager_id" 
                                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 @error('manager_id') border-red-300 @enderror">
                                        <option value="">Select General Manager (Optional)</option>
                                        @foreach($managers as $manager)
                                            <option value="{{ $manager->id }}" {{ old('manager_id') == $manager->id ? 'selected' : '' }}>
                                                {{ $manager->name }} ({{ $manager->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('manager_id')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-500">Choose the General Manager who will lead this business unit (can be assigned later)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information Section -->
                        <div class="mb-6 pb-6 border-b border-gray-200">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Contact Information</h4>
                            
                            <!-- Address -->
                            <div class="mb-6">
                                <label for="address" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Address
                                </label>
                                <textarea name="address" 
                                          id="address"
                                          rows="4"
                                          placeholder="Enter the complete address of the business unit..."
                                          class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 @error('address') border-red-300 @enderror">{{ old('address') }}</textarea>
                                @error('address')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Phone -->
                                <div>
                                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Phone Number
                                    </label>
                                    <input type="text" 
                                           name="phone" 
                                           id="phone"
                                           value="{{ old('phone') }}"
                                           placeholder="e.g., +62 21 1234 5678"
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 @error('phone') border-red-300 @enderror">
                                    @error('phone')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-500">Main contact number for this business unit</p>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Email Address
                                    </label>
                                    <input type="email" 
                                           name="email" 
                                           id="email"
                                           value="{{ old('email') }}"
                                           placeholder="e.g., contact@businessunit.com"
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 @error('email') border-red-300 @enderror">
                                    @error('email')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-500">Official email address for this business unit</p>
                                </div>
                            </div>
                        </div>

                        <!-- Status & Notes -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Additional Information</h4>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <p class="text-sm text-blue-800">
                                    <strong>Note:</strong> Departments for this business unit can be managed separately in the <a href="{{ route('admin.departments.index') }}" class="text-blue-600 hover:text-blue-800 underline font-medium">Departments section</a>.
                                </p>
                            </div>
                            
                            <!-- Hidden field for is_active, always set to true -->
                            <input type="hidden" name="is_active" value="1">
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('admin.business-units.index') }}" 
                       class="inline-flex items-center px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors duration-200">
                        Cancel
                    </a>
                    <button type="submit" 
                            onclick="return validateForm(event)"
                            class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                        Create Business Unit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Helper Example Script -->
    <script>
        // Example validation with toast helpers
        function validateForm(event) {
            event.preventDefault();
            
            const form = event.target.closest('form');
            const nameField = form.querySelector('input[name="name"]');
            const codeField = form.querySelector('input[name="code"]');
            
            // Check required fields
            if (!nameField.value.trim()) {
                notifyRequired('Business Unit Name');
                nameField.focus();
                return false;
            }
            
            if (!codeField.value.trim()) {
                notifyRequired('Business Unit Code');
                codeField.focus();
                return false;
            }
            
            // Show loading state
            const submitBtn = event.target;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating...';
            
            // Submit form
            form.submit();
            
            return true;
        }

        // Show validation errors if they exist
        document.addEventListener('DOMContentLoaded', function() {
            @if($errors->any())
                showValidationErrors({!! json_encode($errors->all()) !!});
            @endif
        });
    </script>
</x-app-layout>