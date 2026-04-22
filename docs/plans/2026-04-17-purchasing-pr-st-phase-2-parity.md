# Purchasing PR and ST Phase 2 Parity Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bring `Purchase Request` and `Stock Request` to end-to-end parity across user-facing request lifecycle surfaces while preserving separate module homes, route families, and business concepts.

**Architecture:** Implement parity backend-first. Freeze route names, authorization props, approval-context props, redirect homes, and artifact-access behavior in the `StockRequest` module to match the maturity of `PurchaseRequest`, then consume that contract in the `StockRequest` detail page and supporting tests. Keep `PR` and `ST` controllers/pages separate; parity is behavioral, not a generic abstraction refactor.

**Parity Guardrail:** This plan must protect both directions of parity. `ST` must catch up to the approved `PR` baseline for phase-2 capabilities, and touched `PR` surfaces used as the baseline must be covered by no-regression assertions so parity work does not silently introduce new drift.

**Governance Gate:** `docs/exec_plans.md` must stay updated during execution, not only at the end. Do not start implementation chunks unless the active task remains recorded there as `in_progress`, and capture any scope shift during the same working session.

**Tech Stack:** Laravel 12, PHP 8.2, Inertia.js v2, React 19, TypeScript, PHPUnit 11, Vitest, Playwright/browser QA

---

## Chunk 1: Backend Contract Freeze

### Task 1: Capture parity-critical backend expectations in tests

**Files:**
- Modify: `I:\Project\Numbering\tests\Feature\Core\SharedRouteContractTest.php`
- Create: `I:\Project\Numbering\tests\Feature\Modules\StockRequest\StockRequestShowAuthorizationParityTest.php`
- Create: `I:\Project\Numbering\tests\Feature\Modules\StockRequest\StockRequestResendApprovalEmailTest.php`
- Create: `I:\Project\Numbering\tests\Feature\Modules\StockRequest\StockRequestActionAuthorizationParityTest.php`
- Create: `I:\Project\Numbering\tests\Feature\Modules\StockRequest\StockApprovalRedirectParityTest.php`
- Reference: `I:\Project\Numbering\docs\superpowers\specs\2026-04-17-purchasing-pr-st-phase-2-parity-design.md`

- [ ] **Step 1: Write failing route and authorization tests**

Cover at minimum:
- `stock-requests.resend-approval-email` route registration and controller mapping
- `StockRequestController@showInertia` returns parity-grade `can` fields:
  - `edit`
  - `delete`
  - `void`
  - `resubmit`
  - `resendApprovalEmail`
  - `approve`
  - `reject`
  - `downloadPdf`
  - `markOfflineApproved`
  - `offlineApprovalDocument`
- `StockApprovalController@show` returns canonical `approvalContext` fields:
  - `approvalId`
  - `canApprove`
  - `approvalStatus`
- authorization behavior for owner vs non-owner vs active approver
- authorization behavior for inactive approver
- stale or mismatched approval-context behavior
- resend-notification failure handling
- action-endpoint authorization parity for the routes behind exposed UI actions:
  - resend approval email
  - mark offline approved
  - approval process approve/reject
  - resubmit
  - void
  - download / artifact-access endpoints that are intentionally exposed in UI
- explicit no-regression assertions that the touched `PR` baseline route names and approval-context shape remain unchanged where parity work references them

- [ ] **Step 2: Run focused backend tests to verify they fail**

Run:
```powershell
php artisan test tests/Feature/Core/SharedRouteContractTest.php tests/Feature/Modules/StockRequest/StockRequestShowAuthorizationParityTest.php tests/Feature/Modules/StockRequest/StockRequestResendApprovalEmailTest.php tests/Feature/Modules/StockRequest/StockRequestActionAuthorizationParityTest.php tests/Feature/Modules/StockRequest/StockApprovalRedirectParityTest.php
```

