# Purchasing PR and ST Phase 2 Parity Design

**Date:** 2026-04-17
**Status:** Draft for review
**Owner:** PM Agent

## Goal

Bring `Purchase Request` (`PR`) and `Stock Request` (`ST`) to end-to-end user-facing parity across the Purchasing module so they feel like equally mature sibling workflows.

This phase is not a generic refactor. The module homes stay separate:
- `PR` remains `PR`
- `ST` remains `ST`
- routes, labels, documents, copy, and controller ownership remain specific to each module

Parity means the same class of user-facing capability should exist on both sides unless there is an explicit product reason for divergence.

## Approved Product Direction

The approved direction is:
- treat `PR` as the current maturity baseline for request lifecycle behavior
- close the capability gap in `ST` until user-facing behavior is effectively `1:1`
- keep each module in its own "house" rather than collapsing them into one shared generic request implementation
- evaluate parity across the surfaces users touch end to end, not just one page or one button

This phase should make the Purchasing module feel coherent and intentional to real users.

## Scope

Phase 2 covers the end-to-end surfaces users and approvers touch:
- request creation
- request editing
- request detail/show pages
- owner actions on detail pages
- approval actions and approval-context journeys
- document access and document evidence behavior
- PDF/export-style request artifact access
- approval resend and retry-style owner actions
- index/list affordances that lead into those journeys
- authenticated browser journeys for the provided real user account

Admin task management pages may consume some of the same request contracts, but they are secondary surfaces in this phase. The primary target is the direct request lifecycle experience.

## Canonical Parity Principle

For any request-lifecycle capability that already exists on `PR`, Phase 2 must decide one of two outcomes for `ST`:
- `match it`
- or `document a product-approved reason it should differ`

Silent drift is not allowed.

The inverse also applies:
- if `ST` already supports a lifecycle capability in a more complete way than `PR`, that drift should be surfaced and either preserved intentionally or closed back toward shared behavior

## Current State Summary

### What is already aligned

Both modules already have these broad surfaces:
- authenticated list/index pages
- authenticated create and edit form pages
- detail/show pages
- resubmit and void actions
- offline approval action handlers at the controller layer
- public PDF/download routes
- approval models and approval processing flows

### Known parity gaps already confirmed

1. `PR` has `resend approval email`; `ST` currently does not expose equivalent route, controller action, permission, or UI
2. `ST` show-page UI renders offline approval affordances, but backend `can` payload does not currently expose parity-grade authorization fields like `markOfflineApproved`, `approve`, or `reject`
3. `ST` approval-context behavior is less mature than `PR` because the page relies on a thinner permission/approval contract
4. `ST` show page has at least one route-home mismatch in approval context, where the back path points to `approvals.index` instead of `stock-approvals.index`
5. `PR` has richer document handling around supporting documents and explicit access control, while `ST` currently uses a thinner surface without equivalent protected artifact handling for its own evidence artifacts
6. `PR` has a more complete owner-action toolbar contract, while `ST` still relies on partial UI logic and weaker backend parity

These are starting examples, not the full gap list. The reviewer pass should treat parity inventory as part of the review objective.

## Frozen Phase-2 Parity Matrix

This matrix freezes the required parity scope for this phase so implementation does not expand into an open-ended refactor.

### Must match in Phase 2

| Area | PR baseline | ST Phase-2 target |
| --- | --- | --- |
| Detail-page owner toolbar | Mature and contract-driven | Match capability depth and loading/visibility quality |
| Detail-page approval actions | Mature and contract-driven | Match capability depth and approval-context handling |
| `can` payload completeness | Rich | Match key lifecycle fields |
| Resubmit | Present | Present and contract-safe |
| Void | Present | Present and contract-safe |
| Offline approval | Present | Present and contract-safe |
| Resend approval email | Present | Add parity capability |
| Correct module-home redirects | Present | Match |
| Request PDF/document affordances | Mature | Match maturity for ST-specific artifacts |
| Negative-path authorization behavior | Must be explicit | Must be explicit |

### Explicitly deferred from Phase 2

| Area | Deferred reason |
| --- | --- |
| Generic shared purchasing-request abstraction | Too broad and risky for a parity pass |
| Admin task UX redesign beyond parity-driven fixes | Secondary to direct request lifecycle maturity |
| New business concepts unique to ST that do not already exist in user-facing flow | Out of scope unless needed to preserve parity safely |

### Stop condition

