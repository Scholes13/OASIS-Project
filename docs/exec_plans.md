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

### 2026-04-17 - Activity export tagged-user participation investigation
- Status: implemented
- Owner: PM Agent
- Delegates: `@coder_backend`, `@reviewer`
- Scope:
  - investigate whether users who are only tagged on an activity are excluded from the Excel export participation counts,
  - trace the activity export dataset and compare it with the task visibility or participation logic used by the activity surfaces,
  - confirm whether the issue is in query filtering, participant aggregation, or export row transformation.
- Risks:
  - activity export may drift from the dashboard/member-filter contract if the export builds its own participant dataset,
  - tagged-user semantics can differ between creator, participant, assignee, and mention relationships, so the bug may be a contract mismatch rather than a pure export formatting issue.
- Verification:
  - code-path inspection for activity export query and workbook transformation,
  - compare with existing focused activity member filtering or participant payload logic before proposing fixes.
- Notes:
  - user reported that when a user is tagged in an activity, the exported Excel output does not count that person as participating in the activity.
  - investigation confirmed the original workbook was creator-centric: export filtering and sheet data did not fully mirror the creator-or-participant semantics used by task views.
  - approved spec and implementation plan were written to `docs/superpowers/specs/2026-04-17-activity-export-tagged-participants-design.md` and `docs/superpowers/plans/2026-04-17-activity-export-tagged-participants.md`.
  - backend implementation aligned personal export scope with task screen semantics by treating `scope=my` as business-unit tasks where the current user is creator or participant, without forcing the current department filter.
  - `Detail` and `Data Mentah` workbook sheets now append additive participant columns for count, sorted participant names, and sorted participant ids, with deterministic empty output `0 / '' / ''`.
  - focused regression coverage now locks participant column schema and data for both sheets, including creator-only, participant-only, cross-department `scope=my`, and department member-focus cases.
  - focused PHPUnit verification and `vendor/bin/pint --dirty` passed in this workspace.
  - reviewer approved the final patch; QA found no blocking defects and only noted a non-blocking residual consideration about historical inactive participants remaining visible in export data.

### 2026-04-17 - Activity task detail modal action cleanup
- Status: implemented
- Owner: PM Agent
- Delegates: `@coder_frontend`, `@reviewer`
- Scope:
  - investigate why the `Delete` action is missing from the shared activity task detail modal,
  - restore a clear delete affordance for editable tasks without changing admin read-only behavior,
  - remove or constrain the `Open in Dashboard` CTA when the modal is already opened from the dashboard route so the journey is not redundant.
- Risks:
  - modal action changes must preserve the current modal-first flow and should not expose destructive actions to read-only viewers,
  - hiding the dashboard CTA should stay route-aware so non-dashboard entry points can still redirect if that journey remains valid.
- Verification:
  - focused Vitest coverage for task detail modal action visibility and delete behavior,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - user reported the modal screenshot still misses `Delete` and the `Open in Dashboard` journey is unclear in that context.
  - the shared `TaskDetailModal` now restores a delete icon for editable tasks, routes the destructive step through the shared `ConfirmDialog`, and disables repeated destructive clicks while the delete request is in flight,
  - `Open in Dashboard` is now hidden when the active Inertia URL is already under `activity/task`, so the CTA no longer appears as a redundant journey inside the dashboard modal flow,
  - focused Vitest coverage, TypeScript compilation, and `npm run build` passed in this workspace.

### 2026-04-17 - Purchasing phase 2 PR and ST end-to-end parity
- Status: implemented
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
  - define and deliver phase-2 parity between `Purchase Request` and `Stock Request` across the end-to-end surfaces users actually touch,
  - align user-facing capability, permission contracts, and approval lifecycle behavior so `PR` and `ST` feel like sibling modules with matching maturity,
  - preserve separate module homes, naming, documents, and routes so parity does not collapse `PR` and `ST` into one generic request type.
- Risks:
  - parity work can accidentally copy `PR` behavior into `ST` without preserving stock-specific semantics, route names, or wording,
  - backend permission drift can leave frontend CTAs visible but non-functional, especially for resend email, offline approval, approval actions, and document access,
  - end-to-end parity touches routes, controllers, props, pages, and browser journeys, so missing one layer can create subtle regression gaps even when individual buttons render.
