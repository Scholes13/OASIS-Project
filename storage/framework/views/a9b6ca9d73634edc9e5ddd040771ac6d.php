<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <?php $__env->startPush('styles'); ?>
    <style>
        /* Fix scrollbar issues and prevent layout shifting */
        html {
            overflow-y: scroll; /* Always show vertical scrollbar */
        }

        body {
            overflow-x: hidden; /* Prevent horizontal scrollbar */
        }

        .status-badge {
            border: 1px solid;
        }

        .enterprise-table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .enterprise-table th:first-child {
            border-top-left-radius: 0.5rem;
        }

        .enterprise-table th:last-child {
            border-top-right-radius: 0.5rem;
        }

        .primary-indicator {
            position: relative;
        }

        .primary-indicator::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 20px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 2px;
        }

        .hover-lift {
            transition: all 0.3s ease;
        }

        .hover-lift:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .enterprise-filter {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
        }

        .action-button {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .action-button:hover {
            transform: scale(1.05);
            border-color: currentColor;
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Prevent table from causing horizontal scroll */
        .table-container {
            width: 100%;
            min-width: 0; /* Allow shrinking */
        }

        /* Stabilize layout to prevent shifting */
        .main-container {
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
        }

        /* Fix any potential scrollbar issues on table */
        .overflow-x-auto {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        .overflow-x-auto::-webkit-scrollbar {
            height: 6px;
        }

        .overflow-x-auto::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .overflow-x-auto::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .overflow-x-auto::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
    <?php $__env->stopPush(); ?>

     <?php $__env->slot('header', null, []); ?> 
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    User Management
                </h2>
                <p class="text-sm text-gray-600 mt-1">Manage system users, roles, and business unit assignments</p>
            </div>
            <a href="<?php echo e(route('admin.users.create')); ?>"
               class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold text-sm rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Create New User
            </a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8 main-container">
        <div class="w-full px-4 sm:px-6 lg:px-8">

            <!-- Enhanced Filters Section -->
            <div class="enterprise-filter rounded-xl p-6 mb-8 hover-lift">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Advanced Filters</h3>
                    </div>
                    <div class="text-sm text-gray-500">
                        <?php echo e($users->total()); ?> users found
                    </div>
                </div>

                <form method="GET" action="<?php echo e(route('admin.users.index')); ?>" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Search -->
                    <div class="space-y-2">
                        <label for="search" class="block text-sm font-medium text-gray-700">Search Users</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="text"
                                   name="search"
                                   id="search"
                                   value="<?php echo e(request('search')); ?>"
                                   placeholder="Name, email, or phone..."
                                   class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <!-- Business Unit Filter -->
                    <div class="space-y-2">
                        <label for="business_unit" class="block text-sm font-medium text-gray-700">Business Unit</label>
                        <select name="business_unit" id="business_unit" class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Business Units</option>
                            <?php $__currentLoopData = $businessUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($bu->id); ?>" <?php echo e(request('business_unit') == $bu->id ? 'selected' : ''); ?>>
                                    <?php echo e($bu->name); ?> (<?php echo e($bu->code); ?>)
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <!-- Department Filter -->
                    <div class="space-y-2">
                        <label for="department" class="block text-sm font-medium text-gray-700">Department</label>
                        <select name="department" id="department" class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Departments</option>
                            <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($dept->id); ?>" <?php echo e(request('department') == $dept->id ? 'selected' : ''); ?>>
                                    <?php echo e($dept->name); ?> (<?php echo e($dept->businessUnit->code); ?>)
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <!-- Global Role Filter -->
                    <div class="space-y-2">
                        <label for="global_role" class="block text-sm font-medium text-gray-700">Global Role</label>
                        <select name="global_role" id="global_role" class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Roles</option>
                            <option value="super_admin" <?php echo e(request('global_role') == 'super_admin' ? 'selected' : ''); ?>>Super Administrator</option>
                            <option value="user" <?php echo e(request('global_role') == 'user' ? 'selected' : ''); ?>>User</option>
                        </select>
                    </div>

                    <!-- Filter Actions -->
                    <div class="lg:col-span-4 flex items-end justify-start space-x-3 pt-4 border-t border-gray-200">
                        <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Apply Filters
                        </button>
                        <a href="<?php echo e(route('admin.users.index')); ?>" class="inline-flex items-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm rounded-lg transition-all duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Clear All
                        </a>
                    </div>
                </form>
            </div>

            <!-- Enhanced Users Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover-lift">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">System Users</h3>
                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                            <span><?php echo e($users->firstItem() ?? 0); ?>-<?php echo e($users->lastItem() ?? 0); ?> of <?php echo e($users->total()); ?></span>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="enterprise-table min-w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Primary Assignment</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Business Units</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <!-- Name -->
                                    <td class="px-6 py-4">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900"><?php echo e($user->name); ?></div>
                                            <div class="text-sm text-gray-600"><?php echo e($user->email); ?></div>
                                            <?php if($user->phone_number): ?>
                                                <div class="text-xs text-gray-500 mt-1"><?php echo e($user->phone_number); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Primary Assignment -->
                                    <td class="px-6 py-4">
                                        <?php if($user->primaryDepartment): ?>
                                            <div class="primary-indicator">
                                                <div class="text-sm font-medium text-gray-900"><?php echo e($user->primaryDepartment->businessUnit->name); ?></div>
                                                <div class="text-sm text-gray-600"><?php echo e($user->primaryDepartment->name); ?></div>
                                                <?php if($user->primaryPosition): ?>
                                                    <div class="text-xs text-gray-500 mt-1"><?php echo e($user->primaryPosition->name); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-sm text-gray-400 italic">Not assigned</div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Business Units -->
                                    <td class="px-6 py-4">
                                        <div class="space-y-2">
                                            <?php $__currentLoopData = $user->activeBusinessUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <div class="flex items-center">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                                        <?php echo e($assignment->businessUnit->code); ?>

                                                        <?php if($assignment->is_primary): ?>
                                                            <span class="ml-1 text-gray-700 font-semibold">PRIMARY</span>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <div class="text-xs text-gray-500">
                                                <?php echo e($user->activeBusinessUnits->count()); ?> <?php echo e(Str::plural('assignment', $user->activeBusinessUnits->count())); ?>

                                            </div>
                                        </div>
                                    </td>

                                    <!-- Role -->
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold status-badge
                                            <?php echo e($user->global_role === 'super_admin' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200'); ?>">
                                            <?php echo e($user->global_role === 'super_admin' ? 'Super Administrator' : 'User'); ?>

                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <a href="<?php echo e(route('admin.users.show', $user)); ?>"
                                               class="action-button text-blue-600 hover:text-blue-800 hover:bg-blue-50"
                                               title="View Details">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                            <a href="<?php echo e(route('admin.users.edit', $user)); ?>"
                                               class="action-button text-amber-600 hover:text-amber-800 hover:bg-amber-50"
                                               title="Edit User">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                            <?php if(!$user->isSuperAdmin()): ?>
                                                <button type="button"
                                                        class="action-button text-red-600 hover:text-red-800 hover:bg-red-50"
                                                        title="Deactivate User"
                                                        onclick="showDeleteModal('<?php echo e($user->id); ?>', '<?php echo e($user->name); ?>')">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">No users found</h3>
                                            <p class="text-gray-500 mb-4">Try adjusting your search criteria or create a new user.</p>
                                            <a href="<?php echo e(route('admin.users.create')); ?>"
                                               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-lg">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                                Create New User
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Enhanced Pagination -->
                <?php if($users->hasPages()): ?>
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing <span class="font-medium"><?php echo e($users->firstItem() ?? 0); ?></span> to
                                <span class="font-medium"><?php echo e($users->lastItem() ?? 0); ?></span> of
                                <span class="font-medium"><?php echo e($users->total()); ?></span> results
                            </div>
                            <div>
                                <?php echo e($users->appends(request()->query())->links()); ?>

                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modern Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="hideDeleteModal()"></div>

            <!-- Modal positioning -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div class="relative inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="sm:flex sm:items-start">
                        <!-- Warning Icon -->
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <!-- Content -->
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-semibold text-gray-900" id="modal-title">
                                Deactivate User Account
                            </h3>
                            <div class="mt-3">
                                <p class="text-sm text-gray-600">
                                    Are you sure you want to deactivate <span id="userName" class="font-semibold text-gray-900"></span>?
                                    This action will revoke their system access and they will no longer be able to log in.
                                </p>
                                <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-4 w-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <div class="ml-2">
                                            <p class="text-xs text-amber-700">
                                                This action can be reversed by reactivating the user account later.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Action buttons -->
                <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse sm:gap-3">
                    <button type="button"
                            id="confirmDelete"
                            class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2.5 bg-red-600 text-base font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Deactivate User
                    </button>
                    <button type="button"
                            onclick="hideDeleteModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2.5 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-200">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form for deletion -->
    <form id="deleteForm" method="POST" class="hidden">
        <?php echo csrf_field(); ?>
        <?php echo method_field('DELETE'); ?>
    </form>

    <?php $__env->startPush('scripts'); ?>
    <script>
        let currentUserId = null;

        function showDeleteModal(userId, userName) {
            currentUserId = userId;
            document.getElementById('userName').textContent = userName;
            document.getElementById('deleteModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.body.style.overflow = 'auto'; // Restore scrolling
            currentUserId = null;
        }

        // Confirm delete action
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (currentUserId) {
                const form = document.getElementById('deleteForm');
                form.action = `<?php echo e(route('admin.users.index')); ?>/${currentUserId}`;
                form.submit();
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hideDeleteModal();
            }
        });
    </script>
    <?php $__env->stopPush(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?><?php /**PATH E:\Learning\WGProject\Numbering\resources\views/admin/users/index.blade.php ENDPATH**/ ?>