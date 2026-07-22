<?php

use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $requests = DB::table('stock_requests')
            ->where('routes_directly_to_purchasing', true)
            ->whereIn('status', ['submitted', 'in_approval', 'ga_review', 'ready_for_purchasing'])
            ->get(['id', 'business_unit_id']);

        $destinationByBusinessUnit = $requests
            ->pluck('business_unit_id')
            ->unique()
            ->mapWithKeys(fn ($businessUnitId) => [
                $businessUnitId => $this->resolvePurchasingDepartmentId((int) $businessUnitId),
            ]);

        DB::transaction(function () use ($requests, $destinationByBusinessUnit) {
            foreach ($requests as $request) {
                $purchasingDepartmentId = $destinationByBusinessUnit[$request->business_unit_id];

                DB::table('stock_approvals')
                    ->where('stock_request_id', $request->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'skipped',
                        'responded_at' => now(),
                        'updated_at' => now(),
                    ]);

                DB::table('stock_items')
                    ->where('stock_request_id', $request->id)
                    ->update([
                        'ga_review_result' => 'need_procurement',
                        'ga_review_note' => null,
                        'warehouse_available_qty' => 0,
                        'updated_at' => now(),
                    ]);

                DB::table('stock_requests')
                    ->where('id', $request->id)
                    ->update([
                        'status' => 'ready_for_purchasing',
                        'ga_review_started_at' => null,
                        'ga_reviewed_at' => null,
                        'ga_reviewed_by' => null,
                        'ga_review_notes' => null,
                        'updated_at' => now(),
                    ]);

                $task = DB::table('admin_tasks')
                    ->where('taskable_type', StockRequest::class)
                    ->where('taskable_id', $request->id)
                    ->first();

                if ($task && in_array($task->status, ['pending_followup', 'in_progress'], true)) {
                    DB::table('admin_tasks')->where('id', $task->id)->update([
                        'department_id' => $purchasingDepartmentId,
                        'assigned_admin_id' => null,
                        'status' => 'pending_followup',
                        'started_at' => null,
                        'followup_time_minutes' => null,
                        'updated_at' => now(),
                    ]);
                } elseif (! $task) {
                    DB::table('admin_tasks')->insert([
                        'taskable_type' => StockRequest::class,
                        'taskable_id' => $request->id,
                        'business_unit_id' => $request->business_unit_id,
                        'department_id' => $purchasingDepartmentId,
                        'assigned_admin_id' => null,
                        'status' => 'pending_followup',
                        'entered_at' => now(),
                        'estimated_total_price' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    private function resolvePurchasingDepartmentId(int $businessUnitId): int
    {
        $departmentIds = DB::table('departments')
            ->where('business_unit_id', $businessUnitId)
            ->where('code', 'SS')
            ->where('is_active', true)
            ->pluck('id');

        if ($departmentIds->count() !== 1) {
            throw new RuntimeException('Exactly one Strategic Sourcing department must be configured for active GA stock requests.');
        }

        return (int) $departmentIds->first();
    }

    public function down(): void {}
};
