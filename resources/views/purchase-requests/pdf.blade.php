@extends('layouts.pdf')

@section('title', 'Purchase Request - ' . $purchaseRequest->pr_number)

@section('content')
    <!-- Professional Header Section -->
    <div class="pdf-header">
        <!-- Logo Section (Left) -->
        <div class="logo-section">
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
        </div>

        <!-- Center Title -->
        <div class="title-section">
            <h1 class="main-title">FORMULIR</h1>
            <h2 class="sub-title">PURCHASE REQUISITION</h2>
        </div>

        <!-- Document Info Box (Right) -->
        <div class="doc-info-box">
            <table class="doc-info-table">
                <tr>
                    <td class="info-label">No Dok</td>
                    <td class="info-value">FRM.01.00.001</td>
                </tr>
                <tr>
                    <td class="info-label">Tgl Efektif</td>
                    <td class="info-value">{{ now()->format('d-M-y') }}</td>
                </tr>
                <tr>
                    <td class="info-label">Rev</td>
                    <td class="info-value">00</td>
                </tr>
                <tr>
                    <td class="info-label">Level</td>
                    <td class="info-value">4</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- PR Number and Basic Info -->
    <div class="pr-info-section">
        <div class="pr-number-line">
            <strong>No. {{ $purchaseRequest->pr_number }}</strong>
        </div>
        
        <div class="basic-info-grid">
            <!-- Left Column -->
            <div class="info-column">
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
                    <span class="info-value">{{ $purchaseRequest->sequence_id ?? 1 }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date of Request</span>
                    <span class="info-colon">:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($purchaseRequest->date_of_request)->format('d-M-y') }}</span>
                </div>
            </div>

            <!-- Right Column -->
            <div class="info-column">
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
                @foreach($purchaseRequest->items as $index => $item)
                <tr class="{{ $index % 2 == 0 ? 'row-even' : 'row-odd' }}">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td>{{ $item->brand_name ?: '-' }}</td>
                    <td>{{ $item->expenseDepartment->code ?? $purchaseRequest->department->code }}</td>
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
                
                @for($i = $purchaseRequest->items->count(); $i < 5; $i++)
                <tr class="{{ $i % 2 == 0 ? 'row-even' : 'row-odd' }}">
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>-</td>
                </tr>
                @endfor
            </tbody>
        </table>
        
        <!-- Total Section -->
        <div class="total-section">
            <div class="total-amount">
                <strong>Total Amount: {{ $purchaseRequest->currency }} {{ number_format($purchaseRequest->total_amount, 0) }}</strong>
            </div>
        </div>
    </div>

    <!-- Approval Section -->
    <div class="approval-section">
        <div class="approval-grid">
            <!-- Created by Section with QR Code -->
            <div class="approval-box creator-box">
                <div class="approval-title">Created by</div>
                
                @if($purchaseRequest->submitted_at)
                <div class="qr-code-container">
                    <div class="qr-placeholder">
                        <!-- QR Code would be generated here -->
                        <div class="qr-content">
                            <div class="qr-lines"></div>
                            <div class="qr-date">{{ $purchaseRequest->submitted_at->format('d/m/Y') }}</div>
                        </div>
                    </div>
                </div>
                @else
                <div class="pending-box">
                    <div class="pending-text">PENDING</div>
                </div>
                @endif
                
                <div class="approver-info">
                    <div class="approver-name">{{ $purchaseRequest->user->name }}</div>
                    <div class="approver-dept">{{ $purchaseRequest->department->code ?? 'N/A' }}</div>
                </div>
            </div>

            <!-- Approval Steps -->
            @php
                $approvals = $purchaseRequest->approvals->sortBy('step_order');
                $maxApprovals = 2; // Show 2 approval columns
            @endphp
            
            @for($i = 0; $i < $maxApprovals; $i++)
                @php
                    $approval = $approvals->skip($i)->first();
                @endphp
                
                <div class="approval-box">
                    <div class="approval-title">Approved by</div>
                    
                    @if($approval && $approval->status === 'approved')
                        <div class="qr-code-container">
                            <div class="qr-placeholder">
                                <div class="qr-content">
                                    <div class="qr-lines"></div>
                                    <div class="qr-date">{{ $approval->responded_at->format('d/m/Y') }}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="approver-info">
                            <div class="approver-name">{{ $approval->approver->name }}</div>
                            <div class="approver-dept">{{ $approval->approver->primaryDepartment->code ?? 'N/A' }}</div>
                        </div>
                    @else
                        <div class="pending-box">
                            <div class="pending-text">PENDING</div>
                        </div>
                        
                        <div class="approver-info">
                            @if($approval)
                                <div class="approver-name">{{ $approval->approver->name }}</div>
                                <div class="approver-dept">{{ $approval->approver->primaryDepartment->code ?? 'BAS' }}</div>
                            @else
                                <div class="approver-name">Pending</div>
                                <div class="approver-dept">-</div>
                            @endif
                        </div>
                    @endif
                </div>
            @endfor
        </div>
    </div>

    <!-- Footer -->
    <div class="pdf-footer">
        <p>This document was generated on {{ now()->format('d F Y, H:i') }} | Purchase Request System v1.0</p>
    </div>
@endsection