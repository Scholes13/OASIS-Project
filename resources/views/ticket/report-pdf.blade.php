<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Support Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            background: white;
            padding: 20px;
            -webkit-print-color-adjust: exact;
        }

        .report-header {
            text-align: center;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 2px solid #2596BE;
        }

        .report-header h1 {
            font-size: 20px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 4px;
        }

        .report-header .date-range {
            font-size: 12px;
            color: #6b7280;
        }

        .report-header .generated {
            font-size: 9px;
            color: #9ca3af;
            margin-top: 2px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            margin: 16px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Summary grid */
        .summary-grid {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .summary-card {
            flex: 1;
            min-width: 120px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px 12px;
            text-align: center;
        }

        .summary-card .card-value {
            font-size: 22px;
            font-weight: bold;
            color: #111827;
        }

        .summary-card .card-label {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        /* Summary tables */
        .summary-tables {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
        }

        .summary-table-wrapper {
            flex: 1;
        }

        .summary-table-wrapper h3 {
            font-size: 11px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        table th {
            background: #2596BE;
            color: white;
            font-weight: bold;
            padding: 5px 8px;
            text-align: left;
            border: 1px solid #2596BE;
        }

        table td {
            padding: 4px 8px;
            border: 1px solid #d1d5db;
            vertical-align: middle;
        }

        table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        /* Status badges */
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }

        .badge-waiting { background: #FEF3C7; color: #92400E; }
        .badge-in_progress { background: #DBEAFE; color: #1E40AF; }
        .badge-done { background: #D1FAE5; color: #065F46; }
        .badge-cancelled { background: #FEE2E2; color: #991B1B; }

        .badge-low { background: #D1FAE5; color: #065F46; }
        .badge-medium { background: #FEF3C7; color: #92400E; }
        .badge-high { background: #FED7AA; color: #9A3412; }
        .badge-critical { background: #FEE2E2; color: #991B1B; }

        /* Ticket list table */
        .ticket-table {
            margin-top: 8px;
        }

        .ticket-table th {
            font-size: 8px;
            text-align: center;
            white-space: nowrap;
        }

        .ticket-table td {
            font-size: 8px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-nowrap { white-space: nowrap; }

        .avg-resolution {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 16px;
            font-size: 11px;
        }

        .avg-resolution strong {
            color: #1E40AF;
        }

        .pdf-footer {
            margin-top: 24px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
        }

        @media print {
            body { padding: 0; }
            @page {
                size: A4 landscape;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="report-header">
        <h1>IT Support Report</h1>
        <div class="date-range">
            {{ $reportData['period']['from'] }} &mdash; {{ $reportData['period']['to'] }}
        </div>
        <div class="generated">Generated on {{ now()->format('d F Y, H:i') }}</div>
    </div>

    {{-- Summary Cards --}}
    <div class="section-title">Summary</div>

    @php
        $byStatus = collect($reportData['by_status']);
        $byPriority = collect($reportData['by_priority']);
        $totalTickets = $byStatus->sum('count');
    @endphp

    <div class="summary-grid">
        <div class="summary-card">
            <div class="card-value">{{ $totalTickets }}</div>
            <div class="card-label">Total Tickets</div>
        </div>
        <div class="summary-card">
            <div class="card-value">{{ $byStatus->firstWhere('status', 'done')['count'] ?? 0 }}</div>
            <div class="card-label">Resolved</div>
        </div>
        <div class="summary-card">
            <div class="card-value">{{ $byStatus->firstWhere('status', 'in_progress')['count'] ?? 0 }}</div>
            <div class="card-label">In Progress</div>
        </div>
        <div class="summary-card">
            <div class="card-value">{{ number_format($reportData['avg_resolution_time'], 1) }}h</div>
            <div class="card-label">Avg Resolution</div>
        </div>
    </div>

    {{-- Status & Priority breakdown --}}
    <div class="summary-tables">
        <div class="summary-table-wrapper">
            <h3>By Status</h3>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th class="text-right">Count</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (['waiting', 'in_progress', 'done', 'cancelled'] as $status)
                        @php $count = $byStatus->firstWhere('status', $status)['count'] ?? 0; @endphp
                        <tr>
                            <td><span class="badge badge-{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span></td>
                            <td class="text-right">{{ $count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="summary-table-wrapper">
            <h3>By Priority</h3>
            <table>
                <thead>
                    <tr>
                        <th>Priority</th>
                        <th class="text-right">Count</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (['low', 'medium', 'high', 'critical'] as $priority)
                        @php $count = $byPriority->firstWhere('priority', $priority)['count'] ?? 0; @endphp
                        <tr>
                            <td><span class="badge badge-{{ $priority }}">{{ ucfirst($priority) }}</span></td>
                            <td class="text-right">{{ $count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="avg-resolution">
        Average Resolution Time: <strong>{{ number_format($reportData['avg_resolution_time'], 2) }} hours</strong>
    </div>

    {{-- Ticket List --}}
    @if (!empty($tickets) && count($tickets) > 0)
    <div class="section-title">Ticket List</div>

    <table class="ticket-table">
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>Ticket #</th>
                <th>Title</th>
                <th>Requester</th>
                <th>Department</th>
                <th>Category</th>
                <th class="text-center">Priority</th>
                <th class="text-center">Status</th>
                <th>Assigned To</th>
                <th class="text-center">Created</th>
                <th class="text-center">Resolved</th>
                <th class="text-center">Resolution Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tickets as $index => $ticket)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-nowrap">{{ $ticket->ticket_number }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($ticket->title, 40) }}</td>
                    <td>{{ $ticket->requester?->name ?? '-' }}</td>
                    <td>{{ $ticket->department?->name ?? '-' }}</td>
                    <td>{{ $ticket->category?->name ?? 'Uncategorized' }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $ticket->status }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                    </td>
                    <td>{{ $ticket->assignedUser?->name ?? 'Unassigned' }}</td>
                    <td class="text-center text-nowrap">{{ $ticket->created_at?->format('Y-m-d H:i') }}</td>
                    <td class="text-center text-nowrap">{{ $ticket->resolved_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td class="text-center">{{ $ticket->processing_time ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Footer --}}
    <div class="pdf-footer">
        <p>This report was generated on {{ now()->format('d F Y, H:i') }} | IT Support Module</p>
    </div>
</body>
</html>
