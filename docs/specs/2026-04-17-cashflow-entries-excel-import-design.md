# Cashflow Entries Excel Import Design

## Summary

This spec adds a strict Excel import workflow to `Cashflow Entries` so users can download a system-approved template, fill it offline, and upload it safely. The workflow supports both creating new line items and updating existing line items, but only through explicit identifiers and exact system codes to minimize ambiguity and production bugs.

The import must be atomic. If any row fails validation, the system rejects the full file, preserves the current database state, and returns clear row-level errors to the user. Import failures must also produce server-side logs with enough context to troubleshoot malformed templates, unauthorized targets, and unexpected parser errors.

## Operational Limits

To keep import behavior deterministic and prevent oversized atomic validation from exhausting memory or session payloads, this workflow defines hard limits:

- accepted file type: `.xlsx` only
- maximum upload size: `2 MB`
- maximum non-empty template rows per upload: `500`
- maximum structured row errors returned to UI: `100`
- maximum structured row errors written inline to app logs: `20`

If the uploaded workbook exceeds any file-level limit, the request fails before row processing starts.

## Product Goal

Provide an enterprise-style import flow for finance users:

1. Download template
2. Match the system structure exactly
3. Fill the template offline
4. Upload the file
5. Receive either:
   - a success summary with created/updated counts, or
   - a failure summary with actionable row-level errors

## Scope

### Included

- A `Download Template` action on `Cashflow Entries`
- A strict `.xlsx` template generated from current allowed scope and action metadata
- A matching `Import Excel` upload flow on `Cashflow Entries`
- Support for both:
  - create new rows
  - update existing rows explicitly
- Atomic import behavior
- Row-level and column-level validation feedback
- Structured server-side logging for failed and successful imports
- Audit logging for created and updated imported line items

### Excluded

- Fuzzy matching by description or composite business keys
- Background or asynchronous import jobs
- Partial success import mode
- Deleting rows through import
- Importing finance inputs or dashboard summary values

## Current Constraints

### Existing line item model

`cashflow_projection_line_items` currently stores:

- `id`
- `cycle_id`
- `department_id`
- `flow_type`
- `action_code`
- `transaction_date`
- `due_date`
- `is_estimated_date`
- `amount`
- `description`
- `notes`
- `source_type`
- `created_by`
- `updated_by`

There is no dedicated external import identifier, and there is no uniqueness constraint that would make composite-key updates safe enough for strict import mode.

### Existing controller behavior

Manual entry create and update logic already enforce:

- access and management permissions
- active business unit and linked BU targeting rules
- action-to-department compatibility
- audit logging for create, update, and delete

The import workflow should reuse the same business rules rather than inventing a separate contract.

## Recommended Design

### Chosen approach

Use a strict three-sheet Excel template:

- `Template`
- `Reference`
- `Existing Entries`

The uploaded file is valid only when:

- the required sheet names exist
- the required columns exist in the expected order
- the values use exact system codes and exact allowed formats

Updates are explicit, not inferred.

### Update strategy

The import uses one explicit control column:

- `line_item_id`

Rules:

- `line_item_id` empty:
  - treat row as create
- `line_item_id` filled:
  - treat row as update
  - row must match an existing accessible cashflow line item
- duplicate non-empty `line_item_id` values within the same uploaded file are rejected as a validation failure
- the import does not allow “last write wins” behavior inside one file

This avoids fuzzy reconciliation and prevents incorrect updates when multiple rows share similar business fields.

## Template File Design

### Sheet 1: `Template`

This is the only sheet users may edit.

Columns, in order:

1. `line_item_id`
2. `year`
3. `business_unit_code`
4. `department_code`
5. `action_code`
6. `transaction_date`
7. `due_date`
8. `is_estimated_date`
9. `amount`
10. `description`
11. `notes`

### Column rules

- `line_item_id`
  - optional
  - integer only
  - empty means create
  - filled means update
- `year`
  - required
  - integer between `2000` and `2100`
  - must map to the cycle year to be created or updated
- `business_unit_code`
  - required
  - must exactly match one BU code in the user’s allowed import scope
- `department_code`
  - required
  - must exactly match one allowed department code inside the provided BU
- `action_code`
  - required
  - must exactly match one allowed action code for the provided department
- `transaction_date`
  - required
  - must resolve to an exact calendar date
  - accepted input forms:
    - Excel date cell formatted as date
    - string in strict `YYYY-MM-DD`
- `due_date`
  - optional
  - if present, must resolve to an exact calendar date
  - accepted input forms:
    - Excel date cell formatted as date
    - string in strict `YYYY-MM-DD`
