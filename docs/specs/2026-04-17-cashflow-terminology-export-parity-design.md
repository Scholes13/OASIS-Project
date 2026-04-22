# Cashflow Terminology and Export Parity Design

**Status:** Proposed
**Date:** 2026-04-17
**Owner:** PM Agent

## Background

Local QA review reproduced the user confusion in the current Cashflow Projection module:
- outflow rows are shown with badge `PROJECTED`,
- the dashboard uses `projected` again for the closing-balance result,
- the export works technically, but the daily workbook does not include the running projected balance users rely on in the chart.

The Product Owner has confirmed that access is **not** the bug here:
- all `CFC/FIN` users should keep access to dashboard, export, settings, and linked business unit management.

This design therefore focuses on business-language clarity and export parity, not authorization changes.

## Goals

1. Remove the ambiguous use of `projected` as a row status for outflow transactions.
2. Separate three concepts cleanly:
   - transaction direction,
   - transaction state,
   - projected result metric.
3. Make the export workbook carry the same daily projected-balance story users see in the dashboard chart.
4. Preserve the current access model for all `CFC/FIN` users.
5. Keep the current dashboard, entries, and settings structure without a broader redesign.

## Non-Goals

- No authorization tightening for `CFC/FIN` users.
- No approval workflow changes.
- No dependency changes.
- No full-screen UI redesign of the cashflow module.
- No changes to deprecated `SalesCrm`.

## Product Decisions Locked In

### Access Model

The current scope is intentional and must remain:
- all `CFC/FIN` users may access the cashflow dashboard,
- all `CFC/FIN` users may export,
- all `CFC/FIN` users may open settings,
- all `CFC/FIN` users may manage linked business units.

The prior QA finding about `qa@werkudara.com` reaching Settings is therefore **expected behavior**, not a bug.

### Vocabulary Model

The module should stop using one word for three different ideas.

Canonical terms for this change:

- **Transaction direction**
  - `Inflow`
  - `Outflow`
- **Transaction state**
  - `Pending` for estimated-date items,
  - `Confirmed` for all non-estimated items.
- **Result metric**
  - `Saldo Proyeksi` on all touched dashboard and export surfaces.

The implementation for this task must not introduce `Planned` as a third row-state label.

## Current-State Problem Summary

### 1. Status mapping is misleading

The current UI maps:
- `pending` when `is_estimated_date = true`,
- `confirmed` when `flow_type = in`,
- `projected` for all remaining outflow rows.

This causes users to read `PROJECTED` as if it were the type of the transaction, especially when the same row already shows a negative amount and the dashboard card already uses the word `Outflow`.

### 2. Dashboard copy overloads `projected`

Today, one screen uses:
- `Cashflow Projection` as the module name,
- `Projected closing balance` in the summary card,
- `PROJECTED` in the transactions table.

The business meaning shifts from line to line, which makes the screen harder to understand than it needs to be.

### 3. Export does not mirror the chart's running projection result

The workbook already exports:
- summary values,
- daily inflow/outflow/net,
- raw entries,
- finance inputs.

But the daily sheet does not include the running projected balance line that the dashboard chart visualizes. This creates a "download works but the important result is missing" feeling.

## Recommended Approach

### 1. Reframe transaction badges

Keep `flow_type` as the source of transaction direction, not as a badge synonym.

Required row-state behavior:
- if `is_estimated_date = true`, badge = `Pending`,
- else badge = `Confirmed`.

This is the simplest and least confusing option because:
- users can already identify inflow vs outflow from amount sign, category, and surrounding labels,
- badge meaning becomes one-dimensional: state only,
- we avoid introducing a third label only for outflow rows.

### 2. Reword projected-result surfaces

Adjust business copy so projected-result language is only used for result metrics, not row states.

Required user-facing wording on touched surfaces:
- dashboard card label:
  - from `Balance Snapshot`
  - to `Saldo Proyeksi`
- dashboard card caption:
  - from generic projected-balance wording
  - to language that explains this is the estimated ending cash position for the selected period
- export daily column:
  - `Saldo Proyeksi`
- export summary field:
  - `Saldo Proyeksi`

This change should not leave mixed alternatives such as `Projected Balance` / `Saldo Proyeksi` on touched dashboard and export fields.

### 3. Add daily projected balance to export

Extend the daily export sheet so it contains:
- `Date`
- `Inflow`
- `Outflow`
- `Net`
- `Saldo Proyeksi`

The new `Saldo Proyeksi` column should mirror the dashboard chart logic exactly for the active filter:
- start each month from `opening_balance + finance_income`,
- apply daily `net` movement in chronological order,
- store the running result for each exported date row.

