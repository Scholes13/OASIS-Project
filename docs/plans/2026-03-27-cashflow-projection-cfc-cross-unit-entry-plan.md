# Cashflow Projection CFC Cross-Unit Entry Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow finance/CFC users to create and edit cashflow projection entries across the active BU and linked BUs, while adding visible attribution and an append-only audit trail for create/update actions.

**Architecture:** Backend centralizes department-scope resolution and audit writing so `entries()`, `index()`, create, and update all follow the same authorization boundary. Frontend changes the Entries form from implicit category-only targeting into explicit BU and department selection with inline edit mode, while both Entries and Settings render current attribution metadata from the server.

**Tech Stack:** Laravel 12, PHP 8.2, Inertia v2, React 19, TypeScript, PHPUnit 11, Vitest, Tailwind v3

---

## Chunk 1: Backend scope, audit model, and failing tests

### Task 1: Add backend tests for cross-BU scope and audit expectations

**Files:**
- Create: `I:\Project\Numbering\tests\Feature\Modules\CashflowProjection\CashflowProjectionCrossUnitEntryTest.php`
- Create: `I:\Project\Numbering\tests\Feature\Modules\CashflowProjection\CashflowProjectionAuditTrailTest.php`
- Modify: `I:\Project\Numbering\tests\Feature\Modules\CashflowProjection\CashflowProjectionEntriesMonthFilterTest.php`

- [ ] **Step 1: Write the failing feature tests for finance scope**

Add tests that prove:
- finance user sees selectable departments from the active BU and linked BUs on Entries,
- finance user can create a line item for another department in the active BU,
- finance user can create a line item for a linked BU department,
- a linked-BU line item is persisted into the selected department's BU cycle instead of the active BU cycle,
- finance users submitting a department outside the current allowed scope receive validation feedback,
- non-finance user still gets `403` for another department,
- finance user loses update access after a BU link is removed.

- [ ] **Step 2: Run the new finance scope tests to verify they fail**

Run: `php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionCrossUnitEntryTest.php`
Expected: FAIL because Entries payload, update flow, or linked-BU write behavior is incomplete.

- [ ] **Step 3: Write the failing audit tests**

Add tests that prove:
- line item create writes an audit row,
- line item update writes a second append-only audit row,
- finance input create/update both write audit rows,
- invalid category and department combinations return validation feedback,
- finance users targeting departments outside their current allowed scope receive validation errors,
- attribution payload returns creator/updater names and departments.

Reviewer-facing note:
- keep test names and final verification notes explicit enough to point reviewers to the tests that prove linked-BU cycle targeting and invalid category/department validation rules.

- [ ] **Step 4: Run the audit tests to verify they fail**

Run: `php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionAuditTrailTest.php`
Expected: FAIL because the audit table and payload metadata do not exist yet.

### Task 2: Add append-only audit storage and reusable scope resolution

**Files:**
- Create: `I:\Project\Numbering\database\migrations\2026_03_27_000001_create_cashflow_projection_audit_logs_table.php`
- Create: `I:\Project\Numbering\app\Models\Modules\CashflowProjection\CashflowProjectionAuditLog.php`
- Create: `I:\Project\Numbering\app\Services\Modules\CashflowProjection\CashflowProjectionAuditService.php`
- Create: `I:\Project\Numbering\app\Services\Modules\CashflowProjection\CashflowProjectionScopeService.php`
- Modify: `I:\Project\Numbering\app\Models\Modules\CashflowProjection\CashflowProjectionLineItem.php`
- Modify: `I:\Project\Numbering\app\Models\Modules\CashflowProjection\CashflowProjectionFinanceInput.php`
- Modify: `I:\Project\Numbering\app\Http\Controllers\Modules\CashflowProjection\CashflowProjectionController.php`

- [ ] **Step 1: Implement the migration and audit model**

Create an append-only audit table with:
- auditable type/id,
- action,
- target BU and department ids,
- actor user id and actor department snapshot,
- compact `old_values` / `new_values` JSON,
- `created_at` only for historical recording.

- [ ] **Step 2: Implement reusable scope service**

Create a service that returns:
- allowed business unit ids,
- allowed departments collection,
- a department query constrained by the current user's finance/non-finance scope.

The controller should stop duplicating assignment logic in `index()`, `entries()`, create, and update.

- [ ] **Step 3: Implement audit writing service**

Add a small service that accepts:
- auditable target,
- action,
- actor,
- actor department snapshot,
- old/new payload arrays.

The service should only create new rows and never update prior audit records.

- [ ] **Step 4: Run the focused tests**

Run:
- `php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionCrossUnitEntryTest.php`
- `php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionAuditTrailTest.php`

