# Execution Plans

## Operating Rule
- The Product Owner defines goals and priorities.
- The main agent acts as `PM Agent`.
- The PM updates this file when substantial work starts, changes scope, completes, or reveals meaningful tech debt.
- Implementation must be routed through the correct sub-agent and reviewed against `docs/coding_standards.json` before being presented as complete.

## Sub-Agent Roster
- `@coder_backend`: database, Laravel domain logic, API and server-side implementation.
- `@coder_frontend`: Inertia/React UI, state, and client-side integration.
- `@reviewer`: standards review, security checks, architecture drift checks, lint and verification review.

## Harness Assignment Rules
- Conceptual roles are mapped onto harness sub-agent types when work starts; they are not permanently connected agents.
- Default mapping:
  - `@coder_backend` => `worker`
  - `@coder_frontend` => `worker`
  - `@reviewer` => `explorer`
  - escalated `@reviewer` => `default`
  - repo discovery or impact analysis => `explorer`
- The `PM Agent` should spawn agents per task, define ownership clearly, and close them after completion.
- Use parallel workers only when their write scopes are meaningfully separate.

## Active Tasks

### 2026-03-27 - Export download navigation bypass for Inertia pages
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_frontend`, `@reviewer`
- Scope:
  - fix export buttons that only work via `open in new tab`,
  - bypass Inertia-style navigation for download endpoints in cashflow and activity surfaces,
  - keep exported URLs and filters unchanged while forcing normal browser download behavior.
- Risks:
  - export buttons must keep current query params intact after changing the click mechanism,
  - activity has multiple export entry points and they should stay behaviorally consistent.
- Verification:
  - focused Vitest coverage for cashflow and activity export click behavior,
  - `npm exec tsc --noEmit --pretty false`,
  - `npm run build`.
- Notes:
  - user reported export only works when opened in a new tab,
  - root cause investigation points to client-side navigation handling instead of standard browser download navigation,
  - export actions now use explicit browser download navigation in the same tab for cashflow and activity surfaces.

### 2026-03-27 - Cashflow Projection dashboard multi-sheet export
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
  - add an `Export Excel` action to the cashflow dashboard header,
  - upgrade the export output from a single HTML table into a multi-sheet Excel workbook,
  - keep dashboard summary sheets aligned to the active filter and scope,
  - always include raw, unfiltered operational data so finance can process the full source rows.
- Risks:
  - export payload rules must distinguish filtered dashboard views from always-on raw sheets,
  - workbook formatting must stay readable in Excel without adding new dependencies,
  - frontend export links must preserve the current period and consolidation scope.
- Verification:
  - focused PHPUnit coverage for workbook content and raw-data behavior,
  - focused Vitest coverage for dashboard export action wiring,
  - `vendor/bin/pint --dirty`,
  - `npm exec tsc --noEmit --pretty false`,
  - `npm run build`.
- Notes:
  - user approved the multi-sheet export direction,
  - user requested that export always includes raw data without dashboard filtering by default,
  - dashboard now exports a multi-sheet Excel workbook with filtered summary sheets plus always-on raw entries for finance processing.

### 2026-03-27 - Cashflow Projection same-user edit attribution visibility
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
  - distinguish between never-edited entries and entries edited by the same account,
  - expose explicit edit-history metadata from the cashflow entries payload,
  - show `Last edited by` whenever an update exists, even if creator and updater are the same person.
- Risks:
  - payload shape change must stay coordinated with frontend rendering and tests,
  - seeded/demo rows without a `created` audit log may still rely on fallback creator metadata.
- Verification:
  - focused PHPUnit coverage for cashflow audit payload metadata,
  - focused Vitest coverage for entries attribution rendering,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - user reported that editing with the same account no longer shows `Last edited by`,
  - root cause investigation found the frontend hides edit attribution by comparing creator/updater labels instead of using explicit audit history,
  - resolved by exposing explicit `has_edit_history` metadata from audit logs and using that flag in the entries table.

### 2026-03-27 - Cashflow Projection entries table business-unit simplification
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_frontend`, `@reviewer`
- Scope:
  - replace the `Department` column in Cashflow Entries with a compact `Business Unit` column,
  - show only BU shorthand codes such as `WNS`, `UT`, and `MRP` in that table column,
  - hide `Last edited by` attribution when the row has not been meaningfully edited yet.
- Risks:
  - attribution cleanup currently depends on frontend-visible creator/updater metadata rather than an explicit backend edited flag,
  - table layout changes may affect current frontend assertions.
