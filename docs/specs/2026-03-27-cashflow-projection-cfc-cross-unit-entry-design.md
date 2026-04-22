# Cashflow Projection CFC Cross-Department Entry and Audit Design

**Status:** Proposed
**Date:** 2026-03-27
**Owner:** PM Agent

## Background

Core Finance / CFC users need to input cashflow projection data on behalf of slower departments. The current module partly supports this on the backend for finance users, but the Entries page still behaves like a self-scope form because it only loads departments directly assigned to the current user in the active business unit.

The product direction for this change is:
- finance/CFC in the active business unit can create and edit projection entries for every active department in the active business unit,
- finance/CFC in the active business unit can also create and edit projection entries for every active department in business units already linked through Cashflow Projection settings,
- the UI must make the target business unit and department explicit,
- create and update actions must leave visible attribution (`created by`, `last edited by`) and preserve a non-destructive audit trail.

## Current State Summary

- Module access is granted to department heads and finance/CFC users in the active business unit.
- Dashboard and settings are finance-only.
- `storeLineItem()` already allows finance users to submit line items for departments in the active business unit and linked business units.
- `entries()` only loads departments from the current user's direct assignments in the active business unit, so finance users cannot reliably target other departments through the current UI.
- Line item flows currently expose create behavior, but not a first-class edit flow.
- `created_by` and `updated_by` exist on line items and finance inputs, but there is no immutable audit trail table and the UI does not surface attribution metadata.

## Goals

1. Let finance/CFC users enter and edit line items for all active departments in the active business unit.
2. Extend the same ability to all active departments in already linked business units.
3. Preserve existing restriction for non-finance department heads so they can still only manage their own department.
4. Make the target business unit and department explicit in the Entries experience.
5. Show `created by` and `last edited by` metadata on the relevant pages.
6. Persist an audit trail for create and update actions so change history is not lost.

## Non-Goals

- No approval workflow changes.
- No delete or restore audit workflow in this change unless required by existing implementation work.
- No dependency changes.
- No changes to deprecated `SalesCrm`.

## Recommended Approach

### 1. Backend access and data-loading alignment

- Keep the existing finance authorization principle in `CashflowProjectionAccessService`.
- Refactor Cashflow Projection controller logic to derive a reusable department scope:
  - non-finance users: active departments directly assigned to the user in the active business unit,
  - finance/CFC users: all active departments in the active business unit plus all active departments in linked business units.
- Reuse that scope in:
  - `entries()` for selectable departments and visible records,
  - `index()` so department payloads remain consistent with finance scope,
  - `storeLineItem()` and the new update endpoint so server-side authorization matches the UI exactly.

### 2. Explicit BU and department selection in Entries

- Replace the implicit "department inferred from category" interaction with explicit selectors:
  - `Business Unit`
  - `Department`
  - `Category`
- `Category` options stay derived from the selected department template.
- Default selection behavior:
  - finance/CFC defaults to the active business unit and their own department when available,
  - non-finance users remain effectively locked to their own department.
- Line item rows should visibly show the target business unit and department.

### 3. Edit support for line items

- Add a first-class update flow for line items.
- The edit flow should reuse the same validation rules and scope checks as create through dedicated Laravel Form Requests for create and update behavior.
- Update behavior should preserve `created_by`, refresh `updated_by`, and emit an audit trail entry.
- UI should allow editing from the Entries list using the existing form surface instead of creating a separate page.
- If a record belongs to a linked BU that is later unlinked, finance users in the host BU must lose update rights for that record and receive `403`; write access always follows the current active-link state.

### 4. Attribution and audit trail

- Keep `created_by` / `updated_by` as fast-access columns for current ownership metadata.
- Add a dedicated audit table, for example `cashflow_projection_audit_logs`, with fields shaped around:
  - auditable type (`line_item`, `finance_input`)
  - auditable id
  - action (`created`, `updated`)
  - business unit id
  - department id
  - actor user id
  - actor department id
  - actor department label snapshot
  - summary label
  - old values JSON
  - new values JSON
  - `created_at`
- Audit rows are append-only:
  - no update route or service behavior may mutate an existing audit row,
  - historical audit snapshots are never rewritten after insertion,
  - implementation should avoid exposing model update helpers for audit rows.
