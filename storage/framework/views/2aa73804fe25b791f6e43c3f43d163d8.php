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
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    <?php echo e(__('Create New Business Unit')); ?>

                </h2>
                <p class="mt-1 text-sm text-gray-600">Add a new business unit with departments and positions</p>
            </div>
            <div class="flex-shrink-0">
                <a href="<?php echo e(route('admin.business-units.index')); ?>" 
                   class="inline-flex items-center bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="pb-6">
        <div class="w-full">
            <form action="<?php echo e(route('admin.business-units.store')); ?>" method="POST" class="space-y-6">
                <?php echo csrf_field(); ?>
                
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
                                           value="<?php echo e(old('code')); ?>"
                                           required
                                           placeholder="e.g., IT, HR, FIN"
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                    <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-2 text-sm text-red-600"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
                                           value="<?php echo e(old('name')); ?>"
                                           required
                                           placeholder="e.g., Information Technology"
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-2 text-sm text-red-600"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
                                          class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old('description')); ?></textarea>
                                <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="mt-2 text-sm text-red-600"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
                                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 <?php $__errorArgs = ['parent_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                        <option value="">Select Parent (Optional)</option>
                                        <?php $__currentLoopData = $parentBusinessUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $parent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($parent->id); ?>" <?php echo e(old('parent_id') == $parent->id ? 'selected' : ''); ?>>
                                                <?php echo e($parent->name); ?> (<?php echo e($parent->code); ?>)
                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <?php $__errorArgs = ['parent_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-2 text-sm text-red-600"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <p class="mt-1 text-xs text-gray-500">Choose a parent business unit if this is a subsidiary</p>
                                </div>

                                <!-- General Manager -->
                                <div>
                                    <label for="manager_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                        General Manager
                                    </label>
                                    <select name="manager_id" 
                                            id="manager_id" 
                                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 <?php $__errorArgs = ['manager_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                        <option value="">Select General Manager (Optional)</option>
                                        <?php $__currentLoopData = $managers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $manager): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($manager->id); ?>" <?php echo e(old('manager_id') == $manager->id ? 'selected' : ''); ?>>
                                                <?php echo e($manager->name); ?> (<?php echo e($manager->email); ?>)
                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <?php $__errorArgs = ['manager_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-2 text-sm text-red-600"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
                                          class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old('address')); ?></textarea>
                                <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="mt-2 text-sm text-red-600"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
                                           value="<?php echo e(old('phone')); ?>"
                                           placeholder="e.g., +62 21 1234 5678"
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                    <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-2 text-sm text-red-600"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
                                           value="<?php echo e(old('email')); ?>"
                                           placeholder="e.g., contact@businessunit.com"
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-300 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-2 text-sm text-red-600"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <p class="mt-1 text-xs text-gray-500">Official email address for this business unit</p>
                                </div>
                            </div>
                        </div>

                        <!-- Status & Notes -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Additional Information</h4>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <p class="text-sm text-blue-800">
                                    <strong>Note:</strong> Departments for this business unit can be managed separately in the <a href="<?php echo e(route('admin.departments.index')); ?>" class="text-blue-600 hover:text-blue-800 underline font-medium">Departments section</a>.
                                </p>
                            </div>
                            
                            <!-- Hidden field for is_active, always set to true -->
                            <input type="hidden" name="is_active" value="1">
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4">
                    <a href="<?php echo e(route('admin.business-units.index')); ?>" 
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
            <?php if($errors->any()): ?>
                showValidationErrors(<?php echo json_encode($errors->all()); ?>);
            <?php endif; ?>
        });
    </script>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?><?php /**PATH E:\Learning\WGProject\Numbering\resources\views/admin/business-units/create.blade.php ENDPATH**/ ?>