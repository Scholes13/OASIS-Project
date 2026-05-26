<?php

namespace App\Services\Core\Restructure;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\Activity\EmployeeTask;

/**
 * Remap historical EmployeeTask department_id (and business_unit_id) for users
 * whose primary assignment moved during a restructure.
 *
 * Context: PRD 2026-05-25 WNS Restructure, follow-up to user reassignment.
 * After moving users to new departments via DepartmentRestructureService,
 * their historical tasks still point to the OLD department_id, which makes
 * the new dept's dashboard look empty for managers like the GM.
 *
 * Scope by design:
 * - Only tasks where `created_by` matches a moved user's id are remapped.
 *   Participants are not touched.
 * - business_unit_id is also updated so cross-BU moves (e.g. WNS -> WG)
 *   stay consistent with the new department's BU.
 *
 * Idempotent: re-running on already-remapped tasks is a no-op (the WHERE
 * clause excludes rows already at the target).
 */
class TaskDepartmentRemapService
{
    /**
     * Remap tasks for a single user to the given (BU, dept).
     *
     * @return array{status: string, message: string, updated: int}
     */
    public function remapForUser(
        string $email,
        string $businessUnitCode,
        string $newDepartmentCode,
        bool $dryRun = true,
    ): array {
        $bu = BusinessUnit::where('code', $businessUnitCode)->first();
        if (! $bu) {
            return ['status' => 'bu_not_found', 'message' => "BU {$businessUnitCode} not found", 'updated' => 0];
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return ['status' => 'user_not_found', 'message' => "User {$email} not found", 'updated' => 0];
        }

        $newDept = Department::where('business_unit_id', $bu->id)
            ->where('code', $newDepartmentCode)
            ->first();
        if (! $newDept) {
            return [
                'status' => 'dept_not_found',
                'message' => "Department {$businessUnitCode}/{$newDepartmentCode} not found",
                'updated' => 0,
            ];
        }

        $query = EmployeeTask::query()
            ->where('created_by', $user->id)
            ->where(function ($q) use ($newDept, $bu) {
                $q->where('department_id', '!=', $newDept->id)
                    ->orWhere('business_unit_id', '!=', $bu->id);
            });

        $count = (clone $query)->count();

        if ($count === 0) {
            return [
                'status' => 'no_change',
                'message' => "{$email}: no tasks need remapping",
                'updated' => 0,
            ];
        }

        if ($dryRun) {
            return [
                'status' => 'would_update',
                'message' => "{$email}: would remap {$count} task(s) to {$businessUnitCode}/{$newDepartmentCode}",
                'updated' => $count,
            ];
        }

        $updated = $query->update([
            'business_unit_id' => $bu->id,
            'department_id' => $newDept->id,
            'updated_at' => now(),
        ]);

        return [
            'status' => 'updated',
            'message' => "{$email}: remapped {$updated} task(s) to {$businessUnitCode}/{$newDepartmentCode}",
            'updated' => $updated,
        ];
    }
}
