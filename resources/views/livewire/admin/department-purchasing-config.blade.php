<div>
    <!-- Purchasing Department Configuration -->
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-900 flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Purchasing Department Configuration
            </h3>
        </div>

        <div class="p-6 space-y-6">
            <!-- Enable/Disable Purchasing Department -->
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input type="checkbox" 
                           wire:model.live="isPurchasingDepartment"
                           wire:change="togglePurchasingDepartment"
                           id="is_purchasing_department"
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                </div>
                <div class="ml-3">
                    <label for="is_purchasing_department" class="text-sm font-medium text-gray-900">
                        Enable as Purchasing Department
                    </label>
                    <p class="text-xs text-gray-500 mt-1">
                        When enabled, users in this department can be assigned as purchasing administrators to manage procurement tasks.
                    </p>
                </div>
            </div>

            @if($isPurchasingDepartment)
                <!-- Default Purchasing Admin Selection -->
                <div class="pt-4 border-t border-gray-200">
                    <label for="default_admin" class="block text-sm font-medium text-gray-700 mb-2">
                        Default Purchasing Admin
                    </label>
                    <select wire:model="defaultPurchasingAdminId"
                            wire:change="updateDefaultAdmin"
                            id="default_admin"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <option value="">No Default Admin (Manual Assignment)</option>
                        @foreach($purchasingAdmins as $admin)
                            <option value="{{ $admin['id'] }}">
                                {{ $admin['name'] }} ({{ $admin['position'] }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        When set, new tasks will be automatically assigned to this admin. Leave empty for manual task claiming.
                    </p>
                </div>

                <!-- Current Purchasing Admins -->
                <div class="pt-4 border-t border-gray-200">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Current Purchasing Admins</h4>
                    
                    @if(count($purchasingAdmins) > 0)
                        <div class="space-y-2">
                            @foreach($purchasingAdmins as $admin)
                                <div class="flex items-center justify-between p-3 bg-indigo-50 rounded-lg border border-indigo-100">
                                    <div class="flex items-center min-w-0 flex-1">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center">
                                                <span class="text-white text-xs font-medium">
                                                    {{ strtoupper(substr($admin['name'], 0, 2)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-3 min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $admin['name'] }}</p>
                                            <p class="text-xs text-gray-500 truncate">{{ $admin['email'] }} • {{ $admin['position'] }}</p>
                                        </div>
                                    </div>
                                    <button type="button"
                                            wire:click="removePurchasingAdmin({{ $admin['id'] }})"
                                            wire:confirm="Are you sure you want to remove this purchasing admin?"
                                            class="ml-3 flex-shrink-0 inline-flex items-center px-2.5 py-1.5 text-xs text-red-600 hover:text-red-700 hover:bg-red-50 rounded-md transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Remove
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg border border-gray-200">
                            <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No purchasing admins assigned yet</p>
                            <p class="text-xs text-gray-400 mt-1">Assign users from the list below</p>
                        </div>
                    @endif
                </div>

                <!-- Available Users to Assign -->
                <div class="pt-4 border-t border-gray-200">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Available Users</h4>
                    
                    @php
                        $nonAdminUsers = array_filter($availableUsers, fn($user) => !$user['is_purchasing_admin']);
                    @endphp

                    @if(count($nonAdminUsers) > 0)
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @foreach($nonAdminUsers as $user)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 hover:border-indigo-300 transition-colors">
                                    <div class="flex items-center min-w-0 flex-1">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center">
                                                <span class="text-white text-xs font-medium">
                                                    {{ strtoupper(substr($user['name'], 0, 2)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-3 min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $user['name'] }}</p>
                                            <p class="text-xs text-gray-500 truncate">{{ $user['email'] }} • {{ $user['position'] }}</p>
                                        </div>
                                    </div>
                                    <button type="button"
                                            wire:click="assignPurchasingAdmin({{ $user['id'] }})"
                                            class="ml-3 flex-shrink-0 inline-flex items-center px-3 py-1.5 text-xs bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Assign
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg border border-gray-200">
                            <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">All users in this department are already purchasing admins</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
