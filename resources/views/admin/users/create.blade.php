<x-app-layout>
    @push('styles')
    <style>
        .business-unit-assignment {
            transition: all 0.3s ease-in-out;
        }
        
        .business-unit-assignment:hover {
            transform: translateY(-2px);
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .notification-enter {
            transform: translateX(100%);
        }
        
        .notification-enter-active {
            transform: translateX(0);
            transition: transform 0.3s ease-out;
        }
        
        .notification-exit {
            transform: translateX(0);
        }
        
        .notification-exit-active {
            transform: translateX(100%);
            transition: transform 0.3s ease-in;
        }
    </style>
    @endpush

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create New User') }}
            </h2>
            <a href="{{ route('admin.users.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Back to Users
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Info Alert -->
                    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">User Management</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p>As Super Admin, you can create users and assign them to multiple business units with different roles and departments.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('admin.users.store') }}" method="POST" id="userForm">
                        @csrf
                        
                        <!-- Basic Information -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-user mr-2 text-gray-600"></i>
                                Basic Information
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                    <input type="text" 
                                           name="name" 
                                           id="name"
                                           value="{{ old('name') }}"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') border-red-300 @enderror"
                                           required>
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                    <input type="email" 
                                           name="email" 
                                           id="email"
                                           value="{{ old('email') }}"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('email') border-red-300 @enderror"
                                           required>
                                    @error('email')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Phone Number -->
                                <div>
                                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                    <input type="text" 
                                           name="phone_number" 
                                           id="phone_number"
                                           value="{{ old('phone_number') }}"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('phone_number') border-red-300 @enderror"
                                           placeholder="+62812345678901">
                                    @error('phone_number')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Global Role -->
                                <div>
                                    <label for="global_role" class="block text-sm font-medium text-gray-700 mb-2">Global Role *</label>
                                    <select name="global_role" 
                                            id="global_role"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('global_role') border-red-300 @enderror"
                                            required>
                                        <option value="">Select Global Role</option>
                                        <option value="user" {{ old('global_role') == 'user' ? 'selected' : '' }}>Regular User</option>
                                        <option value="super_admin" {{ old('global_role') == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                                    </select>
                                    @error('global_role')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Supervisor -->
                                <div>
                                    <label for="supervisor_id" class="block text-sm font-medium text-gray-700 mb-2">Supervisor</label>
                                    <select name="supervisor_id" 
                                            id="supervisor_id"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('supervisor_id') border-red-300 @enderror">
                                        <option value="">No Supervisor</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('supervisor_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('supervisor_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="is_active" 
                                               value="1"
                                               {{ old('is_active', true) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <span class="ml-2 text-sm text-gray-700">Active User</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Password Section -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-lock mr-2 text-gray-600"></i>
                                Password
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Password -->
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                                    <input type="password" 
                                           name="password" 
                                           id="password"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('password') border-red-300 @enderror"
                                           required>
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Confirm Password -->
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
                                    <input type="password" 
                                           name="password_confirmation" 
                                           id="password_confirmation"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                           required>
                                </div>
                            </div>
                        </div>

                        <!-- Business Unit Assignments -->
                        <div class="mb-8">
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 flex items-center mb-4">
                                    <i class="fas fa-building mr-2 text-gray-600"></i>
                                    Business Unit Assignments
                                </h3>
                                
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                    <p class="text-sm text-blue-800">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        Users can be assigned to multiple business units with different roles. One assignment must be marked as primary.
                                    </p>
                                </div>

                                <button type="button" 
                                        id="addBusinessUnit"
                                        class="inline-flex items-center px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                                    <i class="fas fa-plus mr-2"></i>
                                    Add Business Unit
                                </button>
                            </div>

                            <div id="businessUnitsContainer" class="space-y-4">
                                <!-- Business unit assignments will be added here dynamically -->
                            </div>

                            @error('business_units')
                                <p class="mt-4 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg p-3">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200 bg-gray-50 -mx-6 -mb-6 px-6 py-6 rounded-b-lg">
                            <a href="{{ route('admin.users.index') }}" 
                               class="inline-flex items-center px-8 py-3 bg-gray-400 hover:bg-gray-500 text-white font-semibold text-base rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                                <i class="fas fa-times mr-2"></i>
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="inline-flex items-center px-8 py-3 bg-blue-800 hover:bg-blue-900 text-white font-semibold text-base rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                                <i class="fas fa-save mr-2"></i>
                                Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Unit Assignment Template -->
    <template id="businessUnitTemplate">
        <div class="business-unit-assignment border border-gray-200 rounded-lg p-6 bg-white shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex justify-between items-start mb-6">
                <h4 class="text-md font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-briefcase mr-2 text-blue-600"></i>
                    Business Unit Assignment
                </h4>
                <button type="button" 
                        class="remove-business-unit inline-flex items-center px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 hover:text-red-800 text-sm font-medium rounded-lg transition-colors duration-200 border border-red-200 hover:border-red-300"
                        title="Remove this assignment">
                    <i class="fas fa-trash mr-1"></i>
                    Remove
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Business Unit -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Business Unit *</label>
                    <select name="business_units[INDEX][business_unit_id]" 
                            class="business-unit-select block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            required>
                        <option value="">Select Business Unit</option>
                        @foreach($businessUnits as $bu)
                            <option value="{{ $bu->id }}" data-departments="{{ $bu->departments->toJson() }}">
                                {{ $bu->name }} ({{ $bu->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Department -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                    <select name="business_units[INDEX][department_id]" 
                            class="department-select block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            required>
                        <option value="">Select Department</option>
                    </select>
                </div>

                <!-- Position -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Position *</label>
                    <select name="business_units[INDEX][position_id]" 
                            class="position-select block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            required>
                        <option value="">Select Position</option>
                    </select>
                </div>
            </div>

            <!-- Primary Assignment -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <label class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200 cursor-pointer">
                    <input type="radio" 
                           name="primary_business_unit" 
                           value="INDEX"
                           class="text-blue-600 focus:ring-blue-500 border-gray-300 h-4 w-4">
                    <span class="ml-3 text-sm font-medium text-gray-700">
                        <i class="fas fa-star mr-2 text-yellow-500"></i>
                        Set as Primary Business Unit
                    </span>
                </label>
                <p class="mt-2 text-xs text-gray-500 ml-7">
                    The primary business unit will be used as the default assignment for this user.
                </p>
            </div>
        </div>
    </template>

    @push('scripts')
    <script>
        let businessUnitIndex = 0;

        document.addEventListener('DOMContentLoaded', function() {
            // Add first business unit assignment by default
            addBusinessUnitAssignment();

            // Add business unit button
            document.getElementById('addBusinessUnit').addEventListener('click', function() {
                addBusinessUnitAssignment();
            });
        });

        function addBusinessUnitAssignment() {
            const template = document.getElementById('businessUnitTemplate');
            const container = document.getElementById('businessUnitsContainer');
            
            // Clone template
            const clone = template.content.cloneNode(true);
            
            // Replace INDEX with actual index
            const html = clone.querySelector('.business-unit-assignment').outerHTML.replace(/INDEX/g, businessUnitIndex);
            
            // Add to container
            container.insertAdjacentHTML('beforeend', html);
            
            // Get the newly added assignment
            const newAssignment = container.lastElementChild;
            
            // Add fade-in animation
            newAssignment.classList.add('fade-in');
            
            // Add event listeners
            setupBusinessUnitAssignment(newAssignment, businessUnitIndex);
            
            businessUnitIndex++;
            
            // Show success notification
            showNotification('Business unit assignment added successfully!', 'success');
        }

        function setupBusinessUnitAssignment(assignment, index) {
            const businessUnitSelect = assignment.querySelector('.business-unit-select');
            const departmentSelect = assignment.querySelector('.department-select');
            const positionSelect = assignment.querySelector('.position-select');
            const removeButton = assignment.querySelector('.remove-business-unit');

            // Business unit change handler
            businessUnitSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const departments = selectedOption.dataset.departments ? JSON.parse(selectedOption.dataset.departments) : [];
                
                // Clear and populate departments
                departmentSelect.innerHTML = '<option value="">Select Department</option>';
                positionSelect.innerHTML = '<option value="">Select Position</option>';
                
                departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.id;
                    option.textContent = `${dept.name} (${dept.code})`;
                    option.dataset.positions = JSON.stringify(dept.positions || []);
                    departmentSelect.appendChild(option);
                });
            });

            // Department change handler
            departmentSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const positions = selectedOption.dataset.positions ? JSON.parse(selectedOption.dataset.positions) : [];
                
                // Clear and populate positions
                positionSelect.innerHTML = '<option value="">Select Position</option>';
                
                positions.forEach(pos => {
                    const option = document.createElement('option');
                    option.value = pos.id;
                    option.textContent = `${pos.name} (${pos.code})`;
                    positionSelect.appendChild(option);
                });
            });

            // Remove button handler
            removeButton.addEventListener('click', function() {
                const assignments = document.querySelectorAll('.business-unit-assignment');
                
                if (assignments.length > 1) {
                    // Show confirmation dialog
                    if (confirm('Are you sure you want to remove this business unit assignment?')) {
                        // Add fade out animation
                        assignment.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                        assignment.style.opacity = '0';
                        assignment.style.transform = 'translateX(-20px)';
                        
                        // Remove after animation
                        setTimeout(() => {
                            assignment.remove();
                            updateBusinessUnitIndices();
                        }, 300);
                    }
                } else {
                    // Show better error message
                    showNotification('At least one business unit assignment is required.', 'warning');
                }
            });
        }

        function updateBusinessUnitIndices() {
            const assignments = document.querySelectorAll('.business-unit-assignment');
            assignments.forEach((assignment, index) => {
                // Update all name attributes
                const inputs = assignment.querySelectorAll('select, input[type="radio"]');
                inputs.forEach(input => {
                    if (input.name) {
                        input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
                    }
                    if (input.type === 'radio') {
                        input.value = index;
                    }
                });
            });
        }

        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
            
            // Set colors based on type
            const colors = {
                'success': 'bg-green-500 text-white',
                'error': 'bg-red-500 text-white',
                'warning': 'bg-yellow-500 text-white',
                'info': 'bg-blue-500 text-white'
            };
            
            notification.className += ` ${colors[type] || colors.info}`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }
    </script>
    @endpush
</x-app-layout>