- Verification:
  - reviewed design spec with sub-agent feedback before implementation,
  - focused PHPUnit coverage for route/action/authorization parity,
  - focused React coverage for parity-critical page actions and state rendering,
  - reviewer pass after implementation against `docs/coding_standards.json`,
  - browser QA with Playwright using the provided user account on the authenticated purchasing flows.
- Notes:
  - user approved a phase-2 spec-first workflow: spec -> sub-agent review -> gap closure -> implementation -> review -> browser QA,
  - parity target is not just button matching; it covers the end-to-end surfaces users touch so the purchasing module feels mature,
  - approved product direction is `1:1 parity` in capability and UX while keeping separate `PR` and `ST` module homes,
  - implemented backend parity for `ST` resend approval email, protected offline approval evidence access, and parity-grade `can`/`approvalContext` contracts,
  - implemented frontend parity on `ST Show` for resend email CTA, stock-approval home back link, and authenticated offline evidence routing,
  - focused PHPUnit coverage, focused React coverage, `vendor/bin/pint --dirty`, `npm exec tsc --noEmit --pretty false`, and `npm run build` passed in this workspace,
  - browser QA with `pramuji@werkudara.com` created a fresh `ST` (`ST.WNS/202604/006`), confirmed create -> detail -> index flow, confirmed live `Resend Email` POST success on `ST`, and confirmed the offline approval modal opens,
  - browser QA also confirmed `PR.WNS/202603/024` still exposes the sibling `Resend Email` and protected supporting-document actions for parity comparison,
  - live browser limitation: approval-context / approver-side `stock-approvals.show` flow was not validated with the provided owner account because that branch requires an approver session,
  - reviewer sub-agent passes were used successfully during spec/plan hardening, but the final code-review sub-agent attempts timed out in this session; main-lane self-review plus passing focused verification were used as the closure fallback.

### 2026-04-17 - Stock request create submit contract and date picker hardening
- Status: implemented
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
  - investigate why `stock-requests/create` fails during submit on the Inertia create surface,
  - restore the authenticated stock request submit and update route contract expected by the frontend page,
  - harden the stock request date input so browser `showPicker()` restrictions do not emit frontend batch errors after refresh or replayed clicks.
- Risks:
  - route restoration must preserve the existing Inertia create, edit, and show surfaces without colliding with the authenticated detail route order,
  - the date-input fix should avoid suppressing legitimate validation while removing the unnecessary browser API call that requires a trusted gesture,
  - purchasing create flows share similar date input behavior, so any shared hardening should stay contract-safe for purchase requests too.
- Verification:
  - focused PHPUnit coverage for stock request named route contracts,
  - focused Vitest coverage for purchasing request date input interaction,
  - `vendor/bin/pint --dirty`,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - root cause investigation found the stock request Inertia page still calls `route('stock-requests.store')` and `route('stock-requests.update')`, but the authenticated `stock-requests` route group in `routes/web.php` no longer registers those names,
  - a separate frontend batch error comes from calling `HTMLInputElement.showPicker()` directly in the date input click handler, which can throw `NotAllowedError` when the browser does not treat the event as a trusted user gesture,
  - the authenticated stock request route group now restores `store` and `update` named routes so Ziggy can resolve submit targets again,
  - purchasing request and stock request forms now rely on the browser's native date input behavior instead of forcing `showPicker()`,
  - focused PHPUnit coverage, focused Vitest coverage, `vendor/bin/pint --dirty`, `npm exec tsc --noEmit --pretty false`, and `npm run build` all passed in this workspace.

### 2026-04-14 - Activity Admin department detail route performance trim
- Status: implemented
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
  - investigate reports that `Activity Admin > Department Detail` feels heavy on the long-range task register route,
  - measure whether the slowdown comes from backend query time, oversized Inertia payloads, or eager frontend bundle loading,
  - trim the initial route cost without changing the admin task-detail modal workflow.
- Risks:
  - performance work must preserve the current detail-modal journey and query-string hydration from `?modal=detail&task=...`,
  - reducing initial page cost must not accidentally remove task register pagination or break export/filter state,
  - bundle-level changes should stay targeted to the admin department page so other Activity surfaces do not regress.
- Verification:
  - focused PHPUnit coverage for department detail pagination defaults,
  - focused Vitest coverage for admin department detail modal behavior,
  - `npm exec tsc --noEmit --pretty false`,
  - browser performance trace on `activity/admin/department/4?date_from=2026-01-14&date_to=2026-04-14`.