- `is_estimated_date`
  - required
  - strict `TRUE` or `FALSE`
- `amount`
  - required
  - numeric
  - must be `>= 0`
- `description`
  - required
  - max `255` chars
- `notes`
  - optional

If a date cell is uploaded as a plain string in a non-supported format such as `17/04/2026` or `04-17-2026`, the row is rejected. Native Excel date cells are accepted, but the template guidance should still instruct users to format dates visually as `YYYY-MM-DD`.

### Sheet 2: `Reference`

This sheet is read-only guidance generated from live scope data.

Sections:

- import rules summary
- required format examples
- allowed business units
- allowed departments by business unit
- allowed action codes by department
- update instructions for `line_item_id`

The file should make it obvious that users must copy exact codes from `Reference` into `Template`.

### Sheet 3: `Existing Entries`

This sheet is read-only and exists to make explicit update mode workable.

It contains current accessible in-scope entries with at least:

- `line_item_id`
- `year`
- `business_unit_code`
- `department_code`
- `action_code`
- `transaction_date`
- `due_date`
- `is_estimated_date`
- `amount`
- `description`
- `notes`

User update flow:

1. download template
2. find the target row in `Existing Entries`
3. copy the `line_item_id`
4. paste that id into `Template`
5. edit the desired values in `Template`
6. upload the file

The `Existing Entries` sheet is reference-only. The parser must ignore it for mutation.

### Example guidance

Do not include an importable example row in `Template`.

Instead:

- place the example only in `Reference`, or
- render it as a visually separated instruction block that is not part of the parsed table

The upload parser must treat only real data rows under the `Template` header as importable rows.

## UI Workflow

### `Cashflow Entries` page actions

Add two actions to the page header or table toolbar:

- `Download Template`
- `Import Excel`

### Download behavior

When clicked, the system downloads a generated `.xlsx` file containing:

- the `Template` sheet
- the `Reference` sheet
- the `Existing Entries` sheet
- current scope-specific reference data
- current accessible line items for explicit update use

### Upload behavior

The import action opens a modal or upload panel with:

- file picker limited to `.xlsx`
- short strict-template guidance
- warning that the import is all-or-nothing
- warning that updates require `line_item_id`

### Success feedback

On success, show a clear message with:

- file name
- processed row count
- created row count
- updated row count

### Failure feedback

On failure, show:

- top-level error summary
- count of failed rows
- a structured row error list

Example:

- `Baris 7 - action_code: kode tidak valid untuk department HR`
- `Baris 12 - line_item_id: line item tidak ditemukan atau tidak berada dalam scope Anda`
- `Baris 15 - transaction_date: format harus YYYY-MM-DD`

The UI should keep the failure output visible after redirect so users can correct the file and retry.

## Import Processing Rules

### Parsing

The system accepts `.xlsx` only.

The parser must:

- require the `Template` sheet
- ignore `Reference` sheet for write operations
- ignore fully empty rows
- read only the required columns
- reject files with more than `500` non-empty template rows

### Validation stages

Validation should happen in this order:

1. File-level validation
   - file exists
   - extension/type is `.xlsx`
   - file size is within limit
   - workbook contains required sheets and columns
   - template row count is within limit
2. Row-level format validation
   - strict types and date formats
3. Scope validation
   - BU and department are allowed for current user
4. Business-rule validation
   - action code is allowed for department
   - update row references an accessible existing line item
5. Consistency validation
   - if `line_item_id` is supplied, the row’s BU/department/action must remain compatible with update rules

### Create behavior

For rows without `line_item_id`:

- resolve the target cycle from `business_unit_code + year`
- create line item using the same domain rules as manual entry
- set `source_type` to a new import-specific source if added, otherwise continue using `manual` with audit context noting import origin
- set `created_by` and `updated_by` to the importing user

### Update behavior

For rows with `line_item_id`:

- load the line item by id
- verify it is in current user scope
- verify the requested target department and BU remain allowed
- apply the same validation path used by manual update
- log old and new values through the audit service

If the same `line_item_id` appears more than once in the uploaded file, the import is rejected before mutation begins.

## Failure Model

### Atomic import

The import must run inside a database transaction.

If any row fails:

- rollback all created or updated rows
- return the full collected error set
- do not write partial results

### Error payload shape

The controller should send a structured error payload to the frontend like:

```php
[
    'summary' => 'Import gagal. Perbaiki file lalu coba lagi.',
    'file_name' => 'cashflow_entries_import.xlsx',
    'total_rows' => 24,
    'failed_rows' => 3,
    'errors' => [
        [
            'row' => 7,
            'column' => 'action_code',
            'message' => 'Kode tidak valid untuk department HR.',
            'value' => 'OUT_HR_UNKNOWN',
        ],
    ],
]
```