Expected:
- failures for missing `stock-requests.resend-approval-email`
- failures for incomplete `ST` `can` contract
- failures where route or policy enforcement drifts from the visible `can` contract
- failures for incorrect approval redirect home

- [ ] **Step 3: Implement minimal backend parity contract**

Modify:
- `I:\Project\Numbering\routes\web.php`
- `I:\Project\Numbering\app\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController.php`
- `I:\Project\Numbering\app\Http\Controllers\Modules\Purchasing\StockRequest\StockApprovalController.php`

Implement:
- add `stock-requests.resend-approval-email`
- add controller action mirroring `PR` resend-email semantics but staying `ST`-specific
- expand `getShowAuthorization()` to expose full parity contract
- align action-endpoint authorization with the exposed `can` contract so UI and backend cannot drift
- ensure approval-context and redirect homes stay inside `stock-requests.*` / `stock-approvals.*`

- [ ] **Step 4: Run focused backend tests to verify they pass**

Run the same command from Step 2.

Expected:
- all focused backend parity tests pass

- [ ] **Step 5: Commit backend contract chunk**

```bash
git add routes/web.php tests/Feature/Core/SharedRouteContractTest.php tests/Feature/Modules/StockRequest/StockRequestShowAuthorizationParityTest.php tests/Feature/Modules/StockRequest/StockRequestResendApprovalEmailTest.php tests/Feature/Modules/StockRequest/StockRequestActionAuthorizationParityTest.php tests/Feature/Modules/StockRequest/StockApprovalRedirectParityTest.php app/Http/Controllers/Modules/Purchasing/StockRequest/StockRequestController.php app/Http/Controllers/Modules/Purchasing/StockRequest/StockApprovalController.php
git commit -m "feat: align stock request backend parity contract"
```

## Chunk 2: Artifact Access Hardening

### Task 2: Add contract-safe ST artifact access where UI exposes evidence

**Files:**
- Modify: `I:\Project\Numbering\routes\web.php`
- Modify: `I:\Project\Numbering\app\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController.php`
- Create: `I:\Project\Numbering\tests\Feature\Modules\StockRequest\StockRequestOfflineApprovalDocumentAccessTest.php`

- [ ] **Step 1: Write failing artifact-access tests**

Cover:
- authenticated access to `ST` offline approval evidence through an application route
- unauthorized access denied
- missing-file behavior returns controlled failure instead of raw storage leakage
- every exposed UI entry point for `ST` offline approval evidence resolves through that protected application route rather than direct storage

- [ ] **Step 2: Run focused artifact tests to verify they fail**

Run:
```powershell
php artisan test tests/Feature/Modules/StockRequest/StockRequestOfflineApprovalDocumentAccessTest.php
```

Expected:
- failure due to missing route or missing protected artifact handling

- [ ] **Step 3: Implement minimal ST artifact route and controller support**

Implement only what Phase 2 requires:
- keep `PR supporting document` and `ST evidence` as separate business concepts
- add authenticated route/controller method for `ST` offline approval evidence as the canonical access path for any exposed UI surface in this phase
- preserve module-specific naming and access rules

- [ ] **Step 4: Run focused artifact tests to verify they pass**

Run the same command from Step 2.

Expected:
- focused artifact access tests pass

- [ ] **Step 5: Commit artifact chunk**

```bash
git add routes/web.php app/Http/Controllers/Modules/Purchasing/StockRequest/StockRequestController.php tests/Feature/Modules/StockRequest/StockRequestOfflineApprovalDocumentAccessTest.php
git commit -m "feat: harden stock request evidence access"
```

## Chunk 3: Frontend ST Detail Parity

### Task 3: Upgrade the ST detail surface to consume the frozen backend contract

