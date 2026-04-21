<?php

namespace App\Notifications\Purchasing\StockRequest;

use App\Models\Modules\Purchasing\StockRequest\StockApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ApprovalRequested extends Notification
{
    use Queueable;

    protected StockApproval $approval;

    public function __construct(StockApproval $approval)
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
        $st = $this->approval->stockRequest;
        $expiryDays = config('notification.link_expiry_days', 3);

        // Generate signed URL valid for configured days
        $publicUrl = URL::temporarySignedRoute(
            'stock-approvals.public.approve',
            now()->addDays($expiryDays),
            ['approval' => $this->approval->id]
        );

        return (new MailMessage)
            ->subject('Stock Request Approval Required - ST #'.$st->st_number)
            ->view('emails.purchasing.stock-request.approval-requested', [
                'approval' => $this->approval,
                'st' => $st,
                'approver' => $notifiable,
                'publicUrl' => $publicUrl,
                'expiryDays' => $expiryDays,
            ]);
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        $st = $this->approval->stockRequest;

        return [
            'type' => 'stock_approval_requested',
            'category' => 'purchasing',
            'event' => 'stock_request_approval_requested',
            'st_id' => $st->id,
            'st_number' => $st->st_number,
            'approval_id' => $this->approval->id,
            'step_order' => $this->approval->step_order,
            'approval_type' => $this->approval->approval_type,
            'requestor_name' => $st->user->name,
            'due_date' => $this->approval->due_date?->toISOString(),
            'title' => "Stock Request {$st->st_number} is awaiting your approval",
            'message' => "Stock Request #{$st->st_number} requires your approval",
            'action_url' => route('stock-approvals.show', $this->approval->id),
            'priority' => 'high',
            'occurred_at' => $this->approval->assigned_at?->toISOString() ?? now()->toISOString(),
        ];
    }
}
