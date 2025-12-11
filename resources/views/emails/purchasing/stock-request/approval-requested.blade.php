@extends('emails.layouts.email')

@section('title', 'Stock Request Approval Required')

@section('header-title', 'Approval Required')
@section('header-subtitle', 'Stock Request #' . $st->st_number)

@section('content')
    <p style="margin: 0 0 15px 0; font-size: 16px; color: #333333;">
        Dear <strong style="color: #212529;">{{ $approver?->name ?? 'Approver' }}</strong>,
    </p>
    
    <p style="margin: 0 0 20px 0; font-size: 15px; color: #495057; line-height: 1.6;">
        A new Stock Request requires your approval.
    </p>
    
    <!-- ST Information Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8f9fa; border-left: 4px solid #667eea; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 20px;">
                
                <!-- ST Number -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">ST Number:</td>
                        <td align="right" style="color: #212529; font-size: 15px; font-weight: 700; padding: 10px 0;">{{ $st->st_number }}</td>
                    </tr>
                </table>
                
                <!-- Requested By -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Requested By:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $st->user?->name ?? 'Unknown User' }}</td>
                    </tr>
                </table>
                
                <!-- Business Unit -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Business Unit:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $st->businessUnit?->name ?? 'N/A' }}</td>
                    </tr>
                </table>
                
                <!-- Department -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Department:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $st->department?->name ?? 'N/A' }}</td>
                    </tr>
                </table>
                
                <!-- Purpose -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Purpose:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ Str::limit($st->purpose, 50) }}</td>
                    </tr>
                </table>
                
                <!-- Submission Date -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Submission Date:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $st->submitted_at?->format('d M Y, H:i') }}</td>
                    </tr>
                </table>
                
                <!-- Due Date -->
                @if($approval->due_date)
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Due Date:</td>
                        <td align="right" style="color: #dc3545; font-size: 14px; font-weight: 600; padding: 10px 0;">{{ $approval->due_date->format('d M Y, H:i') }}</td>
                    </tr>
                </table>
                @endif
                
            </td>
        </tr>
    </table>

    <!-- Action Required Alert -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 15px; font-size: 15px; color: #856404;">
                <strong style="font-size: 16px;">Action Required:</strong> This approval link will expire in <strong>{{ $expiryDays }} days</strong>.
            </td>
        </tr>
    </table>

    <!-- Action Links -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 35px 0 15px 0;">
        <tr>
            <td align="center" style="padding: 0;">
                <!-- Primary Action Link -->
                <a href="{{ $publicUrl }}" style="display: inline-block; color: #1e40af; font-size: 18px; font-weight: 600; text-decoration: underline; padding: 8px 0;">
                    Review & Sign Here
                </a>
            </td>
        </tr>
    </table>

    <!-- Secondary Action Text -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 10px 0 30px 0;">
        <tr>
            <td align="center" style="padding: 0;">
                <p style="margin: 0; color: #6c757d; font-size: 14px;">
                    or <a href="{{ route('stock-approvals.show', $approval->id) }}" style="color: #1e40af; text-decoration: underline;">login to check the details and history</a>
                </p>
            </td>
        </tr>
    </table>

    <!-- Help Text -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8f9fa; border-radius: 6px; margin: 25px 0 0 0;">
        <tr>
            <td style="padding: 15px; font-size: 14px; color: #6c757d; line-height: 1.6;">
                <strong style="color: #495057;">Quick Approval:</strong> You can approve or reject this request directly from the email link without logging in. 
                Alternatively, login to your dashboard for full details and approval history.
            </td>
        </tr>
    </table>
@endsection
