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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('phone_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Global Role -->
                            <div>
                                <label for="global_role" class="block text-sm font-medium text-gray-700 mb-1">Global Role *</label>
                                <select name="global_role" id="global_role" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                                <select name="supervisor_id" id="supervisor_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                                    <input type="password" 
                                           name="password_confirmation" 
                                           id="password_confirmation"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                                                    class="business-unit-select w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
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
                                                    class="department-select w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
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
                                                    class="position-select w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
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
                                                   name="business_units[{{ $index }}][is_primary]" 
                                                   value="1"
                                                   {{ $assignment->is_primary ? 'checked' : '' }}
                                                   class="primary-radio rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-md transition-colors duration-200">
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
        // Similar JavaScript as in create.blade.php for dynamic form handling
        // This would handle business unit/department/position cascading dropdowns
        // and adding/removing assignments
    </script>
    @endpush
</x-app-layout>