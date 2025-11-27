<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Request - {{ $approval->purchaseRequest->pr_number }}</title>
    <style>
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
            max-width: 50px;
            max-height: 50px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
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
            flex: 0 0 45%;
        }

        .info-column.right-column {
            flex: 0 0 55%;
            margin-left: auto;
            padding-left: 280px;
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
        
        .info-column.right-column .info-row .info-label {
            min-width: 120px;
            max-width: 140px;
            font-weight: bold;
            color: #374151;
            flex-shrink: 0;
            text-align: left;
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
        
        /* Approval Section */
        .approval-section {
            margin-bottom: 25px;
            border: 2px solid #d1d5db;
            border-radius: 6px;
            background: #f9fafb;
            padding: 15px;
        }

        .approval-container {
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
            gap: 12px;
            position: relative;
            min-height: 120px;
        }

        .approval-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            text-align: center;
            min-height: 120px;
            padding: 10px 6px;
            flex: 0 0 auto;
            width: 120px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }

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
            line-height: 1.1;
        }
        
        /* Approval Form Section */
        .approval-form-section {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .form-title {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-size: 12px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 10px;
            display: block;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
        }
        
        .radio-option {
            flex: 1;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .radio-option:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .radio-option input[type="radio"] {
            margin-right: 10px;
        }
        
        .radio-option.approved {
            border-color: #10b981;
            background: #ecfdf5;
        }
        
        .radio-option.rejected {
            border-color: #ef4444;
            background: #fef2f2;
        }
        
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-family: inherit;
            font-size: 11px;
            resize: vertical;
        }
        
        .submit-button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .submit-button:hover {
            background: #2563eb;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        .alert-success {
            background: #ecfdf5;
            border: 1px solid #10b981;
            color: #047857;
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #ef4444;
            color: #dc2626;
        }
    </style>
</head>
<body>
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">
            ✗ {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">
            <strong>Please fix the following errors:</strong>
            <ul style="margin-top: 8px; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Professional Header Section -->
    <table class="pdf-header">
        <tr>
            <!-- Logo Section (Left) -->
            <td class="logo-section">
                @php
                    $prNumber = $approval->purchaseRequest->pr_number;
                    $buCode = explode('/', $prNumber)[0] ?? null;
                    $businessUnit = null;
                    
                    if ($buCode) {
                        $businessUnit = \App\Models\Core\BusinessUnit::where('code', $buCode)->first();
                    }
                    
                    if (!$businessUnit) {
                        $businessUnit = $approval->purchaseRequest->businessUnit;
                    }
                @endphp
                
                @if($businessUnit && $businessUnit->logo_path)
                    <img src="{{ asset('storage/' . $businessUnit->logo_path) }}" alt="{{ $businessUnit->name }}" class="business-logo">
                @else
                    <div class="default-logo">
                        <div class="logo-circle">
                            {{ $businessUnit ? strtoupper(substr($businessUnit->code, 0, 2)) : 'WG' }}
                        </div>
                        <div class="logo-text">
                            {{ $businessUnit ? strtoupper($businessUnit->name) : 'WERKUDARA GROUP' }}
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
                        <td>{{ $approval->step_order }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- PR Number and Basic Info -->
    <div class="pr-info-section">
        <div class="pr-number-line">
            No. {{ $approval->purchaseRequest->pr_number }}
        </div>
        
        <div class="basic-info-grid">
            <!-- Left Column -->
            <div class="info-column left-column">
                <div class="info-row">
                    <span class="info-label">Created by</span>
                    <span class="info-colon">:</span>
                    <span class="info-value">{{ $approval->purchaseRequest->user->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Department of PR</span>
                    <span class="info-colon">:</span>
                    <span class="info-value">{{ $approval->purchaseRequest->department->code ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Request No.</span>
                    <span class="info-colon">:</span>
                    <span class="info-value">{{ (int) substr(strrchr($approval->purchaseRequest->pr_number, '/'), 1) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date of Request</span>
                    <span class="info-colon">:</span>
                    <span class="info-value">{{ $approval->purchaseRequest->date_of_request->format('d-M-y') }}</span>
                </div>
            </div>

            <!-- Right Column -->
            <div class="info-column right-column">
                <div class="info-row">
                    <span class="info-label">Used for</span>
                    <span class="info-colon">:</span>
                    <span class="info-value wide">{{ $approval->purchaseRequest->used_for ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Designated Date</span>
                    <span class="info-colon">:</span>
                    <span class="info-value">{{ $approval->purchaseRequest->designated_date ? $approval->purchaseRequest->designated_date->format('d-M-y') : 'N/A' }}</span>
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
                @foreach($approval->purchaseRequest->items as $index => $item)
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
                    <td class="text-right total-amount"><strong>{{ number_format($approval->purchaseRequest->total_amount, 0) }}</strong></td>
                    <td>&nbsp;</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Approval Section -->
    <div class="approval-section">
        @php
            $approvals = $approval->purchaseRequest->approvals->sortBy('step_order');
            $totalApprovals = $approvals->count();
        @endphp

        <div class="approval-container">
            <!-- Created by Section -->
            <div class="approval-box">
                <div class="approval-title">CREATED BY</div>

                <div class="qr-code-container">
                    @if(isset($qrCodes['requestor']))
                        <img src="{{ $qrCodes['requestor'] }}" alt="Creator QR Code">
                    @else
                        &nbsp;
                    @endif
                </div>

                <div class="approver-info">
                    <div class="approver-name">{{ $approval->purchaseRequest->user->name }}</div>
                    <div class="approver-dept">{{ $approval->purchaseRequest->department->code ?? 'BAS' }}</div>
                </div>
            </div>

            <!-- All Approvals with Last One at Right Edge -->
            @foreach($approvals as $index => $appr)
                <div class="approval-box {{ $loop->last ? 'last-approval' : '' }}">
                    @php
                        $title = match($appr->approval_type) {
                            'knowledge' => 'ACKNOWLEDGED BY',
                            'paraf' => 'ACKNOWLEDGED BY', 
                            'approval' => 'APPROVED BY',
                            default => 'APPROVED BY'
                        };
                    @endphp
                    
                    <div class="approval-title">{{ $title }}</div>

                    <div class="qr-code-container">
                        @if($appr->status === 'approved' && isset($qrCodes['approvals'][$appr->id]))
                            <img src="{{ $qrCodes['approvals'][$appr->id] }}" alt="Approval QR Code">
                        @else
                            &nbsp;
                        @endif
                    </div>

                    <div class="approver-info">
                        <div class="approver-name">{{ $appr->approver->name }}</div>
                        <div class="approver-dept">{{ $appr->approver->primaryDepartment->code ?? 'DEP' }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Approval Form Section -->
    <div class="approval-form-section">
        <div class="form-title">🔐 YOUR APPROVAL DECISION</div>
        
        <form action="{{ route('approvals.public.process', $approval) }}" method="POST">
            @csrf

            <!-- Action Selection -->
            <div class="form-group">
                <label class="form-label">Select Your Decision <span style="color: #ef4444;">*</span></label>
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" name="action" value="approved" required>
                        <strong>✓ Approve</strong> - I approve this purchase request
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="action" value="rejected" required>
                        <strong>✗ Reject</strong> - I reject this purchase request
                    </label>
                </div>
                @error('action')
                    <div style="color: #ef4444; font-size: 10px; margin-top: 8px;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Notes Field -->
            <div class="form-group">
                <label for="notes" class="form-label">
                    Notes / Comments
                    <span style="color: #ef4444; display: none;" id="required-indicator">*</span>
                </label>
                <textarea name="notes" 
                          id="notes" 
                          rows="4" 
                          placeholder="Enter your notes or reason (required for rejection)">{{ old('notes') }}</textarea>
                <div style="font-size: 10px; color: #6b7280; margin-top: 6px;">
                    Optional for approval. Required if rejecting the request.
                </div>
                @error('notes')
                    <div style="color: #ef4444; font-size: 10px; margin-top: 6px;">{{ $message }}</div>
                @enderror
            </div>

            <!-- Submit Button -->
            <div style="text-align: center;">
                <button type="submit" class="submit-button">
                    📝 SUBMIT DECISION
                </button>
            </div>
        </form>
    </div>

    <!-- Footer -->
    <div style="text-align: center; margin-top: 30px; font-size: 10px; color: #6b7280;">
        @auth
            <p>🔒 This is a secure link with limited time access | 
                <a href="{{ route('purchase-requests.show', $approval->purchaseRequest) }}" 
                   style="color: #3b82f6; text-decoration: underline;">View full PR details</a>
            </p>
        @else
            <p>🔒 This is a secure link with limited time access | 
                <a href="{{ route('login', ['redirect' => route('purchase-requests.show', $approval->purchaseRequest)]) }}" 
                   style="color: #3b82f6; text-decoration: underline;">Login to view full details</a>
            </p>
        @endauth
    </div>

    <script>
        const radioButtons = document.querySelectorAll('input[name="action"]');
        const notesField = document.getElementById('notes');
        const requiredIndicator = document.getElementById('required-indicator');

        radioButtons.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'rejected') {
                    requiredIndicator.style.display = 'inline';
                    notesField.required = true;
                } else {
                    requiredIndicator.style.display = 'none';
                    notesField.required = false;
                }
            });
        });
    </script>
</body>
