# Inertia.js + React Migration Plan - Activity Module

## Overview
Migrasi modul Activity dari Livewire ke React menggunakan Inertia.js sebagai bridge, dengan hosting gabung (monorepo). Modul lain (Purchasing, Dashboard) tetap menggunakan Livewire.

## Architecture: Hybrid Livewire + Inertia/React

```
┌──────────────────────────────────────────────────────────────┐
│                        Laravel Backend                        │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────────────┐    ┌─────────────────────────────┐  │
│  │   Livewire Modules  │    │   Inertia.js + React        │  │
│  │   (Existing)        │    │   (Activity Module)         │  │
│  ├─────────────────────┤    ├─────────────────────────────┤  │
│  │ • Purchasing        │    │ • ActivityDashboard.tsx     │  │
│  │ • User Dashboard    │    │ • TaskIndex.tsx             │  │
│  │ • BU Switcher       │    │ • TaskDetail.tsx            │  │
│  │ • Notifications     │    │ • TaskForm.tsx              │  │
│  │ • Sales CRM         │    │ • Analytics/*.tsx           │  │
│  └─────────────────────┘    └─────────────────────────────┘  │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

## Benefits of This Approach

1. **No API Terpisah** - Inertia handle data transfer via Laravel controller
2. **Authentication Shared** - Session-based auth tetap berfungsi
3. **Single Hosting** - Tidak perlu deploy terpisah
4. **Gradual Migration** - Modul lain tetap Livewire
5. **BU Switcher Tetap** - Livewire switcher tetap bisa trigger event ke React

---

## Phase 1: Setup Infrastructure (Day 1-2)

### 1.1 Install Dependencies

```bash
# Server-side
composer require inertiajs/inertia-laravel

# Client-side
npm install @inertiajs/react react react-dom
npm install -D @types/react @types/react-dom @vitejs/plugin-react
```

### 1.2 Configure Inertia Middleware

**File: `app/Http/Middleware/HandleInertiaRequests.php`**

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'layouts.inertia'; // Layout khusus Inertia

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->getRoleNames()->first(),
                ] : null,
            ],
            'currentBusinessUnit' => session('current_business_unit_id') ? [
                'id' => session('current_business_unit_id'),
                'code' => session('current_business_unit_code'),
                'name' => session('current_business_unit_name'),
            ] : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
```

### 1.3 Register Middleware

**File: `bootstrap/app.php`**

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
    ]);
})
```

### 1.4 Create Inertia Layout

**File: `resources/views/layouts/inertia.blade.php`**

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/inertia/app.tsx'])
    @inertiaHead
</head>
<body class="h-full font-inter antialiased">
    {{-- Include Livewire Sidebar & Header (TETAP LIVEWIRE) --}}
    <div class="h-full flex">
        {{-- Sidebar Component (Livewire) --}}
        <livewire:layout.sidebar />
        
        {{-- Main Content --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- Header Component with BU Switcher (Livewire) --}}
            <livewire:layout.header />
            
            {{-- Inertia Content Area --}}
            <main class="flex-1 overflow-y-auto bg-gray-50">
                @inertia
            </main>
        </div>
    </div>

    {{-- Toast Notifications (Livewire) --}}
    <livewire:components.toast-notification />
    
    {{-- Listen for BU Switch from Livewire --}}
    <script>
        document.addEventListener('livewire:dispatch', (event) => {
            if (event.detail.name === 'business-unit-switched') {
                // Trigger React re-fetch via Inertia
                window.dispatchEvent(new CustomEvent('bu-switched', { 
                    detail: event.detail.params 
                }));
            }
        });
    </script>
</body>
</html>
```

### 1.5 Configure Vite for React

