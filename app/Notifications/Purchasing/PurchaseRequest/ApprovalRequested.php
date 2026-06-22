<?php

namespace App\Notifications\Purchasing\PurchaseRequest;

use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ApprovalRequested extends Notification
{
    use Queueable;

    protected PrApproval $approval;

    public function __construct(PrApproval $approval)
    {
        $this->approval = $approval;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $pr = $this->approval->purchaseRequest;
        $expiryDays = config('notification.link_expiry_days', 3);

        // Generate signed URL valid for configured days
        $publicUrl = URL::temporarySignedRoute(
            'approvals.public.approve',
            now()->addDays($expiryDays),
            ['approval' => $this->approval->id]
        );

        $approveUrl = URL::temporarySignedRoute(
            'approvals.public.approve',
            now()->addDays($expiryDays),
            ['approval' => $this->approval->id, 'intent' => 'approve']
        );

        $rejectUrl = URL::temporarySignedRoute(
            'approvals.public.approve',
            now()->addDays($expiryDays),
            ['approval' => $this->approval->id, 'intent' => 'reject']
        );

        return (new MailMessage)
            ->subject('Purchase Request Approval Required - PR #'.$pr->pr_number)
            ->view('emails.purchasing.purchase-request.approval-requested', [
                'approval' => $this->approval,
                'pr' => $pr,
                'approver' => $notifiable,
                'publicUrl' => $publicUrl,
                'approveUrl' => $approveUrl,
                'rejectUrl' => $rejectUrl,
                'detailsUrl' => $publicUrl,
                'dashboardUrl' => route('approvals.show', $this->approval->id),
                'expiryDays' => $expiryDays,
                'hideEmailHeader' => true,
            ]);
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        $pr = $this->approval->purchaseRequest;

        return [
            'type' => 'approval_requested',
            'category' => 'purchasing',
            'event' => 'purchase_request_approval_requested',
            'pr_id' => $pr->id,
            'pr_number' => $pr->pr_number,
            'approval_id' => $this->approval->id,
            'step_order' => $this->approval->step_order,
            'approval_type' => $this->approval->approval_type,
            'amount' => $pr->total_amount,
            'requestor_name' => $pr->user->name,
            'due_date' => $this->approval->due_date?->toISOString(),
            'title' => "Purchase Request {$pr->pr_number} is awaiting your approval",
            'message' => "Purchase Request #{$pr->pr_number} requires your approval",
            'action_url' => route('approvals.show', $this->approval->id),
            'priority' => 'high',
            'occurred_at' => $this->approval->assigned_at?->toISOString() ?? now()->toISOString(),
        ];
    }
}
