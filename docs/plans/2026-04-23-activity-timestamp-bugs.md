# Activity Task Timestamp Bug Fixes

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix 5 timestamp-related bugs in the Activity module where task dates and execution times are recorded incorrectly.

**Architecture:** Targeted fixes across frontend (timezone), controller (timestamp logic), model (new field), and config.

**Tech Stack:** Laravel 12, Carbon, Inertia.js, React, TypeScript.

---

## Bug Summary

| # | Bug | Severity | File |
|---|-----|----------|------|
| 1 | Task date default uses browser timezone, not server | High | `TaskFormModal.tsx:12-18` |
| 2 | Quick status stamps `now()` for non-today tasks | High | `ActivityInertiaController.php:397-405` |
| 3 | Edit task shifts `started_at` to new task_date | Medium | `ActivityInertiaController.php:450-457` |
| 4 | No `edited_at` field to track task edits | Medium | `EmployeeTask` model + migration |
| 5 | Timezone hardcoded in config/app.php | Low | `config/app.php:68` |

---

## Task 1: Fix frontend timezone — use server timezone for default date

**Problem:** `TaskFormModal.tsx` uses `new Date()` from browser to compute "today". If user's browser is in a different timezone than `Asia/Jakarta`, the default task date can be wrong (off by one day around midnight).

**Files:**
- Modify: `resources/js/inertia/components/activity/TaskFormModal.tsx:12-18`

**Fix:** Replace browser-local date with server-provided date. The server already sends the current date in shared Inertia props or we can compute it using the app timezone.

**Option A (preferred):** Pass server date from backend via Inertia shared props:
```php
// In HandleInertiaRequests middleware or AppServiceProvider
Inertia::share('serverDate', now()->format('Y-m-d'));
```

Then in frontend:
```typescript
// Replace getTodayLocalDate() with server date
const { serverDate } = usePage().props;
const todayLocal = serverDate as string || getTodayLocalDate(); // fallback
```

**Option B (simpler):** Force the frontend to use Asia/Jakarta timezone:
```typescript
function getTodayLocalDate(): string {
    const now = new Date();
    // Force Asia/Jakarta timezone (UTC+7)
    const jakartaDate = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Jakarta' }));
    const y = jakartaDate.getFullYear();
    const m = String(jakartaDate.getMonth() + 1).padStart(2, '0');
    const d = String(jakartaDate.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}
```

**Recommendation:** Option A is more robust (server is source of truth). Option B is simpler but hardcodes timezone in frontend.

**Commit:** `fix: use server timezone for default task date in activity form`

---

## Task 2: Fix quick status timestamp — use task_date instead of now()

**Problem:** When user clicks quick status "In Progress" or "Done" on a task that's not today, `started_at`/`completed_at` is set to `now()` (current server time). This makes execution timestamps wrong for historical/future tasks.

**Files:**
- Modify: `app/Http/Controllers/Modules/Activity/ActivityInertiaController.php:383-417`

**Current code (around line 397-405):**
```php
if ($task->status === 'in_progress' && !$task->started_at) {
    $task->started_at = now();
}
if ($task->status === 'completed') {
    $task->completed_at = now();
    // duration calculation uses now()
}
```

**Fix:** For quick status updates, use the task_date as the base date instead of now():
```php
if ($task->status === 'in_progress' && !$task->started_at) {
    // Use task_date with current time if today, or task_date start-of-day if historical
    $task->started_at = $task->task_date->isToday()
        ? now()
        : $task->task_date->startOfDay()->addHours(8); // default 08:00 for historical
}
if ($task->status === 'completed') {
    $task->completed_at = $task->task_date->isToday()
        ? now()
        : $task->task_date->endOfDay()->subHours(7); // default 17:00 for historical
    // Recalculate duration
    if ($task->started_at) {
        $task->duration_minutes = $task->started_at->diffInMinutes($task->completed_at);
    }
}
```

**Note:** The quick-status validation in `UpdateActivityTaskRequest.php:200-220` already blocks historical quick-status without confirmation. But when it IS allowed (e.g., admin override or same-day), the timestamp should still be sensible.

