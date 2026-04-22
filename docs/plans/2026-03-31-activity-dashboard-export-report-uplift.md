# Activity Dashboard Export Report Uplift Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Meningkatkan report activity dashboard dan workbook export agar hours/time-management tetap terjaga, sementara breakdown category/subcategory, count, percent, description, summary, dan raw pivotable ikut tersedia.

**Architecture:** Backend menambahkan satu jalur agregasi yang dipakai bersama oleh workbook export dan dashboard focus summary agar angka tidak drift. Frontend mempertahankan KPI hours yang ada lalu meng-upgrade panel focus menjadi breakdown report yang lebih kaya, dengan kontrak personal dan department yang konsisten terhadap scope export aktif.

**Tech Stack:** Laravel 12, PHP 8.2, Inertia.js, React 19, TypeScript, PhpSpreadsheet, PHPUnit, Vitest

---

## Chunk 1: Backend export workbook and shared aggregation

### Task 1: Lock workbook contract with failing feature coverage

**Files:**
- Create: `tests/Feature/Modules/Activity/ActivityExportReportTest.php`
- Modify: `app/Http/Controllers/Modules/Activity/ActivityInertiaController.php`
- Modify: `app/Services/Modules/Activity/ActivityExportService.php`

- [ ] Step 1: Write failing PHPUnit coverage for `scope=my` export that asserts the workbook now contains sheets `Detail`, `Ringkasan`, `Breakdown Kategori`, and `Data Mentah`.
- [ ] Step 2: Extend the same test file with assertions for Indonesian detail/raw headers, the approved `Ringkasan Aktivitas` template, fallback cases, 120-character truncation, zero-row export behavior, raw-sheet pivotability rules (one header row, one activity per row, no subtotal-style inserted rows), and current export authorization scope.
- [ ] Step 3: Run `php artisan test tests/Feature/Modules/Activity/ActivityExportReportTest.php` and confirm the failures describe the missing workbook contract.
- [ ] Step 4: Implement the minimal export-controller/service changes needed to satisfy the sheet names, header contract, empty-export behavior, and existing `my` versus `department` scope rules.
- [ ] Step 5: Re-run `php artisan test tests/Feature/Modules/Activity/ActivityExportReportTest.php` until green.

### Task 2: Extract shared aggregation and summary generation

**Files:**
- Create: `app/Services/Modules/Activity/ActivityReportAggregationService.php`
- Modify: `app/Services/Modules/Activity/ActivityExportService.php`
- Test: `tests/Feature/Modules/Activity/ActivityExportReportTest.php`

- [ ] Step 1: Add failing assertions that `Ringkasan` includes generated timestamp, total activities, status count + percent rows, top category, top subcategory, and completion rate, and that `Breakdown Kategori` uses the approved denominators for status percent, `% of Category`, and `% of Report`.
- [ ] Step 2: Run `php artisan test tests/Feature/Modules/Activity/ActivityExportReportTest.php --filter=percentage` and confirm the aggregation expectations fail before implementation.
- [ ] Step 3: Create `ActivityReportAggregationService` with focused responsibilities for status totals, category/subcategory totals, percent calculations, and deterministic summary-text generation.
- [ ] Step 4: Refactor `ActivityExportService` to build `Detail`, `Ringkasan`, `Breakdown Kategori`, and `Data Mentah` from the shared aggregation service without adding XLSX chart requirements.
- [ ] Step 5: Re-run `php artisan test tests/Feature/Modules/Activity/ActivityExportReportTest.php` and confirm the full workbook contract is green.

### Task 3: Backend standards pass for export changes

**Files:**
- Modify: `app/Services/Modules/Activity/ActivityExportService.php`
- Modify: `app/Services/Modules/Activity/ActivityReportAggregationService.php`
- Test: `tests/Feature/Modules/Activity/ActivityExportReportTest.php`

- [ ] Step 1: Self-review the export service so workbook generation, null fallbacks, and summary text stay inside backend ownership boundaries from the approved spec.
- [ ] Step 2: Add or tighten failing backend assertions for missing `task_description`, missing `subActivity`, missing `duration_minutes`, and a pre-stream workbook-generation failure path that returns a generic error surface instead of partial output.
- [ ] Step 3: Implement the minimal null fallback and pre-stream failure handling needed to satisfy those assertions without moving export work into the controller.
- [ ] Step 4: Run `php artisan test tests/Feature/Modules/Activity/ActivityExportReportTest.php` and confirm the fallback and failure-path assertions are green.
- [ ] Step 5: Run `vendor/bin/pint --dirty` and confirm the touched PHP files are formatted cleanly.
- [ ] Step 6: Re-run `php artisan test tests/Feature/Modules/Activity/ActivityExportReportTest.php` after formatting to confirm no regression.

## Chunk 2: Dashboard report contract and Hybrid A rendering

### Task 4: Lock the dashboard report contract for personal and department modes

**Files:**
- Create: `tests/Feature/Modules/Activity/ActivityDashboardReportContractTest.php`
- Modify: `app/Http/Controllers/Modules/Activity/ActivityInertiaController.php`
- Modify: `app/Services/Modules/Activity/ActivityReportAggregationService.php`

