# Cashflow Terminology and Export Parity Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Clarify cashflow business terminology so outflow rows no longer appear as `PROJECTED`, and extend the Excel export so the daily sheet includes the running `Saldo Proyeksi` shown by the dashboard logic.

**Architecture:** Keep authorization unchanged and target only the cashflow dashboard, entries, and export contracts. The implementation splits into two small slices: frontend label/state cleanup and backend daily export parity, both protected by focused regression tests.

**Tech Stack:** Laravel 12, PHP 8.2, Inertia React 19, TypeScript, PHPUnit 11, Vitest, Laravel Pint

---

## File Map

- Modify: `I:\Project\Numbering\app\Http\Controllers\Modules\CashflowProjection\CashflowProjectionController.php`
  - extend daily export payload with running projected balance and clarify export labels where needed
- Modify: `I:\Project\Numbering\resources\js\inertia\Pages\CashflowProjection\components\RecentTransactionsTable.tsx`
  - make row badges represent transaction state instead of inflow/outflow direction
- Modify: `I:\Project\Numbering\resources\js\inertia\Pages\CashflowProjection\Entries.tsx`
  - align entries table badge semantics with the dashboard table
- Modify: `I:\Project\Numbering\resources\js\inertia\Pages\CashflowProjection\Index.tsx`
  - clarify projected-result labels and captions without changing page structure
- Modify: `I:\Project\Numbering\tests\Feature\Modules\CashflowProjection\CashflowProjectionDashboardFilterTest.php`
  - add export assertions for the daily `Saldo Proyeksi` column/value and access-regression coverage
- Modify: `I:\Project\Numbering\tests\React\Pages\CashflowProjection\Index.test.tsx`
  - lock dashboard wording and export wiring if text changes
- Create or Modify: `I:\Project\Numbering\tests\React\Pages\CashflowProjection\Entries.test.tsx`
  - cover entries-table badge semantics

## Chunk 1: Frontend Terminology Cleanup

### Task 1: Add failing React test for dashboard row status wording

**Files:**
- Modify: `I:\Project\Numbering\tests\React\Pages\CashflowProjection\Index.test.tsx`
- Modify: `I:\Project\Numbering\resources\js\inertia\Pages\CashflowProjection\components\RecentTransactionsTable.tsx`

- [ ] **Step 1: Write the failing test**

Add a focused test that renders the dashboard page with one outflow row and asserts the exact badge label is `Confirmed`, while estimated-date rows still show `Pending`.

- [ ] **Step 2: Run test to verify it fails**

Run: `npm exec vitest run tests/React/Pages/CashflowProjection/Index.test.tsx --runInBand`
Expected: FAIL because the dashboard still maps outflow to `Projected`.

- [ ] **Step 3: Write minimal implementation**

Update `RecentTransactionsTable.tsx` so:
- estimated-date rows remain `Pending`,
- non-estimated rows render `Confirmed`,
- badge text no longer depends on `flow_type`.

- [ ] **Step 4: Run test to verify it passes**

Run: `npm exec vitest run tests/React/Pages/CashflowProjection/Index.test.tsx --runInBand`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add tests/React/Pages/CashflowProjection/Index.test.tsx resources/js/inertia/Pages/CashflowProjection/components/RecentTransactionsTable.tsx
git commit -m "test: clarify dashboard cashflow row status labels"
```

### Task 2: Add failing React test for entries-table status wording

**Files:**
- Modify: `I:\Project\Numbering\tests\React\Pages\CashflowProjection\Entries.test.tsx`
- Modify: `I:\Project\Numbering\resources\js\inertia\Pages\CashflowProjection\Entries.tsx`

- [ ] **Step 1: Write the failing test**

Add a focused test that renders an outflow entry row and asserts the exact badge label is `Confirmed`, while estimated-date rows still show `Pending`.

- [ ] **Step 2: Run test to verify it fails**

Run: `npm exec vitest run tests/React/Pages/CashflowProjection/Entries.test.tsx --runInBand`
Expected: FAIL because the entries table still maps outflow to `Projected`.

- [ ] **Step 3: Write minimal implementation**

Update the entries table badge mapping so:
- estimated-date rows remain `Pending`,
- non-estimated rows render `Confirmed`,
- status stays a state label, not a direction label.

- [ ] **Step 4: Run test to verify it passes**

Run: `npm exec vitest run tests/React/Pages/CashflowProjection/Entries.test.tsx --runInBand`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add tests/React/Pages/CashflowProjection/Entries.test.tsx resources/js/inertia/Pages/CashflowProjection/Entries.tsx
git commit -m "test: align cashflow entries status wording"
```

### Task 3: Clarify projected-result labels on the dashboard

**Files:**
- Modify: `I:\Project\Numbering\tests\React\Pages\CashflowProjection\Index.test.tsx`
- Modify: `I:\Project\Numbering\resources\js\inertia\Pages\CashflowProjection\Index.tsx`

