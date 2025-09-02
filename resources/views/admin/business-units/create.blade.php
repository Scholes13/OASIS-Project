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

    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Progress Indicator -->
            <div class="mb-8">
                <div class="flex items-center justify-center">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-indigo-600 text-white rounded-full text-sm font-medium">
                                1
                            </div>
                            <span class="ml-2 text-sm font-medium text-indigo-600">Basic Info</span>
                        </div>
                        <div class="w-16 h-0.5 bg-gray-300"></div>
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-indigo-600 text-white rounded-full text-sm font-medium">
                                2
                            </div>
                            <span class="ml-2 text-sm font-medium text-indigo-600">Contact</span>
                        </div>
                        <div class="w-16 h-0.5 bg-gray-300"></div>
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-indigo-600 text-white rounded-full text-sm font-medium">
                                3
                            </div>
                            <span class="ml-2 text-sm font-medium text-indigo-600">Departments</span>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.business-units.store') }}" method="POST" class="space-y-8">
                @csrf
                
                <!-- Step 1: Basic Information -->
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border border-gray-200">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-white text-indigo-600 rounded-full text-sm font-bold mr-3">
                                1
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Basic Information</h3>
                                <p class="text-indigo-100 text-sm">Enter the basic details for the business unit</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-8">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Code -->
                            <div>
                                <label for="code" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Business Unit Code *
                                </label>
                                <input type="text" 
                                       name="code" 
                                       id="code"
                                       value="{{ old('code') }}"
                                       required
                                       placeholder="e.g., IT, HR, FIN"
                                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 @error('code') border-red-300 @enderror">
                                @error('code')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                    </p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Short code to identify this business unit</p>
                            </div>

                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Business Unit Name *
                                </label>
                                <input type="text" 
                                       name="name" 
                                       id="name"
                                       value="{{ old('name') }}"
                                       required
                                       placeholder="e.g., Information Technology"
                                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 @error('name') border-red-300 @enderror">
                                @error('name')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                    </p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Full name of the business unit</p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mt-8">
                            <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                                Description
                            </label>
                            <textarea name="description" 
                                      id="description"
                                      rows="4"
                                      placeholder="Brief description of the business unit's purpose and responsibilities..."
                                      class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 @error('description') border-red-300 @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Parent Business Unit -->
                        <div class="mt-8">
                            <label for="parent_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                Parent Business Unit
                            </label>
                            <select name="parent_id" 
                                    id="parent_id" 
                                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 @error('parent_id') border-red-300 @enderror">
                                <option value="">Select Parent (Optional)</option>
                                @foreach($parentBusinessUnits as $parent)
                                    <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                        {{ $parent->name }} ({{ $parent->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_id')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Choose a parent business unit if this is a subsidiary</p>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Contact Information -->
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl border border-gray-200">
                    <div class="bg-gradient-to-r from-green-500 to-teal-600 px-6 py-4">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-white text-green-600 rounded-full text-sm font-bold mr-3">
                                2
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Contact Information</h3>
                                <p class="text-green-100 text-sm">Enter contact details for the business unit</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-8">
                        <!-- Address -->
                        <div class="mb-8">
                            <label for="address" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-map-marker-alt mr-2 text-gray-500"></i>Address
                            </label>
                            <textarea name="address" 
                                      id="address"
                                      rows="4"
                                      placeholder="Enter the complete address of the business unit..."
                                      class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-200 @error('address') border-red-300 @enderror">{{ old('address') }}</textarea>
                            @error('address')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-phone mr-2 text-gray-500"></i>Phone Number
                                </label>
                                <input type="text" 
                                       name="phone" 
                                       id="phone"
                                       value="{{ old('phone') }}"
                                       placeholder="e.g., +62 21 1234 5678"
                                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-200 @error('phone') border-red-300 @enderror">
                                @error('phone')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                    </p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Main contact number for this business unit</p>
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-envelope mr-2 text-gray-500"></i>Email Address
                                </label>
                                <input type="email" 
                                       name="email" 
                                       id="email"
                                       value="{{ old('email') }}"
                                       placeholder="e.g., contact@businessunit.com"
                                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-200 @error('email') border-red-300 @enderror">
                                @error('email')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                    </p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Official email address for this business unit</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Departments -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 flex items-center mb-4">
                            <i class="fas fa-building mr-2 text-gray-600"></i>
                            Departments & Positions
                        </h3>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <p class="text-sm text-blue-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                Each department will automatically include three default positions: HOD (Head of Department), Leader, and Staff.
                            </p>
                        </div>

                        <button type="button" 
                                id="add-department"
                                class="inline-flex items-center px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                            <i class="fas fa-plus mr-2"></i>
                            Add Department
                        </button>
                    </div>

                    <div class="p-6">
                        <div id="departments-container" class="space-y-4">
                            <!-- Departments will be added here dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Status</h3>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <input type="checkbox" 
                                   name="is_active" 
                                   id="is_active"
                                   value="1"
                                   {{ old('is_active', true) ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="is_active" class="ml-3 text-sm font-medium text-gray-700">
                                Active Business Unit
                            </label>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 ml-7">
                            Active business units can be used for user assignments and operations.
                        </p>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200 bg-gray-50 -mx-4 -mb-6 px-6 py-6 rounded-b-lg">
                    <a href="{{ route('admin.business-units.index') }}" 
                       class="inline-flex items-center px-8 py-3 bg-gray-400 hover:bg-gray-500 text-white font-semibold text-base rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center px-8 py-3 bg-blue-800 hover:bg-blue-900 text-white font-semibold text-base rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Create Business Unit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Department Template -->
    <template id="departmentTemplate">
        <div class="department-item border border-gray-200 rounded-lg p-6 bg-white shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex justify-between items-start mb-6">
                <h4 class="text-md font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-briefcase mr-2 text-blue-600"></i>
                    Department DEPARTMENT_NUMBER
                </h4>
                <button type="button" 
                        class="remove-department inline-flex items-center px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 hover:text-red-800 text-sm font-medium rounded-lg transition-colors duration-200 border border-red-200 hover:border-red-300"
                        title="Remove this department"
                        style="display: none;">
                    <i class="fas fa-trash mr-1"></i>
                    Remove
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Department Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department Code *</label>
                    <input type="text" 
                           name="departments[DEPARTMENT_INDEX][code]" 
                           required
                           placeholder="e.g., IT, HR, FIN"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <!-- Department Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department Name *</label>
                    <input type="text" 
                           name="departments[DEPARTMENT_INDEX][name]" 
                           required
                           placeholder="e.g., Information Technology"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="departments[DEPARTMENT_INDEX][description]" 
                          rows="3"
                          placeholder="Brief description of the department's responsibilities..."
                          class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
            </div>

            <!-- Default Positions -->
            <div class="pt-4 border-t border-gray-200">
                <h5 class="text-sm font-medium text-gray-700 mb-4 flex items-center">
                    <i class="fas fa-users mr-2 text-gray-500"></i>
                    Default Positions
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <input type="text" 
                               name="departments[DEPARTMENT_INDEX][positions][0][name]" 
                               value="HOD"
                               readonly
                               class="block w-full text-center font-medium text-blue-800 bg-transparent border-0 focus:ring-0 sm:text-sm">
                        <p class="text-xs text-blue-600 text-center mt-1">Head of Department</p>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                        <input type="text" 
                               name="departments[DEPARTMENT_INDEX][positions][1][name]" 
                               value="Leader"
                               readonly
                               class="block w-full text-center font-medium text-green-800 bg-transparent border-0 focus:ring-0 sm:text-sm">
                        <p class="text-xs text-green-600 text-center mt-1">Team Leader</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <input type="text" 
                               name="departments[DEPARTMENT_INDEX][positions][2][name]" 
                               value="Staff"
                               readonly
                               class="block w-full text-center font-medium text-gray-800 bg-transparent border-0 focus:ring-0 sm:text-sm">
                        <p class="text-xs text-gray-600 text-center mt-1">Staff Member</p>
                    </div>
                </div>
            </div>
        </div>
    </template>

    @push('scripts')
    <script>
        let departmentIndex = 0;

        document.addEventListener('DOMContentLoaded', function() {
            // Add first department by default
            addDepartment();

            // Add department button
            document.getElementById('add-department').addEventListener('click', function() {
                addDepartment();
            });

            // Handle remove department
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-department') || e.target.closest('.remove-department')) {
                    e.target.closest('.department-item').remove();
                    updateRemoveButtons();
                    updateDepartmentNumbers();
                }
            });
        });

        function addDepartment() {
            const container = document.getElementById('departments-container');
            const template = document.getElementById('departmentTemplate');
            const clone = template.content.cloneNode(true);
            
            // Replace placeholders
            const html = clone.querySelector('.department-item').outerHTML
                .replace(/DEPARTMENT_INDEX/g, departmentIndex)
                .replace(/DEPARTMENT_NUMBER/g, departmentIndex + 1);
            
            container.insertAdjacentHTML('beforeend', html);
            
            departmentIndex++;
            updateRemoveButtons();
        }

        function updateRemoveButtons() {
            const departments = document.querySelectorAll('.department-item');
            const removeButtons = document.querySelectorAll('.remove-department');
            
            removeButtons.forEach(button => {
                button.style.display = departments.length > 1 ? 'inline-flex' : 'none';
            });
        }

        function updateDepartmentNumbers() {
            const departments = document.querySelectorAll('.department-item');
            departments.forEach((dept, index) => {
                const title = dept.querySelector('h4');
                if (title) {
                    title.innerHTML = `<i class="fas fa-briefcase mr-2 text-blue-600"></i>Department ${index + 1}`;
                }
            });
        }
    </script>
    @endpush
</x-app-layout>