- [ ] Step 1: Write failing feature coverage that hits `activity.dashboard` in personal and department modes and asserts the focus payload includes top category, top subcategory, count, and `% of Report` using the same active-scope denominator as export.
- [ ] Step 2: Add assertions that hours/time-management props remain available so the frontend can keep `My Total Hours` and `Team Total Hours` intact.
- [ ] Step 3: Run `php artisan test tests/Feature/Modules/Activity/ActivityDashboardReportContractTest.php` and confirm the current response contract is missing the approved report detail.
- [ ] Step 4: Extend the dashboard backend contract to reuse `ActivityReportAggregationService` for personal and department focus data without changing current access boundaries.
- [ ] Step 5: Re-run `php artisan test tests/Feature/Modules/Activity/ActivityDashboardReportContractTest.php` until green.

### Task 5: Render Hybrid A focus detail without breaking hours-based scan flow

**Files:**
- Create: `resources/js/inertia/components/activity/dashboard/FocusBreakdownPanel.tsx`
- Modify: `resources/js/inertia/components/activity/dashboard/index.ts`
- Modify: `resources/js/inertia/Pages/Activity/ActivityDashboard.tsx`
- Test: `tests/React/Pages/Activity/ActivityDashboard.test.tsx`

- [ ] Step 1: Write failing React coverage that keeps `My Total Hours` / `Team Total Hours` visible while expecting the new focus panel to show category, subcategory, count, `% of Report`, and empty-state behavior in both personal and department views.
- [ ] Step 2: Run `npm exec vitest run tests/React/Pages/Activity/ActivityDashboard.test.tsx --runInBand` and confirm the failures describe the missing Hybrid A rendering.
- [ ] Step 3: Extract the focus panel into `FocusBreakdownPanel.tsx` so the large dashboard page keeps one responsibility for layout while the new panel owns focus-specific rendering.
- [ ] Step 4: Update `ActivityDashboard.tsx` to feed the component from the approved contract, preserve the existing export button flow, and keep the top KPI cards anchored around hours/time-management.
- [ ] Step 5: Re-run `npm exec vitest run tests/React/Pages/Activity/ActivityDashboard.test.tsx --runInBand` and confirm the updated focus panel behavior passes.

### Task 6: Frontend type-safety and visual self-review

**Files:**
- Modify: `resources/js/inertia/components/activity/dashboard/FocusBreakdownPanel.tsx`
- Modify: `resources/js/inertia/Pages/Activity/ActivityDashboard.tsx`
- Test: `tests/React/Pages/Activity/ActivityDashboard.test.tsx`

- [ ] Step 1: Check that the focus panel labels stay mode-aware (`My Focus` vs `Department Focus`) without relabeling unrelated dashboard UI.
- [ ] Step 2: Check that hours, active projects, and efficiency cards keep their existing scan order and are not replaced by report-only metrics.
- [ ] Step 3: Run `npm exec tsc --noEmit --pretty false` and confirm the new dashboard contract and component types are sound.
- [ ] Step 4: Re-run `npm exec vitest run tests/React/Pages/Activity/ActivityDashboard.test.tsx --runInBand` after the type pass to confirm no frontend regression.

## Chunk 3: Verification, execution tracking, and handoff

### Task 7: Record implementation outcome and standards review

**Files:**
- Modify: `docs/exec_plans.md`
- Modify: `docs/plans/2026-03-31-activity-dashboard-export-report-uplift.md`

- [ ] Step 1: Update the matching `docs/exec_plans.md` entry from `planned` to the correct implementation status and append outcome notes for dashboard focus detail, workbook sheets, and raw pivotability.
- [ ] Step 2: Review all changed files against `docs/coding_standards.json` with attention to backend/frontend ownership boundaries and stable response contracts.
- [ ] Step 3: Note any skipped verification or residual risk directly in the execution notes before final handoff.

### Task 8: Run the full focused verification set

**Files:**
- Test: `tests/Feature/Modules/Activity/ActivityExportReportTest.php`
- Test: `tests/Feature/Modules/Activity/ActivityDashboardReportContractTest.php`
- Test: `tests/React/Pages/Activity/ActivityDashboard.test.tsx`

- [ ] Step 1: Run `php artisan test tests/Feature/Modules/Activity/ActivityExportReportTest.php tests/Feature/Modules/Activity/ActivityDashboardReportContractTest.php` and confirm focused backend coverage is green.
- [ ] Step 2: Run `vendor/bin/pint --dirty` and confirm no PHP formatting issues remain.
- [ ] Step 3: Run `npm exec vitest run tests/React/Pages/Activity/ActivityDashboard.test.tsx --runInBand` and confirm the dashboard behavior is green.
- [ ] Step 4: Run `npm exec tsc --noEmit --pretty false` and confirm frontend types are green.
- [ ] Step 5: Summarize the exact commands run, their outcomes, and any remaining non-blocking risk before marking the work complete.
