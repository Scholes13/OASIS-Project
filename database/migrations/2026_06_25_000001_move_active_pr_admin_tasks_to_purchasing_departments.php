<?php

use App\Models\Modules\Purchasing\PurchaseRequest\PurchaseRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tasks = DB::table('admin_tasks')
            ->join('purchase_requests', 'purchase_requests.id', '=', 'admin_tasks.taskable_id')
            ->join('departments as current_departments', 'current_departments.id', '=', 'admin_tasks.department_id')
            ->where('admin_tasks.taskable_type', PurchaseRequest::class)
            ->whereIn('admin_tasks.status', ['pending_followup', 'in_progress'])
            ->where('current_departments.is_purchasing_department', false)
            ->select([
                'admin_tasks.id',
                'admin_tasks.business_unit_id',
                'admin_tasks.assigned_admin_id',
                'admin_tasks.status',
                'purchase_requests.business_unit_id as pr_business_unit_id',
            ])
            ->get();

        foreach ($tasks as $task) {
            $businessUnitId = $task->business_unit_id ?: $task->pr_business_unit_id;
            $purchasingDepartmentId = DB::table('departments')
                ->where('business_unit_id', $businessUnitId)
                ->where('is_purchasing_department', true)
                ->value('id');

            if (! $purchasingDepartmentId) {
                continue;
            }

            $assignedAdminId = $this->resolveAssignedAdminId(
                $task->assigned_admin_id,
                $purchasingDepartmentId,
                $businessUnitId
            );

            DB::table('admin_tasks')
                ->where('id', $task->id)
                ->update([
                    'department_id' => $purchasingDepartmentId,
                    'assigned_admin_id' => $assignedAdminId,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
    }

    private function resolveAssignedAdminId(?int $currentAdminId, int $departmentId, int $businessUnitId): ?int
    {
        if ($currentAdminId && $this->isActivePurchasingAdmin($currentAdminId, $departmentId, $businessUnitId)) {
            return $currentAdminId;
        }

        $adminIds = DB::table('user_business_units')
            ->where('department_id', $departmentId)
            ->where('business_unit_id', $businessUnitId)
            ->where('is_purchasing_admin', true)
            ->where('is_purchasing_readonly', false)
            ->where('is_active', true)
            ->pluck('user_id');

        return $adminIds->count() === 1 ? (int) $adminIds->first() : null;
    }

    private function isActivePurchasingAdmin(int $userId, int $departmentId, int $businessUnitId): bool
    {
        return DB::table('user_business_units')
            ->where('user_id', $userId)
            ->where('department_id', $departmentId)
            ->where('business_unit_id', $businessUnitId)
            ->where('is_purchasing_admin', true)
            ->where('is_purchasing_readonly', false)
            ->where('is_active', true)
            ->exists();
    }
};
