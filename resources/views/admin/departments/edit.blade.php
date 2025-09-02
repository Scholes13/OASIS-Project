<x-app-layout>
    @push('styles')
    <style>
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
                {{ __('Edit Department') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('admin.departments.show', $department) }}" 
                   class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                    <i class="fas fa-eye mr-2"></i>View Department
                </a>
                <a href="{{ route('admin.departments.index') }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Departments
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Info Alert -->
                    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">Edit Department</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p>Update department information. The department code must remain unique within its business unit.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('admin.departments.update', $department) }}" method="POST" id="departmentForm">
                        @csrf
                        @method('PUT')
                        
                        <!-- Department Information -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-sitemap mr-2 text-gray-600"></i>
                                Department Information
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Business Unit -->
                                <div class="md:col-span-2">
                                    <label for="business_unit_id" class="block text-sm font-medium text-gray-700 mb-2">Business Unit *</label>
                                    <select name="business_unit_id" 
                                            id="business_unit_id"
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('business_unit_id') border-red-300 @enderror"
                                            required>
                                        <option value="">Select Business Unit</option>
                                        @foreach($businessUnits as $businessUnit)
                                            <option value="{{ $businessUnit->id }}" {{ old('business_unit_id', $department->business_unit_id) == $businessUnit->id ? 'selected' : '' }}>
                                                {{ $businessUnit->name }} ({{ $businessUnit->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('business_unit_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Department Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Department Name *</label>
                                    <input type="text" 
                                           name="name" 
                                           id="name"
                                           value="{{ old('name', $department->name) }}"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') border-red-300 @enderror"
                                           placeholder="e.g., Human Resources"
                                           required>
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Department Code -->
                                <div>
                                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Department Code *</label>
                                    <input type="text" 
                                           name="code" 
                                           id="code"
                                           value="{{ old('code', $department->code) }}"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('code') border-red-300 @enderror"
                                           placeholder="e.g., HR"
                                           maxlength="10"
                                           style="text-transform: uppercase;"
                                           required>
                                    @error('code')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-500">
                                        Maximum 10 characters. Must be unique within the selected business unit.
                                    </p>
                                </div>

                                <!-- Status -->
                                <div class="md:col-span-2">
                                    <div class="flex items-center">
                                        <input type="checkbox" 
                                               name="is_active" 
                                               id="is_active"
                                               value="1"
                                               {{ old('is_active', $department->is_active) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <label for="is_active" class="ml-2 text-sm text-gray-700">
                                            <span class="font-medium">Active Department</span>
                                            <span class="block text-xs text-gray-500">Active departments can be assigned to users and positions</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Current Statistics -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-chart-bar mr-2 text-gray-600"></i>
                                Current Statistics
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-users text-blue-600 text-xl"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-blue-800">Positions</p>
                                            <p class="text-2xl font-bold text-blue-900">{{ $department->positions->count() }}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-user text-green-600 text-xl"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-green-800">Users</p>
                                            <p class="text-2xl font-bold text-green-900">{{ $department->users->count() }}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-hashtag text-purple-600 text-xl"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-purple-800">Number Sequences</p>
                                            <p class="text-2xl font-bold text-purple-900">{{ $department->numberSequences->count() }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Warning for Deactivation -->
                        @if($department->is_active && ($department->positions->count() > 0 || $department->users->count() > 0))
                        <div class="mb-8">
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-yellow-800">Deactivation Warning</h3>
                                        <div class="mt-2 text-sm text-yellow-700">
                                            <p>This department currently has {{ $department->positions->count() }} position(s) and {{ $department->users->count() }} user(s) assigned. Deactivating this department may affect their access and functionality.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200 bg-gray-50 -mx-6 -mb-6 px-6 py-6 rounded-b-lg">
                            <a href="{{ route('admin.departments.index') }}" 
                               class="inline-flex items-center px-8 py-3 bg-gray-400 hover:bg-gray-500 text-white font-semibold text-base rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                                <i class="fas fa-times mr-2"></i>
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="inline-flex items-center px-8 py-3 bg-blue-800 hover:bg-blue-900 text-white font-semibold text-base rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                                <i class="fas fa-save mr-2"></i>
                                Update Department
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-uppercase department code
            const codeInput = document.getElementById('code');
            codeInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });

            // Form validation
            const form = document.getElementById('departmentForm');
            form.addEventListener('submit', function(e) {
                const businessUnitId = document.getElementById('business_unit_id').value;
                const name = document.getElementById('name').value.trim();
                const code = document.getElementById('code').value.trim();

                if (!businessUnitId) {
                    e.preventDefault();
                    showNotification('Please select a business unit.', 'error');
                    return;
                }

                if (!name) {
                    e.preventDefault();
                    showNotification('Please enter a department name.', 'error');
                    return;
                }

                if (!code) {
                    e.preventDefault();
                    showNotification('Please enter a department code.', 'error');
                    return;
                }

                if (code.length > 10) {
                    e.preventDefault();
                    showNotification('Department code cannot exceed 10 characters.', 'error');
                    return;
                }
            });
        });

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