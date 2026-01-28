@extends('emails.layouts.email')

@section('title', 'Backdate Permission Request Rejected')

@section('header-title', 'Request Rejected')
@section('header-subtitle', 'Activity Tracking Module')

@section('content')
    <p style="margin: 0 0 15px 0; font-size: 16px; color: #333333;">
        Dear <strong style="color: #212529;">{{ $requester?->name ?? 'Employee' }}</strong>,
    </p>
    
    <p style="margin: 0 0 20px 0; font-size: 15px; color: #495057; line-height: 1.6;">
        Your backdate permission request has been reviewed and rejected.
    </p>
    
    <!-- Rejection Alert -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #fee2e2; border-left: 4px solid #dc3545; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 15px; font-size: 15px; color: #991b1b;">
                <strong style="font-size: 16px;">✗ Rejected</strong> - Your request was not approved.
            </td>
        </tr>
    </table>
    
    <!-- Request Information Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8f9fa; border-left: 4px solid #dc3545; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 20px;">
                
                <!-- Rejected By -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Rejected By:</td>
                        <td align="right" style="color: #212529; font-size: 15px; font-weight: 700; padding: 10px 0;">{{ $backdatePermission->rejector->name ?? 'Department Head' }}</td>
                    </tr>
                </table>
                
                <!-- Requested Date -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Requested Date:</td>
                        <td align="right" style="color: #dc3545; font-size: 16px; font-weight: 700; padding: 10px 0;">
                            {{ $backdatePermission->requested_date->format('d M Y') }}
                        </td>
                    </tr>
                </table>
                
                <!-- Rejected At -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Rejected At:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $backdatePermission->rejected_at?->format('d M Y, H:i') }}</td>
                    </tr>
                </table>
                
            </td>
        </tr>
    </table>

    <!-- Rejection Reason Box -->
    @if($backdatePermission->rejection_reason)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 15px;">
                <strong style="font-size: 15px; color: #856404; display: block; margin-bottom: 8px;">Rejection Reason:</strong>
                <p style="margin: 0; font-size: 14px; color: #856404; line-height: 1.6;">
                    {{ $backdatePermission->rejection_reason }}
                </p>
            </td>
        </tr>
    </table>
    @endif

    <!-- Information Notice -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #e0e7ff; border-left: 4px solid #667eea; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 15px;">
                <strong style="font-size: 15px; color: #3730a3; display: block; margin-bottom: 8px;">What's Next?</strong>
                <p style="margin: 0; font-size: 14px; color: #3730a3; line-height: 1.6;">
                    You can still create tasks with the default backdate limit (1 day back). 
                    If you need to backdate further, please discuss with your department head or submit a new request with more details.
                </p>
            </td>
        </tr>
    </table>

    <!-- Action Links -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 35px 0 15px 0;">
        <tr>
            <td align="center" style="padding: 0;">
                <!-- Primary Action Link -->
                <a href="{{ route('activity.backdate-requests') }}" style="display: inline-block; color: #1e40af; font-size: 18px; font-weight: 600; text-decoration: underline; padding: 8px 0;">
                    View Request History
                </a>
            </td>
        </tr>
    </table>

    <!-- Secondary Action Text -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 10px 0 30px 0;">
        <tr>
            <td align="center" style="padding: 0;">
                <p style="margin: 0; color: #6c757d; font-size: 14px;">
                    or <a href="{{ route('activity.index') }}" style="color: #1e40af; text-decoration: underline;">continue with regular task creation</a>
                </p>
            </td>
        </tr>
    </table>

    <!-- Help Text -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8f9fa; border-radius: 6px; margin: 25px 0 0 0;">
        <tr>
            <td style="padding: 15px; font-size: 14px; color: #6c757d; line-height: 1.6;">
                <strong style="color: #495057;">Need Help?</strong> If you have questions about this rejection, 
                please contact your department head for clarification. You can submit a new request with additional information if needed.
            </td>
        </tr>
    </table>
@endsection
