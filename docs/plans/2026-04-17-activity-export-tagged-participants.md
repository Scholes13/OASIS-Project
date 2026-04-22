# Activity Export Tagged Participants Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make Activity Excel exports represent tagged participants explicitly and align personal export scope with existing Activity screen semantics.

**Architecture:** Keep the fix inside the backend export pipeline. Reuse the existing creator-or-participant semantics for personal export filtering, eager load participants into the export dataset, and extend existing task-level sheets with additive participant metadata while preserving the existing workbook layout and sheet names.

**Tech Stack:** Laravel 12, PHP 8.2, PhpSpreadsheet, PHPUnit 11, Pint

---

## File Map

- Modify: `app/Services/Modules/Activity/ActivityExportService.php`
  Responsibility: unify export scope semantics, eager load participant data, and extend existing workbook sheets with additive participant metadata.
- Modify if needed: `app/Services/Modules/Activity/ActivityReportAggregationService.php`
  Responsibility: optional helper extraction for stable participant formatting if the export service starts getting too dense.
- Modify: `tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php`
  Responsibility: cover export scope drift and participant visibility in workbook.
- Create if needed: `tests/Feature/Modules/Activity/ActivityExportWorkbookTest.php`
  Responsibility: isolate workbook-structure assertions if the existing member-focus test grows too broad.

## Chunk 1: Lock Export Scope Contract

### Task 1: Add failing coverage for `scope=my` creator-or-participant export semantics

**Files:**
- Modify: `tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php`

- [ ] **Step 1: Write the failing test**

Add a feature test proving personal export includes:
- a task where the current user is only `created_by`,
- a task where the current user is only a participant,
- only one exported row when both conditions are true on the same task.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php --filter=export`
Expected: FAIL because `ActivityExportService::getFilteredTasks()` still filters personal export by participants only.

- [ ] **Step 3: Implement minimal scope fix**

Update `app/Services/Modules/Activity/ActivityExportService.php` so personal export uses the same predicate as `ActivityInertiaController::buildTaskScopeQuery()` for `scope=my`: `created_by = user OR participants.user_id = user`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php --filter=export`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Modules/Activity/ActivityExportService.php tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php
git commit -m "fix: align activity export personal scope with task visibility"
```

## Chunk 2: Expose Participants In Task-Level Sheets

### Task 2: Add failing workbook assertions for participant metadata in `Detail`

**Files:**
- Modify: `tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php`
- Create if needed: `tests/Feature/Modules/Activity/ActivityExportWorkbookTest.php`

- [ ] **Step 1: Write the failing test**

Add workbook assertions that a task with tagged participants exports:
- participant count,
- participant names,
- participant IDs,
in task-level workbook data.

Also add one task-without-participants case asserting:
- `Jumlah Participant = 0`
- `Daftar Participant = ''`
- `Participant IDs = ''`

Use a workbook reader helper to inspect both header row and exported values instead of only task titles.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php --filter=participant`
Expected: FAIL because current workbook omits participant columns entirely.

- [ ] **Step 3: Implement minimal sheet extension**

Modify `app/Services/Modules/Activity/ActivityExportService.php`:
- eager load `participants:id,name`,
- extend `Detail` headers and rows with participant metadata,
- extend `Data Mentah` headers and rows with participant metadata,
- keep creator columns unchanged for backward compatibility.

If row-construction logic becomes repetitive, extract a small helper for stable participant formatting.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php --filter=participant`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Modules/Activity/ActivityExportService.php tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php
git commit -m "feat: add participant metadata to activity export sheets"
```

## Chunk 3: Lock Workbook Schema And Formatting

**Files:**
- Modify: `tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php`
- Create if needed: `tests/Feature/Modules/Activity/ActivityExportWorkbookTest.php`

- [ ] **Step 1: Write the failing test**

Add workbook assertions for:
- sheet names stay unchanged,
- existing header order remains intact in `Detail`,
- existing header order remains intact in `Data Mentah`,
- new participant headers are appended additively in `Detail`,
- new participant headers are appended additively in `Data Mentah`,
- `Daftar Participant` formatting is deterministic,
- `Participant IDs` formatting uses a documented delimiter and stable order.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php --filter=export`
Expected: FAIL because the workbook schema/formatting contract is not fully asserted or implemented yet.

- [ ] **Step 3: Implement deterministic workbook formatting**

Modify `app/Services/Modules/Activity/ActivityExportService.php` to:
- preserve current sheet names,
- append participant columns after existing columns in `Detail`,
- append participant columns after existing columns in `Data Mentah`,
- sort participant names ascending before joining with `, `,
- sort participant IDs ascending before joining with `|`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php --filter=export`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Modules/Activity/ActivityExportService.php tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php
git commit -m "test: lock activity export participant workbook schema"
```

## Chunk 4: Refine Helpers And Protect Maintainability

### Task 4: Extract or tighten participant formatting helpers if needed

**Files:**
- Modify: `app/Services/Modules/Activity/ActivityExportService.php`
- Modify if needed: `app/Services/Modules/Activity/ActivityReportAggregationService.php`

- [ ] **Step 1: Write a small regression test if helper extraction changes output**

Only if output formatting changes. Prefer keeping this inside the workbook feature test file.

- [ ] **Step 2: Run test to verify current behavior baseline**

Run: `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php`
Expected: current focused export tests PASS before refactor.

- [ ] **Step 3: Perform the refactor**

Keep helpers small and local unless there is clear reuse. Prefer private methods in `ActivityExportService` over broad service reshaping.

- [ ] **Step 4: Run test to verify output remains stable**

Run: `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Modules/Activity/ActivityExportService.php app/Services/Modules/Activity/ActivityReportAggregationService.php tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php
git commit -m "refactor: streamline activity export participant formatting"
```

## Chunk 5: Final Verification

### Task 5: Run focused verification and standards checks

**Files:**
- Modify only if verification reveals defects

- [ ] **Step 1: Run focused PHPUnit coverage**

Run: `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php`
Expected: PASS.

- [ ] **Step 2: Run any dedicated workbook export test file**

Run: `php artisan test tests/Feature/Modules/Activity/ActivityExportWorkbookTest.php`
Expected: PASS, if that file was created.

- [ ] **Step 3: Run formatting**

Run: `vendor/bin/pint --dirty`
Expected: PASS with no remaining style issues.

- [ ] **Step 4: Review changed files against standards**

Confirm:
- no dependency changes,
- no deprecated `SalesCrm` activation,
- backend scope remains inside export/workbook logic,
- tests cover creator-only, participant-only, and combined semantics.

- [ ] **Step 5: Commit final verification or follow-up fixes**

```bash
git add app/Services/Modules/Activity/ActivityExportService.php app/Services/Modules/Activity/ActivityReportAggregationService.php tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php tests/Feature/Modules/Activity/ActivityExportWorkbookTest.php
git commit -m "test: cover tagged participant activity export regression"
```

Plan complete and saved to `docs/plans/2026-04-17-activity-export-tagged-participants.md`. Ready to execute?
