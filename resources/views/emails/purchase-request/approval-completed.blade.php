@extends('emails.layouts.email')

@section('title', 'Purchase Request Fully Approved')

@section('header-title', '✅ Fully Approved')
@section('header-subtitle', 'Purchase Request #' . $pr->pr_number)

@section('content')
    <p>Dear <strong>{{ $recipient->name }}</strong>,</p>
    
    <p>Congratulations! Your Purchase Request has been <strong style="color: #28a745;">fully approved</strong> by all required approvers.</p>
    
    <div class="pr-info">
        <div class="pr-info-item">
            <span class="pr-info-label">PR Number:</span>
            <span class="pr-info-value"><strong>{{ $pr->pr_number }}</strong></span>
        </div>
        <div class="pr-info-item">
            <span class="pr-info-label">Total Amount:</span>
            <span class="pr-info-value"><strong>Rp {{ number_format($pr->total_amount, 0, ',', '.') }}</strong></span>
        </div>
        <div class="pr-info-item">
            <span class="pr-info-label">Submitted At:</span>
            <span class="pr-info-value">{{ $pr->submitted_at?->format('d M Y, H:i') }}</span>
        </div>
        <div class="pr-info-item">
            <span class="pr-info-label">Fully Approved At:</span>
            <span class="pr-info-value">{{ $pr->approved_at?->format('d M Y, H:i') }}</span>
        </div>
    </div>

    @if($approvals->count() > 0)
    <div style="background-color: #d1e7dd; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 4px;">
        <div style="font-weight: 600; color: #0f5132; margin-bottom: 10px;">Approval Chain:</div>
        @foreach($approvals as $index => $approval)
            <div style="padding: 5px 0; color: #0f5132;">
                {{ $index + 1 }}. <strong>{{ $approval->approver?->name ?? 'Unknown Approver' }}</strong> 
                - {{ ucwords(str_replace('_', ' ', $approval->approval_type)) }} 
                <span style="font-size: 12px;">({{ $approval->responded_at?->format('d M Y') }})</span>
            </div>
        @endforeach
    </div>
    @endif

    <p style="background-color: #d1e7dd; border-left: 4px solid #28a745; padding: 12px; border-radius: 4px;">
        🎉 <strong>Your request is now approved!</strong> The procurement team will proceed with processing your purchase request.
    </p>

    <div class="button-container">
        <a href="{{ route('purchase-requests.show', $pr->id) }}" class="button">
            View Purchase Request
        </a>
        <br>
        <a href="{{ route('purchase-requests.download-pdf', $pr->id) }}" class="button-secondary">
            Download PDF
        </a>
    </div>
@endsection