**File: `vite.config.js`**

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/inertia/app.tsx', // React entry point
            ],
            refresh: true,
        }),
        react(),
    ],
});
```

---

## Phase 2: React Components Structure (Day 3-5)

### 2.1 Directory Structure

```
resources/js/inertia/
├── app.tsx                      # React entry point
├── types/
│   └── index.ts                 # TypeScript types
├── hooks/
│   ├── useBusinessUnit.ts       # BU context hook
│   └── useFilters.ts            # Filter state hook
├── components/
│   ├── ui/
│   │   ├── Button.tsx
│   │   ├── Card.tsx
│   │   ├── Badge.tsx
│   │   ├── Modal.tsx
│   │   └── LoadingSpinner.tsx
│   ├── activity/
│   │   ├── StatsCards.tsx
│   │   ├── TaskCard.tsx
│   │   ├── TaskTable.tsx
│   │   ├── TaskBoard.tsx
│   │   ├── FilterDropdown.tsx
│   │   └── ParticipantAvatars.tsx
│   └── shared/
│       └── PageHeader.tsx
└── Pages/
    └── Activity/
        ├── Dashboard.tsx         # Main dashboard (Overview/List/Board)
        ├── TaskDetail.tsx        # Task detail page
        ├── TaskForm.tsx          # Create/Edit task form
        ├── DepartmentTasks.tsx   # Department tasks list
        └── Analytics/
            ├── Personal.tsx
            ├── Department.tsx
            └── BusinessUnit.tsx
```

### 2.2 React Entry Point

**File: `resources/js/inertia/app.tsx`**

```tsx
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
    title: (title) => `${title} - Activity`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx')
        ),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
```

### 2.3 TypeScript Types

**File: `resources/js/inertia/types/index.ts`**

```typescript
export interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    avatar_url?: string;
}

export interface BusinessUnit {
    id: number;
    code: string;
    name: string;
}

export interface ActivityType {
    id: number;
    name: string;
    code: string;
    color: string;
}

export interface Task {
    id: number;
    task_title: string;
    task_description: string;
    status: 'planned' | 'in_progress' | 'completed' | 'cancelled';
    priority: 'low' | 'medium' | 'high';
    due_date: string;
    activity_type: ActivityType;
    creator: User;
    participants: User[];
    department: {
        id: number;
        name: string;
    };
    created_at: string;
    updated_at: string;
}

export interface TaskStats {
    total: number;
    planned: number;
    in_progress: number;
    completed: number;
    overdue: number;
}

export interface PageProps {
    auth: {
        user: User | null;
    };
    currentBusinessUnit: BusinessUnit | null;
    flash: {
        success?: string;
        error?: string;
    };
}
```

### 2.4 Main Dashboard Component

**File: `resources/js/inertia/Pages/Activity/Dashboard.tsx`**

```tsx
import { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { PageProps, Task, TaskStats, ActivityType } from '@/types';
import StatsCards from '@/components/activity/StatsCards';
import TaskTable from '@/components/activity/TaskTable';
import TaskBoard from '@/components/activity/TaskBoard';
import FilterDropdown from '@/components/activity/FilterDropdown';

interface DashboardProps extends PageProps {
    stats: TaskStats;
    tasks: {
        data: Task[];
        links: any;
        meta: any;
    };
    activityTypes: ActivityType[];
    filters: {
        search: string;
        activity_type_id: string;
        status: string;
        date_from: string;
        date_to: string;
    };
}

type ViewType = 'overview' | 'list' | 'board';

export default function Dashboard({ stats, tasks, activityTypes, filters }: DashboardProps) {
    const [view, setView] = useState<ViewType>('overview');
    const [localFilters, setLocalFilters] = useState(filters);
    const { currentBusinessUnit } = usePage<PageProps>().props;

    // Listen for BU switch from Livewire
    useEffect(() => {
        const handleBuSwitch = () => {
            router.reload({ only: ['stats', 'tasks'] });
        };

        window.addEventListener('bu-switched', handleBuSwitch);
        return () => window.removeEventListener('bu-switched', handleBuSwitch);
    }, []);

    // Debounced filter update
    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(route('activity.index'), localFilters, {
                preserveState: true,
                preserveScroll: true,
                only: ['stats', 'tasks'],
            });
        }, 300);

        return () => clearTimeout(timeout);
    }, [localFilters]);

    return (
        <>
            <Head title="Activity Tasks" />
            
            <div className="py-6 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Activity Tasks</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Track and manage your work activities
                        </p>
                    </div>
                    <a
                        href={route('activity.create')}
                        className="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                    >
                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                        </svg>
                        New Task
                    </a>
                </div>

                {/* Tabs */}
                <div className="flex items-center justify-between gap-4 mb-6 pb-3 border-b border-gray-200">
                    <div className="flex items-center gap-1">
                        {(['overview', 'list', 'board'] as ViewType[]).map((v) => (
                            <button
                                key={v}
                                onClick={() => setView(v)}
                                className={`px-3 py-2 text-sm font-medium capitalize transition-colors ${
                                    view === v
                                        ? 'text-indigo-600 border-b-2 border-indigo-600'
                                        : 'text-gray-500 hover:text-gray-700'
                                }`}
                            >
                                {v}
                            </button>
                        ))}
                    </div>

                    <FilterDropdown
                        filters={localFilters}
                        onChange={setLocalFilters}
                        activityTypes={activityTypes}
                    />
                </div>

                {/* Stats Cards (Overview only) */}
                {view === 'overview' && <StatsCards stats={stats} />}

                {/* Content */}
                {view === 'board' ? (
                    <TaskBoard tasks={tasks.data} />
                ) : (
                    <TaskTable tasks={tasks} view={view} />
                )}
            </div>
        </>
    );
}
```

---

## Phase 3: Laravel Controllers (Day 6-7)

### 3.1 Inertia Controller for Activity

**File: `app/Http/Controllers/Modules/Activity/ActivityController.php`**

```php
<?php

