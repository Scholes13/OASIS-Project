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
    <?php $__env->stopPush(); ?>

     <?php $__env->slot('header', null, []); ?> 
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <?php echo e(__('Create New Department')); ?>

            </h2>
            <a href="<?php echo e(route('admin.departments.index')); ?>" 
               class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Back to Departments
            </a>
        </div>
     <?php $__env->endSlot(); ?>

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
                                <h3 class="text-sm font-medium text-blue-800">Department Management</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p>Create a new department within a business unit. Each department must have a unique code within its business unit.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="<?php echo e(route('admin.departments.store')); ?>" method="POST" id="departmentForm">
                        <?php echo csrf_field(); ?>
                        
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
                                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php $__errorArgs = ['business_unit_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                            required>
                                        <option value="">Select Business Unit</option>
                                        <?php $__currentLoopData = $businessUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $businessUnit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($businessUnit->id); ?>" <?php echo e(old('business_unit_id') == $businessUnit->id ? 'selected' : ''); ?>>
                                                <?php echo e($businessUnit->name); ?> (<?php echo e($businessUnit->code); ?>)
                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <?php $__errorArgs = ['business_unit_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <!-- Department Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Department Name *</label>
                                    <input type="text" 
                                           name="name" 
                                           id="name"
                                           value="<?php echo e(old('name')); ?>"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                           placeholder="e.g., Human Resources"
                                           required>
                                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <!-- Department Code -->
                                <div>
                                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Department Code *</label>
                                    <input type="text" 
                                           name="code" 
                                           id="code"
                                           value="<?php echo e(old('code')); ?>"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                           placeholder="e.g., HR"
                                           maxlength="10"
                                           style="text-transform: uppercase;"
                                           required>
                                    <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
                                               <?php echo e(old('is_active', true) ? 'checked' : ''); ?>

                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <label for="is_active" class="ml-2 text-sm text-gray-700">
                                            <span class="font-medium">Active Department</span>
                                            <span class="block text-xs text-gray-500">Active departments can be assigned to users and positions</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="mb-8">
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-lightbulb text-yellow-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-yellow-800">Tips for Department Setup</h3>
                                        <div class="mt-2 text-sm text-yellow-700">
                                            <ul class="list-disc list-inside space-y-1">
                                                <li>Use clear, descriptive names for departments</li>
                                                <li>Keep department codes short and meaningful</li>
                                                <li>Department codes must be unique within each business unit</li>
                                                <li>You can add positions to this department after creation</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200 bg-gray-50 -mx-6 -mb-6 px-6 py-6 rounded-b-lg">
                            <a href="<?php echo e(route('admin.departments.index')); ?>" 
                               class="inline-flex items-center px-8 py-3 bg-gray-400 hover:bg-gray-500 text-white font-semibold text-base rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                                <i class="fas fa-times mr-2"></i>
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="inline-flex items-center px-8 py-3 bg-blue-800 hover:bg-blue-900 text-white font-semibold text-base rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                                <i class="fas fa-save mr-2"></i>
                                Create Department
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-uppercase department code
            const codeInput = document.getElementById('code');
            codeInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });

            // Auto-generate code from name
            const nameInput = document.getElementById('name');
            nameInput.addEventListener('input', function() {
                if (!codeInput.value) {
                    // Generate code from name (first letters of words)
                    const words = this.value.trim().split(/\s+/);
                    let code = '';
                    
                    if (words.length === 1) {
                        // Single word - take first 3-4 characters
                        code = words[0].substring(0, 4).toUpperCase();
                    } else {
                        // Multiple words - take first letter of each word
                        code = words.map(word => word.charAt(0)).join('').toUpperCase();
                        // Limit to 6 characters
                        if (code.length > 6) {
                            code = code.substring(0, 6);
                        }
                    }
                    
                    codeInput.value = code;
                }
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
<?php endif; ?><?php /**PATH E:\Learning\WGProject\Numbering\resources\views/admin/departments/create.blade.php ENDPATH**/ ?>