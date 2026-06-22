@extends('emails.layouts.email')

@section('title', 'Purchase Request Approval Required')

@section('header-title', 'Approval Required')
@section('header-subtitle', 'Purchase Request #' . $pr->pr_number)

@section('content')
    @php
        preg_match('/^PR\.([^\/]+)/', $pr->pr_number, $businessUnitCodeMatch);
        $businessUnitCode = $pr->businessUnit?->code ?? ($businessUnitCodeMatch[1] ?? 'BU');
        $logoPath = $pr->businessUnit?->logo;
        $businessUnitLogo = $logoPath ? (str($logoPath)->startsWith(['http://', 'https://', '/storage/']) ? $logoPath : asset('storage/'.$logoPath)) : null;
    @endphp

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td style="padding: 0 0 22px 0;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 28px 0;">
                    <tr>
                        <td style="width: 54px; height: 54px; border-radius: 16px; background-color: #e0f2fe; color: #0284c7; font-size: 15px; font-weight: 900; text-align: center; vertical-align: middle; overflow: hidden;">
                            @if($businessUnitLogo)
                                <img src="{{ $businessUnitLogo }}" alt="{{ $businessUnitCode }}" width="54" height="54" style="display: block; width: 54px; height: 54px; object-fit: contain; border: 0;">
                            @else
                                {{ $businessUnitCode ?: 'BU' }}
                            @endif
                        </td>
                    </tr>
                </table>

                <h2 style="margin: 0 0 16px 0; color: #0f172a; font-size: 26px; line-height: 1.25; font-weight: 800;">
                    Purchase Request needs your approval.
                </h2>

                <p style="margin: 0 0 8px 0; color: #0f172a; font-size: 15px; line-height: 1.6;">
                    Hi <strong style="color: #0f172a;">{{ $approver?->name ?? 'Approver' }}</strong>,
                </p>

                <p style="margin: 0; color: #475569; font-size: 14px; line-height: 1.7;">
                    A purchase request has been submitted and is waiting for your review. Please approve or reject it before the due date.
                </p>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #eaf8ff; border: 1px solid #c7edff; border-radius: 18px; margin: 0 0 24px 0;">
        <tr>
            <td style="padding: 22px 24px;">
                <p style="margin: 0 0 16px 0; color: #0f172a; font-size: 14px; font-weight: 800;">Request details</p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr><td style="padding: 0 0 12px 0; color: #64748b; font-size: 13px;">PR Number</td><td align="right" style="padding: 0 0 12px 0; color: #0f172a; font-size: 14px; font-weight: 800;">{{ $pr->pr_number }}</td></tr>
                    @if($pr->keperluan)
                        <tr><td style="padding: 0 0 12px 0; color: #64748b; font-size: 13px; vertical-align: top;">PR Purpose</td><td align="right" style="padding: 0 0 12px 24px; color: #0f172a; font-size: 14px; line-height: 1.5; max-width: 320px;">{{ $pr->keperluan }}</td></tr>
                    @endif
                    <tr><td style="padding: 0 0 12px 0; color: #64748b; font-size: 13px;">Requester</td><td align="right" style="padding: 0 0 12px 0; color: #0f172a; font-size: 14px;">{{ $pr->user?->name ?? 'Unknown User' }}</td></tr>
                    <tr><td style="padding: 0 0 12px 0; color: #64748b; font-size: 13px;">Business Unit</td><td align="right" style="padding: 0 0 12px 0; color: #0f172a; font-size: 14px;">{{ $pr->businessUnit?->name ?? 'N/A' }}</td></tr>
                    <tr><td style="padding: 0 0 12px 0; color: #64748b; font-size: 13px;">Submission Date</td><td align="right" style="padding: 0 0 12px 0; color: #0f172a; font-size: 14px;">{{ $pr->submitted_at?->format('d M Y, H:i') }}</td></tr>
                    @if($approval->due_date)
                        <tr><td style="padding: 0; color: #64748b; font-size: 13px;">Due Date</td><td align="right" style="padding: 0; color: #ef4444; font-size: 14px; font-weight: 800;">{{ $approval->due_date->format('d M Y, H:i') }}</td></tr>
                    @endif
                </table>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 20px 0 0 0; padding: 0; border-top: 1px solid #bae6fd;">
                    <tr><td style="padding: 18px 0 0 0; color: #64748b; font-size: 13px;">Total Amount</td><td align="right" style="padding: 18px 0 0 0; color: #0284c7; font-size: 24px; line-height: 1; font-weight: 900;">{{ $pr->currency }} {{ number_format($pr->total_amount, 0, ',', '.') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 24px 0; color: #475569; font-size: 14px; line-height: 1.7;">
        <strong style="color: #0f172a;">Action required:</strong> This approval link will expire in <strong style="color: #ef4444;">{{ $expiryDays }} days</strong>.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 20px 0;">
        <tr>
            <td style="padding: 0 0 12px 0;">
                <a href="{{ $approveUrl ?? $publicUrl }}" style="display: block; background-color: #1e40af; color: #ffffff; font-size: 15px; font-weight: 900; text-decoration: none; padding: 15px 20px; border-radius: 12px; text-align: center; box-shadow: 0 14px 28px rgba(30, 64, 175, 0.16);">
                    Approve request
                </a>
            </td>
        </tr>
        <tr>
            <td>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td style="padding: 0 8px 0 0;" width="50%">
                            <a href="{{ $rejectUrl ?? $publicUrl }}" style="display: block; background-color: #ffffff; color: #991b1b; font-size: 14px; font-weight: 800; text-decoration: none; padding: 12px 18px; border: 1px solid #e5e7eb; border-radius: 12px; text-align: center;">
                                Reject request
                            </a>
                        </td>
                        <td style="padding: 0 0 0 8px;" width="50%">
                            <a href="{{ $detailsUrl ?? $publicUrl }}" style="display: block; background-color: #ffffff; color: #1e40af; font-size: 14px; font-weight: 800; text-decoration: none; padding: 12px 18px; border: 1px solid #e5e7eb; border-radius: 12px; text-align: center;">
                                View details
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 30px 0; color: #64748b; font-size: 13px; line-height: 1.6;">
        Need full details and approval history? <a href="{{ $dashboardUrl ?? route('approvals.show', $approval->id) }}" style="color: #0284c7; font-weight: 700; text-decoration: none;">Open dashboard</a>.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #1e3a8a; border-radius: 16px; margin: 0;">
        <tr>
            <td align="center" style="padding: 24px 20px; color: #ffffff;">
                <p style="margin: 0 0 6px 0; color: #dff6ff; font-size: 12px; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase;">OASIS</p>
                <p style="margin: 0; color: #ffffff; font-size: 22px; line-height: 1.25; font-weight: 900;">Fast, traceable approval workflow</p>
            </td>
        </tr>
    </table>
@endsection
