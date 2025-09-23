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
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <?php echo e(__('Business Units Management')); ?>

            </h2>
            <a href="<?php echo e(route('admin.business-units.create')); ?>" 
               class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                <i class="fas fa-plus mr-2"></i>Create New Business Unit
            </a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="w-full">
            
            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Filters</h3>
                    
                    <form method="GET" action="<?php echo e(route('admin.business-units.index')); ?>" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" 
                                   name="search" 
                                   id="search"
                                   value="<?php echo e(request('search')); ?>"
                                   placeholder="Name, code, or description..."
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" id="status" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Status</option>
                                <option value="active" <?php echo e(request('status') == 'active' ? 'selected' : ''); ?>>Active</option>
                                <option value="inactive" <?php echo e(request('status') == 'inactive' ? 'selected' : ''); ?>>Inactive</option>
                            </select>
                        </div>

                        <!-- Filter Buttons -->
                        <div class="flex items-end gap-2">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md transition-colors duration-200">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>
                            <a href="<?php echo e(route('admin.business-units.index')); ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition-colors duration-200">
                                <i class="fas fa-times mr-2"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Business Units Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" style="min-width: 1000px;">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Unit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statistics</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parent/Children</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php $__empty_1 = true; $__currentLoopData = $businessUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                                        <span class="text-sm font-medium text-indigo-800">
                                                            <?php echo e($bu->code); ?>

                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo e($bu->name); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo e($bu->code); ?></div>
                                                    <?php if($bu->description): ?>
                                                        <div class="text-xs text-gray-400 mt-1"><?php echo e(Str::limit($bu->description, 50)); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <?php echo e($bu->users_count); ?> Users
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php if($bu->parent): ?>
                                                    <div class="text-xs text-gray-500">Parent:</div>
                                                    <div class="font-medium"><?php echo e($bu->parent->name); ?></div>
                                                <?php endif; ?>
                                                <?php if($bu->children->count() > 0): ?>
                                                    <div class="text-xs text-gray-500 mt-1">Children:</div>
                                                    <div class="text-xs"><?php echo e($bu->children->count()); ?> business units</div>
                                                <?php endif; ?>
                                                <?php if(!$bu->parent && $bu->children->count() == 0): ?>
                                                    <span class="text-xs text-gray-400">Standalone unit</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                <?php echo e($bu->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?>">
                                                <?php echo e($bu->is_active ? 'Active' : 'Inactive'); ?>

                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-3">
                                                <a href="<?php echo e(route('admin.business-units.show', $bu)); ?>" 
                                                   class="text-indigo-600 hover:text-indigo-900 transition-colors duration-200"
                                                   title="View Details">
                                                    <i class="fas fa-eye text-lg"></i>
                                                </a>
                                                <a href="<?php echo e(route('admin.business-units.edit', $bu)); ?>" 
                                                   class="text-yellow-600 hover:text-yellow-900 transition-colors duration-200"
                                                   title="Edit">
                                                    <i class="fas fa-edit text-lg"></i>
                                                </a>
                                                <?php if($bu->code !== 'WG'): ?>
                                                    <button type="button" 
                                                            onclick="openDeleteModal('<?php echo e($bu->id); ?>', '<?php echo e($bu->name); ?>')"
                                                            class="text-red-600 hover:text-red-900 transition-colors duration-200"
                                                            title="Delete">
                                                        <i class="fas fa-trash text-lg"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-gray-400" title="Cannot delete parent company">
                                                        <i class="fas fa-lock text-lg"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            No business units found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if($businessUnits->hasPages()): ?>
                        <div class="mt-6">
                            <?php echo e($businessUnits->appends(request()->query())->links()); ?>

                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Slim Toast Notification -->
        <!-- Enhanced Toast Helper Functions -->
    <script>
        // Wait for Toast Manager Helper Function
        function waitForToastManager(callback, maxAttempts = 50) {
            let attempts = 0;
            function check() {
                if (window.toastManager && typeof window.toastManager.show === 'function') {
                    callback();
                } else if (attempts < maxAttempts) {
                    attempts++;
                    setTimeout(check, 100);
                } else {
                    // Only log error if toast manager fails to initialize
                    console.error('Toast manager not available after maximum attempts');
                }
            }
            check();
        }

        // Basic toast functions
        function showSuccess(message, duration = 5000) {
            waitForToastManager(() => {
                if (window.toastManager && window.toastManager.show) {
                    window.toastManager.show(message, 'success', duration);
                }
            });
        }

        function showError(message, duration = 8000) {
            waitForToastManager(() => {
                if (window.toastManager && window.toastManager.show) {
                    window.toastManager.show(message, 'error', duration);
                }
            });
        }

        function showWarning(message, duration = 6000) {
            waitForToastManager(() => {
                if (window.toastManager && window.toastManager.show) {
                    window.toastManager.show(message, 'warning', duration);
                }
            });
        }

        function showInfo(message, duration = 5000) {
            waitForToastManager(() => {
                if (window.toastManager && window.toastManager.show) {
                    window.toastManager.show(message, 'info', duration);
                }
            });
        }

        function showValidationErrors(errors) {
            errors.forEach((error, index) => {
                setTimeout(() => showError(error, 8000), index * 300);
            });
        }

        // Special Business Unit Toast Function
        function showBusinessUnitToast(title, businessUnitName, type = 'success') {
            waitForToastManager(() => {
                // Create custom toast element for business unit
                const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                
                // Create custom toast element for business unit
                const toast = document.createElement('div');
                toast.id = toastId;
                toast.className = `transform transition-all duration-300 translate-x-full opacity-0 scale-95 pointer-events-auto relative overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 border-l-4 border-l-green-600 mb-2`;
                
                toast.innerHTML = `
                    <div class="px-4 py-3 flex items-start">
                        <div class="flex-shrink-0 mr-3 mt-0.5">
                            <div class="flex items-center justify-center w-6 h-6 rounded-full bg-green-100">
                                <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-gray-900 leading-tight">
                                ${title}
                            </div>
                            <div class="text-sm text-gray-600 mt-1 leading-tight">
                                ${businessUnitName}
                            </div>
                        </div>
                        <div class="flex-shrink-0 ml-3">
                            <button type="button" class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" onclick="this.parentElement.parentElement.parentElement.remove()">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;

                // Find or create toast container
                let toastContainer = document.getElementById('toast-container');
                if (!toastContainer) {
                    toastContainer = document.createElement('div');
                    toastContainer.id = 'toast-container';
                    toastContainer.className = 'fixed top-4 right-4 z-50 w-80 max-w-sm space-y-2';
                    document.body.appendChild(toastContainer);
                }
                
                toastContainer.appendChild(toast);

                // Animate in
                setTimeout(() => {
                    toast.classList.remove('translate-x-full', 'opacity-0', 'scale-95');
                    toast.classList.add('translate-x-0', 'opacity-100', 'scale-100');
                }, 100);

                // Auto remove
                setTimeout(() => {
                    toast.classList.add('translate-x-full', 'opacity-0', 'scale-95');
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 300);
                }, 5000);
            });
        }

        // Basic toast functions
        function showSuccess(message, duration = 5000) {
            waitForToastManager(() => {
                if (window.toastManager && window.toastManager.show) {
                    window.toastManager.show(message, 'success', duration);
                }
            });
        }

        function showError(message, duration = 8000) {
            waitForToastManager(() => {
                if (window.toastManager && window.toastManager.show) {
                    window.toastManager.show(message, 'error', duration);
                }
            });
        }

        function showWarning(message, duration = 6000) {
            waitForToastManager(() => {
                if (window.toastManager && window.toastManager.show) {
                    window.toastManager.show(message, 'warning', duration);
                }
            });
        }

        function showInfo(message, duration = 5000) {
            waitForToastManager(() => {
                if (window.toastManager && window.toastManager.show) {
                    window.toastManager.show(message, 'info', duration);
                }
            });
        }

        function showValidationErrors(errors) {
            errors.forEach((error, index) => {
                setTimeout(() => showError(error, 8000), index * 300);
            });
        }

        // Required Fields Helper
        function showRequiredFields(fields) {
            const fieldList = Array.isArray(fields) ? fields.join(', ') : fields;
            showWarning(`Please fill in required fields: ${fieldList}`, 6000);
        }

        // Quick notification for form validation
        function notifyRequired(fieldName) {
            showWarning(`${fieldName} is required`, 3000);
        }

        // Success helpers for CRUD operations
        function notifyCreated(itemName = 'Item') {
            showSuccess(`${itemName} created successfully`, 4000);
        }

        function notifyUpdated(itemName = 'Item') {
            showSuccess(`${itemName} updated successfully`, 4000);
        }

        function notifyDeleted(itemName = 'Item') {
            showSuccess(`${itemName} deleted successfully`, 4000);
        }

        // Check for Laravel session messages and show them
        document.addEventListener('DOMContentLoaded', function() {
            <?php if(session('success')): ?>
                showSuccess("<?php echo e(session('success')); ?>");
            <?php elseif(session('business_unit_created')): ?>
                <?php $data = session('business_unit_created'); ?>
                showBusinessUnitToast("<?php echo e($data['title']); ?>", "<?php echo e($data['name']); ?>", 'success');
            <?php elseif(session('success_create_unit')): ?>
                <?php $data = session('success_create_unit'); ?>
                showBusinessUnitToast("<?php echo e($data['title']); ?>", "<?php echo e($data['name']); ?>", 'success');
            <?php elseif(session('success_update_unit')): ?>
                <?php $data = session('success_update_unit'); ?>
                showBusinessUnitToast("<?php echo e($data['title']); ?>", "<?php echo e($data['name']); ?>", 'success');
            <?php elseif(session('success_delete_unit')): ?>
                <?php $data = session('success_delete_unit'); ?>
                showBusinessUnitToast("<?php echo e($data['title']); ?>", "<?php echo e($data['name']); ?>", 'success');
            <?php elseif(session('error')): ?>
                showError("<?php echo e(session('error')); ?>");
            <?php elseif(session('warning')): ?>
                showWarning("<?php echo e(session('warning')); ?>");
            <?php elseif(session('info')): ?>
                showInfo("<?php echo e(session('info')); ?>");
            <?php endif; ?>

            <?php if($errors->any()): ?>
                showValidationErrors(<?php echo json_encode($errors->all()); ?>);
            <?php endif; ?>

        });
    </script>

    <!-- Mobile Toast Positioning Helper -->
    <style>
        @media (max-width: 640px) {
            #toast {
                top: 15px !important;
                right: 15px !important;
                left: 15px !important;
                max-width: calc(100vw - 30px) !important;
                min-width: auto !important;
                transform: translateY(-100%) !important;
            }
        }
    </style>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-40 flex items-center justify-center p-4">
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md mx-auto transform transition-all">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 pb-4">
                <h3 class="text-xl font-semibold text-gray-900">Delete Business Unit</h3>
                <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200 p-1 rounded-full hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Modal Content -->
            <div class="px-6 pb-6">
                <!-- Warning Icon -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-50 mb-6">
                    <svg class="h-8 w-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                
                <!-- Message -->
                <div class="text-center mb-6">
                    <p class="text-gray-700 text-base mb-2">Are you sure you want to delete business unit</p>
                    <p class="font-semibold text-gray-900 text-lg mb-4" id="deleteBusinessUnitName"></p>
                    <p class="text-sm text-gray-600 leading-relaxed">This action cannot be undone and will also remove all associated departments and positions.</p>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row sm:justify-center gap-3">
                    <button onclick="closeDeleteModal()" 
                            class="w-full sm:w-auto px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-opacity-50">
                        Cancel
                    </button>
                    <button id="confirmDeleteBtn" 
                            class="w-full sm:w-auto px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50"
                            onclick="confirmDelete()">
                        <span class="flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2 hidden" id="deleteSpinner" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span id="deleteButtonText">Delete</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form for deletion -->
    <form id="deleteForm" method="POST" style="display: none;">
        <?php echo csrf_field(); ?>
        <?php echo method_field('DELETE'); ?>
    </form>

    <script>
        let deleteBusinessUnitId = null;
        let deleteBusinessUnitName = null;

        function openDeleteModal(id, name) {
            deleteBusinessUnitId = id;
            deleteBusinessUnitName = name;
            
            document.getElementById('deleteBusinessUnitName').textContent = `"${name}"`;
            const modal = document.getElementById('deleteModal');
            
            modal.classList.remove('hidden');
            
            // Add smooth fade-in animation
            requestAnimationFrame(() => {
                modal.style.backdropFilter = 'blur(4px)';
                const modalContent = modal.querySelector('div > div');
                modalContent.style.transform = 'scale(0.95) translateY(-10px)';
                modalContent.style.opacity = '0';
                
                requestAnimationFrame(() => {
                    modalContent.style.transition = 'all 0.2s ease-out';
                    modalContent.style.transform = 'scale(1) translateY(0)';
                    modalContent.style.opacity = '1';
                });
            });
            
            // Focus on cancel button for accessibility
            setTimeout(() => {
                modal.querySelector('button[onclick="closeDeleteModal()"]').focus();
            }, 200);
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            const modalContent = modal.querySelector('div > div');
            
            modalContent.style.transition = 'all 0.15s ease-in';
            modalContent.style.transform = 'scale(0.95) translateY(-10px)';
            modalContent.style.opacity = '0';
            
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.style.backdropFilter = 'none';
                
                // Reset button state
                const confirmBtn = document.getElementById('confirmDeleteBtn');
                const buttonText = document.getElementById('deleteButtonText');
                const spinner = document.getElementById('deleteSpinner');
                
                buttonText.textContent = 'Delete';
                spinner.classList.add('hidden');
                confirmBtn.disabled = false;
                confirmBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                
                deleteBusinessUnitId = null;
                deleteBusinessUnitName = null;
            }, 150);
        }

        function confirmDelete() {
            if (deleteBusinessUnitId) {
                const form = document.getElementById('deleteForm');
                form.action = `/admin/business-units/${deleteBusinessUnitId}`;
                
                // Show loading state
                const confirmBtn = document.getElementById('confirmDeleteBtn');
                const buttonText = document.getElementById('deleteButtonText');
                const spinner = document.getElementById('deleteSpinner');
                
                buttonText.textContent = 'Deleting...';
                spinner.classList.remove('hidden');
                confirmBtn.disabled = true;
                confirmBtn.classList.add('opacity-75', 'cursor-not-allowed');
                
                form.submit();
            }
        }

        // Enhanced Toast Notification System with Helpers
        function showToast(message, type = 'success', duration = 4000) {
            const toast = document.getElementById('toast');
            const messageEl = document.getElementById('toast-message');
            const iconEl = document.getElementById('toast-icon');
            
            if (!toast || !messageEl || !iconEl) {
                console.error('Toast elements not found');
                return;
            }
            
            // Set message text and force visibility
            messageEl.textContent = message;
            messageEl.style.color = 'white';
            messageEl.style.fontSize = '13px';
            messageEl.style.fontWeight = '500';
            messageEl.style.display = 'block';
            messageEl.style.visibility = 'visible';
            messageEl.style.opacity = '1';
            
            // Set background color and icon based on type
            let bgColor, icon;
            switch(type) {
                case 'success':
                    bgColor = '#10b981'; // green-500
                    icon = `<svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" style="color: white !important;">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>`;
                    break;
                case 'error':
                    bgColor = '#ef4444'; // red-500
                    icon = `<svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" style="color: white !important;">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>`;
                    break;
                case 'warning':
                    bgColor = '#f59e0b'; // amber-500
                    icon = `<svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" style="color: white !important;">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>`;
                    break;
                case 'info':
                    bgColor = '#3b82f6'; // blue-500
                    icon = `<svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" style="color: white !important;">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>`;
                    break;
            }
            
            toast.style.backgroundColor = bgColor;
            iconEl.innerHTML = icon;
            
            // Show toast
            toast.classList.remove('hidden');
            
            // Animate in with proper responsive positioning
            requestAnimationFrame(() => {
                if (window.innerWidth >= 640) { 
                    // Desktop: slide in from right
                    toast.style.transform = 'translateX(0)';
                } else { 
                    // Mobile: slide down from top
                    toast.style.transform = 'translateY(0)';
                }
            });
            
            // Auto hide after specified duration
            setTimeout(() => {
                hideToast();
            }, duration);
        }

        // Helper Functions for Different Toast Types
        function showSuccess(message, duration = 4000) {
            showToast(message, 'success', duration);
        }

        function showError(message, duration = 6000) {
            showToast(message, 'error', duration);
        }

        function showWarning(message, duration = 5000) {
            showToast(message, 'warning', duration);
        }

        function showInfo(message, duration = 4000) {
            showToast(message, 'info', duration);
        }

        // Validation Helper - Show multiple validation errors
        function showValidationErrors(errors) {
            if (typeof errors === 'object' && errors !== null) {
                // Laravel validation errors format
                const errorMessages = [];
                for (const field in errors) {
                    if (errors[field] && Array.isArray(errors[field])) {
                        errorMessages.push(...errors[field]);
                    } else if (errors[field]) {
                        errorMessages.push(errors[field]);
                    }
                }
                if (errorMessages.length > 0) {
                    showError(errorMessages.join('. '), 8000);
                }
            } else if (typeof errors === 'string') {
                showError(errors, 6000);
            }
        }

        // Required Fields Helper
        function showRequiredFields(fields) {
            const fieldList = Array.isArray(fields) ? fields.join(', ') : fields;
            showWarning(`Please fill in required fields: ${fieldList}`, 6000);
        }

        function hideToast() {
            const toast = document.getElementById('toast');
            
            if (window.innerWidth >= 640) { 
                // Desktop: slide out to right
                toast.style.transform = 'translateX(100%)';
            } else { 
                // Mobile: slide up
                toast.style.transform = 'translateY(-100%)';
            }
            
            setTimeout(() => {
                toast.classList.add('hidden');
                // Reset transform for next show
                if (window.innerWidth >= 640) {
                    toast.style.transform = 'translateX(100%)';
                } else {
                    toast.style.transform = 'translateY(-100%)';
                }
            }, 300);
        }

        // Check for session messages on page load
        document.addEventListener('DOMContentLoaded', function() {
            <?php if(session('success')): ?>
                showToast("<?php echo e(session('success')); ?>", 'success');
            <?php elseif(session('error')): ?>
                showToast("<?php echo e(session('error')); ?>", 'error');
            <?php endif; ?>
        });        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });
    </script>

    <!-- Custom Toast Animation Style -->
    <style>
        @keyframes shrink {
            from { width: 100%; }
            to { width: 0%; }
        }
    </style>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?><?php /**PATH E:\Learning\WGProject\Numbering\resources\views/admin/business-units/index.blade.php ENDPATH**/ ?>