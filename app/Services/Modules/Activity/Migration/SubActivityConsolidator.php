<?php

namespace App\Services\Modules\Activity\Migration;

use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\SubActivity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Consolidate prefixed sub activities into global master records keyed by
 * activity type name + sub activity name. Mirrors the behaviour of
 * ActivityTypeConsolidator one level deeper in the hierarchy.
 */
class SubActivityConsolidator
{
    public function __construct(protected ActivityTypeConsolidator $activityTypeConsolidator) {}

    /**
     * Consolidate prefixed sub activities into master records.
     *
     * @return array{
     *     mapping: array<int, int>,
     *     stats: array{
     *         created: int,
     *         consolidated: int,
     *         skipped: int,
     *         errors: array<int, array<string, mixed>>
     *     }
     * }
     */
    public function consolidate(): array
    {
        Log::info('Consolidating sub activities...');

        $mapping = [];
        $stats = [
            'created' => 0,
            'consolidated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $subActivities = SubActivity::with('activityType')->get();
        $grouped = $subActivities->groupBy(function ($subActivity): string {
            $activityTypeName = $subActivity->activityType->name ?? 'Unknown';

            return $activityTypeName.'::'.$subActivity->name;
        });

        foreach ($grouped as $key => $subActivityGroup) {
            try {
                [$activityTypeName, $subActivityName] = explode('::', $key, 2);

                $masterActivityType = $this->resolveMasterActivityType($activityTypeName);

                if (! $masterActivityType) {
                    $stats['errors'][] = [
                        'name' => $subActivityName,
                        'error' => "Master activity type not found for: {$activityTypeName}",
                    ];

                    continue;
                }

                $masterSubCode = $this->generateSubActivityMasterCode(
                    $masterActivityType->code,
                    $subActivityName
                );

                $existingMaster = SubActivity::where('activity_type_id', $masterActivityType->id)
                    ->where('code', $masterSubCode)
                    ->first();

                if ($existingMaster) {
                    $masterId = $existingMaster->id;
                    $stats['skipped']++;
                } else {
                    $template = $subActivityGroup->first();
                    $master = SubActivity::create([
                        'activity_type_id' => $masterActivityType->id,
                        'code' => $masterSubCode,
                        'name' => $subActivityName,
                        'is_active' => true,
                        'sort_order' => $template->sort_order,
                    ]);
                    $masterId = $master->id;
                    $stats['created']++;
                }

                foreach ($subActivityGroup as $subActivity) {
                    if ($subActivity->id !== $masterId) {
                        $mapping[$subActivity->id] = $masterId;
                        $stats['consolidated']++;
                    }
                }
            } catch (\Exception $e) {
                $stats['errors'][] = [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ];
                Log::warning('Failed to consolidate sub activity', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Sub activities consolidation complete', [
            'created' => $stats['created'],
            'consolidated' => $stats['consolidated'],
            'skipped' => $stats['skipped'],
        ]);

        return ['mapping' => $mapping, 'stats' => $stats];
    }

    /**
     * Locate the master activity type record for a given name.
     */
    protected function resolveMasterActivityType(string $activityTypeName): ?ActivityType
    {
        $masterActivityType = ActivityType::where('name', $activityTypeName)
            ->whereRaw("code NOT LIKE '%\\_%'")
            ->first();

        if ($masterActivityType) {
            return $masterActivityType;
        }

        $masterCode = $this->activityTypeConsolidator->generateMasterCode($activityTypeName);

        return ActivityType::where('code', $masterCode)->first();
    }

    /**
     * Build a master code for a sub activity.
     *
     * Format: {ACTIVITY_TYPE_CODE}_{SUB_ACTIVITY_NAME}
     * Example: "LEAVE" + "Sick" => "LEAVE_SICK"
     */
    public function generateSubActivityMasterCode(string $activityTypeCode, string $subActivityName): string
    {
        $cleanName = $subActivityName;

        if (Str::contains($cleanName, '–')) {
            $parts = explode('–', $cleanName);
            $cleanName = trim(end($parts));
        } elseif (Str::contains($cleanName, '-')) {
            $parts = explode('-', $cleanName);
            if (count($parts) > 1) {
                $cleanName = trim(end($parts));
            }
        }

        $cleanName = preg_replace('/[^a-zA-Z0-9\s]/', '', $cleanName);
        $cleanName = preg_replace('/\s+/', ' ', trim($cleanName));

        $subCode = Str::upper(Str::snake($cleanName));

        $maxTotalLength = 50;
        $maxSubCodeLength = $maxTotalLength - strlen($activityTypeCode) - 1;

        if (strlen($subCode) > $maxSubCodeLength) {
            $subCode = substr($subCode, 0, $maxSubCodeLength);
            $subCode = rtrim($subCode, '_');
        }

        return $activityTypeCode.'_'.$subCode;
    }
}