Expected: still failing until controller routes and payloads are completed, but migration/model/service issues should be resolved.

## Chunk 2: Controller contracts, validation, and update flow

### Task 3: Add dedicated update validation and line item update endpoint

**Files:**
- Create: `I:\Project\Numbering\app\Http\Requests\CashflowProjection\UpdateCashflowProjectionLineItemRequest.php`
- Modify: `I:\Project\Numbering\app\Http\Requests\CashflowProjection\StoreCashflowProjectionLineItemRequest.php`
- Modify: `I:\Project\Numbering\routes\web.php`
- Modify: `I:\Project\Numbering\app\Http\Controllers\Modules\CashflowProjection\CashflowProjectionController.php`

- [ ] **Step 1: Write the failing update-path test if not already covered**

Target a test name like `test_finance_user_can_update_line_item_for_linked_department_while_link_is_active`.

- [ ] **Step 2: Run the filtered test to verify red**

Run: `php artisan test --filter=update_line_item_for_linked_department`
Expected: FAIL because no update route/request/controller support exists yet.

- [ ] **Step 3: Implement the minimal update route + request + controller behavior**

Add:
- `PATCH` route for line-item updates,
- dedicated Form Request,
- shared department/action validation,
- persistence that always resolves to the selected department's BU cycle,
- validation errors when finance users submit a department outside their current allowed scope,
- `403` when non-finance users bypass the UI and target another department,
- updated attribution and audit logging.

- [ ] **Step 4: Re-run the update-path test**

Run: `php artisan test --filter=update_line_item_for_linked_department`
Expected: PASS.

### Task 4: Expand Inertia payload contracts for Entries and Settings

**Files:**
- Modify: `I:\Project\Numbering\app\Http\Controllers\Modules\CashflowProjection\CashflowProjectionController.php`
- Modify: `I:\Project\Numbering\tests\Feature\Modules\CashflowProjection\CashflowProjectionEntriesMonthFilterTest.php`
- Modify: `I:\Project\Numbering\tests\Feature\Modules\CashflowProjection\CashflowProjectionDashboardFilterTest.php`

- [ ] **Step 1: Write/extend failing contract assertions**

Assert Entries and Settings payloads include:
- BU identity on departments,
- creator/updater display labels,
- edit-related ids or timestamps needed by the UI.

- [ ] **Step 2: Run the contract-focused tests to verify red**

Run:
- `php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionEntriesMonthFilterTest.php`
- `php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionDashboardFilterTest.php`

Expected: FAIL because the payload shape has not been expanded yet.

- [ ] **Step 3: Implement minimal payload additions**

Load creator/updater relationships efficiently and return stable Inertia props for:
- departments,
- line items,
- finance inputs.

- [ ] **Step 4: Wire finance input create/update into the audit service**

Ensure the Settings create/update handler records append-only audit rows for both:
- first-time finance input creation for a month,
- subsequent finance input updates for that month.

- [ ] **Step 5: Re-run the contract-focused tests**

Run the same two test files.
Expected: PASS.

## Chunk 3: Frontend explicit targeting, edit mode, and UI tests

### Task 5: Add failing frontend tests for BU/department selectors and edit mode

**Files:**
- Create: `I:\Project\Numbering\tests\React\Pages\CashflowProjection\Entries.test.tsx`
- Create: `I:\Project\Numbering\tests\React\Pages\CashflowProjection\Settings.test.tsx`
- Modify: `I:\Project\Numbering\resources\js\inertia\Pages\CashflowProjection\types.ts`

- [ ] **Step 1: Write the failing React tests**

Cover:
- BU selector changes department options,
- department selector changes category options,
- edit mode preloads selected record data,
- attribution labels render,
- empty department/category states render safely,
- failed update state keeps visible errors.
- Settings attribution metadata renders for finance inputs.

- [ ] **Step 2: Run the React test file to verify red**

Run:
- `npm test -- tests/React/Pages/CashflowProjection/Entries.test.tsx`
- `npm test -- tests/React/Pages/CashflowProjection/Settings.test.tsx`

Expected: FAIL because the page currently has no explicit BU/department selectors or edit mode.

### Task 6: Implement explicit target selection and inline edit UI

**Files:**
- Modify: `I:\Project\Numbering\resources\js\inertia\Pages\CashflowProjection\Entries.tsx`
- Modify: `I:\Project\Numbering\resources\js\inertia\Pages\CashflowProjection\components\AddProjectionCard.tsx`
- Modify: `I:\Project\Numbering\resources\js\inertia\Pages\CashflowProjection\types.ts`
- Modify: `I:\Project\Numbering\resources\js\inertia\Pages\CashflowProjection\cashflow-dashboard.css`

