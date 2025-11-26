@extends('emails.layouts.email')

@section('title', 'Purchase Request Approved')

@section('header-title', 'Approval Received')
@section('header-subtitle', 'Purchase Request #' . $pr->pr_number)

@section('content')
    <p style="margin: 0 0 15px 0; font-size: 16px; color: #333333;">
        Dear <strong style="color: #212529;">{{ $recipient->name }}</strong>,
    </p>
    
    <p style="margin: 0 0 20px 0; font-size: 15px; color: #495057; line-height: 1.6;">
        Your Purchase Request has been <strong style="color: #28a745;">APPROVED</strong> by <strong>{{ $approval->approver->name }}</strong>.
    </p>
    
    <!-- PR Information Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #d1e7dd; border-left: 4px solid #28a745; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 20px;">
                
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #a3cfbb;">
                    <tr>
                        <td style="font-weight: 600; color: #0f5132; font-size: 14px; padding: 10px 0;">PR Number:</td>
                        <td align="right" style="color: #212529; font-size: 15px; font-weight: 700; padding: 10px 0;">{{ $pr->pr_number }}</td>
                    </tr>
                </table>
                
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #a3cfbb;">
                    <tr>
                        <td style="font-weight: 600; color: #0f5132; font-size: 14px; padding: 10px 0;">Total Amount:</td>
                        <td align="right" style="color: #28a745; font-size: 18px; font-weight: 700; padding: 10px 0;">
                            {{ $pr->currency }} {{ number_format($pr->total_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
                
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #a3cfbb;">
                    <tr>
                        <td style="font-weight: 600; color: #0f5132; font-size: 14px; padding: 10px 0;">Approved By:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $approval->approver->name }}</td>
                    </tr>
                </table>
                
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #a3cfbb;">
                    <tr>
                        <td style="font-weight: 600; color: #0f5132; font-size: 14px; padding: 10px 0;">Approved At:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $approval->responded_at?->format('d M Y, H:i') }}</td>
                    </tr>
                </table>
                
                @if($approval->notes)
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0">
                    <tr>
                        <td colspan="2" style="padding: 15px 0 0 0;">
                            <div style="background-color: #fff; border-radius: 4px; padding: 12px; border: 1px solid #a3cfbb;">
                                <strong style="color: #0f5132; font-size: 14px;">Notes:</strong>
                                <p style="margin: 8px 0 0 0; color: #212529; font-size: 14px; line-height: 1.5;">{{ $approval->notes }}</p>
                            </div>
                        </td>
                    </tr>
                </table>
                @endif
                
            </td>
        </tr>
    </table>

    @php
        $pendingApprovals = $pr->approvals()->where('status', 'pending')->count();
    @endphp

    @if($pendingApprovals > 0)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #cfe2ff; border-left: 4px solid #0d6efd; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 15px; font-size: 15px; color: #084298;">
                <strong style="font-size: 16px;">Status:</strong> Your PR is now awaiting approval from the next approver. You will be notified once the approval process is complete.
            </td>
        </tr>
    </table>
    @endif

    <!-- Action Link - Simple Text Style -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 35px 0 25px 0;">
        <tr>
            <td align="center" style="padding: 0;">
                <a href="{{ route('purchase-requests.show', $pr->id) }}" style="display: inline-block; color: #1e40af; font-size: 18px; font-weight: 600; text-decoration: underline; padding: 8px 0;">
                    View Purchase Request Details
                </a>
            </td>
        </tr>
    </table>
@endsection
