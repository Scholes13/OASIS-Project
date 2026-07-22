<?php

use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $gaOriginQuery = function ($query) {
            $query->selectRaw('1')
                ->from('departments')
                ->whereColumn('departments.id', 'stock_requests.department_id')
                ->whereColumn('departments.business_unit_id', 'stock_requests.business_unit_id')
                ->where('departments.is_ga_stock_review_department', true);
        };

        $gaRequestIds = DB::table('stock_requests')
            ->whereExists($gaOriginQuery)
            ->pluck('id');
        $directRequestIds = DB::table('stock_requests')
            ->whereIn('id', $gaRequestIds)
            ->where(function ($query) {
                $query->whereIn('status', ['submitted', 'in_approval', 'ga_review'])
                    ->orWhereNotExists(function ($approvalQuery) {
                        $approvalQuery->selectRaw('1')
                            ->from('stock_approvals')
                            ->whereColumn('stock_approvals.stock_request_id', 'stock_requests.id');
                    });
            })
            ->pluck('id');
        $activeRequests = DB::table('stock_requests')
            ->whereIn('id', $directRequestIds)
            ->whereIn('status', ['submitted', 'in_approval', 'ga_review'])
            ->get(['id', 'business_unit_id']);
        $tasks = DB::table('admin_tasks')
            ->join('stock_requests', 'stock_requests.id', '=', 'admin_tasks.taskable_id')
            ->where('admin_tasks.taskable_type', StockRequest::class)
            ->whereIn('stock_requests.id', $gaRequestIds)
            ->whereIn('admin_tasks.status', ['pending_followup', 'in_progress'])
            ->select('admin_tasks.id', 'admin_tasks.business_unit_id')
            ->get();
        $businessUnitIds = $activeRequests->pluck('business_unit_id')
            ->merge($tasks->pluck('business_unit_id'))
            ->unique();
        $destinationByBusinessUnit = $businessUnitIds->mapWithKeys(fn ($businessUnitId) => [
            $businessUnitId => $this->resolveStrategicSourcingDepartmentId((int) $businessUnitId),
        ]);

        DB::transaction(function () use (
            $gaRequestIds,
            $directRequestIds,
            $activeRequests,
            $tasks,
            $destinationByBusinessUnit,
        ) {
            DB::table('stock_requests')->update([
                'routes_directly_to_purchasing' => false,
                'skips_ga_review' => false,
            ]);
            DB::table('stock_requests')->whereIn('id', $gaRequestIds)
                ->update(['skips_ga_review' => true]);
            DB::table('stock_requests')->whereIn('id', $directRequestIds)
                ->update(['routes_directly_to_purchasing' => true]);

            foreach ($activeRequests as $request) {
                $departmentId = $destinationByBusinessUnit[$request->business_unit_id];
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

                DB::table('stock_requests')->where('id', $request->id)->update([
                    'status' => 'ready_for_purchasing',
                    'ga_review_started_at' => null,
                    'updated_at' => now(),
                ]);

                DB::table('admin_tasks')->updateOrInsert(
                    [
                        'taskable_type' => StockRequest::class,
                        'taskable_id' => $request->id,
                    ],
                    [
                        'business_unit_id' => $request->business_unit_id,
                        'department_id' => $departmentId,
                        'assigned_admin_id' => null,
                        'status' => 'pending_followup',
                        'entered_at' => now(),
                        'estimated_total_price' => 0,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }

            foreach ($tasks as $task) {
                DB::table('admin_tasks')->where('id', $task->id)->update([
                    'department_id' => $destinationByBusinessUnit[$task->business_unit_id],
                    'assigned_admin_id' => null,
                    'status' => 'pending_followup',
                    'started_at' => null,
                    'followup_time_minutes' => null,
                    'updated_at' => now(),
                ]);
            }
        });
    }

    private function resolveStrategicSourcingDepartmentId(int $businessUnitId): int
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
