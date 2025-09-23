<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Request - <?php echo e($purchaseRequest->pr_number); ?></title>
    <style>
        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: white;
            padding: 15px;
        }
        
        /* Professional Header */
        .pdf-header {
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            margin-bottom: 20px;
            border: 2px solid #000000;
            min-height: 80px;
        }
        
        /* Logo Section */
        .logo-section {
            width: 150px;
            flex-shrink: 0;
            border-right: 1px solid #000000;
            padding: 15px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .business-logo {
            max-width: 120px;
            max-height: 60px;
            object-fit: contain;
        }
        
        .default-logo {
            text-align: center;
        }
        
        .logo-circle {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #4a5568, #718096);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
            margin: 0 auto 8px;
        }
        
        .logo-text {
            font-size: 9px;
            font-weight: bold;
            color: #4a5568;
            line-height: 1.2;
        }
        
        /* Center Title Section */
                .title-section {
            flex: 1;
            text-align: center;
            padding: 15px 20px;
            border-right: 1px solid #000000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .main-title {
            font-size: 20px;
            font-weight: normal;
            color: #000000;
            margin: 0;
            letter-spacing: 1px;
        }
        
        .title-separator {
            width: 100%;
            height: 1px;
            background: #000000;
            margin: 8px 0;
        }
        
        .sub-title {
            font-size: 16px;
            font-weight: bold;
            color: #000000;
            margin: 0;
            letter-spacing: 0.5px;
        }
        
        /* Document Info Box */
        .doc-info-box {
            width: 150px;
            flex-shrink: 0;
            padding: 0;
            height: 100%;
            display: flex;
        }
        
        .doc-info-table {
            width: 100%;
            height: 100%;
            font-size: 8px;
            border-collapse: collapse;
            display: table;
        }
        
        .doc-info-table td {
            padding: 6px 8px;
            border: 1px solid #000000;
            vertical-align: middle;
            height: 25%;
            font-weight: bold;
            color: #000000;
        }
        
        .doc-info-table tr:first-child td:first-child {
            border-top: none;
            border-left: none;
        }
        
        .doc-info-table tr:first-child td:last-child {
            border-top: none;
            border-right: none;
        }
        
        .doc-info-table tr:last-child td:first-child {
            border-bottom: none;
            border-left: none;
        }
        
        .doc-info-table tr:last-child td:last-child {
            border-bottom: none;
            border-right: none;
        }
        
        .doc-info-table td:first-child {
            border-left: none;
            width: 50%;
            font-weight: bold;
            color: #000000;
        }
        
        .doc-info-table td:last-child {
            border-right: none;
            width: 50%;
            font-weight: bold;
            color: #000000;
        }
        
        /* PR Info Section */
        .pr-info-section {
            margin-bottom: 25px;
        }
        
        .pr-number-line {
            font-size: 11px;
            color: #000000;
            margin-bottom: 15px;
            text-align: left;
            padding: 5px 0;
            font-weight: bold;
        }
        
        .basic-info-grid {
            display: flex;
            gap: 80px;
        }
        
        .info-column {
            flex: 1;
        }
        
        .info-column.left-column {
            flex: 0 0 260px;
            max-width: 260px;
        }
        
        .info-column.right-column {
            flex: 0 0 300px;
            max-width: 300px;
            margin-left: auto;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
            align-items: flex-start;
        }
        
        .info-row .info-label {
            width: 130px;
            font-weight: bold;
            color: #374151;
            flex-shrink: 0;
        }
        
        .info-row .info-colon {
            width: 10px;
            text-align: center;
            flex-shrink: 0;
        }
        
        .info-row .info-value {
            flex: 1;
            color: #111827;
        }
        
        .info-row .info-value.wide {
            word-wrap: break-word;
            line-height: 1.5;
            max-width: 180px;
            word-break: break-word;
        }
        
        /* Items Table */
        .items-section {
            margin-bottom: 25px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 5px;
        }
        
        .items-table th {
            background: #1e40af;
            color: white;
            font-weight: bold;
            padding: 6px 4px;
            text-align: center;
            border: 1px solid #1e40af;
            vertical-align: middle;
            line-height: 1.2;
        }
        
        .items-table td {
            padding: 6px 4px;
            border: 1px solid #d1d5db;
            vertical-align: top;
            line-height: 1.3;
        }
        
        /* Column Widths */
        .col-no { width: 4%; }
        .col-item { width: 15%; }
        .col-brand { width: 10%; }
        .col-expense { width: 8%; }
        .col-description { width: 18%; }
        .col-supplier { width: 12%; }
        .col-qty { width: 5%; }
        .col-unit { width: 5%; }
        .col-price { width: 8%; }
        .col-cr { width: 4%; }
        .col-total { width: 8%; }
        .col-remark { width: 8%; }
        
        .row-even {
            background: #ffffff;
        }
        
                .row-odd {
            background-color: #f8fafc;
        }
        
        .total-row {
            background-color: #f3f4f6 !important;
            color: #374151 !important;
            border-top: 1px solid #d1d5db;
        }
        
        .total-row td {
            padding: 6px 4px;
            font-weight: bold;
            font-size: 10px;
            background: transparent !important;
            border: none !important;
            vertical-align: middle;
        }
        
        .total-row .total-label {
            text-align: right !important;
            font-size: 10px;
            padding-right: 10px !important;
        }
        
        .total-row .total-amount {
            text-align: right !important;
            background-color: transparent !important;
            color: #374151 !important;
            font-size: 10px;
            border: none !important;
            box-shadow: none !important;
            vertical-align: middle;
        }
        
        .text-center { text-align: center; }
        .text-right { 
            text-align: right !important; 
            padding-right: 4px;
        }
        
        /* Total Section */
        .total-section {
            text-align: right;
            margin-top: 15px;
        }
        
        .total-amount {
            background: #1e40af;
            color: white;
            padding: 8px 12px;
            display: inline-block;
            border-radius: 4px;
            font-size: 12px;
        }
        
        /* Approval Section */
        .approval-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .approval-grid {
            display: flex;
            gap: 15px;
            justify-content: flex-start;
            align-items: flex-start;
        }
        
        .approval-box {
            flex: 0 0 auto;
            width: 140px;
            height: 120px;
            border: 1px solid #1e40af;
            border-radius: 3px;
            padding: 8px;
            text-align: center;
            display: flex;
            flex-direction: column;
        }
        
        .approval-box:last-child {
            margin-left: auto;
        }
        
        .creator-box {
            background: #f0f9ff;
        }
        
        .approval-title {
            font-weight: bold;
            color: #1e40af;
            font-size: 7px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .qr-code-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 8px 0;
        }
        
        .qr-code-container img {
            max-width: 60px;
            max-height: 60px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        
        .pending-box {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fef3c7;
            border: 1px dashed #f59e0b;
            border-radius: 4px;
            margin: 8px 0;
        }
        
        .pending-text {
            font-size: 10px;
            font-weight: bold;
            color: #d97706;
            transform: rotate(-15deg);
        }
        
        .approver-info {
            margin-top: 8px;
        }
        
        .approver-name {
            font-weight: bold;
            font-size: 8px;
            color: #111827;
            margin-bottom: 2px;
        }
        
        .approver-dept {
            font-size: 7px;
            color: #6b7280;
        }
        
        /* PDF Footer */
        .pdf-footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
        }
        
        /* Print Optimizations */
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .pdf-header { page-break-after: avoid; }
            .approval-section { page-break-inside: avoid; }
            .items-table { page-break-inside: auto; }
            .items-table tr { page-break-inside: avoid; }
        }
        
        /* Utility Classes */
        .no-break { page-break-inside: avoid; }
        .break-before { page-break-before: always; }
        .break-after { page-break-after: always; }
        
        .mb-10 { margin-bottom: 10px; }
        .mb-15 { margin-bottom: 15px; }
        .mb-20 { margin-bottom: 20px; }
        .mb-25 { margin-bottom: 25px; }
        
        .mt-10 { margin-top: 10px; }
        .mt-15 { margin-top: 15px; }
        .mt-20 { margin-top: 20px; }
        
        .font-bold { font-weight: bold; }
        .text-sm { font-size: 10px; }
        .text-xs { font-size: 8px; }
    </style>
