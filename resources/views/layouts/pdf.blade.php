<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Purchase Request')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: white;
        }

        .container {
            max-width: 297mm; /* A4 landscape width */
            margin: 0 auto;
            padding: 15px;
            background: white;
        }

        /* Header Styles */
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .company-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        .company-details h1 {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .company-details p {
            color: #6b7280;
            font-size: 11px;
        }

        .document-info {
            text-align: right;
        }

        .document-title {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }

        .pr-number {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            background: #f3f4f6;
            padding: 8px 12px;
            border-radius: 6px;
            display: inline-block;
        }

        /* Request Info Grid */
        .request-info {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr; /* 3 columns for landscape */
            gap: 20px;
            margin-bottom: 25px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .info-group h3 {
            font-size: 14px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 15px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 5px;
        }

        .info-item {
            margin-bottom: 12px;
        }

        .info-label {
            font-weight: bold;
            color: #4b5563;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .info-value {
            color: #1f2937;
            font-size: 12px;
        }

        /* Items Table */
        .items-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2563eb;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .items-table th {
            background: #f3f4f6;
            color: #374151;
            font-weight: bold;
            padding: 12px 8px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
        }

        .items-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .items-table tr:nth-child(even) {
            background: #fafafa;
        }

        .item-name {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 3px;
        }

        .item-description {
            color: #6b7280;
            font-size: 10px;
            font-style: italic;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Total Section */
        .total-section {
            margin-top: 20px;
            text-align: right;
        }

        .total-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 8px;
        }

        .total-label {
            font-weight: bold;
            color: #374151;
            margin-right: 20px;
            min-width: 120px;
        }

        .total-value {
            font-weight: bold;
            color: #1f2937;
            min-width: 150px;
            text-align: right;
        }

        .grand-total {
            border-top: 2px solid #2563eb;
            padding-top: 12px;
            margin-top: 12px;
        }

        .grand-total .total-label,
        .grand-total .total-value {
            font-size: 16px;
            color: #2563eb;
        }

        /* Approval Workflow */
        .approval-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .workflow-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .workflow-step {
            text-align: center;
            flex: 1;
            position: relative;
        }

        .workflow-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 25px;
            right: -50%;
            width: 100%;
            height: 2px;
            background: #d1d5db;
            z-index: 1;
        }

        .workflow-step.completed:not(:last-child)::after {
            background: #10b981;
        }

        .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            position: relative;
            z-index: 2;
        }

        .step-icon.pending {
            background: #6b7280;
        }

        .step-icon.approved {
            background: #10b981;
        }

        .step-icon.rejected {
            background: #ef4444;
        }

        .step-icon.current {
            background: #f59e0b;
        }

        .step-name {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 3px;
            font-size: 11px;
        }

        .step-role {
            color: #6b7280;
            font-size: 10px;
            margin-bottom: 5px;
        }

        .step-date {
            color: #4b5563;
            font-size: 9px;
        }

        .step-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .status-approved {
            background: #dcfce7;
            color: #166534;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-current {
            background: #fef3c7;
            color: #92400e;
        }

        /* QR Code Section */
        .qr-section {
            margin-top: 30px;
            display: flex;
            gap: 30px;
        }

        .qr-item {
            text-align: center;
            flex: 1;
            padding: 15px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .qr-code {
            width: 80px;
            height: 80px;
            margin: 0 auto 10px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }

        .qr-label {
            font-size: 10px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 3px;
        }

        .qr-approver {
            font-size: 9px;
            color: #6b7280;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }

        /* Print Styles */
        @media print {
            body {
                font-size: 11px;
            }
            
            .container {
                padding: 15px;
            }
            
            .page-break {
                page-break-before: always;
            }
            
            .no-break {
                page-break-inside: avoid;
            }
        }

        /* Utility Classes */
        .mb-10 { margin-bottom: 10px; }
        .mb-15 { margin-bottom: 15px; }
        .mb-20 { margin-bottom: 20px; }
        .mt-20 { margin-top: 20px; }
        .font-bold { font-weight: bold; }
        .text-sm { font-size: 11px; }
        .text-xs { font-size: 10px; }
    </style>
</head>
<body>
    <div class="container">
        @yield('content')
    </div>
</body>
</html>