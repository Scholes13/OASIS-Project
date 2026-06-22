# Activity Member Focus Filter Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan filter anggota tim pada task management team scope, department activity dashboard, dan export sehingga seluruh data bisa difokuskan ke satu member tanpa mengubah aturan akses yang sudah ada.

**Architecture:** Backend menambahkan satu jalur sanitasi dan predicate member focus yang dipakai ulang oleh task index, department dashboard, dan export. Frontend memakai satu kontrak `member_user_id`, tetapi tetap mengikuti bentuk filter masing-masing surface: task management tetap string-based dan dashboard analytics tetap memakai query params yang sudah ada sambil meneruskan member focus hanya pada mode department.

**Tech Stack:** Laravel 12, PHP 8.2, Inertia.js, React 19, TypeScript, PHPUnit, Vitest, Tailwind CSS

---

## Chunk 1: Backend member-focus contract

### Task 1: Lock the task-management member filter contract with failing feature coverage

**Files:**
- Create: `tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php`
- Modify: `app/Http/Controllers/Modules/Activity/ActivityInertiaController.php`

- [ ] Step 1: Write failing PHPUnit coverage for `activity.task.index` that asserts `scope=department` + `member_user_id` filters both the task list and the summary stats when the focused member is only the creator.
- [ ] Step 2: Extend the same test file with a participant-only case, a creator-plus-participant dedup case, and a `scope=my` case that proves `member_user_id` is ignored there.
- [ ] Step 3: Add a failing assertion that the sanitized response contract returns `filters.member_user_id = ''` and `teamMembers` from active `user_business_units` assignments when the requested member is invalid.
- [ ] Step 4: Run `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php` and confirm the failures describe the missing backend contract.
- [ ] Step 5: Implement the minimal backend changes needed so the task index query, task stats, and sanitized filter props satisfy those assertions without changing the existing team-scope base visibility rule.
- [ ] Step 6: Re-run `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php` until the task-index contract is green.

### Task 2: Extract reusable member sanitization and predicate application

**Files:**
- Create: `app/Services/Modules/Activity/ActivityMemberFocusService.php`
- Modify: `app/Http/Controllers/Modules/Activity/ActivityInertiaController.php`
- Test: `tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php`

- [ ] Step 1: Add failing assertions that department member options come only from active `user_business_units` assignments in the current BU + department context and that empty assignment results safely return an empty option list.
- [ ] Step 2: Run `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php --filter=invalid` and confirm the sanitization expectations fail before implementation.
- [ ] Step 3: Create `ActivityMemberFocusService` with focused responsibilities for resolving department member options, sanitizing the requested member id, and applying the additive `created_by OR participant` predicate to a task-root query.
- [ ] Step 4: Refactor `ActivityInertiaController@index` to use the new service for both sanitization and member-focus query application while preserving the legacy `scope=department` base predicate.
- [ ] Step 5: Re-run `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php` and confirm the reusable contract is green.

### Task 3: Extend member focus to department dashboard datasets and export

**Files:**
- Modify: `app/Http/Controllers/Modules/Activity/ActivityInertiaController.php`
- Modify: `app/Services/Modules/Activity/ActivityExportService.php`
- Test: `tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php`

- [ ] Step 1: Add failing feature assertions that `activity.dashboard` changes only the department datasets when `member_user_id` is present, while personal and executive data keep their current behavior.
- [ ] Step 2: Add failing export assertions that direct export requests with `scope=department` + `member_user_id` include only the focused member’s tasks, while `scope=my` keeps its current semantics and invalid member ids fall back safely.
- [ ] Step 3: Run `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php --filter=dashboard` and `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php --filter=export` to confirm the dashboard/export contract fails before implementation.
- [ ] Step 4: Update the department dashboard builders and export service to reuse `ActivityMemberFocusService`, applying member focus only as an additive predicate on department datasets and keeping export actor-scope semantics unchanged.
- [ ] Step 5: Re-run `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php` until the full backend contract is green.
- [ ] Step 6: Run `vendor/bin/pint --dirty` and confirm the touched PHP files are formatted cleanly.

## Chunk 2: Frontend member-focus controls and export propagation

### Task 4: Add controlled member filtering to task management team scope

**Files:**
- Modify: `resources/js/inertia/types/index.ts`
- Modify: `resources/js/inertia/components/activity/FilterDropdown.tsx`
- Modify: `resources/js/inertia/Pages/Activity/Dashboard.tsx`
- Test: `tests/React/Pages/Activity/Dashboard.test.tsx`

