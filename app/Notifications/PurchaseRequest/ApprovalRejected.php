<?php

namespace App\Notifications\PurchaseRequest;

use App\Models\Modules\PurchaseRequest\PrApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalRejected extends Notification
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $pr = $this->approval->purchaseRequest;

        return (new MailMessage)
            ->subject('Purchase Request Rejected - PR #' . $pr->pr_number)
            ->view('emails.purchase-request.approval-rejected', [
                'approval' => $this->approval,
                'pr' => $pr,
                'recipient' => $notifiable,
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        $pr = $this->approval->purchaseRequest;

        return [
            'type' => 'approval_rejected',
            'pr_id' => $pr->id,
            'pr_number' => $pr->pr_number,
            'approval_id' => $this->approval->id,
            'approver_name' => $this->approval->approver->name,
            'rejection_notes' => $this->approval->notes,
            'message' => "Your Purchase Request #{$pr->pr_number} has been rejected by {$this->approval->approver->name}",
            'action_url' => route('purchase-requests.show', $pr->id),
        ];
    }
}
