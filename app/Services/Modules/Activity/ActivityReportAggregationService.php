<?php

namespace App\Services\Modules\Activity;

use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ActivityReportAggregationService
{
    /**
     * @return array<string, mixed>
     */
    public function buildExportSummary(Collection $tasks): array
    {
        $totalActivities = $tasks->count();
        $statusRows = collect([
            ['key' => 'completed', 'label' => 'Completed'],
            ['key' => 'in_progress', 'label' => 'In Progress'],
            ['key' => 'planned', 'label' => 'Planned'],
            ['key' => 'cancelled', 'label' => 'Cancelled'],
        ])->map(function (array $status) use ($tasks, $totalActivities): array {
            $count = $tasks->where('status', $status['key'])->count();

            return [
                'key' => $status['key'],
                'label' => $status['label'],
                'count' => $count,
                'percentage' => $this->percentage($count, $totalActivities),
            ];
        })->values()->all();

        $topCategory = $this->resolveTopCategory($tasks);

        $topSubcategory = $this->resolveTopSubcategory($tasks);

        return [
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'total_activities' => $totalActivities,
            'status_rows' => $statusRows,
            'completion_rate' => $this->percentage($tasks->where('status', 'completed')->count(), $totalActivities),
            'top_category' => [
                'name' => $topCategory['name'],
                'count' => $topCategory['count'],
                'percentage_of_report' => $this->percentage($topCategory['count'], $totalActivities),
            ],
            'top_subcategory' => [
                'name' => $topSubcategory['subcategory'],
                'count' => $topSubcategory['count'],
                'percentage_of_report' => $this->percentage($topSubcategory['count'], $totalActivities),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildCategoryBreakdown(Collection $tasks): array
    {
        $totalActivities = $tasks->count();
        $categoryGroups = $tasks->groupBy(fn (EmployeeTask $task): string => $this->categoryName($task));

        /** @var array<int, array<string, mixed>> $items */
        $items = [];

        foreach ($categoryGroups as $categoryName => $categoryTasks) {
            $categoryTotal = $categoryTasks->count();
            $subcategoryGroups = $categoryTasks->groupBy(
                fn (EmployeeTask $task): string => $this->subCategoryName($task)
            );

            foreach ($subcategoryGroups as $subcategoryName => $subcategoryTasks) {
                /** @var EmployeeTask|null $firstTask */
                $firstTask = $subcategoryTasks->first();
                $count = $subcategoryTasks->count();

                $items[] = [
                    'category' => $categoryName,
                    'subcategory' => $subcategoryName,
                    'count' => $count,
                    'percentage_of_category' => $this->percentage($count, $categoryTotal),
                    'percentage_of_report' => $this->percentage($count, $totalActivities),
                    'color' => $firstTask?->activityType?->color ?? '#94a3b8',
                ];
            }
        }

        usort($items, function (array $left, array $right): int {
            if ($left['count'] === $right['count']) {
                return [$left['category'], $left['subcategory']] <=> [$right['category'], $right['subcategory']];
            }

            return $right['count'] <=> $left['count'];
        });

        return $items;
    }

    /**
     * @return array<int, array{name: string, color: string, value: int}>
     */
    public function buildDistribution(Collection $tasks): array
    {
        return $tasks
            ->groupBy(fn (EmployeeTask $task): string => $this->categoryName($task))
            ->map(function (Collection $group, string $categoryName): array {
                /** @var EmployeeTask|null $firstTask */
                $firstTask = $group->first();

                return [
                    'name' => $categoryName,
                    'color' => $firstTask?->activityType?->color ?? '#94a3b8',
                    'value' => $group->count(),
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildFocusBreakdown(Collection $tasks): array
    {
        $totalActivities = $tasks->count();
        $breakdownItems = $this->buildCategoryBreakdown($tasks);
        $topSubcategory = $this->resolveTopSubcategory($tasks);
        $topCategory = $this->resolveTopCategory($tasks);

        return [
            'total_activities' => $totalActivities,
            'top_category' => [
                'name' => $topCategory['name'],
                'count' => $topCategory['count'],
                'percentage_of_report' => $this->percentage($topCategory['count'], $totalActivities),
            ],
            'top_subcategory' => [
                'name' => $topSubcategory['subcategory'],
                'count' => $topSubcategory['count'],
                'percentage_of_report' => $this->percentage($topSubcategory['count'], $totalActivities),
            ],
            'items' => array_slice($breakdownItems, 0, 4),
        ];
    }

    public function buildTaskSummary(EmployeeTask $task): string
    {
        $title = trim((string) ($task->task_title ?: 'Aktivitas tanpa judul'));
        $statusLabel = $this->statusLabel((string) $task->status);
        $category = $this->categoryName($task);
        $subcategory = $this->subCategoryName($task, false);

        $parts = [$title, $statusLabel];

        $categorySegment = $category;
        if ($subcategory !== null) {
            $categorySegment .= ' > '.$subcategory;
        }
        $parts[] = $categorySegment;

        if ($task->due_date) {
            $parts[] = 'Due '.$task->due_date->format('Y-m-d');
        }

        return Str::limit(implode(' | ', $parts), 120, '');
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'planned' => 'Planned',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => Str::headline($status),
        };
    }

    public function categoryName(EmployeeTask $task): string
    {
        $category = trim((string) ($task->activityType?->name ?? ''));

        return $category !== '' ? $category : 'Tanpa Kategori';
    }

    public function subCategoryName(EmployeeTask $task, bool $withFallback = true): ?string
    {
        $subCategory = trim((string) ($task->subActivity?->name ?? ''));

        if ($subCategory !== '') {
            return $subCategory;
        }

        return $withFallback ? 'Tanpa Sub Kategori' : null;
    }

    protected function percentage(int $count, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($count / $total) * 100, 1);
    }

    /**
     * @return array{name: string, count: int}
     */
    protected function resolveTopCategory(Collection $tasks): array
    {
        $categoryCounts = $tasks
            ->groupBy(fn (EmployeeTask $task): string => $this->categoryName($task))
            ->map(function (Collection $group, string $name): array {
                /** @var EmployeeTask|null $firstTask */
                $firstTask = $group->sortBy('id')->first();

                return [
                    'name' => $name,
                    'count' => $group->count(),
                    'sort_order' => (int) ($firstTask?->activityType?->sort_order ?? PHP_INT_MAX),
                ];
            })
            ->values()
            ->all();

        usort($categoryCounts, function (array $left, array $right): int {
            if ($left['count'] === $right['count']) {
                if ($left['sort_order'] === $right['sort_order']) {
                    return $left['name'] <=> $right['name'];
                }

                return $left['sort_order'] <=> $right['sort_order'];
            }

            return $right['count'] <=> $left['count'];
        });

        return $categoryCounts[0] ?? [
            'name' => 'Tanpa Kategori',
            'count' => 0,
        ];
    }

    /**
     * @return array{subcategory: string, count: int}
     */
    protected function resolveTopSubcategory(Collection $tasks): array
    {
        $subcategories = $tasks
            ->groupBy(function (EmployeeTask $task): string {
                return $this->categoryName($task).'|'.$this->subCategoryName($task);
            })
            ->map(function (Collection $group, string $key): array {
                /** @var EmployeeTask|null $firstTask */
                $firstTask = $group->sortBy('id')->first();
                [$category, $subcategory] = explode('|', $key, 2);
                $isFallbackSubcategory = $subcategory === 'Tanpa Sub Kategori';

                return [
                    'category' => $category,
                    'subcategory' => $subcategory,
                    'count' => $group->count(),
                    'category_sort_order' => (int) ($firstTask?->activityType?->sort_order ?? PHP_INT_MAX),
                    'subcategory_sort_order' => (int) ($firstTask?->subActivity?->sort_order ?? PHP_INT_MAX),
                    'is_fallback' => $isFallbackSubcategory,
                ];
            })
            ->values()
            ->all();

        usort($subcategories, function (array $left, array $right): int {
            if ($left['count'] !== $right['count']) {
                return $right['count'] <=> $left['count'];
            }

            if ($left['category_sort_order'] !== $right['category_sort_order']) {
                return $left['category_sort_order'] <=> $right['category_sort_order'];
            }

            if ($left['is_fallback'] !== $right['is_fallback']) {
                return $left['is_fallback'] <=> $right['is_fallback'];
            }

            if ($left['subcategory_sort_order'] !== $right['subcategory_sort_order']) {
                return $left['subcategory_sort_order'] <=> $right['subcategory_sort_order'];
            }

            return [$left['category'], $left['subcategory']] <=> [$right['category'], $right['subcategory']];
        });

        return $subcategories[0] ?? [
            'subcategory' => 'Tanpa Sub Kategori',
            'count' => 0,
        ];
    }
}