- Notes:
  - browser investigation showed the route TTFB was acceptable (`~329ms`) but reload LCP was dominated by client-side render delay (`~1.4s`),
  - the initial request chain eagerly loaded `module-activity`, `vendor-dnd`, and `vendor-calendar` because the page statically imported `TaskDetailModal`,
  - the register was already paginated, but it still shipped `20` rows per page; trimming to `10` reduces payload and DOM work on the initial render,
  - the admin department page now lazy-loads `TaskDetailModal`, so the heavy activity chunk only loads when the user actually opens task details.

### 2026-04-13 - Activity task modal create-again checkbox flow
- Status: implemented
- Owner: PM Agent
- Delegates: `@coder_frontend`, `@reviewer`
- Scope:
  - investigate why the activity create-task modal reopens immediately after a successful create,
  - replace the implicit reopen behavior with an explicit `Ingin membuat task lagi?` checkbox in the create modal footer,
  - keep the default unchecked so a normal create closes the modal, while checked mode resets and reopens the form for rapid consecutive task entry,
  - confirm whether edit flow shares the same behavior and preserve the intended post-save destination.
- Risks:
  - create flow now mixes client-side close/reset behavior with backend redirects, so the URL/query-state contract must stay aligned to avoid accidental reopen loops,
  - the new checkbox must only affect create mode and must not leak into edit mode or break the existing detail-after-edit journey,
  - modal reset behavior should keep date defaults and validation state consistent for both normal create and create-again paths.
- Verification:
  - focused Vitest coverage for task form modal create-again behavior,
  - focused Vitest coverage for dashboard modal query handling if the redirect/query contract changes,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - user wants the modal to close by default after create, with an opt-in checkbox to immediately create another task,
  - approved checkbox default is unchecked.
  - root cause was split across frontend and backend: the modal always called `onClose()` after create, but the store redirect also reused the stale `modal=create` referer, so the dashboard could reopen the create modal immediately after the visit completed,
  - `TaskFormModal` now shows an `Ingin membuat task lagi?` checkbox only in create mode, defaulting to unchecked on each fresh open,
  - when the checkbox stays unchecked, successful create closes the modal as before; when checked, the form resets in place for the next task while preserving the selected task date and keeping edit flow unchanged,
  - the store redirect now sanitizes stale `modal`, `task`, and `date` query params from the referer and maps the deprecated `/activity/task/create` origin back to `activity.task.index`, preventing accidental modal reopen loops after create,
  - edit flow is intentionally different and unchanged: after save it still returns to the detail modal for the edited task.

### 2026-04-13 - Activity task modal overflow hardening for short viewports
- Status: implemented
- Owner: PM Agent
- Delegates: `@coder_frontend`, `@reviewer`
- Scope:
  - investigate reports that activity task modals are cut off or cannot scroll on shorter laptop-style viewports,
  - reproduce the issue across task views and identify whether the problem is in shared dialog chrome or activity-specific modal layout,
  - harden the modal layout so create/detail/edit flows remain reachable on shorter screens without changing existing modal-first behavior.
- Risks:
  - layout fixes must preserve the current desktop modal composition and should not regress task action placement or wide-screen spacing,
  - overflow fixes need to keep internal panes scrollable without re-enabling background page scroll behind the modal,
  - task views share modal entry points, so a detail-modal fix must stay compatible with list, board, calendar, and timeline launch paths.
- Verification:
  - focused Vitest coverage for activity task modal layout constraints,
  - browser reproduction on `activity/task` with short-height viewport checks for create/detail modals,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - browser automation reproduced the detail modal issue at `1280x600`: the modal panel respects `max-height`, but the left/right internal panes still measure taller than the visible panel and do not gain scroll,
  - root cause was missing `min-h-0` constraints in the nested flex layout of `TaskDetailModal`, which prevented the overflow regions from shrinking inside the capped dialog,
  - `TaskDetailModal` now constrains the panel shell, split body, and both scroll panes so shorter screens can scroll the sidebar instead of clipping the lower sections,
  - focused Vitest coverage passed for the modal layout guard, `npm exec tsc --noEmit --pretty false` passed, and browser automation confirmed `Create Task` stays visible across list/board/calendar/timeline while the detail modal sidebar becomes scrollable on `1280x600`.