This keeps the UI deterministic and easy to render.

If more than `100` row errors are detected, the payload should:

- include the first `100` structured row errors
- include a summary note that additional errors were truncated
- still fail the full import

## Logging and Audit Requirements

### Failure logging

Every failed import request must write an application log entry with:

- authenticated user id and name
- active business unit id/code
- uploaded file name
- number of parsed rows
- number of failed rows
- first `20` structured errors
- exception trace if the failure is unexpected

### Success logging

Every successful import request must write an application log entry with:

- authenticated user id and name
- active business unit id/code
- uploaded file name
- parsed row count
- created row count
- updated row count

### Audit logging

Per-row create and update actions must continue using `CashflowProjectionAuditService`.

The import entry point should also generate one high-level log summary for observability, but it should not replace the existing row-level audit behavior.

## Backend Design

### New routes

Under `cashflow-projection`:

- `GET /entries/import-template`
- `POST /entries/import`

Suggested names:

- `cashflow-projection.entries.import-template`
- `cashflow-projection.entries.import`

### New backend units

- controller methods in `CashflowProjectionController` or a dedicated import controller if file size becomes too large
- request class for uploaded file validation
- import service for:
  - workbook generation
  - workbook parsing
  - row validation
  - transaction execution
- optional DTO/result objects for predictable success/failure payloads

### Preferred service split

- `CashflowProjectionEntryImportTemplateService`
  - builds `.xlsx` template
- `CashflowProjectionEntryImportService`
  - parses workbook
  - validates rows
  - executes transaction

This keeps template generation separate from import mutation logic.

## Frontend Design

### Entries page contract additions

The `CashflowProjectionEntriesPageProps` payload should expose:

- import capability flags if needed
- endpoints or enough route context for template download and upload
- import flash/error payload when a failed import occurs

### UI components

Likely additions:

- import action button group
- import modal or upload dialog
- import error panel with row list

The UI should preserve the current manual-entry workflow and simply add import as a parallel path.

## Security and Permission Rules

- reuse current `can:access-cashflow-projection` and `canManage` checks
- user may import only into allowed departments and allowed linked BUs already supported by current scope rules
- update by `line_item_id` must still confirm row ownership/scope, not just row existence
- reject files that attempt to target inaccessible line items or departments

## Open Design Decisions

### Import source tracking

Preferred:

- add `'import'` as an allowed `source_type` enum value so imported rows are distinguishable from manual rows

Fallback:

- keep `source_type = manual` and rely on audit summaries plus app logs

Recommendation:

- add `import` to `source_type` if implementation scope allows a safe migration.

### Error persistence mechanism

The import error payload must use an explicit shared-prop contract rather than overloading the existing scalar flash message fields.

Recommended contract:

- extend shared Inertia `flash` with `cashflow_import`
- add a typed frontend shape under `FlashMessages`

Example target shape:

```ts
type CashflowImportFlash = {
  summary: string;
  file_name: string;
  total_rows: number;
  failed_rows: number;
  truncated: boolean;
  errors: Array<{
    row: number;
    column: string;
    message: string;
    value?: string | number | null;
  }>;
};
```

Shared prop path:

- backend: `HandleInertiaRequests::share() -> flash.cashflow_import`
- frontend: `PageProps['flash']['cashflow_import']`

This keeps the contract aligned with current repo conventions while still allowing structured payloads for this one workflow.

## Testing Strategy

### Backend feature coverage

- template download returns `.xlsx`
- template contains `Template` and `Reference`
- valid create-only upload creates rows
- valid mixed create/update upload applies both correctly
- invalid row causes full rollback
- unauthorized BU/department/action is rejected
- invalid `line_item_id` is rejected
- audit logs are created for imported create/update rows

### Backend unit coverage

- row parser handles strict headers
- row validator reports row and column correctly
- update resolution only accepts explicit ids

### Frontend coverage

- entries page renders import actions
- upload flow sends `.xlsx`
- failure state renders row-level errors clearly
- success state renders summary cleanly

## Recommended Implementation Order

1. Add approved spec and implementation plan
2. Build template download service and route
3. Add upload request and import service with transaction + strict validation
4. Add frontend import actions and failure rendering
5. Add focused backend/frontend tests
6. Run standards review and verification

## Approval Gate

Implementation should not start until:

1. reviewer sub-agent approves this spec or returns only non-blocking notes
2. the Product Owner confirms the written spec is acceptable
