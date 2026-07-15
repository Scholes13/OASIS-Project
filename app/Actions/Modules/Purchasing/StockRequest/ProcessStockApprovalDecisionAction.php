<?php

namespace App\Actions\Modules\Purchasing\StockRequest;

use App\Models\Core\User;
use App\Models\Modules\Purchasing\StockRequest\StockApproval;
use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use App\Services\Core\EmailNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessStockApprovalDecisionAction
{
    public function __construct(
        private EmailNotificationService $emailService,
    ) {}

    public function execute(StockApproval $approval, string $action, ?string $notes): void
    {
        DB::transaction(function () use ($approval, $action, $notes) {
            $stockRequest = StockRequest::query()
                ->lockForUpdate()
                ->findOrFail($approval->stock_request_id);
            $approval = StockApproval::query()
                ->lockForUpdate()
                ->findOrFail($approval->id);

            if ($approval->stock_request_id !== $stockRequest->id) {
                throw new \DomainException('Approval does not belong to this stock request.');
            }

            if ($approval->status !== 'pending' || $stockRequest->status !== 'in_approval') {
                throw new \DomainException('This approval is no longer active.');
            }

            $currentApproval = $stockRequest->approvals()
                ->where('status', 'pending')
                ->orderBy('step_order')
                ->first();
            if (! $currentApproval || $currentApproval->id !== $approval->id) {
                throw new \DomainException('This approval is not currently active.');
            }

            if ($action === 'rejected' && blank($notes)) {
                throw new \DomainException('Notes are required when rejecting a request.');
            }

            $approver = User::query()->find($approval->approver_id);
            if (! $approver || ! ($approver->is_active ?? true)) {
                throw new \DomainException('The assigned approver is no longer active. Please contact an administrator to reassign the approval.');
            }

            $approval->update([
                'status' => $action,
                'notes' => $notes,
                'responded_at' => now(),
            ]);

            if ($action === 'rejected') {
                $stockRequest->update([
                    'status' => 'rejected',
                    'rejected_at' => now(),
                    'rejection_notes' => $notes,
                ]);
                DB::afterCommit(fn () => $this->sendNotificationSafely(
                    fn () => $this->emailService->sendStApprovalRejected(
                        StockApproval::query()->with(['stockRequest', 'approver'])->findOrFail($approval->id)
                    ),
                    $stockRequest->id,
                ));

                return;
            }

            if ($approval->approval_type === 'department_lead') {
                $stockRequest->approvals()
                    ->where('id', '!=', $approval->id)
                    ->where('approval_type', 'department_lead')
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'skipped',
                        'responded_at' => now(),
                    ]);
            }

            $nextApproval = $stockRequest->approvals()
                ->where('status', 'pending')
                ->orderBy('step_order')
                ->first();

            if ($nextApproval) {
                $nextApproval->update(['assigned_at' => now()]);
                DB::afterCommit(fn () => $this->sendNotificationSafely(
                    fn () => $this->emailService->sendStApprovalRequested(
                        StockApproval::query()->findOrFail($nextApproval->id)
                    ),
                    $stockRequest->id,
                ));

                return;
            }

            $stockRequest->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);
            DB::afterCommit(fn () => $this->sendNotificationSafely(
                fn () => $this->emailService->sendStApprovalApproved(
                    StockRequest::query()->findOrFail($stockRequest->id)
                ),
                $stockRequest->id,
            ));
        });
    }

    private function sendNotificationSafely(callable $callback, int $stockRequestId): void
    {
        try {
            $callback();
        } catch (\Throwable $exception) {
            Log::error('Failed to send stock approval notification', [
                'stock_request_id' => $stockRequestId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