### 2026-04-13 - Activity member focus filter for dashboard and team task views
- Status: implemented
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
  - add a `member_user_id` filter so team-oriented Activity surfaces can focus on one member at a time,
  - apply the filter across `Activity Dashboard` department analytics and `Task Management` team scope instead of only filtering visible rows,
  - keep dashboard cards, lists, board/calendar/timeline data, focus insight, and export aligned to the same filtered dataset,
  - support the requested matching logic where a task counts if the selected member is the creator/owner, a participant, or both.
- Risks:
  - invalid member selections can leave stale filters behind after BU, department, or scope changes unless the backend sanitizes them,
  - task-management scope rules must stay intact so the member filter does not widen visibility outside the current department context,
  - dashboard and export can drift if they do not share the same member-filter contract.
- Verification:
  - `php artisan test tests/Feature/Modules/Activity/ActivityMemberFocusFilterTest.php`,
  - `npm exec vitest run tests/React/Pages/Activity/Dashboard.test.tsx tests/React/Pages/Activity/ActivityDashboard.test.tsx tests/React/Components/Activity/ActivityDataTableExport.test.tsx --runInBand`,
  - `vendor/bin/pint --dirty`,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - user clarified the original ask is not a cashflow-style period filter; the real need is filtering team views by one specific member so supervisors can isolate one person's work,
  - approved behavior is to filter all data, not only the visible list,
  - user approved direct implementation after sub-agent spec review without another manual spec review step,
  - task management now exposes a team-member filter only in `scope=department`, sanitizes invalid member ids from active `user_business_units` assignments, and applies the focused member as an additive predicate on top of the existing team-scope visibility rule,
  - activity dashboard now exposes a department-member filter that only affects department datasets, keeps personal and executive data unchanged, and forwards the active member filter through the dashboard export action,
  - task-management export now forwards the focused member for `scope=department` while preserving the existing `scope=my` export semantics,
  - follow-up hardening closed the reviewer gaps by ignoring malformed `member_user_id` payloads, excluding inactive users from member options, reusing the shared member-focus helper in export, and returning an empty member list when BU or department context is missing,
  - dashboard mode switching now clears focused-member state by refreshing the cached department dataset, so returning to `Department` no longer reuses stale filtered cards or exports,
  - focused backend coverage, focused frontend coverage, `vendor/bin/pint --dirty`, and `npm exec tsc --noEmit --pretty false` all passed in this workspace,
  - residual non-blocking risk: Headless UI still emits a test-environment warning about `Element.prototype.getAnimations`, but the relevant React tests pass and the warning predates the feature logic itself.

### 2026-04-06 - Activity Admin total hours float precision cleanup
- Status: implemented
- Owner: PM Agent
- Delegates: `@coder_backend`, `@reviewer`
- Scope:
  - investigate why `Total Jam` on `Activity Admin` can render long floating-point tails such as `33512.299999999996`,
  - round the aggregated summary hours after department-level totals are summed so Inertia props stay presentation-safe,
  - add focused regression coverage for classic `0.1 + 0.2` style hour aggregation.
- Risks:
  - the fix must preserve the existing one-decimal dashboard contract for hours instead of truncating useful precision,
  - summary rounding should happen after aggregation so department cards and summary cards stay mathematically aligned.
- Verification:
  - `php artisan test tests/Feature/Activity/ActivityAdminParentBusinessUnitScopeTest.php --filter=parent_business_unit_dashboard_rounds_total_hours_summary_after_aggregating_departments`,
  - `php artisan test tests/Feature/Activity/ActivityAdminParentBusinessUnitScopeTest.php tests/Feature/Activity/ActivityAdminAccessTest.php tests/Feature/Core/NavigationTopManagementTest.php tests/Feature/Core/SuperAdminBusinessUnitSwitchTest.php`,
  - `vendor/bin/pint --dirty`.
- Notes:
  - root cause investigation found `ActivityAdminController` already rounded each department's `total_hours`, but `buSummary.total_hours` used a raw `array_sum`, which let PHP float precision leak into the UI,
  - the summary now rounds the post-sum value to one decimal place so values like `0.1 + 0.2` are emitted as `0.3` instead of `0.30000000000000004`.

