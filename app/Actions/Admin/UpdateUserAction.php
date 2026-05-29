<?php

namespace App\Actions\Admin;

use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Support\Facades\Hash;

/**
 * Updates a {@see User} record and rebuilds their business-unit / department
 * / position assignments.
 *
 * Lifted verbatim from {@see \App\Http\Controllers\Admin\UserManagementController::update()}
 * so the controller stays under the 500-line cap.  Behaviour parity:
 * existing assignments are deleted and recreated rather than diffed — this
 * matches the legacy implementation exactly to avoid surprising callers
 * that rely on assignment-id renumbering.
 */
class UpdateUserAction
{
    /**
     * Execute the update flow.
     *
     * @param  array<string, mixed>  $validated
     * @return array{ok: true, assignments: int}|array{ok: false, error: string, status?: int}
     */
    public function execute(User $user, array $validated): array
    {
        $primaryIndex = $validated['primary_business_unit'];

        if (! isset($validated['business_units'][$primaryIndex])) {
            return [
                'ok' => false,
                'error' => 'Invalid primary business unit selection.',
                'status' => 422,
            ];
        }

        $primaryBu = $validated['business_units'][$primaryIndex];

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'global_role' => $validated['global_role'],
            'supervisor_id' => $validated['supervisor_id'] ?? null,
            'primary_department_id' => $primaryBu['department_id'],
            'primary_position_id' => $primaryBu['position_id'],
            'is_active' => $validated['is_active'] ?? true,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Replace assignments wholesale to mirror legacy behaviour.
        $user->businessUnits()->delete();

        foreach ($validated['business_units'] as $index => $buData) {
            UserBusinessUnit::create([
                'user_id' => $user->id,
                'business_unit_id' => $buData['business_unit_id'],
                'department_id' => $buData['department_id'],
                'position_id' => $buData['position_id'],
                'is_primary' => ($index == $primaryIndex),
                'is_active' => true,
            ]);
        }

        return [
            'ok' => true,
            'assignments' => count($validated['business_units']),
        ];
    }
}
