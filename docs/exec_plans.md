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

### 2026-03-31 - Department switch API route restoration
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_backend`, `@reviewer`
- Scope:
  - investigate the 404 shown when switching departments for multi-department users,
  - restore the missing backend route wiring for the existing department switch controller,
  - add focused Laravel regression coverage for the Inertia department switch flow.
- Risks:
  - route middleware must match the existing same-origin Inertia pattern so authenticated session switching keeps working,
  - the fix must not open department switching outside the current business unit assignment rules already enforced by the controller.
- Verification:
  - focused PHPUnit coverage for department switching,
  - `vendor/bin/pint --dirty`.
- Notes:
  - user reproduced the bug on `adiel@werkudara.com` when switching from `Product Development` to `Tour & Event Planning`,
  - root cause investigation found the frontend posts to `/api/department/switch`, but no Laravel route is registered for that endpoint even though `App\\Http\\Controllers\\Api\\DepartmentController` already exists,
  - backend wiring now registers the missing same-origin session route as `api.department.switch`,
  - focused PHPUnit coverage, `vendor/bin/pint --dirty`, and route-list verification all passed,
  - reviewer found no standards issues; remaining risk is limited to untested negative route cases already enforced by the controller guards.

### 2026-03-31 - Navbar department switcher wiring for multi-department users
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_frontend`, `@reviewer`
- Scope:
  - investigate why users with multiple department assignments do not see the department switcher in the shared navbar,
  - restore the navbar wiring so multi-department users can switch context from the global header,
  - add focused React regression coverage for the shared layout.
- Risks:
  - navbar layout spacing must stay stable on desktop and mobile after adding another shared header control,
  - the fix should not surface a department switcher for single-department users because rendering still depends on shared Inertia props.
- Verification:
  - focused Vitest coverage for navbar shared controls,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - user report confirmed the bug on `adiel@werkudara.com`, which has two active department assignments in `WNS`,
  - root cause investigation found the `DepartmentSwitcher` component exists but is not mounted in the shared navbar,
  - shared navbar wiring now mounts the existing department switcher next to the business unit switcher,
  - focused Vitest coverage and `npm exec tsc --noEmit --pretty false` both passed,
  - reviewer found no standards or architecture issues; remaining risk is limited to shared Inertia department props, which this navbar regression test does not exercise directly.

### 2026-03-31 - Cashflow Projection entries action dropdown and delete flow
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
  - add a delete action for cashflow projection line items on the entries page,
  - simplify row actions into a single dropdown trigger while keeping the UI compact and enterprise-friendly,
  - confirm destructive deletes through a classic modal and preserve the existing edit flow.
- Risks:
  - delete authorization must align with entries visibility scope so linked BU and non-finance department access do not drift,
  - adding a dropdown inside a dense table must not regress row readability or edit discoverability,
  - delete requests should preserve month/year context and append audit logs before removal.
- Verification:
  - focused PHPUnit coverage for line item delete authorization and audit logging,
  - focused Vitest coverage for entries dropdown and delete confirmation behavior,
  - `vendor/bin/pint --dirty`,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - user approved the compact three-dot dropdown direction after reviewing side-by-side visual options,
  - delete is intentionally available to all users who can access the entries page, within the rows already visible to their scope,
  - reviewer feedback identified two follow-up gaps during implementation: missing non-finance delete coverage and missing double-click protection for destructive requests,
  - both follow-ups were resolved in this pass by adding HoD authorization coverage and disabling repeat delete submits while the dialog is processing,
  - focused PHPUnit, focused Vitest, `vendor/bin/pint --dirty`, and `npm exec tsc --noEmit --pretty false` all passed after the final patch set.

### 2026-03-30 - Activity calendar owner visibility upgrade
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
  - make activity calendar entries clearly show who owns or is assigned to each task,
  - preserve the current compact month layout while improving owner scanability to an enterprise-ready level,
  - keep week/day views richer than month view without breaking existing modal-first calendar behavior.
- Risks:
  - month cells can become visually noisy if avatar treatment is too large or competes with status/type signals,
  - owner identity must degrade gracefully when users have no uploaded avatar and when tasks have multiple participants,
  - calendar event rendering changes must not regress create/detail/edit interaction wiring.
- Verification:
  - focused Vitest coverage for calendar event owner rendering in month and expanded views,
  - focused Vitest coverage for existing calendar create behavior,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - user reported that the calendar is still difficult to map to the responsible person because entries do not show photo or initials,
  - recommended direction is status dot on the left, title in the center, and compact owner avatar or initials on the right,
  - multiple participants should collapse into one visible owner avatar plus a `+n` badge to stay compact,
  - review uncovered a payload gap: the dashboard task query was not yet selecting `avatar_url`, so the implementation expanded to include the backend contract fix and regression coverage for flat participant payloads,
  - month view now shows the owner marker while week/day surfaces richer owner details,
  - focused verification passed for backend task payload contract, calendar owner rendering, dashboard modal flow, targeted Pint checks, and TypeScript compilation.