Phase 2 is complete when:
- every item in the `Must match in Phase 2` table is covered by implementation and verification,
- no known end-to-end request-lifecycle gap remains between `PR` and `ST` for the approved surfaces,
- and any remaining difference is explicitly documented as intentional rather than accidental drift.

## Canonical End-to-End Surfaces

Phase 2 should normalize parity across these journeys.

### 1. Requestor lifecycle

For both `PR` and `ST`, the request owner should have parity in:
- create request
- edit request when editable
- view detail page
- void request when allowed
- resubmit rejected request
- mark request as offline approved when allowed
- re-trigger approval communication when the request is in active approval and a pending approver exists
- access the request PDF/document artifact from the detail surface

### 2. Approver lifecycle

For both `PR` and `ST`, the active approver should have parity in:
- open approval-specific detail context
- see whether the approval is pending vs already processed
- approve
- reject
- add notes
- return to the correct approval list for that module

### 3. Evidence and documents

For both `PR` and `ST`, request evidence access should be consistent in concept:
- uploaded artifacts must not rely on weaker direct-storage behavior if the sibling module uses protected application routes
- offline approval evidence should be reachable through a clear, permission-respecting path
- document actions shown in the UI must correspond to routes that actually exist and are authorized

Important parity rule:
- parity does **not** mean `ST` must inherit the exact `PR` supporting-document field model
- `PR supporting document` and `ST offline approval evidence` are different business concepts
- this phase should align maturity and access safety, not force identical artifact types where the product model is different

### 4. Page-state contracts

For both `PR` and `ST`, detail pages should receive:
- a complete `can` contract for all relevant actions
- approval-context props when being viewed from the approval flow
- stable route names and navigation homes that stay inside the right module family

## Recommended Technical Approach

### Option A: UI-only parity patching

Add missing buttons and patch local page logic where gaps are visible.

Trade-offs:
- fast short-term
- high risk of hidden drift because backend routes, permissions, and approval contracts remain inconsistent
- likely to regress in browser QA

### Option B: Module contract parity, then UI parity

Treat `PR` as the baseline contract and close the gap in `ST` across:
- routes
- controller actions
- authorization payloads
- page props
- detail-page UI
- focused tests

Trade-offs:
- slightly broader than UI-only changes
- much safer for end-to-end behavior
- best fit for the user-approved phase-2 maturity goal

### Option C: Shared abstraction refactor

Create a generic purchasing-request base layer and migrate both modules.

Trade-offs:
- attractive long-term
- too large and risky for this phase
- likely to mix unrelated refactoring into a parity-focused pass

### Approved implementation approach

Use **Option B**.

Phase 2 should align contracts first and then consume those contracts in the UI. This avoids false parity where buttons exist but the backend cannot support them correctly.

## Backend Design

### Route parity

Review and align request-lifecycle routes between `purchase-requests.*` and `stock-requests.*`.

Required outcome:
- every end-to-end lifecycle action exposed to `PR` users should have an `ST` sibling route unless there is an explicit approved exception
- route homes must stay inside their own families, such as `purchase-requests.*`, `approvals.*`, `stock-requests.*`, and `stock-approvals.*`

At minimum, this phase should evaluate and likely align:
- `show`
- `edit`
- `update`
- `void`
- `resubmit`
- `mark-offline-approved`
- `resend-approval-email` for `ST`
- any authenticated document-serving route equivalents needed for parity

Route-home correctness is part of parity:
- `ST` request and approval actions must redirect back into `stock-requests.*` or `stock-approvals.*` homes
- `PR` request and approval actions must redirect back into `purchase-requests.*` or `approvals.*` homes

### Backend-first contract checklist

Before frontend parity work starts, backend ownership must freeze and confirm this checklist:
- exact named routes for every action used by the page
- Inertia prop schema for `show` surfaces
- canonical `can` fields
- canonical `approvalContext` fields
- artifact-access routes and response modes
- redirect targets after each action

Frontend implementation should not begin on parity-critical actions until this contract checklist is settled.

### Controller action parity

`StockRequestController` should be brought to parity with the lifecycle expectations already embodied in `PurchaseRequestController`.

Expected additions or hardening include:
- resend approval email action for the current pending `ST` approver
- parity-grade show authorization payload generation
- parity-grade document access patterns where `PR` already has a protected route model
- approval-context correctness for detail rendering
- redirect correctness inside `StockApprovalController` and any `ST` lifecycle post-action responses

Important rule:
- implementation can reuse patterns and helper shapes from `PR`, but `ST` keeps its own controller, routes, logging, labels, and notifications