- [ ] **Step 1: Implement the minimal selector flow**

Add explicit:
- business unit selector,
- department selector,
- category selector filtered by department.

Ensure stale selection resets when BU/department changes.

- [ ] **Step 2: Implement inline edit mode**

Add:
- edit trigger from the Entries table,
- form prefill from the selected line item,
- submit switching between create and update endpoint,
- cancel edit action.

- [ ] **Step 3: Render attribution and target metadata**

Display:
- target BU and department in each row,
- `Created by`,
- `Last edited by`.

- [ ] **Step 4: Re-run the React test file**

Run:
- `npm test -- tests/React/Pages/CashflowProjection/Entries.test.tsx`
- `npm test -- tests/React/Pages/CashflowProjection/Settings.test.tsx`

Expected: PASS.

## Chunk 4: Finance Settings attribution and final verification

### Task 7: Surface finance input attribution and verify audit coverage

**Files:**
- Modify: `I:\Project\Numbering\resources\js\inertia\Pages\CashflowProjection\Settings.tsx`
- Modify: `I:\Project\Numbering\resources\js\inertia\Pages\CashflowProjection\types.ts`
- Modify: `I:\Project\Numbering\tests\Feature\Modules\CashflowProjection\CashflowProjectionAuditTrailTest.php`

- [ ] **Step 1: Extend failing test coverage if needed**

Assert Settings payload and UI-visible data expose creator/updater labels for finance inputs.

- [ ] **Step 2: Run the finance-input audit test to verify red**

Run: `php artisan test --filter=finance_input`
Expected: FAIL until attribution fields are surfaced.

- [ ] **Step 3: Implement minimal Settings attribution UI**

Render current attribution under the monthly finance input summary rows or in a compact metadata line that matches existing page density.

- [ ] **Step 4: Re-run the finance-input audit test**

Run: `php artisan test --filter=finance_input`
Expected: PASS.

### Task 8: Full focused verification and single final commit

**Files:**
- Modify only the files touched above

- [ ] **Step 1: Update execution tracking before final review**

Set `docs/exec_plans.md` status from `in_progress` to `review` once implementation and focused tests are done.

- [ ] **Step 2: Run all focused Cashflow Projection backend tests**

Run: `php artisan test tests/Feature/Modules/CashflowProjection`
Expected: PASS.

- [ ] **Step 3: Run focused React tests**

Run:
- `npm test -- tests/React/Pages/CashflowProjection/Entries.test.tsx`
- `npm test -- tests/React/Pages/CashflowProjection/Index.test.tsx`
- `npm test -- tests/React/Pages/CashflowProjection/Settings.test.tsx`

Expected: PASS.

- [ ] **Step 4: Run formatting and type verification**

Run:
- `vendor/bin/pint --dirty`
- `npm exec tsc --noEmit --pretty false`

Expected: PASS.

- [ ] **Step 5: Route the combined backend/frontend work through reviewer validation**

Dispatch reviewer validation against:
- `docs/coding_standards.json`,
- the approved spec,
- changed backend and frontend files,
- focused verification outputs.

Fix any findings and re-review if needed.

- [ ] **Step 6: Review changed files against spec and standards**

Confirm:
- finance scope is identical across `entries()`, `index()`, create, and update,
- non-finance scope remains restricted,
- reviewer notes clearly identify the tests proving linked-BU cycle targeting and invalid category/department validation feedback,
- reviewer notes clearly identify the tests proving finance out-of-scope validation feedback versus non-finance `403` bypass handling,
- audit writes are append-only,
- payloads are contract-safe.

- [ ] **Step 7: Update execution tracking to completed**

Set `docs/exec_plans.md` task status to `completed` after reviewer approval and verification are in hand.

- [ ] **Step 8: Create one final commit for this task**

Stage only the Cashflow Projection files, plan/spec docs for this task, and related tests.

Suggested commit:

```bash
git add docs/exec_plans.md docs/specs/2026-03-27-cashflow-projection-cfc-cross-unit-entry-design.md docs/plans/2026-03-27-cashflow-projection-cfc-cross-unit-entry-plan.md app/Http/Controllers/Modules/CashflowProjection app/Http/Requests/CashflowProjection app/Models/Modules/CashflowProjection app/Services/Modules/CashflowProjection database/migrations routes/web.php resources/js/inertia/Pages/CashflowProjection tests/Feature/Modules/CashflowProjection tests/React/Pages/CashflowProjection
git commit -m "feat: expand cashflow projection finance entry scope with audit trail"
```
