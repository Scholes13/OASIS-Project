<?php

namespace App\Actions\Admin;

use App\Models\Core\Department;
use App\Models\Modules\Activity\ActivityType;
use Illuminate\Support\Facades\DB;

/**
 * Creates an {@see ActivityType}, applying the legacy code-generation rules
 * extracted from {@see \App\Http\Controllers\Admin\ActivityTypeController::store()}.
 *
 * Super admin: creates a global activity type with no department prefix.
 * Regular user: prepends the department code and links the activity type to
 * the chosen department through {@see DepartmentActivityType}.
 */
class CreateActivityTypeAction
{
    /**
     * @param  array{name: string, color?: string|null, department_id?: int|string|null}  $validated
     */
    public function execute(array $validated, bool $isSuperAdmin): ActivityType
    {
        $code = $this->buildCode($validated, $isSuperAdmin);
        $code = $this->ensureUniqueCode($code);

        $activityType = null;

        DB::transaction(function () use ($validated, $code, $isSuperAdmin, &$activityType): void {
            $activityType = ActivityType::create([
                'code' => $code,
                'name' => $validated['name'],
                'color' => $validated['color'] ?? 'blue',
                'is_active' => true,
            ]);

            if (! $isSuperAdmin && ! empty($validated['department_id'])) {
                DB::table('department_activity_types')->insert([
                    'department_id' => $validated['department_id'],
                    'activity_type_id' => $activityType->id,
                    'is_default' => false,
                    'sort_order' => 999,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return $activityType;
    }

    /**
     * @param  array{name: string, department_id?: int|string|null}  $validated
     */
    private function buildCode(array $validated, bool $isSuperAdmin): string
    {
        $baseCode = strtoupper(str_replace(' ', '_', $validated['name']));

        if ($isSuperAdmin) {
            return $baseCode;
        }

        $department = Department::findOrFail($validated['department_id']);

        return "{$department->code}_{$baseCode}";
    }

    private function ensureUniqueCode(string $code): string
    {
        $existingCount = ActivityType::where('code', 'like', "{$code}%")->count();

        if ($existingCount > 0) {
            return "{$code}_{$existingCount}";
        }

        return $code;
    }
}
