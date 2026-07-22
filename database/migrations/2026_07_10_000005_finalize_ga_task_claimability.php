<?php

use App\Models\Modules\Purchasing\StockRequest\StockRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tasks = DB::table('admin_tasks')
            ->join('stock_requests', 'stock_requests.id', '=', 'admin_tasks.taskable_id')
            ->join('departments as origin_departments', 'origin_departments.id', '=', 'stock_requests.department_id')
            ->where('admin_tasks.taskable_type', StockRequest::class)
            ->where('origin_departments.is_ga_stock_review_department', true)
            ->whereIn('admin_tasks.status', ['pending_followup', 'in_progress'])
            ->select('admin_tasks.id', 'admin_tasks.business_unit_id')
            ->get();
        $destinationByBusinessUnit = $tasks->pluck('business_unit_id')
            ->unique()
            ->mapWithKeys(fn ($businessUnitId) => [
                $businessUnitId => $this->resolveStrategicSourcingDepartmentId((int) $businessUnitId),
            ]);

        DB::transaction(function () use ($tasks, $destinationByBusinessUnit) {
            DB::table('stock_requests')
                ->whereNotExists(function ($approvalQuery) {
                    $approvalQuery->selectRaw('1')
                        ->from('stock_approvals')
                        ->whereColumn('stock_approvals.stock_request_id', 'stock_requests.id');
                })
                ->whereExists(function ($departmentQuery) {
                    $departmentQuery->selectRaw('1')
                        ->from('departments')
                        ->whereColumn('departments.id', 'stock_requests.department_id')
                        ->where('departments.is_ga_stock_review_department', true);
                })
                ->update([
                    'routes_directly_to_purchasing' => true,
                    'skips_ga_review' => true,
                ]);

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