namespace App\Http\Controllers\Modules\Activity;

use App\Http\Controllers\Controller;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\EmployeeTask;
use App\Services\Modules\Activity\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ActivityController extends Controller
{
    public function __construct(
        protected TaskService $taskService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $buId = session('current_business_unit_id');
        $departmentId = $user->primary_department_id;

        $filters = $request->only(['search', 'activity_type_id', 'status', 'date_from', 'date_to']);
        $filters = array_merge([
            'search' => '',
            'activity_type_id' => '',
            'status' => '',
            'date_from' => now()->subMonths(3)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ], $filters);

        // Stats with caching
        $stats = Cache::remember("activity_stats_{$buId}_{$user->id}", 300, function () use ($user, $buId, $departmentId) {
            return $this->taskService->getStats($buId, $user->id, $departmentId);
        });

        // Tasks query
        $tasks = EmployeeTask::query()
            ->where('business_unit_id', $buId)
            ->where(function ($query) use ($user, $departmentId) {
                $query->where('department_id', $departmentId)
                    ->orWhereHas('participants', fn ($q) => $q->where('user_id', $user->id));
            })
            ->when($filters['activity_type_id'], fn ($q, $v) => $q->where('activity_type_id', $v))
            ->when($filters['status'], fn ($q, $v) => $q->where('status', $v))
            ->when($filters['search'], fn ($q, $v) => $q->where('task_title', 'like', "%{$v}%"))
            ->when($filters['date_from'], fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->with(['activityType', 'subActivity', 'participants', 'creator', 'department'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Activity/Dashboard', [
            'stats' => $stats,
            'tasks' => $tasks,
            'activityTypes' => ActivityType::all(),
            'filters' => $filters,
        ]);
    }

    public function show(EmployeeTask $task)
    {
        $task->load(['activityType', 'subActivity', 'participants', 'creator', 'department', 'attachments']);

        return Inertia::render('Activity/TaskDetail', [
            'task' => $task,
        ]);
    }

    public function create()
    {
        return Inertia::render('Activity/TaskForm', [
            'task' => null,
            'activityTypes' => ActivityType::with('subActivities')->get(),
        ]);
    }

    public function edit(EmployeeTask $task)
    {
        $task->load(['activityType', 'subActivity', 'participants']);

        return Inertia::render('Activity/TaskForm', [
            'task' => $task,
            'activityTypes' => ActivityType::with('subActivities')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_title' => 'required|string|max:255',
            'task_description' => 'nullable|string',
            'activity_type_id' => 'required|exists:activity_types,id',
            'sub_activity_id' => 'nullable|exists:sub_activities,id',
            'status' => 'required|in:planned,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'required|date',
            'participant_ids' => 'nullable|array',
        ]);

        $task = $this->taskService->createTask($validated);

        return redirect()->route('activity.show', $task)
            ->with('success', 'Task created successfully.');
    }

    public function update(Request $request, EmployeeTask $task)
    {
        $validated = $request->validate([
            'task_title' => 'required|string|max:255',
            'task_description' => 'nullable|string',
            'activity_type_id' => 'required|exists:activity_types,id',
            'sub_activity_id' => 'nullable|exists:sub_activities,id',
            'status' => 'required|in:planned,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'required|date',
            'participant_ids' => 'nullable|array',
        ]);

        $this->taskService->updateTask($task, $validated);

        return redirect()->route('activity.show', $task)
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(EmployeeTask $task)
    {
        $this->taskService->deleteTask($task);

        return redirect()->route('activity.index')
            ->with('success', 'Task deleted successfully.');
    }
}
```

### 3.2 Update Routes

**File: `routes/web.php` (partial update)**

```php
// Activity Module - INERTIA/REACT
Route::prefix('activity')->name('activity.')->middleware(['auth', 'verified'])->group(function () {
    // Inertia Routes (handled by ActivityController)
    Route::get('/', [App\Http\Controllers\Modules\Activity\ActivityController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Modules\Activity\ActivityController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Modules\Activity\ActivityController::class, 'store'])->name('store');
    Route::get('/{task}', [App\Http\Controllers\Modules\Activity\ActivityController::class, 'show'])->name('show');
    Route::get('/{task}/edit', [App\Http\Controllers\Modules\Activity\ActivityController::class, 'edit'])->name('edit');
    Route::put('/{task}', [App\Http\Controllers\Modules\Activity\ActivityController::class, 'update'])->name('update');
    Route::delete('/{task}', [App\Http\Controllers\Modules\Activity\ActivityController::class, 'destroy'])->name('destroy');

    // Department Tasks
    Route::get('/department', [App\Http\Controllers\Modules\Activity\ActivityController::class, 'department'])->name('department');

    // Analytics Routes
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/personal', [App\Http\Controllers\Modules\Activity\AnalyticsController::class, 'personal'])->name('personal');
        Route::get('/department', [App\Http\Controllers\Modules\Activity\AnalyticsController::class, 'department'])->middleware('can:view-department-analytics')->name('department');
        Route::get('/business-unit', [App\Http\Controllers\Modules\Activity\AnalyticsController::class, 'businessUnit'])->middleware('can:view-reports')->name('business-unit');
    });
});
```

---

## Phase 4: Bridge Livewire ↔ React (Day 8)

### 4.1 Business Unit Switch Integration

BU Switcher tetap Livewire, tapi harus trigger reload ke React:

**Update: `app/Livewire/Components/BusinessUnitSwitcher.php`**

```php
// Existing method - add extra dispatch for Inertia
public function switchBusinessUnit(int $businessUnitId): void
{
    // ... existing code ...

    // Dispatch for Livewire components
    $this->dispatch('business-unit-switched', businessUnitId: $businessUnitId);
    
    // Dispatch browser event for Inertia/React (NEW)
    $this->dispatch('bu-switched-inertia', businessUnitId: $businessUnitId);
}
```

**Update: `resources/views/layouts/inertia.blade.php`**

```blade
<script>
    // Bridge Livewire events to React
    document.addEventListener('livewire:dispatch', (event) => {
        if (event.detail.name === 'bu-switched-inertia') {
            // Trigger Inertia page reload
            const { router } = await import('@inertiajs/react');
            router.reload({ only: ['stats', 'tasks', 'currentBusinessUnit'] });
        }
    });