- Verification:
  - focused Vitest coverage for entries table rendering,
  - `npm exec tsc --noEmit --pretty false`,
  - `npm run build`.
- Notes:
  - user requested a cleaner `All Entries` table with BU abbreviations only and less noisy attribution output,
  - frontend table now shows only business unit codes in that column and suppresses `Last edited by` when creator/updater metadata are identical.

### 2026-03-27 - Cashflow Projection global category label harmonization
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
  - standardize all category labels in Cashflow Projection to one searchable pattern: `DEPT - Label`,
  - apply the same concept to operational labels such as `GA - Operational Department GA`,
  - remove mixed prefixed and unprefixed labels from the entries flow.
- Risks:
  - changing labels again may break recent assertions if not updated end-to-end,
  - label formatting must stay stable between dropdown options and rendered line items.
- Verification:
  - focused PHPUnit coverage for template label generation and feature flows,
  - focused Vitest coverage for entries category rendering,
  - `vendor/bin/pint --dirty`,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - follow-up requested because mixed prefix patterns were still confusing in the category picker,
  - current BU categories now use `DEPT - Label`,
  - linked BU categories now use `DEPT - BU - Label` so cross-unit options stay explicit in the picker.

### 2026-03-27 - Cashflow Projection category label clarity and linked BU notice
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
  - normalize operational category labels to a single `Operational Department <CODE>` pattern,
  - make CFC cross-department categories explicit with department-prefixed labels,
  - add an entries-form notice when the selected target BU is a linked BU instead of the active BU,
  - cover the behavior with focused backend and frontend tests.
- Risks:
  - changing labels may affect existing assertions or user recognition of historical entries,
  - CFC labels must stay readable while still mapping to existing action codes,
  - linked-BU notice must not appear for the active BU or empty states.
- Verification:
  - focused PHPUnit coverage for template label generation and cashflow feature flows,
  - focused Vitest coverage for entries-form notice behavior,
  - `vendor/bin/pint --dirty`,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - follow-up requested after user feedback on duplicated and ambiguous category labels,
  - backend label normalization and frontend linked-BU notice both completed in this pass.

### 2026-03-27 - Cashflow Projection cross-department finance entry and audit trail
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
  - allow finance/CFC to create and edit line items for all active departments in the active BU and linked BUs,
  - align Entries UI with explicit BU and department targeting,
  - add visible attribution and immutable audit logs for create/update actions,
  - preserve non-finance department scoping.
- Risks:
  - inconsistent scope between GET and POST flows,
  - ambiguous action labels across departments,
  - audit payload growth if change snapshots are too broad.
- Verification:
  - focused PHPUnit coverage for finance scope, linked BU scope, update flow, and audit trail,
  - `vendor/bin/pint --dirty`,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - user explicitly approved the "full audit surface" direction,
  - linked BU support must apply to entry input, not only consolidated dashboard visibility,
  - focused Vitest coverage passed after rerunning outside the sandbox because Vitest needed to spawn `esbuild`.

## Completed Tasks
- 2026-03-27: Multi-agent repo exploration completed across platform, purchasing, cashflow, and activity surfaces.
- 2026-03-27: Shared route and module parity fixes implemented and verified.
- 2026-03-27: Full PHPUnit suite and TypeScript checks brought back to passing state.
- 2026-03-27: `SalesCrm` marked deprecated, disabled by feature flag, hidden from navigation, and documented for future agents.
- 2026-03-27: Harness Engineering scaffold established with architecture, standards, and execution tracking documents.
- 2026-03-27: Harness sub-agent role mapping documented for `worker`, `explorer`, and `default` usage.
- 2026-03-27: `context7` MCP server wired into workspace MCP configs and documented as the preferred source for up-to-date external package guidance.

## Known Tech Debt
- Generated route artifacts and client helpers may still contain deprecated `SalesCrm` route names even though the module is disabled.
- Deprecated module cleanup is incomplete until any remaining stale frontend references are intentionally sunset.
- Existing repo documentation in `docs/` predates the new execution model and may need gradual consolidation.

## MCP Verification Checklist
- After changing `.mcp.json` or editor-specific MCP config, reload the client that owns those settings.
- Confirm both `laravel-boost` and `context7` are listed as active MCP servers.
- Confirm `context7` tools are invokable before relying on external package guidance in a task.
- If server discovery fails, verify `npx.cmd -y @upstash/context7-mcp --help` still works locally.

## Task Template
Use this shape for future updates:

### YYYY-MM-DD - Task Title
- Status: planned | in_progress | review | blocked | completed
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
- Risks:
- Verification:
- Notes:
