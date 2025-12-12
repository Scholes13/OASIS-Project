@extends('emails.layouts.email')

@section('title', 'SLA Alert - ' . $slaLabel . ' Time Exceeded')

@section('header-title', 'SLA Alert')
@section('header-subtitle', '{{ $slaLabel }} Time Exceeded')

@section('content')
    <p style="margin: 0 0 15px 0; font-size: 16px; color: #333333;">
        Dear <strong style="color: #212529;">{{ $recipient?->name ?? 'Team Member' }}</strong>,
    </p>
    
    <p style="margin: 0 0 20px 0; font-size: 15px; color: #495057; line-height: 1.6;">
        This is an automated alert to notify you that a procurement task has exceeded its {{ strtolower($slaLabel) }} SLA target.
    </p>
    
    <!-- SLA Alert Banner -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #fee2e2; border-left: 4px solid #dc2626; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td style="font-size: 18px; font-weight: 700; color: #991b1b; padding-bottom: 8px;">
                            ⚠️ {{ $slaLabel }} SLA Exceeded
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 14px; color: #7f1d1d; line-height: 1.6;">
                            @if($slaType === 'followup')
                                This task has been pending for longer than the configured follow-up SLA target. Immediate action is required to begin the procurement process.
                            @else
                                This task has been in progress for longer than the configured completion SLA target. Please prioritize completing this task.
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <!-- Task Information Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8f9fa; border-left: 4px solid #dc2626; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 20px;">
                
                <!-- Task Type & Number -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Task Number:</td>
                        <td align="right" style="color: #212529; font-size: 15px; font-weight: 700; padding: 10px 0;">{{ $taskType }} #{{ $taskNumber }}</td>
                    </tr>
                </table>
                
                <!-- Assigned Admin -->
                @if($task->assignedAdmin)
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Assigned To:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $task->assignedAdmin->name }}</td>
                    </tr>
                </table>
                @endif
                
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
                        <td align="right" style="color: #dc2626; font-size: 18px; font-weight: 700; padding: 10px 0;">
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
                
                <!-- Started Date (if applicable) -->
                @if($task->started_at)
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Started:</td>
                        <td align="right" style="color: #212529; font-size: 14px; padding: 10px 0;">{{ $task->started_at->format('d M Y, H:i') }}</td>
                    </tr>
                </table>
                @endif
                
                <!-- Time Elapsed -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-bottom: 1px solid #e9ecef;">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Time Elapsed:</td>
                        <td align="right" style="color: #dc2626; font-size: 14px; font-weight: 600; padding: 10px 0;">
                            @if($slaType === 'followup')
                                {{ $task->entered_at->diffForHumans(null, true) }}
                            @else
                                {{ $task->started_at->diffForHumans(null, true) }}
                            @endif
                        </td>
                    </tr>
                </table>
                
                <!-- Status -->
                <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0">
                    <tr>
                        <td style="font-weight: 600; color: #495057; font-size: 14px; padding: 10px 0;">Status:</td>
                        <td align="right" style="padding: 10px 0;">
                            @if($task->status === 'pending_followup')
                                <span style="display: inline-block; padding: 4px 12px; background-color: #fef3c7; color: #92400e; font-size: 13px; font-weight: 600; border-radius: 12px;">
                                    Pending Follow-up
                                </span>
                            @elseif($task->status === 'in_progress')
                                <span style="display: inline-block; padding: 4px 12px; background-color: #dbeafe; color: #1e40af; font-size: 13px; font-weight: 600; border-radius: 12px;">
                                    In Progress
                                </span>
                            @else
                                <span style="display: inline-block; padding: 4px 12px; background-color: #d1fae5; color: #065f46; font-size: 13px; font-weight: 600; border-radius: 12px;">
                                    {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                </table>
                
            </td>
        </tr>
    </table>

    <!-- Action Required Alert -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 6px; margin: 20px 0;">
        <tr>
            <td style="padding: 15px; font-size: 15px; color: #856404;">
                <strong style="font-size: 16px;">Urgent Action Required:</strong> 
                @if($slaType === 'followup')
                    Please start working on this task immediately to minimize delays in the procurement process.
                @else
                    Please prioritize completing this task to meet procurement deadlines.
                @endif
            </td>
        </tr>
    </table>

    <!-- Action Button -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 35px 0 15px 0;">
        <tr>
            <td align="center" style="padding: 0;">
                <a href="{{ url('/purchasing/admin/tasks/' . $task->id) }}" style="display: inline-block; padding: 14px 32px; background-color: #dc2626; color: #ffffff; font-size: 16px; font-weight: 600; text-decoration: none; border-radius: 8px; box-shadow: 0 2px 4px rgba(220, 38, 38, 0.3);">
                    View Task & Take Action
                </a>
            </td>
        </tr>
    </table>

    <!-- Help Text -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8f9fa; border-radius: 6px; margin: 25px 0 0 0;">
        <tr>
            <td style="padding: 15px; font-size: 14px; color: #6c757d; line-height: 1.6;">
                <strong style="color: #495057;">About SLA Alerts:</strong> This automated alert is sent when tasks exceed their configured Service Level Agreement (SLA) targets. 
                @if($slaType === 'followup')
                    The follow-up SLA measures the time from task entry to when work begins.
                @else
                    The completion SLA measures the time from starting work to task completion.
                @endif
                Please take immediate action to address this task.
            </td>
        </tr>
    </table>
@endsection