**Alternative:** If the validation already forces users to provide actual times for non-today tasks, then the `now()` fallback only fires for today's tasks — which is correct. Verify this path before changing.

**Investigation needed:** Read `UpdateActivityTaskRequest.php:200-220` to confirm whether quick-status on non-today tasks is actually blocked or just warned. If blocked, this bug may only affect edge cases.

**Commit:** `fix: use task_date-based timestamps for quick status updates`

---

## Task 3: Fix edit task — don't shift started_at when task_date changes

**Problem:** When editing a task and changing `task_date`, the controller re-bases `started_at` to the new date. This can shift execution timestamps unexpectedly.

**Files:**
- Modify: `app/Http/Controllers/Modules/Activity/ActivityInertiaController.php:450-457`

**Current behavior:**
```php
// If task_date changed and task has started_at, shift started_at to new date
if ($task->started_at) {
    $task->started_at = Carbon::parse($submittedTaskDate->format('Y-m-d') . ' ' . $task->started_at->format('H:i:s'));
}
```

**Fix:** Only shift `started_at` if the user explicitly provides a new start_time. If they only change the task_date without touching start_time, preserve the original `started_at`:

```php
// Only update started_at if user explicitly provided start_time
if ($validated['start_time'] ?? null) {
    $task->started_at = Carbon::parse($submittedTaskDate->format('Y-m-d') . ' ' . $validated['start_time']);
} 
// If task_date changed but no explicit start_time, keep original started_at
// (don't auto-shift to new date)
```

**Commit:** `fix: preserve started_at when editing task_date without explicit start_time`

---

## Task 4: Add `edited_at` and `edited_by` fields to employee_tasks

**Problem:** There's no way to track when a task was last edited and by whom. Only `updated_at` exists, which changes on any model save (including status changes, not just user edits).

**Files:**
- Create: `database/migrations/modules/activity/2026_04_23_200000_add_edited_at_to_employee_tasks.php`
- Modify: `app/Models/Modules/Activity/EmployeeTask.php`
- Modify: `app/Http/Controllers/Modules/Activity/ActivityInertiaController.php` (update method)

**Migration:**
```php
Schema::table('employee_tasks', function (Blueprint $table) {
    $table->timestamp('edited_at')->nullable()->after('completed_at');
    $table->foreignId('edited_by')->nullable()->after('edited_at')
        ->constrained('users')->nullOnDelete();
});
```

**Model update:**
```php
// Add to $fillable
'edited_at',
'edited_by',

// Add to $casts
'edited_at' => 'datetime',
```

**Controller update:** In the full update method (not quick-status), set:
```php
$task->edited_at = now();
$task->edited_by = auth()->id();
```

**Commit:** `feat: add edited_at and edited_by tracking to activity tasks`

---

## Task 5: Use env() for timezone in config/app.php

**Problem:** Timezone is hardcoded as `'Asia/Jakarta'` in `config/app.php:68`, ignoring any `APP_TIMEZONE` env variable.

**Files:**
- Modify: `config/app.php:68`

**Current:**
```php
'timezone' => 'Asia/Jakarta',
```

**Fix:**
```php
'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
```

**Also verify .env has:**
```
APP_TIMEZONE=Asia/Jakarta
```

**Commit:** `fix: use env() for timezone config with Asia/Jakarta default`

---

## Implementation Order

1. **Task 5** (timezone config) — smallest, no risk, do first
2. **Task 1** (frontend timezone) — fixes the most visible user-facing bug
3. **Task 2** (quick status timestamps) — fixes the reported bug
4. **Task 4** (edited_at field) — new feature, migration needed
5. **Task 3** (edit shift fix) — needs careful testing, do last

## Testing Notes

- After Task 1: Verify default task date matches server date, not browser date
- After Task 2: Create a task with past date, quick-status to "Done", verify completed_at uses task_date not now()
- After Task 3: Edit a task's task_date, verify started_at is NOT shifted unless start_time explicitly changed
- After Task 4: Edit a task, verify edited_at and edited_by are populated
- After Task 5: Verify `config('app.timezone')` returns `Asia/Jakarta`
