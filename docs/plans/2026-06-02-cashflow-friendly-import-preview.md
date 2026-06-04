# Cashflow Friendly Import Preview Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the technical Cashflow import flow with a user-friendly Excel import that accepts the current Finance/CFC sheet shape, auto-classifies department/action/IN-OUT, previews ambiguous rows, supports safe bulk update, and logs update actors.

**Architecture:** Keep persistence in existing Cashflow line-item tables, but add `keterangan` and widen `description` to `text`. Introduce parser/classifier/preview services before persistence so uploads produce `Ready`, `Update`, `No Change`, `Need Review`, or `Invalid` rows. Existing line-item actions and audit logging remain the final write path where possible.

**Tech Stack:** Laravel 12, Inertia React, PhpSpreadsheet, PHPUnit, Vitest/React Testing Library, existing CashflowProjection services/actions.

---

## Product Decisions Captured

- `excel/samplecfc.xlsx` is the product reference for the new import UX.
- Cashflow is not production yet, so the strict old `Template` contract may change or be replaced.
- Users can either upload their current Finance sheet (`Data CFC`) or download a new friendly template.
- Upload always goes through preview before save.
- Ambiguous department/action rows must be corrected by user in preview.
- `description` is one main field, displayed as a taller textarea in UI.
- Add `keterangan` as a separate field.
- `department_code` still exists in normalized import output, but system helps auto-parse it from `description`/`no_dokumen`.
- `action` determines `flow_type` (`in`/`out`) and `action_code`.
- Bulk update must not silently replace data; preview must show updates and before/after changes.
- `description` acts as a natural identifier for matching, but database primary key remains `id`.
- Updates must log `updated_by` and audit old/new values.

## Current User Excel Shape

Sheet: `Data CFC`

Header row currently observed at row 3:

```text
BULAN
TGL BAYAR
NO DOKUMEN
NAMA VENDOR
DESKRIPSI
NOMINAL
DUE DATE
KETERANGAN
ENTITAS
```

Normalize to:

```text
year                 <- from TGL BAYAR
business_unit_code   <- ENTITAS
department_code      <- auto parse from DESKRIPSI / NO DOKUMEN / explicit template column
action_code          <- classifier result
flow_type            <- from action metadata
transaction_date     <- TGL BAYAR
due_date             <- DUE DATE
amount               <- NOMINAL
description          <- DESKRIPSI
keterangan           <- KETERANGAN
notes                <- optional combined NO DOKUMEN + NAMA VENDOR
```

## Import Statuses

- `New`: no matching existing row; ready to create.
- `Update`: matched existing row by `line_item_id` or scoped normalized description; changes detected.
- `No Change`: matched existing row; no field changes.
- `Need Review`: department/action ambiguous or multiple description matches.
- `Invalid`: required field invalid, root department target, invalid department/action, bad date/amount.

## Matching Rules

Priority:

1. If `line_item_id` present, update exact accessible row.
2. Else match existing line item by scoped normalized description.
3. Else create new row.

Scoped normalized description key:

```text
cycle_id + department_id + normalized_description
```

Normalize description by trimming, collapsing whitespace, and comparing case-insensitively.

If multiple matches exist, status must be `Need Review`.

## Classifier Rules

Department detection priority:

1. Explicit `department_code` column when using friendly template.
2. `DESKRIPSI` pattern like `WNS - TEP - ...`.
3. `NO DOKUMEN` prefix like `HR-02/...`.
4. Otherwise `Need Review`.

Action classification examples:

```text
ACC + OPERASIONAL           -> OUT_ACC_OPS
ACC + PAJAK                 -> OUT_ACC_PAJAK
ACC + PIUTANG/REVENUE       -> IN_ACC_PIUTANG_REVENUE

HR + OPERASIONAL            -> OUT_HR_OPS
HR + GAJI/BENEFIT           -> OUT_HR_GAJI_BENEFIT
HR + PINJAMAN               -> OUT_HR_PEMBERIAN_PINJAMAN

CFC + OPERASIONAL           -> OUT_CFC_OPS
CFC + SUNTIKAN MODAL        -> IN_CFC_SUNTIKAN_MODAL
CFC + PENGEMBALIAN PINJAMAN -> IN_CFC_PENERIMAAN_PENGEMBALIAN_PINJAMAN
CFC + CORPORATE             -> OUT_CFC_CORPORATE_EXPENSES
CFC + BUNGA/ANGSURAN        -> OUT_CFC_BUNGA_ANGSURAN
CFC + HUTANG USAHA          -> OUT_CFC_HUTANG_USAHA
CFC + PENGEMBALIAN SUNTIKAN -> OUT_CFC_PENGEMBALIAN_SUNTIKAN_MODAL

TEP + OPERASIONAL           -> OUT_TEP_OPS
TEP + EVENT/COST OF REVENUE -> OUT_TEP_COST_OF_REVENUE
TEP + REVENUE               -> IN_TEP_ESTIMASI_UPCOMING_REVENUE

OTHER + OPERASIONAL         -> OUT_<DEPT>_OPS
```

If classifier confidence is low or multiple rules match, status is `Need Review`.

---

