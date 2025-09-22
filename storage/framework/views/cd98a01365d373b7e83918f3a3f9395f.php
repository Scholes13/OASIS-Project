<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Request - <?php echo e($purchaseRequest->pr_number); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: white;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-left {
            flex: 1;
        }

        .header-center {
            flex: 2;
            text-align: center;
        }

        .header-right {
            flex: 1;
            text-align: right;
        }

        .document-title {
            font-size: 20px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0;
        }

        .pr-number {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            border: 1px solid #007bff;
            padding: 5px 10px;
            display: inline-block;
        }

        .company-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        .page-number {
            font-size: 16px;
            color: #999;
            margin-top: 5px;
        }

        .info-section {
            width: 100%;
            margin-bottom: 20px;
            display: table;
        }

        .info-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 25px;
        }

        .info-column:last-child {
            padding-right: 0;
        }

        .info-row {
            margin-bottom: 8px;
            font-size: 12px;
            color: #333;
        }

        .info-label {
            font-weight: bold;
            display: inline;
        }

        .info-value {
            display: inline;
            margin-left: 5px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }

        .items-table th {
            background: #1e3a8a;
            color: white;
            font-weight: bold;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            border: 1px solid #1e3a8a;
        }

        .items-table td {
            padding: 8px;
            border: 1px solid #ddd;
            vertical-align: top;
            font-size: 11px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-section {
            margin-top: 20px;
            text-align: right;
        }

        .total-row {
            margin-bottom: 8px;
        }

        .total-label {
            font-weight: bold;
            color: #374151;
            display: inline-block;
            width: 120px;
        }

        .total-value {
            font-weight: bold;
            color: #1f2937;
            display: inline-block;
            width: 150px;
            text-align: right;
        }

        .grand-total {
            border-top: 2px solid #2563eb;
            padding-top: 12px;
            margin-top: 12px;
            font-size: 14px;
            color: #2563eb;
        }

        /* Simple Approval Section */
        .approval-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .approval-grid {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            border: 1px solid #ddd;
        }

        .approval-column {
            flex: 1;
            text-align: center;
            padding: 15px 8px;
            border-right: 1px solid #ddd;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .approval-column:last-child {
            border-right: none;
        }

        .approval-role {
            font-size: 10px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .approval-qr {
            width: 60px;
            height: 60px;
            margin: 10px auto;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #666;
            background: #f9f9f9;
        }

        .approval-qr.approved {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .approval-name {
            font-size: 9px;
            font-weight: bold;
            color: #333;
            margin-top: 10px;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        .approval-date {
            font-size: 8px;
            color: #666;
            margin-top: 2px;
        }

        .approval-status {
            font-size: 8px;
            margin-top: 5px;
            padding: 2px 6px;
            border-radius: 3px;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <table style="width: 100%; border-bottom: 2px solid #1e3a8a; margin-bottom: 15px; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; vertical-align: middle; padding: 10px 0;">
                <div style="font-size: 20px; font-weight: bold; color: #1e3a8a; margin: 0; line-height: 1;">PURCHASE REQUISITION</div>
            </td>
            <td style="width: 50%; vertical-align: middle; text-align: right; padding: 10px 0;">
                <div style="font-size: 20px; font-weight: bold; color: #1e3a8a; margin: 0; line-height: 1;"><?php echo e($purchaseRequest->pr_number); ?></div>
            </td>
        </tr>
    </table>

    <!-- Request Information -->
    <table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
        <tr>
            <td style="width: 45%; vertical-align: top; padding-right: 30px;">
                <div class="info-row">
                    <span class="info-label">Created by :</span>
                    <span class="info-value"><?php echo e($purchaseRequest->user->name); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Department of PR :</span>
                    <span class="info-value"><?php echo e($purchaseRequest->department->name); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Request No :</span>
                    <span class="info-value"><?php echo e($purchaseRequest->sequence_id ?? $purchaseRequest->id); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date of Request :</span>
                    <span class="info-value"><?php echo e(\Carbon\Carbon::parse($purchaseRequest->date_of_request)->format('d-M-Y')); ?></span>
                </div>
            </td>
            <td style="width: 55%; vertical-align: top; text-align: right;">
                <div style="margin-bottom: 8px; text-align: right; font-size: 12px; color: #333;">
                    <span style="font-weight: bold;">Purpose/Requirements :</span>
                    <span style="margin-left: 5px;"><?php echo e($purchaseRequest->keperluan); ?></span>
                </div>
                <div style="margin-bottom: 8px; text-align: right; font-size: 12px; color: #333;">
                    <span style="font-weight: bold;">Detailed Description :</span>
                    <span style="margin-left: 5px;"><?php echo e($purchaseRequest->used_for); ?></span>
                </div>
                <div style="margin-bottom: 8px; text-align: right; font-size: 12px; color: #333;">
                    <span style="font-weight: bold;">Designated Date :</span>
                    <span style="margin-left: 5px;"><?php echo e($purchaseRequest->designated_date ? \Carbon\Carbon::parse($purchaseRequest->designated_date)->format('d-M-Y') : \Carbon\Carbon::parse($purchaseRequest->date_of_request)->addDays(7)->format('d-M-Y')); ?></span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Purchase Items -->
    <h3 style="margin-bottom: 10px; color: #333;">Purchase Items</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 25%">Item Details</th>
                <th style="width: 15%">Supplier</th>
                <th style="width: 10%">Quantity</th>
                <th style="width: 15%">Unit Price</th>
                <th style="width: 15%">Total Price</th>
                <th style="width: 15%">Expense Dept</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $purchaseRequest->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td class="text-center"><?php echo e($index + 1); ?></td>
                <td>
                    <div style="font-weight: bold;"><?php echo e($item->item_name); ?></div>
                    <?php if($item->brand_name): ?>
                        <div style="font-size: 10px; color: #666;">Brand: <?php echo e($item->brand_name); ?></div>
                    <?php endif; ?>
                    <?php if($item->item_description): ?>
                        <div style="font-size: 10px; color: #666;"><?php echo e($item->item_description); ?></div>
                    <?php endif; ?>
                </td>
                <td><?php echo e($item->supplier_name ?: '-'); ?></td>
                <td class="text-center"><?php echo e(number_format($item->quantity, 0)); ?> <?php echo e($item->unit); ?></td>
                <td class="text-right"><?php echo e($item->currency); ?> <?php echo e(number_format($item->unit_price, 0)); ?></td>
                <td class="text-right" style="font-weight: bold;"><?php echo e($item->currency); ?> <?php echo e(number_format($item->quantity * $item->unit_price, 0)); ?></td>
                <td><?php echo e($item->expenseDepartment->name ?? 'Business & Administrative Services'); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <!-- Total Amount -->
    <div style="text-align: right; margin: 15px 0; padding: 10px; background: #e9ecef; border: 1px solid #ddd;">
        <strong>Total Amount: <?php echo e($purchaseRequest->currency); ?> <?php echo e(number_format($purchaseRequest->total_amount, 0)); ?></strong>
    </div>



    <!-- Approval Workflow -->
    <?php
        $approvals = $purchaseRequest->approvals ? $purchaseRequest->approvals->sortBy('step_order') : collect();
        $totalApprovers = $approvals->count();
    ?>
    
    <?php if($totalApprovers > 0): ?>
    <div style="margin-top: 30px;">
        <!-- Horizontal Approval Table -->
        <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">
            <tr>
                <!-- Created by -->
                <td style="width: <?php echo e(100 / ($totalApprovers + 1)); ?>%; text-align: center; padding: 15px; border: 1px solid #ddd; vertical-align: top;">
                    <div style="font-size: 12px; font-weight: bold; margin-bottom: 10px;">Created by</div>
                    <div style="height: 60px; border: 1px solid #ddd; margin: 10px 0; display: flex; align-items: center; justify-content: center; background: <?php echo e($purchaseRequest->submitted_at ? '#d4edda' : '#f9f9f9'); ?>;">
                        <?php if($purchaseRequest->submitted_at): ?>
                            <div style="font-size: 8px; text-align: center;">
                                <div style="width: 40px; height: 40px; margin: 0 auto 5px; background: white; border: 1px solid #ddd;">
                                    <img src="<?php echo e($qrCodes['requestor']); ?>" style="width: 100%; height: 100%; object-fit: contain;" alt="Requestor QR Code">
                                </div>
                                <div style="font-weight: bold;"><?php echo e($purchaseRequest->submitted_at->format('d/m/Y')); ?></div>
                            </div>
                        <?php else: ?>
                            <div style="font-size: 8px; text-align: center;">
                                <div style="border: 1px solid #666; width: 40px; height: 40px; margin: 0 auto 5px; display: flex; align-items: center; justify-content: center; background: white; font-size: 6px;">PENDING</div>
                                <div>Pending</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 11px; font-weight: bold; margin-top: 10px;"><?php echo e($purchaseRequest->user->name); ?></div>
                    <div style="font-size: 10px; color: #666;"><?php echo e($purchaseRequest->department->code ?? 'BAS'); ?></div>
                </td>
                
                <!-- Approvers -->
                <?php $__currentLoopData = $approvals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $approval): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <td style="width: <?php echo e(100 / ($totalApprovers + 1)); ?>%; text-align: center; padding: 15px; border: 1px solid #ddd; vertical-align: top;">
                    <div style="font-size: 12px; font-weight: bold; margin-bottom: 10px;">
                        <?php if($approval->approval_type === 'supervisor'): ?>
                            Acknowledged by
                        <?php elseif($approval->approval_type === 'manager'): ?>
                            Reviewed by
                        <?php elseif($approval->approval_type === 'director'): ?>
                            Approved by
                        <?php else: ?>
                            Approved by
                        <?php endif; ?>
                    </div>
                    <div style="height: 60px; border: 1px solid #ddd; margin: 10px 0; display: flex; align-items: center; justify-content: center; background: <?php echo e($approval->status === 'approved' ? '#d4edda' : '#f9f9f9'); ?>;">
                        <?php if($approval->status === 'approved'): ?>
                            <div style="font-size: 8px; text-align: center;">
                                <div style="width: 40px; height: 40px; margin: 0 auto 5px; background: white; border: 1px solid #ddd;">
                                    <img src="<?php echo e($qrCodes['approvals'][$approval->id]); ?>" style="width: 100%; height: 100%; object-fit: contain;" alt="Approver QR Code">
                                </div>
                                <div style="font-weight: bold;"><?php echo e($approval->responded_at->format('d/m/Y')); ?></div>
                            </div>
                        <?php else: ?>
                            <div style="font-size: 8px; text-align: center;">
                                <div style="border: 1px solid #666; width: 40px; height: 40px; margin: 0 auto 5px; display: flex; align-items: center; justify-content: center; background: white; font-size: 6px;">PENDING</div>
                                <div>Pending</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 11px; font-weight: bold; margin-top: 10px;"><?php echo e($approval->approver->name); ?></div>
                    <div style="font-size: 10px; color: #666;">
                        <?php if($approval->approver->department): ?>
                            <?php echo e($approval->approver->department->code ?? 'BAS'); ?>

                        <?php else: ?>
                            <?php echo e($approval->approval_type === 'director' ? 'SS (Procurement)' : 'BAS'); ?>

                        <?php endif; ?>
                    </div>
                    <?php if($approval->status !== 'pending'): ?>
                        <div style="font-size: 9px; margin-top: 5px; padding: 2px 6px; border-radius: 3px; background: <?php echo e($approval->status === 'approved' ? '#d4edda' : ($approval->status === 'rejected' ? '#f8d7da' : '#fff3cd')); ?>; color: <?php echo e($approval->status === 'approved' ? '#155724' : ($approval->status === 'rejected' ? '#721c24' : '#856404')); ?>;">
                            <?php echo e(ucfirst($approval->status)); ?>

                        </div>
                    <?php endif; ?>
                </td>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer">
        <p>This document was generated on <?php echo e(now()->format('d F Y, H:i')); ?> | Purchase Request System v1.0</p>
        <p>This is a computer-generated document and does not require a physical signature when digitally approved.</p>
    </div>
</body>
</html><?php /**PATH E:\Learning\WGProject\Numbering\resources\views/purchase-requests/pdf-simple.blade.php ENDPATH**/ ?>