</head>
<body>
    <!-- Professional Header Section -->
    <div class="pdf-header">
        <!-- Logo Section (Left) -->
        <div class="logo-section">
            <?php if($purchaseRequest->businessUnit && $purchaseRequest->businessUnit->logo): ?>
                <img src="<?php echo e(asset('storage/' . $purchaseRequest->businessUnit->logo)); ?>" alt="<?php echo e($purchaseRequest->businessUnit->name); ?> Logo" class="business-logo">
            <?php else: ?>
                <div class="default-logo">
                    <div class="logo-circle">
                        <?php echo e(substr($purchaseRequest->businessUnit->code ?? 'WG', 0, 2)); ?>

                    </div>
                    <div class="logo-text">
                        <?php echo e($purchaseRequest->businessUnit->name ?? 'WERKUDARA GROUP'); ?>

                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Center Title -->
        <div class="title-section">
            <h1 class="main-title">FORMULIR</h1>
            <div class="title-separator"></div>
            <h2 class="sub-title">PURCHASE REQUISITION</h2>
        </div>

        <!-- Document Info Box (Right) -->
        <div class="doc-info-box">
            <table class="doc-info-table">
                <tr>
                    <td>No Dok</td>
                    <td>FRM.01.00.001</td>
                </tr>
                <tr>
                    <td>Tgl Efektif</td>
                    <td>22-Sep-25</td>
                </tr>
                <tr>
                    <td>Rev</td>
                    <td>00</td>
                </tr>
                <tr>
                    <td>Level</td>
                    <td>4</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- PR Number and Basic Info -->
    <div class="pr-info-section">
        <div class="pr-number-line">
            No. <?php echo e($purchaseRequest->pr_number); ?>

        </div>
        
        <div class="basic-info-grid">
            <!-- Left Column -->

            <div class="info-column left-column">
                <div class="info-row">
                    <span class="info-label">Created by</span>
                    <span class="info-colon">:</span>
                    <span class="info-value"><?php echo e($purchaseRequest->user->name); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Department of PR</span>
                    <span class="info-colon">:</span>
                    <span class="info-value"><?php echo e($purchaseRequest->department->code ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Request No.</span>
                    <span class="info-colon">:</span>
                    <span class="info-value"><?php echo e($purchaseRequest->sequence_id ?? 1); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date of Request</span>
                    <span class="info-colon">:</span>
                    <span class="info-value"><?php echo e(\Carbon\Carbon::parse($purchaseRequest->date_of_request)->format('d-M-y')); ?></span>
                </div>
            </div>

            <!-- Right Column -->
            <div class="info-column right-column">
                <div class="info-row">
                    <span class="info-label">Used for</span>
                    <span class="info-colon">:</span>
                    <span class="info-value wide"><?php echo e($purchaseRequest->used_for); ?></span>
                </div>
                <div class="info-row" style="margin-top: 15px;">
                    <span class="info-label">Designated Date</span>
                    <span class="info-colon">:</span>
                    <span class="info-value"><?php echo e($purchaseRequest->designated_date ? \Carbon\Carbon::parse($purchaseRequest->designated_date)->format('d-M-y') : 'N/A'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr class="table-header">
                    <th class="col-no">No</th>
                    <th class="col-item">ITEM NAME</th>
                    <th class="col-brand">BRAND NAME</th>
                    <th class="col-expense">EXPENSE DEPT</th>
                    <th class="col-description">ITEM DESCRIPTION/ SPECIFICATION</th>
                    <th class="col-supplier">SUPPLIER NAME</th>
                    <th class="col-qty">QT</th>
                    <th class="col-unit">UN</th>
                    <th class="col-price">UNIT PRICE</th>
                    <th class="col-cr">CR</th>
                    <th class="col-total">TOTAL PRICE</th>
                    <th class="col-remark">REMARK</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $purchaseRequest->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="<?php echo e($index % 2 == 0 ? 'row-even' : 'row-odd'); ?>">
                    <td class="text-center"><?php echo e($index + 1); ?></td>
                    <td><?php echo e($item->item_name); ?></td>
                    <td><?php echo e($item->brand_name ?: '-'); ?></td>
                    <td><?php echo e($item->expenseDepartment->code ?? $purchaseRequest->department->code); ?></td>
                    <td><?php echo e($item->item_description ?: '-'); ?></td>
                    <td><?php echo e($item->supplier_name ?: '-'); ?></td>
                    <td class="text-center"><?php echo e(number_format($item->quantity, 0)); ?></td>
                    <td class="text-center"><?php echo e($item->unit); ?></td>
                    <td class="text-right"><?php echo e(number_format($item->unit_price, 0)); ?></td>
                    <td class="text-center"><?php echo e($item->currency); ?></td>
                    <td class="text-right"><?php echo e(number_format($item->quantity * $item->unit_price, 0)); ?></td>
                    <td>-</td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                
                <!-- Total Row -->
                <tr class="total-row">
                    <td colspan="10" class="total-label"><strong>Total Amount:</strong></td>
                    <td class="text-right total-amount"><strong><?php echo e(number_format($purchaseRequest->total_amount, 0)); ?></strong></td>
                    <td>&nbsp;</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Approval Section -->
    <div class="approval-section">
        <div class="approval-grid">
            <!-- Created by Section with QR Code -->
            <div class="approval-box creator-box">
                <div class="approval-title">Created by</div>
                
                <?php if($purchaseRequest->submitted_at): ?>
                <div class="qr-code-container">
                    <img src="<?php echo e($qrCodes['requestor']); ?>" alt="QR Code" style="width: 60px; height: 60px;">
                </div>
                <?php else: ?>
                <div class="pending-box">
                    <div class="pending-text">PENDING</div>
                </div>
                <?php endif; ?>
                
                <div class="approver-info">
                    <div class="approver-name"><?php echo e($purchaseRequest->user->name); ?></div>
                    <div class="approver-dept"><?php echo e($purchaseRequest->department->code ?? 'N/A'); ?></div>
                </div>
            </div>

            <!-- Approval Steps -->
            <?php
                $approvals = $purchaseRequest->approvals->sortBy('step_order');
                $maxApprovals = 2; // Show 2 approval columns
            ?>
            
            <?php for($i = 0; $i < $maxApprovals; $i++): ?>
                <?php
                    $approval = $approvals->skip($i)->first();
                ?>
                
                <div class="approval-box">
                    <?php
                        $isAcknowledge = $approval && isset($approval->workflow) && str_contains(strtolower($approval->workflow->task_type ?? ''), 'paraf');
                    ?>
                    <div class="approval-title"><?php echo e($isAcknowledge ? 'Acknowledged by' : 'Approved by'); ?></div>
                    
                    <?php if($approval && $approval->status === 'approved'): ?>
                        <div class="qr-code-container">
                            <img src="<?php echo e($qrCodes['approvals'][$approval->id] ?? $qrCodes['requestor']); ?>" alt="Approval QR Code" style="width: 60px; height: 60px;">
                        </div>
                        
                        <div class="approver-info">
                            <div class="approver-name"><?php echo e($approval->approver->name); ?></div>
                            <div class="approver-dept"><?php echo e($approval->approver->primaryDepartment->code ?? 'N/A'); ?></div>
                        </div>
                    <?php else: ?>
                        <div class="pending-box">
                            <div class="pending-text">PENDING</div>
                        </div>
                        
                        <div class="approver-info">
                            <?php if($approval): ?>
                                <div class="approver-name"><?php echo e($approval->approver->name); ?></div>
                                <div class="approver-dept"><?php echo e($approval->approver->primaryDepartment->code ?? 'BAS'); ?></div>
                            <?php else: ?>
                                <div class="approver-name">Pending</div>
                                <div class="approver-dept">-</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="pdf-footer">
        <p>This document was generated on <?php echo e(now()->format('d F Y, H:i')); ?> | Purchase Request System v1.0</p>
    </div>
</body>
</html><?php /**PATH E:\Learning\WGProject\Numbering\resources\views/purchase-requests/pdf-simple.blade.php ENDPATH**/ ?>