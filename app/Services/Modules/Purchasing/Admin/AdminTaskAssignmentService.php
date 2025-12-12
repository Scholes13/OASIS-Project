<?php

namespace App\Services\Modules\Purchasing\Admin;

use App\Models\Core\Department;
use App\Models\Core\UserBusinessUnit;

class AdminTaskAssignmentService
{
    /**
     * Determine if a task should be auto-assigned and return the admin ID
     *
     * @return int|null Returns admin ID for auto-assignment, or null for manual claiming
     */
    public function determineAssignment(int $departmentId, int $businessUnitId): ?int
    {
        $department = Department::with('defaultPurchasingAdmin')->find($departmentId);

        if (! $department || ! $department->is_purchasing_department) {
            return null;
        }

        // Get count of purchasing admins in this department for this business unit
        $adminCount = UserBusinessUnit::where('department_id', $departmentId)
            ->where('business_unit_id', $businessUnitId)
            ->where('is_purchasing_admin', true)
            ->where('is_active', true)
            ->count();

        // If exactly one admin exists, auto-assign to default admin
        if ($adminCount === 1) {
            // Get the single admin
            $admin = UserBusinessUnit::where('department_id', $departmentId)
                ->where('business_unit_id', $businessUnitId)
                ->where('is_purchasing_admin', true)
                ->where('is_active', true)
                ->first();

            return $admin?->user_id;
        }

        // If multiple admins exist, leave unassigned for manual claiming
        return null;
    }

    /**
     * Check if a user is a purchasing admin in a department
     */
    public function isPurchasingAdmin(int $userId, int $departmentId, int $businessUnitId): bool
    {
        return UserBusinessUnit::where('user_id', $userId)
            ->where('department_id', $departmentId)
            ->where('business_unit_id', $businessUnitId)
            ->where('is_purchasing_admin', true)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get all purchasing admins for a department in a business unit
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPurchasingAdmins(int $departmentId, int $businessUnitId)
    {
        return UserBusinessUnit::with('user')
            ->where('department_id', $departmentId)
            ->where('business_unit_id', $businessUnitId)
            ->where('is_purchasing_admin', true)
            ->where('is_active', true)
            ->get()
            ->pluck('user');
    }
}