## Task 1: Add `keterangan` and widen `description`

**Files:**
- Create migration: `database/migrations/<timestamp>_add_keterangan_and_widen_description_on_cashflow_projection_line_items.php`
- Modify: `app/Models/Modules/CashflowProjection/CashflowProjectionLineItem.php`
- Modify: `app/Http/Requests/CashflowProjection/StoreCashflowProjectionLineItemRequest.php`
- Modify: `app/Services/Modules/CashflowProjection/CashflowProjectionPayloadFormatter.php`
- Modify: `app/Actions/Modules/CashflowProjection/StoreCashflowLineItemAction.php`
- Modify: `app/Actions/Modules/CashflowProjection/UpdateCashflowLineItemAction.php`
- Modify: `app/Services/Modules/CashflowProjection/CashflowProjectionEntryImportService.php`
- Test: existing Cashflow feature tests plus new assertions

**Step 1: Write failing tests**

Add assertions that manual create/update and import preserve `keterangan`, and long descriptions over 255 chars pass validation.

**Step 2: Run tests to verify red**

Run:

```bash
php artisan test tests/Feature/Modules/CashflowProjection --filter="keterangan|long description"
```

Expected: fail because `keterangan` is missing and description max is 255.

**Step 3: Implement migration/model/request/action/payload changes**

- Add nullable `keterangan` text/string column.
- Change `description` to `text`.
- Change validation from `max:255` to a larger explicit cap, recommended `max:5000`.
- Include `keterangan` in create/update/import/audit values and payloads.

**Step 4: Verify green**

Run:

```bash
php -l app/Models/Modules/CashflowProjection/CashflowProjectionLineItem.php
php artisan test tests/Feature/Modules/CashflowProjection tests/Unit/Services/CashflowProjection
```

Expected: all Cashflow tests pass.

---

## Task 2: Create parser and classifier services

**Files:**
- Create: `app/Services/Modules/CashflowProjection/Import/CashflowFriendlyImportParser.php`
- Create: `app/Services/Modules/CashflowProjection/Import/CashflowImportClassifier.php`
- Create: `app/Services/Modules/CashflowProjection/Import/CashflowImportPreviewService.php`
- Test: `tests/Unit/Services/CashflowProjection/CashflowFriendlyImportParserTest.php`
- Test: `tests/Unit/Services/CashflowProjection/CashflowImportClassifierTest.php`

**Step 1: Write parser tests**

Cover:

- `Data CFC` sheet detection.
- Header detection at row 3.
- Date/amount parsing.
- Mapping `DESKRIPSI` to `description` and `KETERANGAN` to `keterangan`.
- `NO DOKUMEN` + `NAMA VENDOR` preserved in notes.

**Step 2: Run parser tests to verify red**

Run:

```bash
php artisan test tests/Unit/Services/CashflowProjection/CashflowFriendlyImportParserTest.php
```

Expected: class not found / behavior missing.

**Step 3: Implement minimal parser**

Read `.xlsx` with PhpSpreadsheet and return normalized arrays. Do not persist.

**Step 4: Write classifier tests**

Cover department detection and action classification rules for ACC, HR, CFC, TEP, and standard departments.

**Step 5: Implement classifier**

Use existing `CashflowProjectionTemplateService` to validate action codes and derive `flow_type`.

**Step 6: Verify green**

Run:

```bash
php artisan test tests/Unit/Services/CashflowProjection/CashflowFriendlyImportParserTest.php tests/Unit/Services/CashflowProjection/CashflowImportClassifierTest.php
```

Expected: tests pass.

---

## Task 3: Add preview endpoint and preview payload

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/Modules/CashflowProjection/CashflowProjectionController.php`
- Create request: `app/Http/Requests/CashflowProjection/PreviewCashflowProjectionImportRequest.php`
- Modify/Create service: `app/Services/Modules/CashflowProjection/Import/CashflowImportPreviewService.php`
- Test: `tests/Feature/Modules/CashflowProjection/CashflowProjectionImportPreviewTest.php`

**Step 1: Write failing feature tests**

Cover:

- Upload `Data CFC` style workbook returns preview rows.
- Ambiguous rows return `Need Review`.
- Root department rows return `Invalid`.
- Matching by scoped normalized description returns `Update` with before/after changes.
- Same row with no changes returns `No Change`.

**Step 2: Run tests to verify red**

Run:

```bash
php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionImportPreviewTest.php
```

Expected: route/class missing.

**Step 3: Implement preview route**

Add route under `cashflow-projection.entries.import-preview` or similar. Return Inertia-friendly JSON/redirect payload matching current app conventions.

**Step 4: Implement preview service**

Build rows with:

```text
status
row_number
business_unit_code
department_code
department_label
action_code
action_label
flow_type
transaction_date
due_date
amount
description
keterangan
notes
match.line_item_id
changes[]
errors[]
```

**Step 5: Verify green**

Run:

```bash
php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionImportPreviewTest.php
```

Expected: tests pass.

---

## Task 4: Confirm import from preview

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/Modules/CashflowProjection/CashflowProjectionController.php`
- Create request: `app/Http/Requests/CashflowProjection/ConfirmCashflowProjectionImportRequest.php`
- Create action/service if needed: `app/Services/Modules/CashflowProjection/Import/CashflowImportConfirmService.php`
- Test: `tests/Feature/Modules/CashflowProjection/CashflowProjectionImportConfirmTest.php`