**Files:**
- Modify: `I:\Project\Numbering\resources\js\inertia\Pages\Purchasing\StockRequest\Show.tsx`
- Modify: `I:\Project\Numbering\resources\js\inertia\types\purchasing.ts`
- Create: `I:\Project\Numbering\tests\React\Pages\Purchasing\StockRequest\Show.test.tsx`
- Modify: `I:\Project\Numbering\tests\React\Pages\Purchasing\PurchaseRequest\Show.test.tsx`
- Reference: `I:\Project\Numbering\resources\js\inertia\Pages\Purchasing\PurchaseRequest\Show.tsx`

- [ ] **Step 1: Write failing React tests for ST detail parity**

Cover:
- render parity-grade owner actions from explicit `can` props
- render resend-email CTA when allowed
- approval-context back link uses `stock-approvals.index`
- explicit `approvalContext` field consumption for `approvalId`, `canApprove`, and `approvalStatus`
- approval actions respect active/processed state
- artifact link renders only when the backend contract supports it
- action loading/pending-state rendering
- hidden or disabled actions for unauthorized actors or invalid statuses
- safe rendering for stale or mismatched approval-context props
- no-regression assertions for `PR Show` behaviors used as the parity baseline when the ST page mirrors them

- [ ] **Step 2: Run focused React tests to verify they fail**

Run:
```powershell
npm exec vitest run tests/React/Pages/Purchasing/StockRequest/Show.test.tsx tests/React/Pages/Purchasing/PurchaseRequest/Show.test.tsx --runInBand
```

Expected:
- failures around missing CTA rendering and/or route-home mismatches

- [ ] **Step 3: Implement minimal ST show-page parity**

Use:
- `I:\Project\Numbering\resources\js\inertia\Pages\Purchasing\PurchaseRequest\Show.tsx`

Do:
- keep `ST` labels/routes/module home
- consume backend `can` and `approvalContext` explicitly
- add resend approval email flow if exposed in page scope
- fix approval-context back-home and stale-state rendering
- expose ST-specific artifact actions only through supported routes

- [ ] **Step 4: Run focused React tests to verify they pass**

Run the same command from Step 2.

Expected:
- all focused `ST Show` parity tests pass

- [ ] **Step 5: Run TypeScript verification for touched frontend contract**

Run:
```powershell
npm exec tsc --noEmit --pretty false
```

Expected:
- zero type errors

- [ ] **Step 6: Commit frontend chunk**

```bash
git add resources/js/inertia/Pages/Purchasing/StockRequest/Show.tsx resources/js/inertia/types/purchasing.ts tests/React/Pages/Purchasing/StockRequest/Show.test.tsx tests/React/Pages/Purchasing/PurchaseRequest/Show.test.tsx
git commit -m "feat: align stock request detail parity with purchase requests"
```

## Chunk 4: Verification and Review

### Task 4: Run standards verification and self-review

**Files:**
- Modify if needed after review: files from Chunks 1-3
- Reference: `I:\Project\Numbering\docs\coding_standards.json`

- [ ] **Step 1: Run focused backend parity suite**

Run:
```powershell
php artisan test tests/Feature/Core/SharedRouteContractTest.php tests/Feature/Modules/StockRequest/StockRequestShowAuthorizationParityTest.php tests/Feature/Modules/StockRequest/StockRequestResendApprovalEmailTest.php tests/Feature/Modules/StockRequest/StockRequestActionAuthorizationParityTest.php tests/Feature/Modules/StockRequest/StockApprovalRedirectParityTest.php tests/Feature/Modules/StockRequest/StockRequestOfflineApprovalDocumentAccessTest.php
```

Expected:
- all focused backend parity tests pass

- [ ] **Step 2: Run focused frontend parity suite**

Run:
```powershell
npm exec vitest run tests/React/Pages/Purchasing/StockRequest/Show.test.tsx tests/React/Pages/Purchasing/PurchaseRequest/Show.test.tsx --runInBand
```

Expected:
- all focused frontend parity tests pass

- [ ] **Step 3: Run formatting and type verification**

