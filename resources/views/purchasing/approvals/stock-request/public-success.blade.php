<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Decision Submitted - Stock Request</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #1e293b;
        }

        .container {
            width: 100%;
            max-width: 480px;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            padding: 48px 32px 32px;
            text-align: center;
        }

        .icon-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }

        .icon-circle.approved {
            background-color: #ecfdf5;
        }

        .icon-circle.rejected {
            background-color: #fef2f2;
        }

        .icon-circle svg {
            width: 36px;
            height: 36px;
        }

        .icon-circle.approved svg {
            color: #10b981;
        }

        .icon-circle.rejected svg {
            color: #ef4444;
        }

        .title {
            font-size: 24px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 15px;
            color: #64748b;
            line-height: 1.5;
        }

        .card-body {
            padding: 0 32px 32px;
        }

        .details-section {
            background-color: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 10px 0;
        }

        .detail-row:not(:last-child) {
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-label {
            font-size: 13px;
            color: #64748b;
        }

        .detail-value {
            font-size: 14px;
            font-weight: 500;
            color: #0f172a;
            text-align: right;
        }

        .detail-value.approved {
            color: #10b981;
        }

        .detail-value.rejected {
            color: #ef4444;
        }

        .notes-section {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }

        .notes-label {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .notes-content {
            font-size: 14px;
            color: #334155;
            background: white;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            line-height: 1.5;
        }

        .info-box {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            background-color: #f0f9ff;
            border-radius: 10px;
            margin-bottom: 24px;
        }

        .info-box.warning {
            background-color: #fffbeb;
        }

        .info-box-icon {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            color: #0ea5e9;
        }

        .info-box.warning .info-box-icon {
            color: #f59e0b;
        }

        .info-box-content h4 {
            font-size: 13px;
            font-weight: 600;
            color: #0c4a6e;
            margin-bottom: 4px;
        }

        .info-box.warning .info-box-content h4 {
            color: #78350f;
        }

        .info-box-content p {
            font-size: 13px;
            color: #0369a1;
            line-height: 1.5;
        }

        .info-box.warning .info-box-content p {
            color: #92400e;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 14px 24px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-color: #0f172a;
            color: white;
        }

        .btn-primary:hover {
            background-color: #1e293b;
        }

        .card-footer {
            padding: 20px 32px;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }

        .footer-text {
            font-size: 13px;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .footer-text svg {
            width: 16px;
            height: 16px;
            color: #10b981;
        }

        .brand {
            margin-top: 24px;
            text-align: center;
        }

        .brand-text {
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <!-- Header -->
            <div class="card-header">
                @if($action === 'approved')
                    <div class="icon-circle approved">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h1 class="title">Request Approved</h1>
                    <p class="subtitle">Your approval has been recorded successfully.</p>
                @else
                    <div class="icon-circle rejected">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <h1 class="title">Request Rejected</h1>
                    <p class="subtitle">Your decision has been recorded and the requestor will be notified.</p>
                @endif
            </div>

            <!-- Body -->
            <div class="card-body">
                <!-- Details -->
                <div class="details-section">
                    <div class="detail-row">
                        <span class="detail-label">PR Number</span>
                        <span class="detail-value">{{ $approval->stockRequest->st_number }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Decision</span>
                        <span class="detail-value {{ $action }}">{{ ucfirst($action) }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date & Time</span>
                        <span class="detail-value">{{ now()->format('d M Y, H:i') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Amount</span>
                        <span class="detail-value">Rp {{ number_format($approval->stockRequest->items->sum('total'), 0, ',', '.') }}</span>
                    </div>

                    @if($notes)
                        <div class="notes-section">
                            <div class="notes-label">Your Notes</div>
                            <div class="notes-content">{{ $notes }}</div>
                        </div>
                    @endif
                </div>

                <!-- Info Box -->
                @if($action === 'approved')
                    <div class="info-box">
                        <svg class="info-box-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="info-box-content">
                            <h4>What happens next?</h4>
                            <p>
                                @if($approval->step_order < $approval->stockRequest->approvals()->count())
                                    The next approver will be notified to continue the approval process.
                                @else
                                    The requestor will be notified that all approvals are complete.
                                @endif
                            </p>
                        </div>
                    </div>
                @else
                    <div class="info-box warning">
                        <svg class="info-box-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="info-box-content">
                            <h4>What happens next?</h4>
                            <p>The requestor will receive your feedback and can edit and resubmit if needed.</p>
                        </div>
                    </div>
                @endif

                <!-- Dashboard Button -->
                <a href="{{ route('purchasing.dashboard') }}" class="btn btn-primary">
                    Open Dashboard
                </a>
            </div>

            <!-- Footer -->
            <div class="card-footer">
                <p class="footer-text">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Your decision has been recorded
                </p>
            </div>
        </div>

        <!-- Brand -->
        <div class="brand">
            <p class="brand-text">Werkudara Group</p>
        </div>
    </div>
</body>
</html>

