<?php
    use Illuminate\Support\Facades\Auth;
?>

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
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Purchase Request: <?php echo e($purchaseRequest->pr_number); ?></h1>
                <p class="text-sm text-gray-600 mt-1">Created by <?php echo e($purchaseRequest->user->name); ?> on <?php echo e($purchaseRequest->created_at->format('M d, Y')); ?></p>
            </div>
            <div class="flex items-center space-x-3">
                <?php if($purchaseRequest->canBeEdited() && $purchaseRequest->user_id === Auth::id()): ?>
                    <a href="<?php echo e(route('purchase-requests.edit', $purchaseRequest)); ?>" 
                       wire:navigate
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </a>
                <?php endif; ?>
                
                <?php if($purchaseRequest->canBeSubmitted() && $purchaseRequest->user_id === Auth::id()): ?>
                    <form method="POST" action="<?php echo e(route('purchase-requests.submit', $purchaseRequest)); ?>" class="inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                                onclick="return confirm('Are you sure you want to submit this purchase request for approval?')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Submit for Approval
                        </button>
                    </form>
                <?php endif; ?>
                
                <!-- PDF Actions -->
                <a href="<?php echo e(route('purchase-requests.pdf', $purchaseRequest)); ?>" 
                   target="_blank"
                   class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-lg text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    View PDF
                </a>
                
                <a href="<?php echo e(route('purchase-requests.download-pdf', $purchaseRequest)); ?>" 
                   class="inline-flex items-center px-4 py-2 border border-green-300 rounded-lg text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download PDF
                </a>
                
                <a href="<?php echo e(route('purchase-requests.index')); ?>" 
                   wire:navigate
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to List
                </a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

     <?php $__env->slot('breadcrumbs', null, []); ?> 
        <li class="flex">
            <div class="flex items-center">
                <a href="<?php echo e(route('dashboard')); ?>" wire:navigate class="text-gray-400 hover:text-gray-500">
                    <svg class="flex-shrink-0 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    <span class="sr-only">Dashboard</span>
                </a>
            </div>
        </li>
        <li class="flex">
            <div class="flex items-center">
                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                <a href="<?php echo e(route('purchase-requests.index')); ?>" wire:navigate class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">
                    Purchase Requests
                </a>
            </div>
        </li>
        <li class="flex">
            <div class="flex items-center">
                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="ml-4 text-sm font-medium text-gray-500"><?php echo e($purchaseRequest->pr_number); ?></span>
            </div>
        </li>
     <?php $__env->endSlot(); ?>

    <!-- Purchase Request Details -->
    <div class="w-full space-y-6">
        <!-- PR Header Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Purchase Request Information</h3>
                    <?php
                        $statusConfig = [
                            'draft' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => 'Draft'],
                            'submitted' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Submitted'],
                            'in_approval' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'In Approval'],
                            'approved' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Approved'],
                            'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Rejected'],
                            'voided' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => 'Voided'],
                        ];
                        $config = $statusConfig[$purchaseRequest->status] ?? $statusConfig['draft'];
                    ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo e($config['bg']); ?> <?php echo e($config['text']); ?>">
                        <?php echo e($config['label']); ?>

                    </span>
                </div>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">PR Number</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono"><?php echo e($purchaseRequest->pr_number); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Requestor</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo e($purchaseRequest->user->name); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Department</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo e($purchaseRequest->department->name ?? 'N/A'); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Date of Request</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo e($purchaseRequest->date_of_request->format('M d, Y')); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Total Amount</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-semibold"><?php echo e($purchaseRequest->currency); ?> <?php echo e(number_format($purchaseRequest->total_amount, 2)); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo e($purchaseRequest->created_at->format('M d, Y H:i')); ?></dd>
                    </div>
                </div>
                
                <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Purpose / Requirements</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo e($purchaseRequest->keperluan); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Detailed Description</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo e($purchaseRequest->used_for); ?></dd>
                    </div>
                </div>
                
                <!-- PDF Export Section -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">📄 Export Options</h4>
                            <p class="text-sm text-gray-500">Download or view this purchase request as PDF document</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <a href="<?php echo e(route('purchase-requests.pdf', $purchaseRequest)); ?>" 
                               target="_blank"
                               class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-lg text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                View PDF
                            </a>
                            
                            <a href="<?php echo e(route('purchase-requests.download-pdf', $purchaseRequest)); ?>" 
                               class="inline-flex items-center px-4 py-2 border border-green-300 rounded-lg text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PR Items -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Purchase Items</h3>
                <p class="text-sm text-gray-600 mt-1"><?php echo e($purchaseRequest->items->count()); ?> item(s) in this purchase request</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expense Dept</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php $__currentLoopData = $purchaseRequest->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo e($item->item_order); ?>

                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo e($item->item_name); ?></div>
                                    <?php if($item->brand_name): ?>
                                        <div class="text-sm text-gray-500">Brand: <?php echo e($item->brand_name); ?></div>
                                    <?php endif; ?>
                                    <?php if($item->item_description): ?>
                                        <div class="text-sm text-gray-500 mt-1"><?php echo e($item->item_description); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo e($item->supplier_name ?: 'Not specified'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo e(number_format($item->quantity, 2)); ?> <?php echo e($item->unit); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo e($item->currency); ?> <?php echo e(number_format($item->unit_price, 2)); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo e($item->currency); ?> <?php echo e(number_format($item->total_price, 2)); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo e($item->expenseDepartment->name ?? 'N/A'); ?>

                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                Total Amount:
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                <?php echo e($purchaseRequest->currency); ?> <?php echo e(number_format($purchaseRequest->total_amount, 2)); ?>

                            </td>
                            <td class="px-6 py-4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Approval Status -->
        <?php if($purchaseRequest->approvals->count() > 0): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Approval Status</h3>
                    <p class="text-sm text-gray-600 mt-1">Track the approval progress of this purchase request</p>
                </div>
                
                <div class="p-6">
                    <div class="flow-root">
                        <ul class="-mb-8">
                            <?php $__currentLoopData = $purchaseRequest->approvals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $approval): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    <div class="relative pb-8">
                                        <?php if(!$loop->last): ?>
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                        <?php endif; ?>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <?php
                                                    $iconConfig = [
                                                        'pending' => ['bg' => 'bg-gray-500', 'icon' => 'clock'],
                                                        'approved' => ['bg' => 'bg-green-500', 'icon' => 'check'],
                                                        'rejected' => ['bg' => 'bg-red-500', 'icon' => 'x'],
                                                    ];
                                                    $config = $iconConfig[$approval->status] ?? $iconConfig['pending'];
                                                ?>
                                                <span class="h-8 w-8 rounded-full <?php echo e($config['bg']); ?> flex items-center justify-center ring-8 ring-white">
                                                    <?php if($config['icon'] === 'check'): ?>
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    <?php elseif($config['icon'] === 'x'): ?>
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    <?php else: ?>
                                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm text-gray-500">
                                                        Step <?php echo e($approval->step_order); ?>: 
                                                        <span class="font-medium text-gray-900"><?php echo e($approval->approver->name ?? 'Unknown Approver'); ?></span>
                                                        <?php if($approval->status === 'approved'): ?>
                                                            approved this request
                                                        <?php elseif($approval->status === 'rejected'): ?>
                                                            rejected this request
                                                        <?php else: ?>
                                                            is reviewing this request
                                                        <?php endif; ?>
                                                    </p>
                                                    <?php if($approval->notes): ?>
                                                        <p class="text-sm text-gray-700 mt-1"><?php echo e($approval->notes); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                    <?php if($approval->responded_at): ?>
                                                        <time><?php echo e($approval->responded_at->format('M d, Y H:i')); ?></time>
                                                    <?php elseif($approval->assigned_at): ?>
                                                        <time>Assigned <?php echo e($approval->assigned_at->format('M d, Y')); ?></time>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Action Buttons for Special Cases -->
        <?php if($purchaseRequest->canBeVoided() && ($purchaseRequest->user_id === Auth::id() || in_array(session('current_user_role'), ['admin', 'manager']))): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Danger Zone</h3>
                    
                    <form method="POST" action="<?php echo e(route('purchase-requests.void', $purchaseRequest)); ?>" class="inline">
                        <?php echo csrf_field(); ?>
                        <div class="mb-4">
                            <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">Reason for voiding</label>
                            <textarea name="reason" id="reason" rows="3" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      placeholder="Please provide a reason for voiding this purchase request..."></textarea>
                        </div>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-red-300 rounded-lg text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
                                onclick="return confirm('Are you sure you want to void this purchase request? This action cannot be undone.')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Void Purchase Request
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?><?php /**PATH E:\Learning\WGProject\Numbering\resources\views/purchase-requests/show.blade.php ENDPATH**/ ?>