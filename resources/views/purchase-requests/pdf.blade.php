@extends('layouts.pdf')

@section('title', 'Purchase Request - ' . $purchaseRequest->pr_number)

@section('content')
    <!-- Header Section -->
    <div class="header">
        <div class="company-info">
            <div class="company-logo">
                WNS
            </div>
            <div class="company-details">
                <h1>{{ $purchaseRequest->businessUnit->name ?? 'Werkudara Nusantara Sejahtera' }}</h1>
                <p>{{ $purchaseRequest->businessUnit->address ?? 'Jakarta, Indonesia' }}</p>
                <p>Phone: {{ $purchaseRequest->businessUnit->phone ?? '+62 21 1234 5678' }} | Email: {{ $purchaseRequest->businessUnit->email ?? 'info@wns.co.id' }}</p>
            </div>
            <div class="document-info">
                <div class="document-title">PURCHASE REQUEST</div>
                <div class="pr-number">{{ $purchaseRequest->pr_number }}</div>
            </div>
        </div>
    </div>

    <!-- Request Information -->
    <div class="request-info">
        <div class="info-group">
            <h3>Request Details</h3>
            <div class="info-item">
                <div class="info-label">Request Date</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($purchaseRequest->date_of_request)->format('d F Y') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value">{{ ucfirst($purchaseRequest->status) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Currency</div>
                <div class="info-value">{{ $purchaseRequest->currency }}</div>
            </div>
        </div>

        <div class="info-group">
            <h3>Requestor Information</h3>
            <div class="info-item">
                <div class="info-label">Requestor</div>
                <div class="info-value">{{ $purchaseRequest->user->name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value">{{ $purchaseRequest->user->email }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Department</div>
                <div class="info-value">{{ $purchaseRequest->department->name }}</div>
            </div>
        </div>

        <div class="info-group">
            <h3>Organization</h3>
            <div class="info-item">
                <div class="info-label">Business Unit</div>
                <div class="info-value">{{ $purchaseRequest->businessUnit->name }}</div>
            </div>
            @if($purchaseRequest->submitted_at)
            <div class="info-item">
                <div class="info-label">Submitted At</div>
                <div class="info-value">{{ $purchaseRequest->submitted_at->format('d F Y, H:i') }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Purpose and Usage -->
    <div class="mb-20">
        <div class="info-item mb-15">
            <div class="info-label">Purpose</div>
            <div class="info-value">{{ $purchaseRequest->keperluan }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Used For</div>
            <div class="info-value">{{ $purchaseRequest->used_for }}</div>
        </div>
    </div>

    <!-- Items Section -->
    <div class="items-section">
        <h2 class="section-title">Items Requested</h2>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 4%">#</th>
                    <th style="width: 30%">Item Name & Description</th>
                    <th style="width: 12%">Brand</th>
                    <th style="width: 15%">Supplier</th>
                    <th style="width: 8%">Qty</th>
                    <th style="width: 8%">Unit</th>
                    <th style="width: 11%">Unit Price</th>
                    <th style="width: 12%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseRequest->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <div class="item-name">{{ $item->item_name }}</div>
                        @if($item->item_description)
                            <div class="item-description">{{ $item->item_description }}</div>
                        @endif
                    </td>
                    <td>{{ $item->brand_name ?: '-' }}</td>
                    <td>{{ $item->supplier_name ?: '-' }}</td>
                    <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                    <td class="text-center">{{ $item->unit }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 0) }}</td>
                    <td class="text-right font-bold">{{ number_format($item->quantity * $item->unit_price, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Total Section -->
        <div class="total-section">
            <div class="total-row">
                <div class="total-label">Subtotal:</div>
                <div class="total-value">{{ $purchaseRequest->currency }} {{ number_format($purchaseRequest->total_amount, 0) }}</div>
            </div>
            <div class="total-row">
                <div class="total-label">Tax (if applicable):</div>
                <div class="total-value">{{ $purchaseRequest->currency }} 0</div>
            </div>
            <div class="total-row grand-total">
                <div class="total-label">Grand Total:</div>
                <div class="total-value">{{ $purchaseRequest->currency }} {{ number_format($purchaseRequest->total_amount, 0) }}</div>
            </div>
        </div>
    </div>

    <!-- Approval Workflow Section -->
    @if($purchaseRequest->approvals && $purchaseRequest->approvals->count() > 0)
    <div class="approval-section no-break">
        <h2 class="section-title">Approval Workflow</h2>
        
        <div class="workflow-container">
            <!-- Requestor -->
            <div class="workflow-step completed">
                <div class="step-icon approved">✓</div>
                <div class="step-name">{{ $purchaseRequest->user->name }}</div>
                <div class="step-role">Requestor</div>
                <div class="step-date">{{ $purchaseRequest->submitted_at ? $purchaseRequest->submitted_at->format('d/m/Y') : 'N/A' }}</div>
                <div class="step-status status-approved">Submitted</div>
            </div>

            <!-- Approval Steps -->
            @foreach($purchaseRequest->approvals->sortBy('step_order') as $approval)
            <div class="workflow-step {{ $approval->status === 'approved' ? 'completed' : ($approval->status === 'rejected' ? 'rejected' : 'pending') }}">
                <div class="step-icon {{ $approval->status === 'approved' ? 'approved' : ($approval->status === 'rejected' ? 'rejected' : ($approval->id === $purchaseRequest->currentApproval()?->id ? 'current' : 'pending')) }}">
                    @if($approval->status === 'approved')
                        ✓
                    @elseif($approval->status === 'rejected')
                        ✗
                    @else
                        {{ $approval->step_order }}
                    @endif
                </div>
                <div class="step-name">{{ $approval->approver->name }}</div>
                <div class="step-role">{{ ucfirst(str_replace('_', ' ', $approval->approval_type ?? 'Approver')) }}</div>
                @if($approval->responded_at)
                    <div class="step-date">{{ $approval->responded_at->format('d/m/Y H:i') }}</div>
                @else
                    <div class="step-date">Due: {{ $approval->due_date ? $approval->due_date->format('d/m/Y') : 'N/A' }}</div>
                @endif
                <div class="step-status status-{{ $approval->status === 'approved' ? 'approved' : ($approval->status === 'rejected' ? 'rejected' : ($approval->id === $purchaseRequest->currentApproval()?->id ? 'current' : 'pending')) }}">
                    {{ $approval->status === 'approved' ? 'Approved' : ($approval->status === 'rejected' ? 'Rejected' : ($approval->id === $purchaseRequest->currentApproval()?->id ? 'Current' : 'Pending')) }}
                </div>
                @if($approval->notes)
                    <div class="step-date" style="margin-top: 5px; font-style: italic;">"{{ $approval->notes }}"</div>
                @endif
            </div>
            @endforeach
        </div>

        <!-- QR Codes for Approved Steps -->
        @php
            $approvedApprovals = $purchaseRequest->approvals->where('status', 'approved');
        @endphp
        
        @if($approvedApprovals->count() > 0)
        <div class="qr-section">
            @foreach($approvedApprovals as $approval)
            <div class="qr-item">
                <div class="qr-code">
                    <!-- Real QR Code would be generated here -->
                    <img src="{{ route('approvals.qr-code', $approval->id) }}" 
                         alt="QR Code for {{ $approval->approver->name }}" 
                         style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div class="qr-label">Digital Signature</div>
                <div class="qr-approver">{{ $approval->approver->name }}</div>
                <div class="qr-approver">{{ $approval->responded_at->format('d/m/Y H:i') }}</div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>This document was generated on {{ now()->format('d F Y, H:i') }} | Purchase Request System v1.0</p>
        <p>This is a computer-generated document and does not require a physical signature when digitally approved.</p>
    </div>
@endsection