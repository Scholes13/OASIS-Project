@extends('emails.layouts.email')

@section('title', 'Backdate Permission Request')

@section('header-title', 'Backdate Permission Request')
@section('header-subtitle', 'Activity Tracking Module')

@section('content')
    <p style="margin: 0 0 15px 0; font-size: 16px; color: #333333;">
        Dear <strong style="color: #212529;">{{ $departmentHead?->name ?? 'Department Head' }}</strong>,
    </p>
    
    <p style="margin: 0 0 20px 0; font-size: 15px; color: #495057; line-height: 1.6;">
        An employee from your department has requested permission to backdate task entries.
    </p>
    
    <!-- Request Information Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8f9fa; border-left: 4px solid #667eea; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 20px;">
                
                <!-- Requester -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Requested By:</td>
                        <td align="right" style="color: #212529; font-size: 15px; font-weight: 700; padding: 10px 0;">{{ $backdatePermission->requester->name }}</td>
                    </tr>
                </table>
                
                <!-- Department -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Department:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $backdatePermission->department->name ?? 'N/A' }}</td>
                    </tr>
                </table>
                
                <!-- Business Unit -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Business Unit:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $backdatePermission->businessUnit->name ?? 'N/A' }}</td>
                    </tr>
                </table>
                
                <!-- Requested Date -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Requested Date:</td>
                        <td align="right" style="color: #667eea; font-size: 16px; font-weight: 700; padding: 10px 0;">
                            {{ $backdatePermission->requested_date->format('d M Y') }}
                        </td>
                    </tr>
                </table>
                
                <!-- Submission Date -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Submitted At:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $backdatePermission->created_at->format('d M Y, H:i') }}</td>
                    </tr>
                </table>
                
            </td>
        </tr>
    </table>

    <!-- Reason Box -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 15px;">
                <strong style="font-size: 15px; color: #856404; display: block; margin-bottom: 8px;">Reason:</strong>
                <p style="margin: 0; font-size: 14px; color: #856404; line-height: 1.6;">
                    {{ $backdatePermission->reason }}
                </p>
            </td>
        </tr>
    </table>

    <!-- Action Links -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 35px 0 15px 0;">
        <tr>
            <td align="center" style="padding: 0;">
                <!-- Primary Action Link -->
                <a href="{{ route('activity.backdate.approvals') }}" style="display: inline-block; color: #1e40af; font-size: 18px; font-weight: 600; text-decoration: underline; padding: 8px 0;">
                    Review Request
                </a>
            </td>
        </tr>
    </table>

    <!-- Help Text -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8f9fa; border-radius: 6px; margin: 25px 0 0 0;">
        <tr>
            <td style="padding: 15px; font-size: 14px; color: #6c757d; line-height: 1.6;">
                <strong style="color: #495057;">Action Required:</strong> Please review this backdate permission request. 
                If approved, the employee will be able to enter tasks with dates up to the requested date until the end of today.
            </td>
        </tr>
    </table>
@endsection
