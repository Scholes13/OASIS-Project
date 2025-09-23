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
                <?php echo e(__('Edit User')); ?>: <?php echo e($user->name); ?>

            </h2>
            <a href="<?php echo e(route('admin.users.index')); ?>" 
               class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="w-full">
            <form action="<?php echo e(route('admin.users.update', $user)); ?>" method="POST" id="userForm">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                
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
                                       value="<?php echo e(old('name', $user->name)); ?>"
                                       required
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                <input type="email" 
                                       name="email" 
                                       id="email"
                                       value="<?php echo e(old('email', $user->email)); ?>"
                                       required
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <?php $__errorArgs = ['email'];
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

                            <!-- Phone -->
                            <div>
                                <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="text" 
                                       name="phone_number" 
                                       id="phone_number"
                                       value="<?php echo e(old('phone_number', $user->phone_number)); ?>"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <?php $__errorArgs = ['phone_number'];
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

                            <!-- Global Role -->
                            <div>
                                <label for="global_role" class="block text-sm font-medium text-gray-700 mb-1">Global Role *</label>
                                <select name="global_role" id="global_role" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="user" <?php echo e(old('global_role', $user->global_role) === 'user' ? 'selected' : ''); ?>>User</option>
                                    <option value="super_admin" <?php echo e(old('global_role', $user->global_role) === 'super_admin' ? 'selected' : ''); ?>>Super Admin</option>
                                </select>
                                <?php $__errorArgs = ['global_role'];
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

                            <!-- Supervisor -->
                            <div>
                                <label for="supervisor_id" class="block text-sm font-medium text-gray-700 mb-1">Supervisor</label>
                                <select name="supervisor_id" id="supervisor_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">No Supervisor</option>
                                    <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supervisor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($supervisor->id); ?>" <?php echo e(old('supervisor_id', $user->supervisor_id) == $supervisor->id ? 'selected' : ''); ?>>
                                            <?php echo e($supervisor->name); ?> (<?php echo e($supervisor->email); ?>)
                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php $__errorArgs = ['supervisor_id'];
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

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           name="is_active" 
                                           id="is_active"
                                           value="1"
                                           <?php echo e(old('is_active', $user->is_active) ? 'checked' : ''); ?>

                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <label for="is_active" class="ml-2 text-sm text-gray-700">Active</label>
                                </div>
                                <?php $__errorArgs = ['is_active'];
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
                                    <?php $__errorArgs = ['password'];
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
                            <?php $__currentLoopData = $user->activeBusinessUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="business-unit-assignment border border-gray-200 rounded-lg p-4 mb-4" data-index="<?php echo e($index); ?>">
                                    <div class="flex justify-between items-center mb-4">
                                        <h4 class="text-md font-medium text-gray-900">Assignment <?php echo e($index + 1); ?></h4>
                                        <?php if($index > 0): ?>
                                            <button type="button" class="remove-assignment text-red-600 hover:text-red-800">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <!-- Business Unit -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Business Unit *</label>
                                            <select name="business_units[<?php echo e($index); ?>][business_unit_id]" 
                                                    class="business-unit-select w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                                                    required>
                                                <option value="">Select Business Unit</option>
                                                <?php $__currentLoopData = $businessUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($bu->id); ?>" 
                                                            <?php echo e($assignment->business_unit_id == $bu->id ? 'selected' : ''); ?>

                                                            data-departments="<?php echo e($bu->departments->toJson()); ?>">
                                                        <?php echo e($bu->name); ?> (<?php echo e($bu->code); ?>)
                                                    </option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>

                                        <!-- Department -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Department *</label>
                                            <select name="business_units[<?php echo e($index); ?>][department_id]" 
                                                    class="department-select w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                                                    required>
                                                <option value="">Select Department</option>
                                                <?php if($assignment->businessUnit): ?>
                                                    <?php $__currentLoopData = $assignment->businessUnit->departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($dept->id); ?>" 
                                                                <?php echo e($assignment->department_id == $dept->id ? 'selected' : ''); ?>

                                                                data-positions="<?php echo e($dept->positions->toJson()); ?>">
                                                            <?php echo e($dept->name); ?>

                                                        </option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>

                                        <!-- Position -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Position *</label>
                                            <select name="business_units[<?php echo e($index); ?>][position_id]" 
                                                    class="position-select w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                                                    required>
                                                <option value="">Select Position</option>
                                                <?php if($assignment->department): ?>
                                                    <?php $__currentLoopData = $assignment->department->positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pos): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($pos->id); ?>" <?php echo e($assignment->position_id == $pos->id ? 'selected' : ''); ?>>
                                                            <?php echo e($pos->name); ?>

                                                        </option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>


                                    </div>

                                    <!-- Primary Assignment -->
                                    <div class="mt-4">
                                        <div class="flex items-center">
                                            <input type="radio"
                                                   name="primary_business_unit"
                                                   value="<?php echo e($index); ?>"
                                                   <?php echo e($assignment->is_primary ? 'checked' : ''); ?>

                                                   class="primary-radio rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <label class="ml-2 text-sm text-gray-700">Set as Primary Assignment</label>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                        <button type="button" id="add-assignment" class="mt-4 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors duration-200">
                            <i class="fas fa-plus mr-2"></i>Add Another Assignment
                        </button>

                        <?php $__errorArgs = ['business_units'];
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

                <!-- Submit Buttons -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-end space-x-4">
                            <a href="<?php echo e(route('admin.users.index')); ?>" 
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

    <?php $__env->startPush('scripts'); ?>
    <script>
        let businessUnitIndex = <?php echo e($user->activeBusinessUnits->count()); ?>;

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
                                    class="business-unit-select w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                <option value="">Select Business Unit</option>
                                <?php $__currentLoopData = $businessUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($bu->id); ?>" data-departments="<?php echo e($bu->departments->toJson()); ?>">
                                        <?php echo e($bu->name); ?> (<?php echo e($bu->code); ?>)
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <!-- Department -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department *</label>
                            <select name="business_units[${businessUnitIndex}][department_id]"
                                    class="department-select w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                <option value="">Select Department</option>
                            </select>
                        </div>

                        <!-- Position -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Position *</label>
                            <select name="business_units[${businessUnitIndex}][position_id]"
                                    class="position-select w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                   class="primary-radio rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
<?php endif; ?><?php /**PATH E:\Learning\WGProject\Numbering\resources\views/admin/users/edit.blade.php ENDPATH**/ ?>