### 2026-03-30 - Activity board stale task recovery
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_frontend`, `@reviewer`
- Scope:
  - investigate and fix the Activity My Tasks board issue where tasks can disappear until refresh or view remount,
  - remove or constrain board-local state drift so kanban stays aligned with Inertia task payloads,
  - add focused React regression coverage for the stale-board recovery scenario.
- Risks:
  - kanban drag interactions must keep current status-update behavior without introducing jumpy cards,
  - the fix must not regress modal-first task detail and edit flows introduced earlier today,
  - calendar and timeline views should remain contract-compatible with the shared dashboard task payload.
- Verification:
  - `npm exec vitest run tests/React/Components/Activity/KanbanBoardCreateEntry.test.tsx --runInBand`,
  - `npm exec vitest run tests/React/Pages/Activity/Dashboard.test.tsx --runInBand`,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - user reported that activities sometimes disappear in My Tasks board until refresh or switching views such as kanban -> calendar -> kanban,
  - root cause investigation found `KanbanBoard` can leave its local shadow state diverged from server props when a drag is cancelled,
  - board drag cancellation now restores local task state from the current server payload, and focused React coverage protects the regression.

### 2026-03-30 - Activity calendar click should stay in modal flow
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_frontend`, `@reviewer`
- Scope:
  - investigate why clicking an activity in calendar view navigates to the legacy task page,
  - align calendar click behavior with the modal-first task flow used by kanban,
  - add focused React coverage for the dashboard-level calendar interaction.
- Risks:
  - changing dashboard click wiring must not regress timeline or list navigation behavior unless intentionally coordinated,
  - calendar event clicks still need access to edit flow through the existing modal contract.
- Verification:
  - focused Vitest coverage for activity dashboard calendar click behavior,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - user reported calendar item clicks open the legacy page instead of staying in the modal workflow seen in kanban.
  - root cause investigation found the activity dashboard was overriding calendar event clicks with `router.visit(route('activity.task.show', ...))`, bypassing the calendar component's built-in modal flow.
  - dashboard calendar events now use the component's default detail modal while preserving the modal edit handoff through `onEditTask`.
  - focused React verification passed for the dashboard calendar click path, existing calendar create behavior, and the related activity dashboard page test.

### 2026-03-30 - Activity task modal-first consistency and legacy page deprecation
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
  - audit and replace remaining activity task entry points that still navigate to legacy full pages,
  - make activity task detail and edit flows open through dashboard-hosted modals,
  - convert deprecated task `show/edit` routes into compatibility redirects that preserve the selected task context.
- Risks:
  - redirect compatibility must preserve direct links and bookmarks without dropping users onto an unscoped task list,
  - dashboard modal hydration must still work when the selected task is outside the current paginated task payload,
  - activity analytics widgets and shared task components may need contract-safe callback wiring to avoid regressions.
- Verification:
  - focused Vitest coverage for modal-first navigation and legacy route compatibility,
  - focused Laravel feature coverage for deprecated task route redirects,
  - `vendor/bin/pint --dirty`,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - product direction is to standardize on modal detail and modal edit flows instead of separate task pages,
  - `Activity/TaskDetail` and `Activity/TaskForm` are now considered deprecated implementation surfaces.
  - frontend modal-first routing now prefers `activity.task.index?task=...&modal=detail|edit` and `activity.task.index?modal=create` for cross-page entry points,
  - deprecated GET routes for task `show`, `edit`, and `create` now redirect back into the dashboard modal flow,
  - the dashboard now hydrates detail/edit/create modals from query state and can use backend-provided `selectedTask` and `selectedTaskModal` props when the selected task is outside the current payload,
  - reviewer feedback exposed two follow-up risks during implementation: unauthorized deep-linked edits and loss of dashboard context after modal edit submission,
  - both follow-ups were resolved in this pass by restricting edit hydration/update access to creator or participants, preserving dashboard query context on edit submit, and synchronizing modal open state back into the URL for refresh/share consistency.

### 2026-03-27 - Purchasing offline approval document access hardening
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
  - replace raw `/storage/...` offline approval document links with authenticated application routes,
  - add backend authorization-aware document streaming for purchase request and stock request offline approvals,
  - return a clear missing-file response instead of falling through to framework storage 403s.
- Risks:
  - purchasing admin access must keep current business-unit scoping and not open cross-BU document access,
  - existing records with missing files will still fail after the code fix and need explicit 404 handling,
  - task card links cover both PR and ST flows and should stay behaviorally consistent.
- Verification:
  - focused PHPUnit coverage for authorized and unauthorized offline approval document access,
  - focused PHPUnit coverage for missing offline approval files,
  - `vendor/bin/pint --dirty`,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - user reported 403 when purchasing admin opens offline approval evidence from the admin task view,
  - root cause investigation found the UI links directly to `/storage/...`, which can hit Laravel's `storage.local` file-serving route instead of an app-level authorization path,
  - authenticated PR/ST offline approval document routes now stream files from the `public` disk with business-unit authorization and explicit 404 handling,
  - purchasing admin and stock request offline document links now target the new named routes instead of raw `/storage/...` URLs,
  - PR 102 still points to a missing offline approval file locally, so the application now returns 404 until that file is restored.

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
