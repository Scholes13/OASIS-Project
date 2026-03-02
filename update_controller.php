<?php

$file = 'app/Http/Controllers/Modules/Activity/ActivityInertiaController.php';
$content = file_get_contents($file);

// Find the return Inertia::render('Activity/Dashboard'
$search = "            return Inertia::render('Activity/Dashboard', [
                'stats' => \$stats,
                'tasks' => \$tasks,
                'activityTypes' => \$activityTypes,
                'filters' => \$filters,
                'byActivityType' => \$byActivityType,
            ]);";

$replace = "            return Inertia::render('Activity/Dashboard', [
                'stats' => \$stats,
                'tasks' => \$tasks,
                'activityTypes' => \$activityTypes,
                'filters' => \$filters,
                'byActivityType' => \$byActivityType,
                // Lazy loaded props for Create Task Modal
                'departmentUsers' => \Inertia\Inertia::lazy(fn () => \App\Models\Core\User::where('primary_department_id', \$departmentId)
                    ->where('id', '!=', \$user->id)
                    ->select(['id', 'name', 'email'])
                    ->get()),
                'backdatePermission' => \Inertia\Inertia::lazy(fn () => \$this->backdateService->checkUserPermission(\$user->id)),
                'allowedDateRange' => \Inertia\Inertia::lazy(fn () => \$this->backdateService->getAllowedDateRange(\$user)),
                'backdateEnabled' => \Inertia\Inertia::lazy(fn () => \$this->backdateService->isBackdateApprovalEnabled()),
                'prioritizedActivityTypes' => \Inertia\Inertia::lazy(fn () => \$this->formatPrioritizedActivityTypes(\$this->prioritizationService->getForUser(\$user))),
            ]);";

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Updated controller successfully.\n";
