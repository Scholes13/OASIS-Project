<?php

namespace App\Notifications\PurchaseRequest;

use App\Models\Modules\PurchaseRequest\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalCompleted extends Notification
{
    use Queueable;

    protected PurchaseRequest $purchaseRequest;

    public function __construct(PurchaseRequest $purchaseRequest)
    {
        $this->purchaseRequest = $purchaseRequest;
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
        // Eager load approvals to avoid N+1 query in view
        $approvals = $this->purchaseRequest->approvals()
            ->where('status', 'approved')
            ->with('approver')
            ->get();

        return (new MailMessage)
            ->subject('Purchase Request Fully Approved - PR #' . $this->purchaseRequest->pr_number)
            ->view('emails.purchase-request.approval-completed', [
                'pr' => $this->purchaseRequest,
                'recipient' => $notifiable,
                'approvals' => $approvals,  // Pass pre-loaded approvals
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        $approvers = $this->purchaseRequest->approvals()
            ->where('status', 'approved')
            ->with('approver')
            ->get()
            ->pluck('approver.name')
            ->toArray();

        return [
            'type' => 'approval_completed',
            'pr_id' => $this->purchaseRequest->id,
            'pr_number' => $this->purchaseRequest->pr_number,
            'amount' => $this->purchaseRequest->total_amount,
            'approvers' => $approvers,
            'approved_at' => $this->purchaseRequest->approved_at?->toISOString(),
            'message' => "Your Purchase Request #{$this->purchaseRequest->pr_number} has been fully approved",
            'action_url' => route('purchase-requests.show', $this->purchaseRequest->id),
        ];
    }
}
