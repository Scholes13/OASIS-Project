@extends('emails.layouts.email')

@section('title', 'Backdate Permission Approved')

@section('header-title', 'Request Approved')
@section('header-subtitle', 'Activity Tracking Module')

@section('content')
    <p style="margin: 0 0 15px 0; font-size: 16px; color: #333333;">
        Dear <strong style="color: #212529;">{{ $requester?->name ?? 'Employee' }}</strong>,
    </p>
    
    <p style="margin: 0 0 20px 0; font-size: 15px; color: #495057; line-height: 1.6;">
        Good news! Your backdate permission request has been approved.
    </p>
    
    <!-- Success Alert -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #d1fae5; border-left: 4px solid #10b981; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 15px; font-size: 15px; color: #065f46;">
                <strong style="font-size: 16px;">✓ Approved</strong> - You can now enter tasks with backdated dates.
            </td>
        </tr>
    </table>
    
    <!-- Request Information Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8f9fa; border-left: 4px solid #10b981; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 20px;">
                
                <!-- Approved By -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Approved By:</td>
                        <td align="right" style="color: #212529; font-size: 15px; font-weight: 700; padding: 10px 0;">{{ $backdatePermission->approver->name ?? 'Department Head' }}</td>
                    </tr>
                </table>
                
                <!-- Requested Date -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Requested Date:</td>
                        <td align="right" style="color: #10b981; font-size: 16px; font-weight: 700; padding: 10px 0;">
                            {{ $backdatePermission->requested_date->format('d M Y') }}
                        </td>
                    </tr>
                </table>
                
                <!-- Valid Until -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Valid Until:</td>
                        <td align="right" style="color: #dc3545; font-size: 14px; font-weight: 600; padding: 10px 0;">
                            {{ $backdatePermission->granted_until?->format('d M Y, H:i') ?? 'End of today' }}
                        </td>
                    </tr>
                </table>
                
                <!-- Approved At -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Approved At:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $backdatePermission->approved_at?->format('d M Y, H:i') }}</td>
                    </tr>
                </table>
                
            </td>
        </tr>
    </table>

    <!-- Important Notice -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 15px;">
                <strong style="font-size: 15px; color: #856404; display: block; margin-bottom: 8px;">Important:</strong>
                <p style="margin: 0; font-size: 14px; color: #856404; line-height: 1.6;">
                    This permission is valid until <strong>{{ $backdatePermission->granted_until?->format('d M Y, H:i') ?? 'end of today' }}</strong>. 
                    After this time, you will only be able to backdate tasks by 1 day (default limit).
                </p>
            </td>
        </tr>
    </table>

    <!-- Action Links -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 35px 0 15px 0;">
        <tr>
            <td align="center" style="padding: 0;">
                <!-- Primary Action Link -->
                <a href="{{ route('activity.index') }}" style="display: inline-block; color: #1e40af; font-size: 18px; font-weight: 600; text-decoration: underline; padding: 8px 0;">
                    Create Task
                </a>
            </td>
        </tr>
    </table>

    <!-- Secondary Action Text -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 10px 0 30px 0;">
        <tr>
            <td align="center" style="padding: 0;">
                <p style="margin: 0; color: #6c757d; font-size: 14px;">
                    or <a href="{{ route('activity.backdate-requests') }}" style="color: #1e40af; text-decoration: underline;">view your backdate requests</a>
                </p>
            </td>
        </tr>
    </table>

    <!-- Help Text -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8f9fa; border-radius: 6px; margin: 25px 0 0 0;">
        <tr>
            <td style="padding: 15px; font-size: 14px; color: #6c757d; line-height: 1.6;">
                <strong style="color: #495057;">What's Next?</strong> You can now create tasks with dates going back to {{ $backdatePermission->requested_date->format('d M Y') }}. 
                This permission will automatically expire at the end of today.
            </td>
        </tr>
    </table>
@endsection
