<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
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
        }
        
        /* Professional Header */
        .pdf-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 15px 0;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e40af;
        }
        
        /* Logo Section */
        .logo-section {
            width: 150px;
            flex-shrink: 0;
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
            background: linear-gradient(135deg, #1e40af, #3b82f6);
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
            color: #1e40af;
            line-height: 1.2;
        }
        
        /* Center Title Section */
        .title-section {
            flex: 1;
            text-align: center;
            padding: 0 20px;
        }
        
        .main-title {
            font-size: 20px;
            font-weight: bold;
            color: #1e40af;
            margin: 0;
            letter-spacing: 1px;
        }
        
        .sub-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
            margin: 2px 0 0;
            letter-spacing: 0.5px;
        }
        
        /* Document Info Box */
        .doc-info-box {
            width: 150px;
            flex-shrink: 0;
            border: 1px solid #1e40af;
            border-radius: 4px;
        }
        
        .doc-info-table {
            width: 100%;
            font-size: 8px;
        }
        
        .doc-info-table td {
            padding: 3px 6px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        
        .doc-info-table tr:last-child td {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: bold;
            color: #1e40af;
            width: 50%;
        }
        
        .info-value {
            color: #333;
            font-weight: normal;
        }
        
        /* PR Info Section */
        .pr-info-section {
            margin-bottom: 25px;
        }
        
        .pr-number-line {
            font-size: 14px;
            color: #1e40af;
            margin-bottom: 15px;
            text-align: center;
            padding: 8px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        
        .basic-info-grid {
            display: flex;
            gap: 40px;
        }
        
        .info-column {
            flex: 1;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
            align-items: flex-start;
        }
        
        .info-row .info-label {
            width: 120px;
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
        }
        
        .info-row .info-value.wide {
            word-wrap: break-word;
        }
        
        /* Items Table */
        .items-section {
            margin-bottom: 25px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 10px;
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
            background: #f9fafb;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
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
            justify-content: space-between;
        }
        
        .approval-box {
            flex: 1;
            border: 1px solid #1e40af;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
            min-height: 140px;
            display: flex;
            flex-direction: column;
        }
        
        .creator-box {
            background: #f0f9ff;
        }
        
        .approval-title {
            font-weight: bold;
            color: #1e40af;
            font-size: 10px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .qr-code-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 8px 0;
        }
        
        .qr-placeholder {
            width: 60px;
            height: 60px;
            border: 2px solid #1e40af;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            position: relative;
        }
        
        .qr-content {
            text-align: center;
        }
        
        .qr-lines {
            width: 30px;
            height: 30px;
            background-image: 
                repeating-linear-gradient(90deg, #1e40af 0px, #1e40af 2px, transparent 2px, transparent 4px),
                repeating-linear-gradient(0deg, #1e40af 0px, #1e40af 2px, transparent 2px, transparent 4px);
            margin: 0 auto 4px;
        }
        
        .qr-date {
            font-size: 7px;
            color: #1e40af;
            font-weight: bold;
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
            font-size: 9px;
            color: #111827;
            margin-bottom: 2px;
        }
        
        .approver-dept {
            font-size: 8px;
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
    @yield('content')
</body>
</html>