**Step 1: Write failing confirm tests**

Cover:

- `New` rows create line items.
- `Update` rows replace allowed fields only.
- `No Change` rows skip.
- `Need Review`/`Invalid` rows cannot be confirmed without correction.
- Audit logs record update actor and old/new values.

**Step 2: Run tests to verify red**

Run:

```bash
php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionImportConfirmTest.php
```

Expected: route/class missing.

**Step 3: Implement confirmation**

Prefer reusing existing `StoreCashflowLineItemAction`, `UpdateCashflowLineItemAction`, and `CashflowProjectionAuditService` logic. If payload shape makes direct reuse awkward, keep new confirm service small and duplicate no audit logic.

**Step 4: Verify green**

Run:

```bash
php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionImportConfirmTest.php tests/Feature/Modules/CashflowProjection/CashflowProjectionEntriesImportTest.php
```

Expected: tests pass.

---

## Task 5: Generate friendly template

**Files:**
- Modify: `app/Services/Modules/CashflowProjection/CashflowProjectionEntryImportTemplateService.php`
- Test: `tests/Feature/Modules/CashflowProjection/CashflowProjectionEntriesImportTest.php`

**Step 1: Write failing test**

Assert template uses sheet `Import` with user-friendly headers:

```text
bulan
tgl_bayar
no_dokumen
nama_vendor
deskripsi
nominal
due_date
keterangan
entitas
department_code
action_code
is_estimated_date
notes
line_item_id
```

**Step 2: Run test to verify red**

Run:

```bash
php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionEntriesImportTest.php --filter="template"
```

Expected: old headers/sheet fail.

**Step 3: Implement template generation**

Keep `Reference` and `Existing Entries`. Include allowed department/action labels. Exclude root departments with active children.

**Step 4: Verify green**

Run:

```bash
php artisan test tests/Feature/Modules/CashflowProjection/CashflowProjectionEntriesImportTest.php
```

Expected: tests pass.

---

## Task 6: Frontend preview UX

**Files:**
- Modify: `resources/js/inertia/Pages/CashflowProjection/Entries.tsx`
- Modify/Create components under: `resources/js/inertia/Pages/CashflowProjection/components/`
- Modify: `resources/js/inertia/Pages/CashflowProjection/types.ts`
- Test: `tests/React/Pages/CashflowProjection/Entries.test.tsx`

**Step 1: Write failing React tests**

Cover:

- Upload calls preview endpoint, not immediate import.
- Preview shows `Ready/New/Update/No Change/Need Review/Invalid` counts.
- Ambiguous rows expose department/action dropdowns.
- Confirm button disabled while invalid/need-review rows selected.
- Description field uses textarea/tall display.

**Step 2: Run test to verify red**

Run:

```bash
npm exec vitest run tests/React/Pages/CashflowProjection/Entries.test.tsx --runInBand
```

Expected: UI behavior missing.

**Step 3: Implement minimal UI**

Add import modal/preview table. Avoid rewriting whole page. Split components if `Entries.tsx` approaches hard cap.

**Step 4: Verify green**

Run:

```bash
npm exec vitest run tests/React/Pages/CashflowProjection/Entries.test.tsx --runInBand
npm exec tsc --noEmit --pretty false
npm run build
```

Expected: tests/build pass, aside from explicitly documented pre-existing errors if any.

---

## Task 7: Full verification and review

**Files:**
- Review all touched backend/frontend files.
- Update docs if final route/UX wording differs from this plan.

**Step 1: Run backend verification**

```bash
php -l <each touched PHP file>
php artisan test tests/Feature/Modules/CashflowProjection tests/Unit/Services/CashflowProjection
```

**Step 2: Run frontend verification**

```bash
npm exec vitest run tests/React/Pages/CashflowProjection/Entries.test.tsx --runInBand
npm exec tsc --noEmit --pretty false
npm run build
```

**Step 3: Format**

Windows:

```bash
.\vendor\bin\pint.bat --dirty
```

**Step 4: Request review**

Use `requesting-code-review` / reviewer lane. Include:

- import security/authorization contract,
- description matching behavior,
- audit logging behavior,
- UI preview behavior,
- verification outputs.

**Step 5: QA user-facing flow**

Run browser-level QA for:

- download template,
- upload existing `Data CFC`,
- preview ambiguous row,
- confirm create/update,
- verify Entries table shows description/keterangan/update attribution.

---

## Open Decisions Before Implementation

- Whether to keep old strict `Template` import compatibility. Current recommendation: no, because module is not production and PO wants `samplecfc.xlsx` as reference.
- Whether `vendor_name` and `document_number` become dedicated DB columns. Current recommendation: no for first patch; preserve in `notes`.
- Whether preview payload is stored server-side between preview and confirm or posted back from client. Current recommendation: post normalized rows back with signed/validated fields only if simple; otherwise store temporary import token in session/cache.