### Authorization parity

Both detail pages should receive a comparable `can` contract shape.

Parity target fields include:
- `edit`
- `delete`
- `void`
- `resubmit`
- `resendApprovalEmail`
- `approve`
- `reject`
- `downloadPdf`
- `markOfflineApproved`
- module-specific document access flags where needed

Behavioral rule:
- the page should not need to infer missing permissions from status alone when the sibling module already gets an explicit backend contract

### Lifecycle transition matrix

This matrix defines the expected authority and state rules for parity-critical actions in this phase.

| Action | Actor | Allowed statuses | Denied statuses | Success contract | Failure contract |
| --- | --- | --- | --- | --- | --- |
| `edit` | owner | editable states only | approved, voided, and any non-editable state | redirect to module-specific edit form or render edit surface | `403` for wrong actor, redirect with error for invalid state |
| `resubmit` | owner | `rejected` | any other status | redirect to module-specific show page with success flash and reset workflow contract | redirect back with error |
| `void` | owner or authorized elevated actor | module-defined voidable states | approved, voided, and any non-voidable state | redirect to module-specific home/show contract with success flash | `403` for wrong actor, redirect back with error for invalid state |
| `mark offline approved` | owner | `submitted`, `in_approval` | draft, approved, rejected, voided | redirect to module-specific show page with success flash and persisted evidence | `403` for wrong actor, redirect back with validation or state error |
| `resend approval email` | owner | request in active approval with current pending approver | any status without current pending approver | redirect back or module-specific show page with success flash after attempting notification send | `403` for wrong actor, redirect back with error if state invalid or send fails |
| `approve` | active approver only | pending approval step only | processed, stale, mismatched, or inactive approval step | redirect to correct module-specific approval/request home with success flash | `403` or redirect with error, depending on invalid actor vs stale/invalid context |
| `reject` | active approver only | pending approval step only | processed, stale, mismatched, or inactive approval step | redirect to correct module-specific approval/request home with success flash | `403` or redirect with error, depending on invalid actor vs stale/invalid context |

Implementation may preserve module-specific copy and logging, but it should not diverge from these actor/state rules without an explicit product decision.

### Approval-context parity

Approval-driven detail views for `PR` and `ST` should both support:
- current approval identity
- pending vs processed state
- can-current-user-act
- correct back navigation
- correct approve/reject destination

This phase should avoid having one module depend on page-local heuristics while the sibling module gets a complete backend approval context.

Controller-level approval redirects are included in this parity target, not only page-level back buttons.

### Invalid and stale approval-context behavior

Both modules should explicitly define behavior for:
- missing approval context on an approval-only action
- approval already processed
- approval belongs to a different request than the page/request being rendered
- user is not the active approver for the current step
- stale bookmarked approval links after workflow reset or offline approval

Expected rule:
- invalid actor should return `403` where appropriate,
- stale or invalid lifecycle context should redirect to the correct module home with an explanatory flash instead of failing silently,
- and the page should not leave the user on a broken action state.

### Document and evidence parity

`PR` already has stronger patterns for serving documents through application routes.

Phase 2 should inventory and resolve whether `ST` needs:
- equivalent authenticated routes for any user-facing evidence artifacts
- equivalent download vs inline-view behavior
- equivalent not-found and unauthorized handling

If `ST` intentionally does not have a supporting-document concept equivalent to `PR`, the implementation must preserve that difference while still hardening `ST`-specific artifact access such as offline approval evidence or other request attachments already present in the module.

### Artifact access matrix

Parity for artifacts is defined by access safety and clarity, not by forcing identical fields.

| Module | Artifact | Route family | Access model | Response mode |
| --- | --- | --- | --- | --- |
| PR | request PDF | `purchase-requests.pdf-public` / `purchase-requests.download-pdf` | preserve existing module contract unless a safer authenticated route is already required by current behavior | inline/public-view for browser render, download for explicit download |
| PR | supporting document | authenticated `purchase-requests.supporting-document*` routes | permission-checked application route | inline or download depending on action |
| PR | offline approval evidence | existing PR-specific route or authenticated artifact path if exposed in UI | permission-checked application route | inline view or download as designed |
| ST | request PDF | `stock-requests.pdf-public` / `stock-requests.download-pdf` | preserve existing module contract unless parity work requires clearer authenticated behavior | inline/public-view for browser render, download for explicit download |
| ST | offline approval evidence | ST-specific authenticated artifact route if shown in UI | permission-checked application route | inline or download depending on action |

