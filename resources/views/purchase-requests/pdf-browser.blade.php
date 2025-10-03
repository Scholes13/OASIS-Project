<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Purchase Request - {{ $purchaseRequest->pr_number }}</title>
    <style>
        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', 'Helvetica', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: white;
            padding: 15px;
            -webkit-print-color-adjust: exact;
        }
        
        .pdf-instruction {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 12px;
            text-align: center;
        }
        
        @media print {
            .pdf-instruction {
                display: none;
            }
            
            /* Print-specific optimizations */
            body {
                padding: 0 !important;
                margin: 0 !important;
                font-size: 10px !important;
                background: white !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            /* Ensure tables don't break awkwardly */
            table {
                page-break-inside: avoid;
                border-collapse: collapse !important;
            }
            
            /* Prevent orphaned headers */
            .pdf-header {
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            
            /* Keep items table together */
            .items-table {
                page-break-inside: avoid;
            }
            
            /* Keep signature section together */
            .approval-signatures {
                page-break-before: avoid;
                page-break-inside: avoid;
            }
            
            /* Force landscape orientation */
            @page {
                size: A4 landscape;
                margin: 10mm;
            }
            
            /* Ensure colors print correctly */
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }

        /* Professional Header */
        .pdf-header {
            width: 100%;
            margin-bottom: 20px;
            border: 2px solid #000000;
            min-height: 80px;
            border-collapse: collapse;
            display: table;
        }
        
        /* Logo Section */
        .logo-section {
            display: table-cell;
            width: 150px;
            min-width: 150px;
            max-width: 150px;
            border-right: 1px solid #000000;
            padding: 15px;
            text-align: center;
            vertical-align: middle;
            overflow: hidden;
        }
        
        .business-logo {
            width: auto;
            height: auto;
            max-width: 120px;
            max-height: 60px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        
        .default-logo {
            text-align: center;
            width: 100%;
            height: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .logo-circle {
            width: 50px;
            height: 50px;
            min-width: 50px;
            min-height: 50px;
            background: linear-gradient(135deg, #4a5568, #718096);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
            margin: 0 auto 5px;
            flex-shrink: 0;
        }
        
        .logo-text {
            font-size: 9px;
            font-weight: bold;
            color: #4a5568;
            line-height: 1.2;
            max-height: 20px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Center Title Section */
        .title-section {
            display: table-cell;
            text-align: center;
            padding: 15px 20px;
            border-right: 1px solid #000000;
            vertical-align: middle;
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
            display: table-cell;
            width: 150px;
            padding: 0;
            vertical-align: top;
        }
        
        .doc-info-table {
            width: 100%;
            font-size: 8px;
            border-collapse: collapse;
        }
        
        .doc-info-table td {
            padding: 6px 8px;
            border: 1px solid #000000;
            vertical-align: middle;
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
        }
        
        .doc-info-table td:last-child {
            border-right: none;
            width: 50%;
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
            width: 100%;
            align-items: flex-start;
        }
        
        .info-column {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .info-column.left-column {
            flex: 0 0 45%; /* Fixed 45% for left column */
        }

        .info-column.right-column {
            flex: 0 0 55%; /* Fixed 55% for right column */
            margin-left: auto; /* Push to right side */
            padding-left: 280px; /* Position above Unit Price column */
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
            align-items: flex-start;
        }

        .info-row .info-label {
            min-width: 120px;
            max-width: 140px;
            font-weight: bold;
            color: #374151;
            flex-shrink: 0;
        }

        .info-row .info-colon {
            width: 15px;
            text-align: center;
            flex-shrink: 0;
        }

        .info-row .info-value {
            flex: 1;
            color: #111827;
            text-align: left;
        }
        
        /* Right column uses same alignment as left column */
        .info-column.right-column .info-row .info-label {
            min-width: 120px;
            max-width: 140px;
            font-weight: bold;
            color: #374151;
            flex-shrink: 0;
            text-align: left; /* Same as left column */
        }
        
        .info-column.right-column .info-row .info-colon {
            width: 15px;
            text-align: center;
            flex-shrink: 0;
        }
        
        .info-column.right-column .info-row .info-value {
            flex: 1;
            color: #111827;
            text-align: left;
        }

        .info-row .info-value.wide {
            word-wrap: break-word;
            line-height: 1.5;
            max-width: 260px;
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
            background-color: #f3f4f6;
            color: #374151;
            border-top: 1px solid #d1d5db;
        }
        
        .total-row td {
            padding: 6px 4px;
            font-weight: bold;
            font-size: 10px;
            background: transparent;
            border: none;
            vertical-align: middle;
        }
        
        .total-row .total-label {
            text-align: right;
            font-size: 10px;
            padding-right: 10px;
        }
        
        .total-row .total-amount {
            text-align: right;
            background-color: transparent;
            color: #374151;
            font-size: 10px;
            border: none;
            box-shadow: none;
            vertical-align: middle;
        }
        
        .text-center { text-align: center; }
        .text-right { 
            text-align: right; 
            padding-right: 4px;
        }
        
        /* Modern Approval Section - Using Flexbox (Browsershot supports this!) */
        .approval-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .approval-container {
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
            gap: 8px;
            position: relative;
            min-height: 110px;
        }

        .approval-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            text-align: center;
            min-height: 110px;
            padding: 8px 4px;
            flex: 0 0 auto;
            width: 120px;
        }

        /* Last approval positioned at right edge */
        .approval-box.last-approval {
            position: absolute;
            right: 0;
            top: 0;
            width: 140px;
        }

        .approval-title {
            font-weight: bold;
            color: #111827;
            font-size: 7px;
            margin-bottom: 8px;
            text-transform: uppercase;
            text-align: center;
        }

        .qr-code-container {
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 4px auto;
        }

        .qr-code-container img {
            max-width: 45px;
            max-height: 45px;
            display: block;
        }

        .qr-code-container.empty {
            background: transparent;
            height: 50px;
        }

        .approver-info {
            margin-top: 4px;
            text-align: center;
        }

        .approver-name {
            font-weight: bold;
            font-size: 8px;
            color: #111827;
            margin-bottom: 2px;
            line-height: 1.1;
            text-align: center;
        }
        
        .approver-dept {
            font-size: 7px;
            color: #6b7280;
            text-align: center;
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

        /* Screen view - Force natural flow like print */
        @media screen {
            /* Override any height constraints on root elements */
            html, body {
                height: auto !important;
                max-height: none !important;
                min-height: auto !important;
                overflow: visible !important;
            }
            
            /* Force all containers to allow natural flow */
            *, *::before, *::after {
                max-height: none !important;
                overflow: visible !important;
            }
            
            /* Specific overrides for common containers */
            .container, .wrapper, .content, .main, .page {
                height: auto !important;
                max-height: none !important;
                min-height: auto !important;
                overflow: visible !important;
            }
            
            /* Remove height constraints from tables and sections */
            .items-table, .approval-section, .approval-container,
            .pr-info-section, .items-section {
                height: auto !important;
                max-height: none !important;
                min-height: auto !important;
                overflow: visible !important;
            }
            
            /* Allow content to flow naturally */
            .pdf-content, .main-content, .content-wrapper,
            .document, .page-content {
                height: auto !important;
                max-height: none !important;
                min-height: auto !important;
                overflow: visible !important;
            }
            
            /* Force table to break naturally */
            table, tbody, thead, tr, td {
                height: auto !important;
                max-height: none !important;
                page-break-inside: auto !important;
            }
            
            /* Ensure natural spacing between sections */
            .pr-info-section {
                margin-bottom: 20px;
            }
            
            .items-section {
                margin-bottom: 25px;
            }
            
            .approval-container {
                margin-top: 25px;
                page-break-before: auto;
            }
            
            /* Add visual page break indication for screen */
            .approval-container::before {
                content: "";
                display: block;
                height: 40px;
                border-top: 2px dashed #ccc;
                margin: 20px 0;
            }
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
    <!-- PDF Download Instruction (visible only on screen) -->
    <div class="pdf-instruction">
        <strong>💡 How to save this as PDF:</strong> Press <kbd>Ctrl+P</kbd> (Windows) or <kbd>Cmd+P</kbd> (Mac), then choose "Save as PDF" as destination.
    </div>

    <!-- Professional Header Section -->
    <table class="pdf-header">
        <tr>
            <!-- Logo Section (Left) -->
            <td class="logo-section">
                @if($purchaseRequest->businessUnit && $purchaseRequest->businessUnit->logo)
                    <img src="{{ asset('storage/' . $purchaseRequest->businessUnit->logo) }}" alt="{{ $purchaseRequest->businessUnit->name }} Logo" class="business-logo">
                @else
                    <div class="default-logo">
                        <div class="logo-circle">
                            {{ substr($purchaseRequest->businessUnit->code ?? 'WG', 0, 2) }}
                        </div>
                        <div class="logo-text">
                            {{ $purchaseRequest->businessUnit->name ?? 'WERKUDARA GROUP' }}
                        </div>
                    </div>
                @endif
            </td>

            <!-- Center Title -->
            <td class="title-section">
                <h1 class="main-title">FORMULIR</h1>
                <div class="title-separator"></div>
                <h2 class="sub-title">PURCHASE REQUISITION</h2>
            </td>

            <!-- Document Info Box (Right) -->
            <td class="doc-info-box">
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
            </td>
        </tr>
    </table>

    <!-- PR Number and Basic Info -->
    <div class="pr-info-section">
        <div class="pr-number-line">
            No. {{ $purchaseRequest->pr_number }}
        </div>
        
        <div class="basic-info-grid">
            <!-- Left Column -->
            <div class="info-column left-column">
                    <div class="info-row">
                        <span class="info-label">Created by</span>
                        <span class="info-colon">:</span>
                        <span class="info-value">{{ $purchaseRequest->user->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Department of PR</span>
                        <span class="info-colon">:</span>
                        <span class="info-value">{{ $purchaseRequest->department->code ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Request No.</span>
                        <span class="info-colon">:</span>
                        <span class="info-value">{{ (int) substr(strrchr($purchaseRequest->pr_number, '/'), 1) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date of Request</span>
                        <span class="info-colon">:</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($purchaseRequest->date_of_request)->format('d-M-y') }}</span>
                    </div>
            </div>

            <!-- Right Column -->
            <div class="info-column right-column">
                    <div class="info-row">
                        <span class="info-label">Used for</span>
                        <span class="info-colon">:</span>
                        <span class="info-value wide">{{ $purchaseRequest->used_for }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Designated Date</span>
                        <span class="info-colon">:</span>
                        <span class="info-value">{{ $purchaseRequest->designated_date ? \Carbon\Carbon::parse($purchaseRequest->designated_date)->format('d-M-y') : 'N/A' }}</span>
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
                @foreach($purchaseRequest->items as $index => $item)
                <tr class="{{ $index % 2 == 0 ? 'row-even' : 'row-odd' }}">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td>{{ $item->brand_name ?: '-' }}</td>
                    <td>{{ $item->item_description ?: '-' }}</td>
                    <td>{{ $item->supplier_name ?: '-' }}</td>
                    <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                    <td class="text-center">{{ $item->unit }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 0) }}</td>
                    <td class="text-center">{{ $item->currency }}</td>
                    <td class="text-right">{{ number_format($item->quantity * $item->unit_price, 0) }}</td>
                    <td>-</td>
                </tr>
                @endforeach
                
                <!-- Total Row -->
                <tr class="total-row">
                    <td colspan="9" class="total-label"><strong>Total Amount:</strong></td>
                    <td class="text-right total-amount"><strong>{{ number_format($purchaseRequest->total_amount, 0) }}</strong></td>
                    <td>&nbsp;</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Modern Approval Section using Flexbox -->
    <div class="approval-section">
        @php
            // Get all approvals sorted by step_order
            $approvals = $purchaseRequest->approvals->sortBy('step_order');
            $totalApprovals = $approvals->count();
        @endphp

        <div class="approval-container">
            <!-- Created by Section (Always shows) -->
            <div class="approval-box">
                <div class="approval-title">Created by</div>

                @if($purchaseRequest->submitted_at && isset($qrCodes['requestor']))
                <div class="qr-code-container">
                    <img src="{{ $qrCodes['requestor'] }}" alt="QR Code" style="width: 50px; height: 50px;">
                </div>
                @else
                <div class="qr-code-container empty">&nbsp;</div>
                @endif

                <div class="approver-info">
                    <div class="approver-name">{{ $purchaseRequest->user->name }}</div>
                    <div class="approver-dept">{{ $purchaseRequest->department->code ?? 'BAS' }}</div>
                </div>
            </div>

            <!-- Dynamic Approval Sections based on actual approval data -->
            @foreach($approvals as $index => $approval)
                <div class="approval-box {{ $loop->last ? 'last-approval' : '' }}">
                    @php
                        // Determine title based on approval_type from database directly
                        $title = match($approval->approval_type) {
                            'knowledge' => 'Acknowledged by',
                            'paraf' => 'Acknowledged by', 
                            'approval' => 'Approved by',
                            default => 'Approved by'
                        };
                    @endphp
                    
                    <div class="approval-title">{{ $title }}</div>

                    @if($approval->status === 'approved' && isset($qrCodes['approvals'][$approval->id]))
                        <div class="qr-code-container">
                            <img src="{{ $qrCodes['approvals'][$approval->id] }}" alt="Approval QR Code" style="width: 50px; height: 50px;">
                        </div>
                    @else
                        <div class="qr-code-container empty">&nbsp;</div>
                    @endif

                    <div class="approver-info">
                        <div class="approver-name">{{ $approval->approver->name }}</div>
                        <div class="approver-dept">{{ $approval->approver->primaryDepartment->code ?? 'DEP' }}</div>
                    </div>
                </div>
            @endforeach

            <!-- No empty slots - only show actual approvals from database -->
        </div>
    </div>

    <!-- Footer -->
    <div class="pdf-footer">
        <p>This document was generated on {{ now()->format('d F Y, H:i') }} | Purchase Request System v1.0</p>
    </div>
</body>
</html>