- [ ] Step 1: Write failing React coverage that expects the `Member` select to appear only when `scope=department`, to clear when switching back to `My Tasks`, and to resync from sanitized backend filter props.
- [ ] Step 2: Add a failing assertion that changing the selected member sends `member_user_id` as a string filter without causing the page-level filter sync logic to loop.
- [ ] Step 3: Run `npm exec vitest run tests/React/Pages/Activity/Dashboard.test.tsx --runInBand` and confirm the failures describe the missing member-filter control.
- [ ] Step 4: Extend `TaskFilters` with `member_user_id`, convert `FilterDropdown` into a controlled member filter for team scope, and keep `Dashboard.tsx` as the single source of truth for sanitized filter state.
- [ ] Step 5: Re-run `npm exec vitest run tests/React/Pages/Activity/Dashboard.test.tsx --runInBand` until the task-management member filter behavior is green.

### Task 5: Wire the activity dashboard member filter and preserve active period state

**Files:**
- Modify: `resources/js/inertia/Pages/Activity/ActivityDashboard.tsx`
- Test: `tests/React/Pages/Activity/ActivityDashboard.test.tsx`

- [ ] Step 1: Write failing React coverage that expects the dashboard header `Filter` control to expose a `Member` select only in department mode, show an active indicator when a member is selected, and clear the filter when switching to personal or executive mode.
- [ ] Step 2: Add failing assertions that changing the focused member preserves the active `dept_distribution_period` query and resets department pagination/context that should restart from page one.
- [ ] Step 3: Run `npm exec vitest run tests/React/Pages/Activity/ActivityDashboard.test.tsx --runInBand` and confirm the failures describe the missing department member filter behavior.
- [ ] Step 4: Implement the minimal popover state and Inertia request changes needed to send sanitized `member_user_id` only for department-mode interactions while leaving personal and executive datasets alone.
- [ ] Step 5: Re-run `npm exec vitest run tests/React/Pages/Activity/ActivityDashboard.test.tsx --runInBand` until the dashboard member filter behavior is green.

### Task 6: Forward member focus through both export entry points

**Files:**
- Modify: `resources/js/inertia/Pages/Activity/ActivityDashboard.tsx`
- Modify: `resources/js/inertia/components/activity/ActivityDataTable.tsx`
- Test: `tests/React/Components/Activity/ActivityDataTableExport.test.tsx`
- Test: `tests/React/Pages/Activity/ActivityDashboard.test.tsx`

- [ ] Step 1: Add failing React coverage that the dashboard export button forwards sanitized `member_user_id` only in department mode.
- [ ] Step 2: Extend the existing task-management export test to expect `member_user_id` on team-scope export and to confirm `scope=my` export stays unchanged.
- [ ] Step 3: Run `npm exec vitest run tests/React/Components/Activity/ActivityDataTableExport.test.tsx tests/React/Pages/Activity/ActivityDashboard.test.tsx --runInBand` and confirm the export propagation failures are real.
- [ ] Step 4: Update both export triggers to include `member_user_id` from the sanitized local state only when the active scope supports it.
- [ ] Step 5: Re-run `npm exec vitest run tests/React/Components/Activity/ActivityDataTableExport.test.tsx tests/React/Pages/Activity/ActivityDashboard.test.tsx --runInBand` until both export paths are green.
- [ ] Step 6: Run `npm exec tsc --noEmit --pretty false` and confirm the new member filter contracts are type-safe.

## Chunk 3: Execution tracking and focused verification

### Task 7: Record implementation outcome and standards review

**Files:**
- Modify: `docs/exec_plans.md`
- Modify: `docs/plans/2026-04-13-activity-member-focus-filter.md`

- [ ] Step 1: Update the matching `docs/exec_plans.md` entry from `in_progress` to the final implementation status and append outcome notes for task management, dashboard, and export member focus behavior.
- [ ] Step 2: Review all changed files against `docs/coding_standards.json` with attention to backend/frontend ownership boundaries, stable response contracts, and preserved scope rules.
- [ ] Step 3: Note any skipped verification or residual risk directly in the execution notes before final handoff.

### Task 8: Run the focused verification set

**Files:**
- Test: `tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php`
- Test: `tests/React/Pages/Activity/Dashboard.test.tsx`
- Test: `tests/React/Pages/Activity/ActivityDashboard.test.tsx`
- Test: `tests/React/Components/Activity/ActivityDataTableExport.test.tsx`

- [ ] Step 1: Run `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php` and confirm the backend member-focus contract is green.
- [ ] Step 2: Run `vendor/bin/pint --dirty` and confirm no PHP formatting issues remain.
- [ ] Step 3: Run `npm exec vitest run tests/React/Pages/Activity/Dashboard.test.tsx tests/React/Pages/Activity/ActivityDashboard.test.tsx tests/React/Components/Activity/ActivityDataTableExport.test.tsx --runInBand` and confirm the frontend member-focus behavior is green.
- [ ] Step 4: Run `npm exec tsc --noEmit --pretty false` and confirm frontend types are green.
- [ ] Step 5: Summarize the exact commands run, their outcomes, and any remaining non-blocking risk before marking the work complete.