Run:
```powershell
vendor/bin/pint --dirty
npm exec tsc --noEmit --pretty false
```

Expected:
- formatting complete
- no type errors

- [ ] **Step 4: Review touched files against standards**

Check:
- no cross-module route leakage
- no UI action without backend support
- no direct storage exposure for new ST evidence routes
- `SalesCrm` remains untouched
- touched `PR` baseline tests still pass so parity is bidirectional rather than ST-only

- [ ] **Step 5: Run reviewer-agent pass against the implemented multi-file change**

Check with `@reviewer` against:
- `docs/coding_standards.json`
- backend/frontend boundary compliance
- missing route/payload/test coverage
- parity drift left between `PR` and `ST`

- [ ] **Step 6: Update execution tracking if scope or intentional divergences changed**

Modify:
- `I:\Project\Numbering\docs\exec_plans.md`

Capture:
- any scope adjustment discovered during implementation
- any intentional parity divergence left after review/QA

- [ ] **Step 7: Commit review fixes if needed**

```bash
git add -A
git commit -m "chore: address purchasing parity review fixes"
```

## Chunk 5: Browser QA

### Task 5: Validate end-to-end parity in the browser

**Files:**
- No code files required unless QA finds issues
- Reference: `I:\Project\Numbering\docs\superpowers\specs\2026-04-17-purchasing-pr-st-phase-2-parity-design.md`

- [ ] **Step 1: Build assets if frontend files changed**

Run:
```powershell
npm run build
```

Expected:
- build succeeds

- [ ] **Step 2: Log in with the provided owner account**

Use:
- email: `pramuji@werkudara.com`
- password: `werkudara88`

Verify:
- authenticated dashboard loads

- [ ] **Step 2a: Confirm execution tracking is still current before QA**

Check:
- `docs/exec_plans.md` still shows the parity task as active
- any scope adjustment discovered during implementation has already been recorded

- [ ] **Step 3: Exercise owner-side PR and ST detail journeys**

Validate where data/state exists:
- compare index/list affordances that lead into request detail for both modules
- compare create surface affordances and request submission entry points for both modules
- compare edit entry affordances when a request is editable
- confirm create/edit/index entry points for both modules still resolve to their own module homes with no cross-route leakage
- open PR detail
- open ST detail
- compare action toolbar parity
- compare offline-approval affordance
- compare PDF/document affordances
- compare rejected-state resubmit behavior if available
- compare in-approval resend-email behavior if available
- validate denied-access or missing-action states where the user should not see or trigger a lifecycle action

- [ ] **Step 4: Exercise approval-context journey if state exists**

Validate:
- approval detail opens in the correct module home
- back navigation returns to the correct approval index
- stale or already-processed states fail safely
- inactive approver path does not expose active approval controls

- [ ] **Step 5: If live data is missing, create or supplement evidence instead of silently skipping**

If the provided owner account lacks a needed owner-side state:
- create the missing draft/submitted/editable owner request in-browser when it is safe and non-destructive
- re-run the relevant owner journey on the created data

If approver-only or forbidden-state branches cannot be exercised with the provided owner account alone:
- supplement with focused automated evidence from the parity tests
- mark that branch as not owner-live-validated
- do not treat documentation-only fallback as equivalent to execution

- [ ] **Step 6: Document any intentional divergences explicitly**

If any parity item remains intentionally different after implementation:
- record it in the implementation summary and `docs/exec_plans.md`
- state why it remains different
- confirm it is an intentional product distinction rather than an implementation gap

- [ ] **Step 7: Update `docs/exec_plans.md` to final status at closure**

Before finalizing:
- set the active parity task to its final status
- add concise closure notes covering implementation, reviewer result, and QA coverage/limitations

- [ ] **Step 8: Commit only if QA-driven fixes were required**

```bash
git add -A
git commit -m "fix: address browser QA issues in purchasing parity"
```