Required filter behavior:
- **month filter**
  - export one row per day in the selected month,
  - include zero-movement days,
  - start the running balance from the selected month's `opening_balance + finance_income`.
- **range filter**
  - export one row per day in the selected date range,
  - include zero-movement days,
  - when the range crosses into a new month, reset the running-balance base for that month to that month's `opening_balance + finance_income`, then continue applying daily `net` movement from the first exported day of that month.
- **year filter**
  - export one row per day from January 1 through December 31,
  - include zero-movement days,
  - reset the running balance at each month boundary using that month-specific base.

Required empty-state behavior:
- if a month has no finance input, treat `opening_balance` and `finance_income` as `0`,
- if a day has no movement, still emit the row and carry forward the last running `Saldo Proyeksi` for that month.

This keeps the workbook aligned with what users visually inspect in the dashboard.

### 4. Preserve export structure

Keep the current workbook structure:
- `Summary`
- `Daily Movement`
- `Raw Entries`
- `Finance Inputs`

The change is additive, not structural:
- summary wording can be clarified,
- daily movement gains the projected-balance column,
- raw entries and finance inputs remain available for finance processing.

### 5. Keep settings access unchanged

Do not change `CashflowProjectionAccessService` or settings authorization rules in this work.

If future governance needs change, that should be handled in a separate access-control task with explicit product approval.

## UX Guidelines

- Badge meaning must answer only one question: "what is the state of this entry?"
- Labels for amount direction must remain separate from labels for entry state.
- The same word should not mean both:
  - "future status of a transaction"
  - and "resulting projected balance"
- Prefer Indonesian-first copy for the touched dashboard labels, transaction badges, and export headers introduced or changed by this task.

## Backend Design

### Controller logic

- Keep current dashboard summary and monthly summary calculation sources.
- Extend the daily summary builder or add a dedicated export-focused builder so each row includes running `Saldo Proyeksi`.
- Reuse the same logic the frontend chart already implies to avoid dashboard/export drift.

### Workbook contract

- Update the `Daily Movement` sheet header and rows to include the `Saldo Proyeksi` field.
- Update touched summary labels for business clarity without changing response type or filename conventions.

## Frontend Design

- Update transaction badge mapping in:
  - dashboard recent/selected transactions table,
  - entries table.
- Update card labels/captions where `projected` currently competes with status language.
- Keep the existing layout and controls.

## Error Handling

- Export must continue to succeed even when there are no line items for some dates in the selected range.
- Running projected balance should default safely when no opening balance or finance input exists for a month.
- No internal exception detail should be exposed to users.

## Testing Strategy

### Feature tests

- export workbook daily sheet includes the new `Saldo Proyeksi` column,
- projected-balance values match the same monthly opening-balance and finance-input logic used by the dashboard for:
  - month filter,
  - range filter across at least one month boundary,
  - year filter with month reset behavior,
  - zero-movement days,
- existing export sheets remain present and downloadable,
- endpoint-level access rules for `CFC/FIN` users remain unchanged for:
  - dashboard,
  - export,
  - settings,
  - linked business unit management.

### Frontend tests

- dashboard recent transactions no longer label outflow rows as `Projected`,
- entries table no longer labels outflow rows as `Projected`,
- updated labels/captions render expected business wording,
- export action wiring remains unchanged.

## Risks and Mitigations

- Risk: changing labels without changing logic still leaves business confusion.
  - Mitigation: update both label semantics and export parity in the same pass.
- Risk: export projected-balance logic diverges from dashboard chart logic later.
  - Mitigation: derive export running balance from the same monthly opening/finance assumptions, lock month-boundary behavior explicitly, and cover zero-movement days with regression tests.
- Risk: Indonesian wording change feels inconsistent with existing English UI.
  - Mitigation: limit wording changes to the most confusing terms and keep structure intact.

## Acceptance Criteria

- Outflow rows are no longer shown with badge `PROJECTED`.
- All non-estimated transaction rows touched by this task show badge `Confirmed`.
- Estimated-date transaction rows touched by this task show badge `Pending`.
- Transaction badges describe state, not direction.
- Touched dashboard/export result fields use `Saldo Proyeksi` as the projected-result term.
- Exported `Daily Movement` includes a running `Saldo Proyeksi` column aligned with the dashboard chart for month, range, and year filters, including zero-movement days and month-boundary resets.
- `CFC/FIN` users retain the same current access to dashboard, export, settings, and linked BU management.
