<?php

namespace App\Notifications\Purchasing\PurchaseRequest;

use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
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
        // Eager load approvals to avoid N+1 query in view
        $approvals = $this->purchaseRequest->approvals()
            ->where('status', 'approved')
            ->with('approver')
            ->get();

        return (new MailMessage)
            ->subject('Purchase Request Fully Approved - PR #'.$this->purchaseRequest->pr_number)
            ->view('emails.purchasing.purchase-request.approval-completed', [
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
            'category' => 'purchasing',
            'event' => 'purchase_request_approved',
            'pr_id' => $this->purchaseRequest->id,
            'pr_number' => $this->purchaseRequest->pr_number,
            'amount' => $this->purchaseRequest->total_amount,
            'approvers' => $approvers,
            'approved_at' => $this->purchaseRequest->approved_at?->toISOString(),
            'title' => "Purchase Request {$this->purchaseRequest->pr_number} was approved",
            'message' => "Your Purchase Request #{$this->purchaseRequest->pr_number} has been fully approved",
            'action_url' => route('purchase-requests.show', $this->purchaseRequest->id),
            'priority' => 'high',
            'occurred_at' => $this->purchaseRequest->approved_at?->toISOString() ?? now()->toISOString(),
        ];
    }
}