</script>
```

---

## Phase 5: UI Components Library (Day 9-10)

### 5.1 Recommended Libraries

```bash
# UI Components
npm install @headlessui/react      # Dropdowns, Modals, Transitions
npm install @heroicons/react       # Icons (same as used in Blade)

# Animations (replaces Alpine x-transition)
npm install framer-motion

# Form handling
npm install react-hook-form @hookform/resolvers zod

# Date handling
npm install date-fns

# Charts (if needed for analytics)
npm install recharts
```

### 5.2 Component Mapping

| Livewire/Alpine | React Equivalent |
|---|---|
| `wire:model.live` | `useState` + `useEffect` debounce |
| `wire:click` | `onClick` handler |
| `wire:loading` | Loading state dengan `useState` |
| `x-transition` | Framer Motion `<AnimatePresence>` |
| `x-show` | Conditional rendering `{show && ...}` |
| `@click.away` | Headless UI `<Popover>` atau custom hook |
| `wire:poll` | `useEffect` dengan `setInterval` |
| Alpine `$dispatch` | Custom events / React Context |

---

## Phase 6: Testing & Migration (Day 11-14)

### 6.1 Testing Strategy

```bash
# Feature tests untuk Inertia
php artisan make:test --phpunit Activity/ActivityControllerTest

