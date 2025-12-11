<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Notice' }} - Stock Request</title>
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

        .icon-circle.green {
            background-color: #ecfdf5;
        }

        .icon-circle.red {
            background-color: #fef2f2;
        }

        .icon-circle.yellow {
            background-color: #fffbeb;
        }

        .icon-circle.blue {
            background-color: #eff6ff;
        }

        .icon-circle.gray {
            background-color: #f1f5f9;
        }

        .icon-circle svg {
            width: 36px;
            height: 36px;
        }

        .icon-circle.green svg {
            color: #10b981;
        }

        .icon-circle.red svg {
            color: #ef4444;
        }

        .icon-circle.yellow svg {
            color: #f59e0b;
        }

        .icon-circle.blue svg {
            color: #3b82f6;
        }

        .icon-circle.gray svg {
            color: #64748b;
        }

        .title {
            font-size: 24px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 12px;
        }

        .message {
            font-size: 15px;
            color: #64748b;
            line-height: 1.6;
        }

        .card-body {
            padding: 0 32px 32px;
        }

        .btn-group {
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
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

        .btn-secondary {
            background-color: white;
            color: #374151;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background-color: #f8fafc;
        }

        .card-footer {
            padding: 20px 32px;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }

        .footer-text {
            font-size: 13px;
            color: #94a3b8;
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
    @php
        $color = $color ?? 'gray';
    @endphp
    
    <div class="container">
        <div class="card">
            <!-- Header -->
            <div class="card-header">
                <div class="icon-circle {{ $color }}">
                    @if($color === 'green')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    @elseif($color === 'red')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    @elseif($color === 'yellow')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    @elseif($color === 'blue')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif
                </div>
                <h1 class="title">{{ $title ?? 'Notice' }}</h1>
                <p class="message">{{ $message ?? 'An error occurred.' }}</p>
            </div>

            <!-- Body -->
            <div class="card-body">
                <div class="btn-group">
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">
                        Open Dashboard
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="card-footer">
                <p class="footer-text">Need help? Contact your system administrator</p>
            </div>
        </div>

        <!-- Brand -->
        <div class="brand">
            <p class="brand-text">Werkudara Group</p>
        </div>
    </div>
</body>
</html>

