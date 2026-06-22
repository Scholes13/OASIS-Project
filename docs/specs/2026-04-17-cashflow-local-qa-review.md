# Cashflow Local QA Review

Date: 2026-04-17
Reviewer: PM Agent
Environment: local (`http://127.0.0.1:8000`)
Account used: `qa@werkudara.com`

## Review Scope
- Login and navigate through `Cashflow Projection` as the local QA user.
- Reproduce the dashboard, entries, settings, and export flows.
- Review whether the wording and information hierarchy are easy for non-technical users to understand.
- Identify access gaps or behavior mismatches that should feed the next implementation spec.

## Account Context
- `qa@werkudara.com` is a regular `user`, not `super_admin`.
- Primary department: `Corporate Finance Controller`.
- Primary position: `Staff of Corporate`.
- Active assignment in current BU: CFC staff.

## Confirmed Findings

### 1. Outflow rows are labeled `PROJECTED`, which is easy to misread as a transaction type
- Reproduced on local dashboard and entries page for `[Demo] April corporate spend`.
- User-visible effect:
  - the transaction amount already shows `-Rp 85.000.000`,
  - the chart and cards already explain this as `Outflow`,
  - but the row badge says `PROJECTED`.
- Business-user reading:
  - many users will read `Projected` as a replacement for `Outflow`,
  - or assume only projected items are exported, while confirmed outflow is treated differently.
- Likely product intent:
  - `Outflow` should describe transaction direction,
  - a separate badge should describe confidence/state if needed.
- Current implementation cause:
  - rows map status as `pending` when `is_estimated_date = true`,
  - else `confirmed` for inflow,
  - else `projected` for all remaining outflow rows.

### 2. Dashboard wording mixes three different concepts under the word `projected`
- Reproduced on the local dashboard.
- The same screen currently mixes:
  - `Projected closing balance` on the balance card,
  - `PROJECTED` as a row badge,
  - `Cashflow Projection` as the module name.
- Business-user effect:
  - users can no longer tell whether `projected` means:
    - future estimate,
    - outflow status,
    - end balance result,
    - or just the module title.
- Recommendation for next spec:
  - separate terminology into:
    - transaction direction: `Inflow` / `Outflow`,
    - transaction state: `Pending` / `Confirmed` / `Planned`,
    - result metric: `Saldo Proyeksi` or `Projected Balance`.

### 3. Export can be triggered successfully by QA, but the daily export still does not expose the projected balance line seen on the dashboard
- Reproduced locally by clicking `Export Excel` on `Cashflow Projection`.
- Network request succeeded:
  - `GET /cashflow-projection/export?filter=month&year=2026&month=4&scope=consolidated`
  - response `200`
  - attachment filename `cashflow-projection-consolidated-2026-04.xls`
- Behavior gap:
  - dashboard chart shows a running balance line,
  - export currently sends summary, daily inflow/outflow/net, raw entries, and finance inputs,
  - but the daily sheet does not carry the running projected balance that users visually rely on.
- Business-user effect:
  - export feels incomplete even though download works,
  - users can believe only inflow is exported because the running projection result is missing from the detailed daily sheet.

### 4. Local QA staff can access and manage global finance settings
- Reproduced locally:
  - `qa@werkudara.com` can open `Cashflow Projection > Settings`,
  - can see editable finance inputs,
  - can see linked business unit controls with `Link` / `Hapus`.
- Governance concern:
  - the QA account is a normal `user` with CFC staff assignment,
  - but current access rules treat any finance/CFC member as finance manager for settings and linked-BU management.
- This may be intended for MVP, but if not, it is an access gap.
- Recommendation for next spec:
  - decide explicitly whether:
    - all CFC/FIN staff may edit finance inputs and linked BUs, or
    - only approved finance leads/admins may do so.

## UX Review

### What already feels good
- Dashboard summary cards are fast to scan.
- The chart makes the trend direction visible quickly.
- `Export Excel` is easy to find in the header.
- Entries form is complete and does not feel technically intimidating.

### What still feels hard to understand
- Status naming is the biggest problem:
  - `PROJECTED` on a negative transaction reads like a type, not a status.
- Dashboard language is mixed:
  - cards and table labels are mostly English,
  - settings descriptions are mostly Indonesian.
- `Balance Snapshot` is less clear than `Saldo Proyeksi` for business users in this module.
- `Finance income` is understandable for finance users, but the relation between it and `Saldo Proyeksi` is not explained on the screen.

## Suggested Spec Direction
- Replace overloaded `projected` wording with separate business terms.
- Add daily `Projected Balance` / `Saldo Proyeksi` into the export workbook.
- Clarify whether settings are editable by all finance/CFC users or only privileged finance owners.
- Normalize the dashboard language to one user-facing vocabulary set, preferably Indonesian-first if that is the product norm for this module.

## Reproduction Summary
1. Start local Laravel app and log in as `qa@werkudara.com`.
2. Open `Cashflow Projection` dashboard.
3. Observe `[Demo] April corporate spend` showing amount `-Rp 85.000.000` with badge `PROJECTED`.
4. Open `Entries` and confirm the same row still uses `PROJECTED`.
5. Click `Export Excel` and confirm the workbook download starts successfully.
6. Open `Settings` and confirm QA can access editable finance inputs and linked business unit management.
