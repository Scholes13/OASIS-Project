<?php

namespace App\Actions\Admin;

use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Creates a new {@see User} with their business-unit / department / position
 * assignments inside a single transaction.
 *
 * Lifted verbatim from {@see \App\Http\Controllers\Admin\UserManagementController::store()}
 * to keep the controller under the 500-line cap while preserving the exact
 * persistence behaviour, logging, and error-translation rules.
 */
class CreateUserAction
{
    /**
     * Execute the create flow.
     *
     * @param  array<string, mixed>  $validated
     * @return array{ok: true, user: User, assignments: int}|array{ok: false, error: string, status?: int}
     */
    public function execute(array $validated): array
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

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'] ?? null,
                'password' => Hash::make($validated['password']),
                'global_role' => $validated['global_role'],
                'supervisor_id' => $validated['supervisor_id'] ?? null,
                'primary_department_id' => $primaryBu['department_id'],
                'primary_position_id' => $primaryBu['position_id'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            Log::info('User created successfully', [
                'user_id' => $user->id,
                'user_name' => $user->name,
            ]);

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

            DB::commit();

            return [
                'ok' => true,
                'user' => $user,
                'assignments' => count($validated['business_units']),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('User creation failed with exception', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Translate the well-known SQLite CHECK constraint error to a
            // friendlier message; the legacy controller returned the same
            // sentence, so we preserve it byte-for-byte.
            if (str_contains($e->getMessage(), 'CHECK constraint failed: global_role')) {
                return [
                    'ok' => false,
                    'error' => 'Invalid role value. Please select either Super Admin or User from the dropdown.',
                ];
            }

            return [
                'ok' => false,
                'error' => 'Failed to create user: '.$e->getMessage(),
            ];
        }
    }
}