Rule:
- any artifact exposed from a detail page must have a concrete route, access rule, and response mode chosen explicitly during implementation.

## Frontend Design

### Detail page parity

`resources/js/inertia/Pages/Purchasing/PurchaseRequest/Show.tsx` is the maturity reference for:
- action-toolbar completeness
- modal affordances
- approval action visibility
- disabled/loading states
- document access affordances
- status-driven empty and alert states

`resources/js/inertia/Pages/Purchasing/StockRequest/Show.tsx` should match this level of completeness while staying `ST`-specific in:
- labels
- route names
- item presentation
- stock-specific copy

### Navigation-home correctness

The UI must never jump across module families incorrectly.

Examples:
- `PR` approval context returns to `approvals.index`
- `ST` approval context returns to `stock-approvals.index`

This principle applies to:
- back links
- post-action redirects
- fallback routes

### Action-state parity

For both modules:
- loading and pending states should be visible for async actions
- action buttons should only render when the backend says they are allowed
- request owner actions and approval actions should not conflict visually or logically

### Artifact access parity

If a document or evidence action is shown:
- the destination route must exist
- the user must be allowed to access it
- the browser journey should succeed without storage-level leakage or route mismatch

## Data and Contract Rules

### Page prop principle

Both modules should follow the same contract philosophy:
- backend owns truth for capability and approval context
- frontend consumes explicit props instead of rebuilding business rules ad hoc

### Contract-safe differences that remain allowed

These may differ between `PR` and `ST` as long as the user-facing maturity remains equal:
- field names specific to the business object
- document types and upload fields
- item payload shape
- internal approval model naming
- service classes and notification classes

### Differences that should not drift anymore

These should be aligned in principle:
- owner action availability
- approval action availability
- approval journey clarity
- offline approval journey maturity
- resend approval communication support
- route-home correctness
- protected artifact access expectations

## Testing Strategy

### Backend tests

Add focused feature coverage for:
- `ST` route parity with lifecycle actions expected by the detail UI
- `ST` authorization payload parity on the show page
- `ST` resend approval email behavior and guards
- `ST` approval-context rendering behavior where relevant
- `ST` approval-controller redirect/home correctness after processing decisions
- document/evidence access behavior for any new authenticated routes
- parity-critical redirect or back-home behavior after request actions
- notification-send behavior and failure handling for any new resend-email action
- actor/status authorization matrix for owner, active approver, inactive approver, and unauthorized user
- stale approval-context and mismatched request/approval negative paths

### Frontend tests

Add focused React coverage for:
- `ST Show` action toolbar parity
- `ST Show` approval actions visibility and loading states
- `ST Show` resend approval email CTA rendering and firing if implemented in page scope
- route-home correctness in `ST` approval context
- artifact link rendering only when supported by props
- hidden or disabled action behavior for unauthorized actors and invalid statuses
- stale or processed approval-context rendering fallbacks

### Browser QA

Use Playwright with the provided user account to validate end-to-end journeys across both modules:
- create and open request detail
- owner action visibility
- offline approval journey
- resend approval communication journey where applicable
- approval entry and back navigation correctness
- PDF/document affordances

The browser QA goal is parity confidence, not just smoke coverage.

If the provided user account lacks one of the required permissions or request states for a target journey, QA should explicitly note the missing precondition instead of silently skipping it.

Because one real account cannot prove every actor/permission branch, browser QA for this phase should use:
- the provided real owner account for authenticated requestor journeys,
- plus seeded or existing request states that allow active-approver and denied-access checks where available,
- or explicit documentation of any role path that could not be exercised live in this workspace.

## Risks and Guardrails

- Do not "fake" parity by only adding frontend buttons without backend support
- Do not collapse `PR` and `ST` into a shared generic request abstraction in this phase
- Do not introduce cross-module route leakage such as `ST` returning to `PR` homes
- Do not widen document access while chasing parity
- Do not break existing `PR` behavior while aligning `ST`
- Do not reactivate unrelated deprecated modules or change dependencies

## Out of Scope

- a generic shared purchasing-request domain refactor
- admin-task UX redesign beyond parity-driven fixes needed to support request lifecycle behavior
- unrelated reporting or dashboard enhancements
- dependency changes

## Implementation Handoff

Once this spec is reviewed and approved:
- write an implementation plan that splits backend and frontend ownership cleanly
- implement parity changes in the appropriate module homes
- run reviewer pass against `docs/coding_standards.json`
- run browser QA with the provided account before claiming completion
