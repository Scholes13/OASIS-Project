<?php

namespace App\Services\Modules\Purchasing\Admin;

use App\Models\Modules\Purchasing\Admin\AdminTask;
use Illuminate\Support\Facades\DB;

class PriceEfficiencyService
{
    /**
     * Calculate savings amount and percentage
     *
     * @return array{savings_amount: float, savings_percentage: float}
     */
    public function calculateSavings(float $estimatedPrice, float $realizedPrice): array
    {
        $savingsAmount = $estimatedPrice - $realizedPrice;

        // Prevent division by zero
        $savingsPercentage = $estimatedPrice > 0
            ? (($estimatedPrice - $realizedPrice) / $estimatedPrice) * 100
            : 0;

        return [
            'savings_amount' => round($savingsAmount, 2),
            'savings_percentage' => round($savingsPercentage, 2),
        ];
    }

    /**
     * Get total savings for an admin
     */
    public function getAdminTotalSavings(int $adminId, ?int $businessUnitId = null): float
    {
        $query = AdminTask::where('assigned_admin_id', $adminId)
            ->where('status', 'done');

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        return $query->sum('savings_amount') ?? 0;
    }

    /**
     * Get average savings percentage for an admin
     */
    public function getAdminAverageSavingsPercentage(int $adminId, ?int $businessUnitId = null): float
    {
        $query = AdminTask::where('assigned_admin_id', $adminId)
            ->where('status', 'done');

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        return $query->avg('savings_percentage') ?? 0;
    }

    /**
     * Get total savings for a department
     */
    public function getDepartmentTotalSavings(int $departmentId, ?int $businessUnitId = null): float
    {
        $query = AdminTask::where('department_id', $departmentId)
            ->where('status', 'done');

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        return $query->sum('savings_amount') ?? 0;
    }

    /**
     * Get average savings percentage for a department
     */
    public function getDepartmentAverageSavingsPercentage(int $departmentId, ?int $businessUnitId = null): float
    {
        $query = AdminTask::where('department_id', $departmentId)
            ->where('status', 'done');

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        return $query->avg('savings_percentage') ?? 0;
    }

    /**
     * Get total savings for a business unit
     */
    public function getBusinessUnitTotalSavings(int $businessUnitId): float
    {
        return AdminTask::where('business_unit_id', $businessUnitId)
            ->where('status', 'done')
            ->sum('savings_amount') ?? 0;
    }

    /**
     * Get average savings percentage for a business unit
     */
    public function getBusinessUnitAverageSavingsPercentage(int $businessUnitId): float
    {
        return AdminTask::where('business_unit_id', $businessUnitId)
            ->where('status', 'done')
            ->avg('savings_percentage') ?? 0;
    }

    /**
     * Get savings breakdown by category (PR vs ST)
     */
    public function getSavingsBreakdownByType(int $departmentId, ?int $businessUnitId = null): array
    {
        $query = AdminTask::select('taskable_type', DB::raw('SUM(savings_amount) as total_savings'))
            ->where('department_id', $departmentId)
            ->where('status', 'done')
            ->groupBy('taskable_type');

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        $results = $query->get();

        $breakdown = [
            'purchase_request' => 0,
            'stock_request' => 0,
        ];

        foreach ($results as $result) {
            if (str_contains($result->taskable_type, 'PurchaseRequest')) {
                $breakdown['purchase_request'] = $result->total_savings;
            } elseif (str_contains($result->taskable_type, 'StockRequest')) {
                $breakdown['stock_request'] = $result->total_savings;
            }
        }

        return $breakdown;
    }

    /**
     * Get average follow-up time for an admin
     *
     * @return float Average in minutes
     */
    public function getAdminAverageFollowupTime(int $adminId, ?int $businessUnitId = null): float
    {
        $query = AdminTask::where('assigned_admin_id', $adminId)
            ->where('status', 'done')
            ->whereNotNull('followup_time_minutes');

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        return $query->avg('followup_time_minutes') ?? 0;
    }

    /**
     * Get average completion time for an admin
     *
     * @return float Average in minutes
     */
    public function getAdminAverageCompletionTime(int $adminId, ?int $businessUnitId = null): float
    {
        $query = AdminTask::where('assigned_admin_id', $adminId)
            ->where('status', 'done')
            ->whereNotNull('completion_time_minutes');

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        return $query->avg('completion_time_minutes') ?? 0;
    }

    /**
     * Get average follow-up time for a department
     *
     * @return float Average in minutes
     */
    public function getDepartmentAverageFollowupTime(int $departmentId, ?int $businessUnitId = null): float
    {
        $query = AdminTask::where('department_id', $departmentId)
            ->where('status', 'done')
            ->whereNotNull('followup_time_minutes');

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        return $query->avg('followup_time_minutes') ?? 0;
    }

    /**
     * Get average completion time for a department
     *
     * @return float Average in minutes
     */
    public function getDepartmentAverageCompletionTime(int $departmentId, ?int $businessUnitId = null): float
    {
        $query = AdminTask::where('department_id', $departmentId)
            ->where('status', 'done')
            ->whereNotNull('completion_time_minutes');

        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        return $query->avg('completion_time_minutes') ?? 0;
    }

    /**
     * Get average follow-up time for a business unit
     *
     * @return float Average in minutes
     */
    public function getBusinessUnitAverageFollowupTime(int $businessUnitId): float
    {
        return AdminTask::where('business_unit_id', $businessUnitId)
            ->where('status', 'done')
            ->whereNotNull('followup_time_minutes')
            ->avg('followup_time_minutes') ?? 0;
    }

    /**
     * Get average completion time for a business unit
     *
     * @return float Average in minutes
     */
    public function getBusinessUnitAverageCompletionTime(int $businessUnitId): float
    {
        return AdminTask::where('business_unit_id', $businessUnitId)
            ->where('status', 'done')
            ->whereNotNull('completion_time_minutes')
            ->avg('completion_time_minutes') ?? 0;
    }
}