### 2026-04-06 - Activity Admin stale department filter after BU switch
- Status: implemented
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
  - investigate why switching from a parent BU view to a child BU like `WNS` can leave `Activity Admin` empty even though the child BU has data,
  - ignore stale `department_id` query params that no longer belong to the newly selected BU scope,
  - keep the department dropdown state synchronized with sanitized backend filters after a BU switch.
- Risks:
  - filter sanitation must not discard valid department selections inside the active BU,
  - backend and frontend filter state must stay aligned so the page does not show data for one scope while the select still displays another.
- Verification:
  - `php artisan test tests/Feature/Activity/ActivityAdminParentBusinessUnitScopeTest.php`,
  - `npm exec tsc --noEmit --pretty false`,
  - `vendor/bin/pint --dirty`.
- Notes:
  - root cause investigation found the BU switch preserved the previous page URL, including `department_id` from the old BU scope, and `ActivityAdminController` treated that stale department as an active filter,
  - the dashboard and export endpoints now validate `department_id` against the departments available in the current BU scope and fall back to `null` when it no longer matches,
  - the Activity Admin dashboard page now re-syncs its local department filter state from the sanitized Inertia props so the dropdown does not drift after a switch.

### 2026-04-06 - Activity Admin parent business unit roll-up scope
- Status: implemented
- Owner: PM Agent
- Delegates: `@coder_backend`, `@reviewer`
- Scope:
  - investigate why `Activity Admin` shows empty data when the active BU is the parent holding `WG`,
  - make parent or holding BU scope include descendant business units across dashboard, department detail, task detail, and export,
  - add focused regression coverage for parent-BU roll-up behavior.
- Risks:
  - parent-BU roll-up must not break existing child-BU views that should stay scoped to the selected BU only,
  - detail pages and export must stay aligned with dashboard scope so users do not see clickable items that later 403 or empty exports.
- Verification:
  - `php artisan test tests/Feature/Activity/ActivityAdminParentBusinessUnitScopeTest.php`,
  - `php artisan test tests/Feature/Activity/ActivityAdminAccessTest.php tests/Feature/Core/NavigationTopManagementTest.php`,
  - `vendor/bin/pint --dirty`.
- Notes:
  - root cause investigation found `ActivityAdminController` queried only `session('current_business_unit_id')`, so selecting `WG` ignored child BU activity stored under `WNS`, `MRP`, `TEE`, and other descendants,
  - `ActivityAdminController` now resolves a BU scope from the selected BU plus descendants and reuses it for dashboard, detail pages, backdate queues, and export,
  - new regression coverage proves parent-BU users can see child-BU totals and open child department and task detail pages from the aggregated dashboard.

### 2026-04-06 - Super admin business unit switch fallback to WG
- Status: implemented
- Owner: PM Agent
- Delegates: `@coder_backend`, `@reviewer`
- Scope:
  - investigate why the business unit switcher shows success for `TEE` but the next page render returns `super@werkudara.com` to `WG`,
  - fix the session guard so super admins can stay in business units whose `logo` value is intentionally `null`,
  - add focused regression coverage for the switch-followed-by-next-request flow.
- Risks:
  - the guard must still bootstrap missing BU session context on login or expired sessions,
  - `null` logos must be treated as valid state without hiding genuinely missing session keys.
- Verification:
  - focused PHPUnit coverage for super admin BU switching with a logo-less target BU,
  - `vendor/bin/pint --dirty`.
- Notes:
  - root cause investigation found `EnsureBusinessUnitSelected` used a truthy logo check for super admins, so switching to a BU like `TEE` with `logo = null` looked like an invalid session and reinitialized the context from the primary BU `WG`,
  - the guard now checks whether the session key exists instead of whether the logo value is truthy, preserving intentional `null` logos and the selected BU context across the next request.

### 2026-03-31 - Docs help changelog v3.0.2 article update
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_frontend`, `@reviewer`
- Scope:
  - add a new `docs-help` changelog article for `v3.0.2`,
  - summarize user-facing updates shipped on 2026-03-30 and 2026-03-31 in help-center language instead of raw commit language,
  - preserve the existing bilingual EN/ID article pattern and deep-link behavior used by other changelog pages.
- Risks:
  - changelog copy can drift from the actual shipped behavior if recent commits are summarized too loosely,
  - bilingual markup must follow the existing `lang-id` and `lang-en` span pattern or the article language toggle will show mixed content,
  - article ordering should keep the newest changelog discoverable without breaking existing docs-help hashes.
- Verification:
  - reviewer validation against `docs/coding_standards.json`,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - user requested a new article matching `http://localhost:8000/docs-help#article/changelog-v3-0-1` but for version `3.0.2`,
  - source material is the user-facing commits shipped on 2026-03-30 and 2026-03-31,
  - docs-help now includes `#article/changelog-v3-0-2` with bilingual EN/ID content grouped into workflow, access, navigation, and reporting updates,
  - focused verification passed with `npm exec tsc --noEmit --pretty false`,
  - reviewer validation found no standards issues, and self-review found no docs-help rendering or hash-routing regressions in the touched data entry.

