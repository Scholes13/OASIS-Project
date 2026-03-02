<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit User') }}: {{ $user->name }}
            </h2>
            <a href="{{ route('admin.users.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="w-full">
            <form action="{{ route('admin.users.update', $user) }}" method="POST" id="userForm">
                @csrf
                @method('PUT')
                
                <!-- Basic Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                <input type="text" 
                                       name="name" 
                                       id="name"
                                       value="{{ old('name', $user->name) }}"
                                       required
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                <input type="email" 
                                       name="email" 
                                       id="email"
                                       value="{{ old('email', $user->email) }}"
                                       required
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="text" 
                                       name="phone_number" 
                                       id="phone_number"
                                       value="{{ old('phone_number', $user->phone_number) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary">
                                @error('phone_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Global Role -->
                            <div>
                                <label for="global_role" class="block text-sm font-medium text-gray-700 mb-1">Global Role *</label>
                                <select name="global_role" id="global_role" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary">
                                    <option value="user" {{ old('global_role', $user->global_role) === 'user' ? 'selected' : '' }}>User</option>
                                    <option value="super_admin" {{ old('global_role', $user->global_role) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                                </select>
                                @error('global_role')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Supervisor -->
                            <div>
                                <label for="supervisor_id" class="block text-sm font-medium text-gray-700 mb-1">Supervisor</label>
                                <select name="supervisor_id" id="supervisor_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary">
                                    <option value="">No Supervisor</option>
                                    @foreach($users as $supervisor)
                                        <option value="{{ $supervisor->id }}" {{ old('supervisor_id', $user->supervisor_id) == $supervisor->id ? 'selected' : '' }}>
                                            {{ $supervisor->name }} ({{ $supervisor->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('supervisor_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           name="is_active" 
                                           id="is_active"
                                           value="1"
                                           {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring-primary">
                                    <label for="is_active" class="ml-2 text-sm text-gray-700">Active</label>
                                </div>
                                @error('is_active')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Password Section -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Change Password (Optional)</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                    <input type="password" 
                                           name="password" 
                                           id="password"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary">
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                                    <input type="password" 
                                           name="password_confirmation" 
                                           id="password_confirmation"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Business Unit Assignments -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Business Unit Assignments</h3>
                        
                        <div id="business-unit-assignments">
                            @foreach($user->activeBusinessUnits as $index => $assignment)
                                <div class="business-unit-assignment border border-gray-200 rounded-lg p-4 mb-4" data-index="{{ $index }}">
                                    <div class="flex justify-between items-center mb-4">
                                        <h4 class="text-md font-medium text-gray-900">Assignment {{ $index + 1 }}</h4>
                                        @if($index > 0)
                                            <button type="button" class="remove-assignment text-red-600 hover:text-red-800">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        @endif
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <!-- Business Unit -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Business Unit *</label>
                                            <select name="business_units[{{ $index }}][business_unit_id]" 
                                                    class="business-unit-select w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary" 
                                                    required>
                                                <option value="">Select Business Unit</option>
                                                @foreach($businessUnits as $bu)
                                                    <option value="{{ $bu->id }}" 
                                                            {{ $assignment->business_unit_id == $bu->id ? 'selected' : '' }}
                                                            data-departments="{{ $bu->departments->toJson() }}">
                                                        {{ $bu->name }} ({{ $bu->code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Department -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Department *</label>
                                            <select name="business_units[{{ $index }}][department_id]" 
                                                    class="department-select w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary" 
                                                    required>
                                                <option value="">Select Department</option>
                                                @if($assignment->businessUnit)
                                                    @foreach($assignment->businessUnit->departments as $dept)
                                                        <option value="{{ $dept->id }}" 
                                                                {{ $assignment->department_id == $dept->id ? 'selected' : '' }}
                                                                data-positions="{{ $dept->positions->toJson() }}">
                                                            {{ $dept->name }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>

                                        <!-- Position -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Position *</label>
                                            <select name="business_units[{{ $index }}][position_id]" 
                                                    class="position-select w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary" 
                                                    required>
                                                <option value="">Select Position</option>
                                                @if($assignment->department)
                                                    @foreach($assignment->department->positions as $pos)
                                                        <option value="{{ $pos->id }}" {{ $assignment->position_id == $pos->id ? 'selected' : '' }}>
                                                            {{ $pos->name }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>


                                    </div>

                                    <!-- Primary Assignment -->
                                    <div class="mt-4">
                                        <div class="flex items-center">
                                            <input type="radio"
                                                   name="primary_business_unit"
                                                   value="{{ $index }}"
                                                   {{ $assignment->is_primary ? 'checked' : '' }}
                                                   class="primary-radio rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring-primary">
                                            <label class="ml-2 text-sm text-gray-700">Set as Primary Assignment</label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" id="add-assignment" class="mt-4 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors duration-200">
                            <i class="fas fa-plus mr-2"></i>Add Another Assignment
                        </button>

                        @error('business_units')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-end space-x-4">
                            <a href="{{ route('admin.users.index') }}" 
                               class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md transition-colors duration-200">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-primary hover:bg-blue-600 text-white px-6 py-2 rounded-md transition-colors duration-200">
                                <i class="fas fa-save mr-2"></i>Update User
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        let businessUnitIndex = {{ $user->activeBusinessUnits->count() }};

        document.addEventListener('DOMContentLoaded', function() {
            // Add assignment button
            document.getElementById('add-assignment').addEventListener('click', function() {
                addBusinessUnitAssignment();
            });

            // Setup existing assignments
            document.querySelectorAll('.business-unit-assignment').forEach(function(assignment, index) {
                setupBusinessUnitAssignment(assignment, index);
            });
        });

        function addBusinessUnitAssignment() {
            const container = document.getElementById('business-unit-assignments');

            // Create new assignment HTML
            const assignmentHtml = `
                <div class="business-unit-assignment border border-gray-200 rounded-lg p-4 mb-4" data-index="${businessUnitIndex}">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-md font-medium text-gray-900">Assignment ${businessUnitIndex + 1}</h4>
                        <button type="button" class="remove-assignment text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Business Unit -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Business Unit *</label>
                            <select name="business_units[${businessUnitIndex}][business_unit_id]"
                                    class="business-unit-select w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary"
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department *</label>
                            <select name="business_units[${businessUnitIndex}][department_id]"
                                    class="department-select w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary"
                                    required>
                                <option value="">Select Department</option>
                            </select>
                        </div>

                        <!-- Position -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Position *</label>
                            <select name="business_units[${businessUnitIndex}][position_id]"
                                    class="position-select w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary"
                                    required>
                                <option value="">Select Position</option>
                            </select>
                        </div>
                    </div>

                    <!-- Primary Assignment -->
                    <div class="mt-4">
                        <div class="flex items-center">
                            <input type="radio"
                                   name="primary_business_unit"
                                   value="${businessUnitIndex}"
                                   class="primary-radio rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring-primary">
                            <label class="ml-2 text-sm text-gray-700">Set as Primary Assignment</label>
                        </div>
                    </div>
                </div>
            `;

            // Add to container
            container.insertAdjacentHTML('beforeend', assignmentHtml);

            // Get the newly added assignment
            const newAssignment = container.lastElementChild;

            // Setup event listeners for the new assignment
            setupBusinessUnitAssignment(newAssignment, businessUnitIndex);

            businessUnitIndex++;
        }

        function setupBusinessUnitAssignment(assignment, index) {
            const businessUnitSelect = assignment.querySelector('.business-unit-select');
            const departmentSelect = assignment.querySelector('.department-select');
            const positionSelect = assignment.querySelector('.position-select');
            const removeButton = assignment.querySelector('.remove-assignment');

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
            if (removeButton) {
                removeButton.addEventListener('click', function() {
                    const assignments = document.querySelectorAll('.business-unit-assignment');

                    if (assignments.length > 1) {
                        if (confirm('Are you sure you want to remove this business unit assignment?')) {
                            assignment.remove();
                            updateAssignmentIndices();
                        }
                    } else {
                        alert('At least one business unit assignment is required.');
                    }
                });
            }
        }

        function updateAssignmentIndices() {
            const assignments = document.querySelectorAll('.business-unit-assignment');
            assignments.forEach((assignment, index) => {
                // Update assignment title
                const title = assignment.querySelector('h4');
                if (title) {
                    title.textContent = `Assignment ${index + 1}`;
                }

                // Update all name attributes for selects
                const selects = assignment.querySelectorAll('select');
                selects.forEach(select => {
                    if (select.name) {
                        select.name = select.name.replace(/\[\d+\]/, `[${index}]`);
                    }
                });

                // Update radio button values
                const radio = assignment.querySelector('input[type="radio"]');
                if (radio) {
                    radio.value = index;
                }

                // Update data-index
                assignment.setAttribute('data-index', index);
            });
        }
    </script>
    @endpush
</x-app-layout>