# React component tests
npm install -D @testing-library/react @testing-library/jest-dom vitest
```

### 6.2 Migration Checklist

- [ ] Install Inertia + React dependencies
- [ ] Configure Vite for React
- [ ] Create Inertia middleware
- [ ] Create hybrid layout (Livewire sidebar + Inertia content)
- [ ] Create ActivityController (Inertia)
- [ ] Migrate Dashboard.tsx
- [ ] Migrate TaskDetail.tsx
- [ ] Migrate TaskForm.tsx
- [ ] Migrate DepartmentTasks.tsx
- [ ] Migrate Analytics pages
- [ ] Test BU Switcher integration
- [ ] Test all CRUD operations
- [ ] Remove old Livewire components (after testing)

---

## Estimated Timeline

| Phase | Duration | Tasks |
|---|---|---|
| Phase 1: Setup | 2 days | Dependencies, config, layout |
| Phase 2: Components | 3 days | React components structure |
| Phase 3: Controllers | 2 days | Inertia controllers |
| Phase 4: Bridge | 1 day | Livewire ↔ React events |
| Phase 5: UI Library | 2 days | Components, animations |
| Phase 6: Testing | 4 days | Testing, bug fixes, cleanup |
| **Total** | **14 days** | |

---

## Risks & Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| BU Switcher tidak sync | High | Test extensively, use browser events |
| Session auth issues | High | Use Inertia middleware untuk share auth |
| Performance regression | Medium | Keep caching strategy dari Livewire |
| Learning curve React | Medium | Use simple patterns, avoid complex state mgmt |

---

## Files to Keep (Livewire)

Komponen ini TETAP Livewire karena dipakai di seluruh aplikasi:

1. `app/Livewire/Layout/Sidebar.php`
2. `app/Livewire/Layout/Header.php`
3. `app/Livewire/Components/BusinessUnitSwitcher.php`
4. `app/Livewire/Components/ToastNotification.php`
5. `app/Livewire/Dashboard/UserDashboard.php`
6. Semua komponen di `app/Livewire/Modules/Purchasing/`
7. Semua komponen di `app/Livewire/Modules/SalesCrm/`

---

## Next Steps

1. **Approve plan** - Konfirmasi timeline dan scope
2. **Setup dependencies** - Install Inertia + React
3. **Create proof-of-concept** - Buat 1 halaman dulu (Dashboard)
4. **Iterate** - Migrate komponen satu per satu
5. **Test thoroughly** - Pastikan semua fitur berfungsi
6. **Cleanup** - Hapus Livewire components yang sudah dimigrate