- Record audit entries for:
  - line item creation,
  - line item updates,
  - finance input creation through Settings,
  - finance input updates through Settings.
- Surface compact attribution in UI:
  - `Created by: {user} ({department})`
  - `Last edited by: {user} ({department})`
- Keep the full audit trail primarily server-side for now, while exposing the latest attribution and enough summary to satisfy the request.

### 5. Contract changes

- Extend department payloads used by Entries with business unit identity for selector grouping.
- Extend line item and finance input payloads with:
  - creator name and department label,
  - updater name and department label,
  - timestamps needed for compact audit display if present.
- Add a route and request contract for updating line items.

## Data Model Design

### New table

`cashflow_projection_audit_logs`

Suggested columns:
- `id`
- `auditable_type`
- `auditable_id`
- `action`
- `business_unit_id`
- `department_id`
- `actor_user_id`
- `actor_department_id`
- `actor_department_label`
- `summary`
- `old_values`
- `new_values`
- `created_at`

Indexes:
- `auditable_type`, `auditable_id`
- `actor_user_id`
- `business_unit_id`, `department_id`

### Existing models

- `CashflowProjectionLineItem`
  - add creator/updater eager loading where needed,
  - add metadata accessors only if they reduce controller duplication.
- `CashflowProjectionFinanceInput`
  - same treatment for attribution metadata.

## Error Handling

- Finance users must receive validation errors if they target a department outside the allowed scope.
- Non-finance users attempting to bypass the UI must still receive `403`.
- Finance users trying to update records in a BU that is no longer linked must receive `403`.
- Invalid category/department combinations must continue to return validation feedback rather than silent fallback.
- Audit logging must not expose raw exception details to the user.
- Entries UI must handle empty department and empty category states explicitly and must reset stale selections when BU or department changes.
- Failed create or update submissions must surface validation or action errors without silent form resets.

## Testing Strategy

### Feature tests

- finance user can load Entries with departments from:
  - active business unit,
  - linked business units.
- finance user can create a line item for another department in the active business unit.
- finance user can create a line item for a department in a linked business unit.
- non-finance department head cannot create or update a line item for another department.
- finance user can update an existing line item in allowed scope.
- finance user loses update access to linked-BU records after the BU link is removed.
- create and update actions persist audit log rows with correct actor and target metadata.
- finance input creation and updates persist attribution and audit rows.
- contract-focused feature coverage asserts the Entries and Settings Inertia payload shapes for attribution fields and selector data remain stable.

### Frontend tests

- Entries form updates department options when business unit changes.
- category options follow the selected department.
- edit mode preloads existing data and submits to the update endpoint.
- attribution labels render when provided.
- empty department/category states render clear fallback UI.
- stale selections reset safely when business unit or department changes.
- failed update handling preserves visible errors.

## Risks and Mitigations

- Risk: duplicated or ambiguous categories across departments or business units.
  - Mitigation: explicit BU and department selectors rather than category-only routing.
- Risk: finance users create entries into the wrong BU cycle by mistake.
  - Mitigation: visible BU selector and row metadata, with server-side cycle resolution bound to the selected department's BU.
- Risk: audit payload becomes noisy.
  - Mitigation: store compact JSON diffs for changed fields plus a human-readable summary.
- Risk: wider finance scope affects current list queries.
  - Mitigation: centralize scope resolution in a helper/service and cover with focused tests.

## Implementation Plan Outline

1. Update execution plan tracking and add tests for the new allowed scope and audit behavior.
2. Add audit log migration, model, and append-only write path.
3. Refactor backend scope resolution and add line item update endpoint backed by dedicated Form Requests.
4. Expand Entries contracts and UI for explicit BU and department selection plus edit mode and failure-state handling.
5. Surface attribution metadata in Entries and Settings and add contract assertions for response shape changes.
6. Run standards review plus focused verification (`phpunit`, `pint --dirty`, `tsc --noEmit`).

## Acceptance Criteria

- Finance/CFC in the active BU can create and edit line items for all active departments in the active BU and all linked BUs.
- Non-finance users remain restricted to their own department.
- Entries clearly shows which BU and department a record belongs to.
- Line items and finance inputs show `created by` and `last edited by`.
- Create and update actions persist immutable audit trail records with actor user and actor department context.
