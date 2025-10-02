<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PR Verification - <?php echo e($purchaseRequest->pr_number); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Purchase Request Verification</h1>
                <p class="mt-2 text-gray-600">This document has been digitally verified</p>
            </div>

            <!-- Verification Badge -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <div>
                        <?php if($verificationData['type'] === 'requestor'): ?>
                            <p class="text-green-800 font-medium">Verified Request Creator</p>
                            <p class="text-green-700 text-sm">
                                Created by <?php echo e($verificationData['verified_by']->name); ?> on <?php echo e($verificationData['verified_at']->format('d F Y, H:i')); ?>

                            </p>
                        <?php else: ?>
                            <p class="text-green-800 font-medium">Verified <?php echo e($verificationData['role']); ?></p>
                            <p class="text-green-700 text-sm">
                                Approved by <?php echo e($verificationData['verified_by']->name); ?> on <?php echo e($verificationData['verified_at']->format('d F Y, H:i')); ?>

                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Purchase Request Details -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-900">Purchase Request Details</h2>
                        <span class="text-2xl font-bold text-indigo-600"><?php echo e($purchaseRequest->pr_number); ?></span>
                    </div>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Requestor</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo e($purchaseRequest->user->name); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Department</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo e($purchaseRequest->department->name); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Business Unit</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo e($purchaseRequest->businessUnit->name); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Request Date</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo e(\Carbon\Carbon::parse($purchaseRequest->date_of_request)->format('d F Y')); ?></p>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700">Purpose</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo e($purchaseRequest->keperluan); ?></p>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700">Used For</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo e($purchaseRequest->used_for); ?></p>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Items</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php $__currentLoopData = $purchaseRequest->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo e($item->item_name); ?></div>
                                        <?php if($item->item_description): ?>
                                            <div class="text-sm text-gray-500"><?php echo e($item->item_description); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo e($item->brand_name ?: '-'); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo e($item->supplier_name ?: '-'); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo e($item->quantity); ?> <?php echo e($item->unit); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo e($item->currency); ?> <?php echo e(number_format($item->unit_price, 0)); ?></td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo e($item->currency); ?> <?php echo e(number_format($item->quantity * $item->unit_price, 0)); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-right text-sm font-medium text-gray-900">Grand Total:</td>
                                <td class="px-6 py-4 text-sm font-bold text-indigo-600"><?php echo e($purchaseRequest->currency); ?> <?php echo e(number_format($purchaseRequest->total_amount, 0)); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Approval Workflow -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Approval Workflow</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-4">
                        <!-- Requestor -->
                        <div class="flex items-center <?php echo e($verificationData['type'] === 'requestor' ? 'bg-green-50 -mx-2 px-2 py-2 rounded-lg' : ''); ?>">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo e($purchaseRequest->user->name); ?>

                                    <?php if($verificationData['type'] === 'requestor'): ?>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Verified Creator
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-gray-500">Requestor</div>
                                <div class="text-xs text-gray-400"><?php echo e($purchaseRequest->submitted_at ? $purchaseRequest->submitted_at->format('d/m/Y H:i') : 'N/A'); ?></div>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Submitted
                                </span>
                            </div>
                        </div>

                        <!-- Approval Steps -->
                        <?php $__currentLoopData = $purchaseRequest->approvals->sortBy('step_order'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex items-center <?php echo e((isset($verificationData['approval']) && $step->id === $verificationData['approval']->id) ? 'bg-green-50 -mx-2 px-2 py-2 rounded-lg' : ''); ?>">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 <?php echo e($step->status === 'approved' ? 'bg-green-100' : ($step->status === 'rejected' ? 'bg-red-100' : 'bg-gray-100')); ?> rounded-full flex items-center justify-center">
                                    <?php if($step->status === 'approved'): ?>
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    <?php elseif($step->status === 'rejected'): ?>
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    <?php else: ?>
                                        <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo e($step->approver->name); ?>

                                    <?php if(isset($verificationData['approval']) && $step->id === $verificationData['approval']->id): ?>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Verified Approver
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-gray-500"><?php echo e(ucfirst(str_replace('_', ' ', $step->approval_type ?? 'Approver'))); ?></div>
                                <?php if($step->responded_at): ?>
                                    <div class="text-xs text-gray-400"><?php echo e($step->responded_at->format('d/m/Y H:i')); ?></div>
                                <?php endif; ?>
                                <?php if($step->notes): ?>
                                    <div class="text-xs text-gray-600 mt-1 italic">"<?php echo e($step->notes); ?>"</div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-shrink-0">
                                <?php if($step->status === 'approved'): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Approved
                                    </span>
                                <?php elseif($step->status === 'rejected'): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Rejected
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Pending
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>

            <!-- Verification Footer -->
            <div class="text-center text-sm text-gray-500">
                <p>This document was verified on <?php echo e(now()->format('d F Y, H:i')); ?></p>
                <p class="mt-1">Digital verification ensures authenticity and prevents tampering</p>
            </div>
        </div>
    </div>
</body>
</html><?php /**PATH E:\Learning\WGProject\Numbering\resources\views/purchase-requests/public.blade.php ENDPATH**/ ?>