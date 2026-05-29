<?php

namespace App\Actions\Admin;

use App\Models\Core\Department;
use Illuminate\Http\Request;

/**
 * Persists a {@see Department} update along with its position upserts and
 * sub-activity sync.
 *
 * Extracted from {@see \App\Http\Controllers\Admin\DepartmentController::update()}
 * verbatim — every field name, soft-cast, and pivot ordering rule is
 * preserved so the existing API contract (no payload or route changes) stays
 * intact.
 */
class UpdateDepartmentAction
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(Request $request, Department $department, array $validated): void
    {
        $payload = $this->buildDepartmentPayload($request, $validated);
        $department->update($payload);

        $this->syncSubActivities($department, (array) $request->input('sub_activity_ids', []));

        if ($request->has('positions')) {
            $this->upsertPositions($department, (array) $request->input('positions', []));
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildDepartmentPayload(Request $request, array $validated): array
    {
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_purchasing_department'] = $request->boolean('is_purchasing_enabled', false);
        $validated['default_purchasing_admin_id'] = $request->purchasing_admin_id;

        // Remove fields that don't exist in departments table
        unset(
            $validated['is_purchasing_enabled'],
            $validated['purchasing_admin_id'],
            $validated['positions'],
            $validated['sub_activity_ids'],
        );

        return $validated;
    }

    /**
     * @param  array<int, int|string>  $subActivityIds
     */
    private function syncSubActivities(Department $department, array $subActivityIds): void
    {
        $syncData = [];

        foreach ($subActivityIds as $index => $subId) {
            $syncData[$subId] = ['sort_order' => $index];
        }

        $department->subActivities()->sync($syncData);
    }

    /**
     * @param  array<int, array<string, mixed>>  $positions
     */
    private function upsertPositions(Department $department, array $positions): void
    {
        foreach ($positions as $positionData) {
            // Skip if marked for deletion
            if (! empty($positionData['_destroy'])) {
                if (! empty($positionData['id'])) {
                    $department->positions()->where('id', $positionData['id'])->delete();
                }

                continue;
            }

            // Skip if empty
            if (empty($positionData['name']) || empty($positionData['code'])) {
                continue;
            }

            if (! empty($positionData['id'])) {
                // Update existing position
                $position = $department->positions()->find($positionData['id']);
                if ($position) {
                    $position->update([
                        'name' => $positionData['name'],
                        'code' => $positionData['code'],
                        'access_level' => $positionData['access_level'] ?? 'staff',
                    ]);
                }
            } else {
                // Create new position
                $department->positions()->create([
                    'name' => $positionData['name'],
                    'code' => $positionData['code'],
                    'access_level' => $positionData['access_level'] ?? 'staff',
                ]);
            }
        }
    }
}