### 2026-03-31 - Activity dashboard and export report detail uplift
- Status: implemented
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
  - enrich the activity dashboard report insight while preserving `Team Total Hours` as a workload and overwork signal,
  - upgrade activity export workbook with `Description`, generated `Summary`, category plus subcategory breakdown, counts, percentages, and a pivot-friendly raw sheet,
  - keep dashboard and workbook summary metrics aligned so users do not see conflicting report stories.
- Risks:
  - report improvements must not remove the existing time-management journey centered on total tracked hours,
  - workbook formatting can become presentation-heavy and hurt pivotability if the raw sheet is not kept flat,
  - dashboard and export metrics may drift if summary aggregation is duplicated across surfaces.
- Verification:
  - focused PHPUnit coverage for workbook detail, summary, and category/subcategory breakdown behavior,
  - focused Vitest coverage for Hybrid A focus rendering and export CTA behavior,
  - `vendor/bin/pint --dirty`,
  - `npm exec tsc --noEmit --pretty false`,
  - `npm run build`.
- Outcome notes:
  - dashboard now keeps the hours-first KPI scan while the right panel shows top category, top subcategory, count, percent, visual distribution, and a reusable focus breakdown list,
  - export now ships `Detail`, `Ringkasan`, `Breakdown Kategori`, and `Data Mentah` sheets with description, generated activity summary, category plus subcategory metrics, and a flat raw sheet for pivot workflows,
  - backend aggregation for dashboard and export is shared through `ActivityReportAggregationService` so the two surfaces do not drift,
  - TS casing drift on the shared button component was normalized by renaming `Button.tsx` to `button.tsx` so full-project type checking can pass in this workspace.
  - focused Vitest coverage if dashboard report rendering contracts change,
  - `vendor/bin/pint --dirty`,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - user feedback asks for more detailed report output on `http://localhost:8000/activity/dashboard`,
  - approved design direction is Hybrid A: preserve hours/time-management cards, deepen report detail mainly in the focus/insight area, and align that view with a richer export workbook,
  - export requirements include category + subcategory, counts + percentages, `Description`, more detailed `Summary`, and data that remains easy to pivot for further processing.

### 2026-03-31 - Purchase request supporting document access for creator and approvers
- Status: completed
- Owner: PM Agent
- Delegates: `@coder_backend`, `@coder_frontend`, `@reviewer`
- Scope:
  - investigate why supporting documents uploaded on purchase requests return 403 for the request creator,
  - replace raw `/storage/...` supporting document links with an authenticated application route,
  - authorize supporting document access for the PR creator and assigned approvers, and add focused regression coverage.
- Risks:
  - document access must not leak to unrelated users in the same business unit even though the PR detail page is broadly visible,
  - route changes must preserve inline view and download behavior for supported files.
- Verification:
  - focused PHPUnit coverage for purchase request supporting document access,
  - focused frontend coverage if link wiring changes require it,
  - `vendor/bin/pint --dirty`,
  - `npm exec tsc --noEmit --pretty false`.
- Notes:
  - user reported `403 Forbidden` while opening a supporting document on PR `PR.WNS/202603/024`,
  - initial tracing shows the PR show page still links directly to `/storage/{path}` instead of an authenticated controller route.
  - supporting document access now uses authenticated PR routes for inline view and download instead of raw `/storage/...` links,
  - backend authorization now allows the PR creator and assigned approvers, including approvers acting from an ancestor business unit session,
  - PR show props now expose supporting document capability so unrelated same-BU viewers no longer see document actions they cannot open,
  - focused PHPUnit coverage, focused React coverage, `vendor/bin/pint --dirty`, and `npm exec tsc --noEmit --pretty false` all passed after reviewer feedback was addressed.

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
