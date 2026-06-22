<?php

namespace App\Services\Core;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;

/**
 * Reusable logic to move a user from one (BU, dept, position) to another.
 *
 * Used by the WNS restructure 2026 migration command. Designed to be:
 * - Idempotent (re-running the move on an already-migrated user is a no-op).
 * - Transactional-friendly (returns a result array, does not control the transaction).
 * - Reusable (any future restructure can call moveUser()).
 *
 * It only touches `users` (primary_*) and `user_business_units`. Domain data
 * (tasks, line items, PR) follows user_id automatically and is not touched.
 */
class DepartmentRestructureService
{
    /**
     * Move a user to a new (BU, department, position) combination.
     *
     * @return array{status: string, message: string, user_id?: int}
     *               status: 'moved' | 'already_migrated' | 'user_not_found'
     *                     | 'dept_not_found' | 'position_not_found'
     */
    public function moveUser(
        string $email,
        string $businessUnitCode,
        string $newDepartmentCode,
        string $newPositionCode,
        bool $dryRun = true,
    ): array {
        $bu = BusinessUnit::where('code', $businessUnitCode)->first();
        if (! $bu) {
            return ['status' => 'bu_not_found', 'message' => "BU {$businessUnitCode} not found"];
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return ['status' => 'user_not_found', 'message' => "User {$email} not found"];
        }

        $newDept = Department::where('business_unit_id', $bu->id)
            ->where('code', $newDepartmentCode)
            ->first();
        if (! $newDept) {
            return [
                'status' => 'dept_not_found',
                'message' => "Department {$businessUnitCode}/{$newDepartmentCode} not found",
                'user_id' => $user->id,
            ];
        }

        $newPosition = Position::where('department_id', $newDept->id)
            ->where('code', $newPositionCode)
            ->first();
        if (! $newPosition) {
            return [
                'status' => 'position_not_found',
                'message' => "Position {$newPositionCode} not found in {$businessUnitCode}/{$newDepartmentCode}",
                'user_id' => $user->id,
            ];
        }

        if ($this->isAlreadyMigrated($user, $bu, $newDept, $newPosition)) {
            return [
                'status' => 'already_migrated',
                'message' => "{$email} already at {$businessUnitCode}/{$newDepartmentCode} as {$newPositionCode}",
                'user_id' => $user->id,
            ];
        }

        if ($dryRun) {
            return [
                'status' => 'would_move',
                'message' => sprintf(
                    '%s: %s -> %s/%s as %s',
                    $email,
                    $user->primaryDepartment?->code ?? '(none)',
                    $businessUnitCode,
                    $newDepartmentCode,
                    $newPositionCode,
                ),
                'user_id' => $user->id,
            ];
        }

        $this->applyMove($user, $bu, $newDept, $newPosition);

        return [
            'status' => 'moved',
            'message' => "{$email} moved to {$businessUnitCode}/{$newDepartmentCode} as {$newPositionCode}",
            'user_id' => $user->id,
        ];
    }

    /**
     * Check whether the user already has the desired primary state.
     */
    protected function isAlreadyMigrated(
        User $user,
        BusinessUnit $bu,
        Department $dept,
        Position $position,
    ): bool {
        if ($user->primary_department_id !== $dept->id) {
            return false;
        }
        if ($user->primary_position_id !== $position->id) {
            return false;
        }

        return UserBusinessUnit::query()
            ->where('user_id', $user->id)
            ->where('business_unit_id', $bu->id)
            ->where('department_id', $dept->id)
            ->where('position_id', $position->id)
            ->where('is_primary', true)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Apply the actual DB changes: update users.primary_*, deactivate old UBU
     * rows for this BU, and upsert a new primary UBU row.
     */
    protected function applyMove(
        User $user,
        BusinessUnit $bu,
        Department $dept,
        Position $position,
    ): void {
        $user->update([
            'primary_department_id' => $dept->id,
            'primary_position_id' => $position->id,
        ]);

        UserBusinessUnit::query()
            ->where('user_id', $user->id)
            ->where('business_unit_id', $bu->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'is_primary' => false,
            ]);

        UserBusinessUnit::updateOrCreate(
            [
                'user_id' => $user->id,
                'business_unit_id' => $bu->id,
                'department_id' => $dept->id,
            ],
            [
                'position_id' => $position->id,
                'is_primary' => true,
                'is_active' => true,
            ]
        );
    }
}
