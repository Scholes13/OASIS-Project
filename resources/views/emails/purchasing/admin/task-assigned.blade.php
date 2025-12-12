@extends('emails.layouts.email')

@section('title', 'Admin Task Assigned')

@section('header-title', 'New Task Assigned')
@section('header-subtitle', '{{ $taskType }} #{{ $taskNumber }}')

@section('content')
    <p style="margin: 0 0 15px 0; font-size: 16px; color: #333333;">
        Dear <strong style="color: #212529;">{{ $admin?->name ?? 'Admin' }}</strong>,
    </p>
    
    <p style="margin: 0 0 20px 0; font-size: 15px; color: #495057; line-height: 1.6;">
        A new procurement follow-up task has been assigned to you.
    </p>
    
    <!-- Task Information Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8f9fa; border-left: 4px solid #667eea; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 20px;">
                
                <!-- Task Type & Number -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Task Number:</td>
                        <td align="right" style="color: #212529; font-size: 15px; font-weight: 700; padding: 10px 0;">{{ $taskType }} #{{ $taskNumber }}</td>
                    </tr>
                </table>
                
                <!-- Requested By -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Requested By:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $taskable->user?->name ?? 'Unknown User' }}</td>
                    </tr>
                </table>
                
                <!-- Business Unit -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Business Unit:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $task->businessUnit?->name ?? 'N/A' }}</td>
                    </tr>
                </table>
                
                <!-- Department -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Department:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $task->department?->name ?? 'N/A' }}</td>
                    </tr>
                </table>
                
                <!-- Estimated Amount -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Estimated Amount:</td>
                        <td align="right" style="color: #667eea; font-size: 18px; font-weight: 700; padding: 10px 0;">
                            {{ $taskable->currency ?? 'IDR' }} {{ number_format($task->estimated_total_price, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
                
                <!-- Task Entered Date -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Task Entered:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $task->entered_at->format('d M Y, H:i') }}</td>
                    </tr>
                </table>
                
                <!-- Status -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Status:</td>
                        <td align="right" style="padding: 10px 0;">
                            <span style="display: inline-block; padding: 4px 12px; background-color: #fef3c7; color: #92400e; font-size: 13px; font-weight: 600; border-radius: 12px;">
                                Pending Follow-up
                            </span>
                        </td>
                    </tr>
                </table>
                
            </td>
        </tr>
    </table>

    <!-- Action Required Alert -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #dbeafe; border-left: 4px solid #3b82f6; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 15px; font-size: 15px; color: #1e40af;">
                <strong style="font-size: 16px;">Action Required:</strong> Please review this task and begin the procurement follow-up process.
            </td>
        </tr>
    </table>

    <!-- Action Button -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 35px 0 15px 0;">
        <tr>
            <td align="center" style="padding: 0;">
                <a href="{{ url('/purchasing/admin/tasks/' . $task->id) }}" style="display: inline-block; padding: 14px 32px; background-color: #667eea; color: #ffffff; font-size: 16px; font-weight: 600; text-decoration: none; border-radius: 8px; box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);">
                    View Task Details
                </a>
            </td>
        </tr>
    </table>

    <!-- Help Text -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8f9fa; border-radius: 6px; margin: 25px 0 0 0;">
        <tr>
            <td style="padding: 15px; font-size: 14px; color: #6c757d; line-height: 1.6;">
                <strong style="color: #495057;">Next Steps:</strong> Login to your dashboard to view the complete task details, start working on the procurement follow-up, and track your progress. Remember to update the realized price when completing the task.
            </td>
        </tr>
    </table>
@endsection