- [ ] **Step 1: Write the failing test**

Extend the dashboard page test to assert the balance card uses the exact term `Saldo Proyeksi`, and no longer renders `Balance Snapshot`.

- [ ] **Step 2: Run test to verify it fails**

Run: `npm exec vitest run tests/React/Pages/CashflowProjection/Index.test.tsx --runInBand`
Expected: FAIL because the page still renders `Balance Snapshot` / old projected caption.

- [ ] **Step 3: Write minimal implementation**

Update `Index.tsx` card labels/captions so the projected-result wording is reserved for the balance/result metric only.

- [ ] **Step 4: Run test to verify it passes**

Run: `npm exec vitest run tests/React/Pages/CashflowProjection/Index.test.tsx --runInBand`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add tests/React/Pages/CashflowProjection/Index.test.tsx resources/js/inertia/Pages/CashflowProjection/Index.tsx
git commit -m "test: clarify cashflow projected balance wording"
```

## Chunk 2: Backend Export Parity

### Task 4: Add failing feature tests for export parity and access regression

**Files:**
- Modify: `I:\Project\Numbering\tests\Feature\Modules\CashflowProjection\CashflowProjectionDashboardFilterTest.php`
- Modify: `I:\Project\Numbering\app\Http\Controllers\Modules\CashflowProjection\CashflowProjectionController.php`

- [ ] **Step 1: Write the failing test**

Extend the export feature test to assert:
- the `Daily Movement` sheet contains the exact `Saldo Proyeksi` column,
- the export summary uses the exact `Saldo Proyeksi` label on the touched result field,
- the workbook includes the expected running value for:
  - month filter,
  - range filter across a month boundary,
  - year filter month-reset behavior,
  - zero-movement days.

Add endpoint-level access assertions confirming `CFC/FIN` users retain access to:
- dashboard,
- export,
- settings,
- linked business unit management actions (`link` and `unlink`).

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionDashboardFilterTest.php`
Expected: FAIL because the export currently only writes `Date / Inflow / Outflow / Net`.

- [ ] **Step 3: Write minimal implementation**

Update `CashflowProjectionController.php` to:
- build an export-ready daily series with running `Saldo Proyeksi`,
- append the new column to the `Daily Movement` sheet,
- keep the workbook structure and access rules unchanged.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionDashboardFilterTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Modules/CashflowProjection/CashflowProjectionDashboardFilterTest.php app/Http/Controllers/Modules/CashflowProjection/CashflowProjectionController.php
git commit -m "test: add projected balance to cashflow export"
```

### Task 5: Run focused verification for the full scope

**Files:**
- Modify if needed based on failures: touched files above

- [ ] **Step 1: Run focused backend verification**

Run: `php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionDashboardFilterTest.php`
Expected: PASS

- [ ] **Step 2: Run focused frontend verification**

Run: `npm exec vitest run tests/React/Pages/CashflowProjection/Index.test.tsx tests/React/Pages/CashflowProjection/Entries.test.tsx --runInBand`
Expected: PASS

- [ ] **Step 3: Run formatting verification**

Run: `vendor/bin/pint --dirty`
Expected: exit 0

- [ ] **Step 4: Run type verification**

Run: `npm exec tsc --noEmit --pretty false`
Expected: exit 0

- [ ] **Step 5: Commit final stabilization if needed**

```bash
git add app/Http/Controllers/Modules/CashflowProjection/CashflowProjectionController.php resources/js/inertia/Pages/CashflowProjection/Index.tsx resources/js/inertia/Pages/CashflowProjection/Entries.tsx resources/js/inertia/Pages/CashflowProjection/components/RecentTransactionsTable.tsx tests/Feature/Modules/CashflowProjection/CashflowProjectionDashboardFilterTest.php tests/React/Pages/CashflowProjection/Index.test.tsx tests/React/Pages/CashflowProjection/Entries.test.tsx
git commit -m "feat: align cashflow terminology and export parity"
```

### Task 6: Standards review and reviewer gate before completion

**Files:**
- Review: `I:\Project\Numbering\docs\coding_standards.json`
- Review: all touched implementation and test files

- [ ] **Step 1: Run self-review against repo standards**

Check:
- no access-control changes slipped in,
- labels are deterministic (`Pending`, `Confirmed`, `Saldo Proyeksi`),
- export parity covers month/range/year and zero-movement days,
- touched files stay within approved backend/frontend boundaries.

- [ ] **Step 2: Request reviewer-agent review of the final patch**

Use the reviewer lane after implementation and before claiming completion.
Expected: no blocking findings.

- [ ] **Step 3: Address any review findings and rerun focused verification**

Run the same commands from Task 5 after fixes.
Expected: PASS
