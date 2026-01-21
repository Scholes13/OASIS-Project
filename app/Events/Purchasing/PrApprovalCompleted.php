<?php

namespace App\Events\Purchasing;

use App\Models\Modules\Purchasing\PurchaseRequest\PrApproval;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrApprovalCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public PrApproval $approval;

    /**
     * Create a new event instance.
     */
    public function __construct(PrApproval $approval)
    {
        $this->approval = $approval;
    }
}
