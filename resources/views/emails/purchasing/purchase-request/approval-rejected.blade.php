@extends('emails.layouts.email')

@section('title', 'Purchase Request Rejected')

@section('header-title', 'Request Rejected')
@section('header-subtitle', 'Purchase Request #' . $pr->pr_number)

@section('content')
    <p style="margin: 0 0 15px 0; font-size: 16px; color: #333333;">
        Dear <strong style="color: #212529;">{{ $recipient->name }}</strong>,
    </p>
    
    <p style="margin: 0 0 20px 0; font-size: 15px; color: #495057; line-height: 1.6;">
        Unfortunately, your Purchase Request has been <strong style="color: #dc3545;">REJECTED</strong> by <strong>{{ $approval->approver?->name ?? 'Approver' }}</strong>.
    </p>
    
    <!-- PR Information Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8d7da; border-left: 4px solid #dc3545; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 20px;">
                
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #f1aeb5;">
                    <tr>
                        <td style="font-weight: 600; color: #842029; font-size: 14px; padding: 10px 0;">PR Number:</td>
                        <td align="right" style="color: #212529; font-size: 15px; font-weight: 700; padding: 10px 0;">{{ $pr->pr_number }}</td>
                    </tr>
                </table>
                
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #f1aeb5;">
                    <tr>
                        <td style="font-weight: 600; color: #842029; font-size: 14px; padding: 10px 0;">Total Amount:</td>
                        <td align="right" style="color: #dc3545; font-size: 18px; font-weight: 700; padding: 10px 0;">
                            {{ $pr->currency }} {{ number_format($pr->total_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
                
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #f1aeb5;">
                    <tr>
                        <td style="font-weight: 600; color: #842029; font-size: 14px; padding: 10px 0;">Rejected By:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $approval->approver?->name ?? 'Unknown' }}</td>
                    </tr>
                </table>
                
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #f1aeb5;">
                    <tr>
                        <td style="font-weight: 600; color: #842029; font-size: 14px; padding: 10px 0;">Rejected At:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $approval->responded_at?->format('d M Y, H:i') }}</td>
                    </tr>
                </table>
                
                @if($approval->notes)
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0">
                    <tr>
                        <td colspan="2" style="padding: 15px 0 0 0;">
                            <div style="background-color: #fff; border-radius: 4px; padding: 15px; border: 2px solid #dc3545;">
                                <strong style="color: #842029; font-size: 15px;">Rejection Reason:</strong>
                                <p style="margin: 10px 0 0 0; color: #721c24; font-size: 14px; line-height: 1.6; font-weight: 500;">{{ $approval->notes }}</p>
                            </div>
                        </td>
                    </tr>
                </table>
                @endif
                
            </td>
        </tr>
    </table>

    <!-- Next Steps Alert -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 15px; font-size: 15px; color: #856404;">
                <strong style="font-size: 16px;">Next Steps:</strong> You can review the rejection reason and make necessary changes to resubmit your Purchase Request.
            </td>
        </tr>
    </table>

    <!-- Action Links - Mekari Style -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 35px 0 15px 0;">
        <tr>
            <td align="center" style="padding: 0;">
                <a href="{{ route('purchase-requests.show', $pr->id) }}" style="display: inline-block; color: #1e40af; font-size: 18px; font-weight: 600; text-decoration: underline; padding: 8px 0;">
                    View Purchase Request Details
                </a>
            </td>
        </tr>
    </table>
    
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 10px 0 30px 0;">
        <tr>
            <td align="center" style="padding: 0;">
                <p style="margin: 0; color: #6c757d; font-size: 14px;">
                    or <a href="{{ route('purchase-requests.edit', $pr->id) }}" style="color: #dc3545; text-decoration: underline; font-weight: 600;">Edit & Resubmit Request</a>
                </p>
            </td>
        </tr>
    </table>
@endsection
