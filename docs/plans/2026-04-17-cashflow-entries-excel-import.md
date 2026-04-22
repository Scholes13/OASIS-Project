# Cashflow Entries Excel Import Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a strict Excel template download and import flow for Cashflow Entries that supports explicit create/update, atomic rollback, and clear failure reporting.

**Architecture:** Backend owns workbook generation, workbook parsing, strict row validation, transaction-wrapped mutation, and flash/log payloads. Frontend extends `CashflowProjection/Entries` with download/upload actions, an import modal, and clear success/failure rendering using typed Inertia flash props. The workflow stays strict by using exact codes and explicit `line_item_id` for updates.

**Tech Stack:** Laravel 12, PHP 8.2, Inertia.js v2, React 19, TypeScript, PhpSpreadsheet, PHPUnit, Vitest

---

## Chunk 1: Backend Import Contract

### Task 1: Lock the backend import behavior with failing tests

**Files:**
- Modify: `tests/Feature/Modules/CashflowProjection/CashflowProjectionDashboardFilterTest.php`
- Create: `tests/Feature/Modules/CashflowProjection/CashflowProjectionEntriesImportTest.php`

- [ ] **Step 1: Write failing feature tests for template download**

Add tests that assert:
- `GET cashflow-projection.entries.import-template` returns `.xlsx`
- workbook contains `Template`, `Reference`, and `Existing Entries`
- `Existing Entries` includes accessible `line_item_id` rows for update discovery

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionEntriesImportTest.php`
Expected: FAIL because the route and template generator do not exist yet

- [ ] **Step 3: Write failing feature tests for import**

Add tests that assert:
- valid create-only import creates rows
- valid update import changes an existing row by `line_item_id`
- duplicate `line_item_id` in one file fails atomically
- invalid row rolls back the full import
- failure payload is returned through flash contract
- success and failure generate logging/audit side effects expected by the spec

- [ ] **Step 4: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionEntriesImportTest.php`
Expected: FAIL on missing request/controller/service behavior

### Task 2: Implement backend template and import services

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/Modules/CashflowProjection/CashflowProjectionController.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Modify: `app/Models/Modules/CashflowProjection/CashflowProjectionLineItem.php`
- Modify: `app/Services/Modules/CashflowProjection/CashflowProjectionAuditService.php`
- Create: `app/Http/Requests/CashflowProjection/ImportCashflowProjectionEntriesRequest.php`
- Create: `app/Services/Modules/CashflowProjection/CashflowProjectionEntryImportTemplateService.php`
- Create: `app/Services/Modules/CashflowProjection/CashflowProjectionEntryImportService.php`
- Create: `database/migrations/2026_04_17_000100_add_import_source_type_to_cashflow_projection_line_items.php`

- [ ] **Step 1: Add the failing request/route contract**

Implement routes:
- `cashflow-projection.entries.import-template`
- `cashflow-projection.entries.import`

Add request validation for:
- `.xlsx` only
- max `2 MB`

- [ ] **Step 2: Implement workbook template generation**

Generate a `.xlsx` file using PhpSpreadsheet with:
- `Template`
- `Reference`
- `Existing Entries`

- [ ] **Step 3: Implement strict parser + validator**

Build import service that:
- parses only `Template`
- accepts native Excel date cells or strict `YYYY-MM-DD`
- caps rows/errors
- rejects duplicate non-empty `line_item_id`
- validates scope and action compatibility

- [ ] **Step 4: Implement atomic mutation path**

Inside a transaction:
- create rows when `line_item_id` empty
- update rows when `line_item_id` present
- set `source_type = import`
- reuse audit logging per row

- [ ] **Step 5: Implement import flash payload and app logging**

Extend shared flash contract with `cashflow_import`, and write success/failure logs with capped structured error lists.

- [ ] **Step 6: Run focused backend tests**

Run: `php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionEntriesImportTest.php`
Expected: PASS

- [ ] **Step 7: Run formatting verification**

Run: `vendor/bin/pint --dirty`
Expected: PASS

## Chunk 2: Frontend Import UX

### Task 3: Lock the frontend behavior with failing tests

**Files:**
- Modify: `tests/React/Pages/CashflowProjection/Entries.test.tsx`
- Modify: `resources/js/inertia/Pages/CashflowProjection/types.ts`
- Modify: `resources/js/inertia/types/index.ts`

- [ ] **Step 1: Write failing React tests**

Add tests that assert:
- `Entries` shows `Download Template` and `Import Excel`
- import modal/panel appears
- `.xlsx` upload submits to the new route
- structured flash errors render row-level failures
- success summary renders clearly

- [ ] **Step 2: Run tests to verify they fail**

Run: `npm exec vitest run tests/React/Pages/CashflowProjection/Entries.test.tsx --runInBand`
Expected: FAIL because the import UI does not exist yet

### Task 4: Implement frontend import flow

**Files:**
- Modify: `resources/js/inertia/Pages/CashflowProjection/Entries.tsx`
- Modify: `resources/js/inertia/Pages/CashflowProjection/types.ts`
- Modify: `resources/js/inertia/types/index.ts`
- Create: `resources/js/inertia/Pages/CashflowProjection/components/ImportEntriesDialog.tsx`

- [ ] **Step 1: Add typed flash payload support**

Extend shared types for:
- `flash.cashflow_import`
- any entries page props needed for import actions

- [ ] **Step 2: Add import action controls**

Implement:
- `Download Template` action using standard browser download navigation
- `Import Excel` action opening a focused upload dialog

- [ ] **Step 3: Add failure and success rendering**

Render:
- file name
- created/updated counts
- row-level errors with row + column + message

- [ ] **Step 4: Run focused frontend tests**

Run: `npm exec vitest run tests/React/Pages/CashflowProjection/Entries.test.tsx --runInBand`
Expected: PASS

- [ ] **Step 5: Run TypeScript verification**

Run: `npm exec tsc --noEmit --pretty false`
Expected: PASS

## Chunk 3: Integrated Verification

### Task 5: Verify the full import workflow and review touched files

**Files:**
- Modify: `docs/exec_plans.md`

- [ ] **Step 1: Review touched files against the approved spec**

Confirm:
- strict template sheets
- explicit update by `line_item_id`
- atomic rollback
- flash error contract
- logging and audit behavior

- [ ] **Step 2: Run final focused verification**

Run: `php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionEntriesImportTest.php`
Expected: PASS

Run: `npm exec vitest run tests/React/Pages/CashflowProjection/Entries.test.tsx --runInBand`
Expected: PASS

Run: `vendor/bin/pint --dirty`
Expected: PASS

Run: `npm exec tsc --noEmit --pretty false`
Expected: PASS

- [ ] **Step 3: Update execution notes**

Record implementation status, verification, and any residual risk in `docs/exec_plans.md`.
