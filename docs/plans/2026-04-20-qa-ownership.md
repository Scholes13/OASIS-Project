# QA Ownership Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Introduce a dedicated `@qa` ownership lane into the repo harness documentation so browser-level validation and runtime evidence become explicit workflow responsibilities.

**Architecture:** Keep the change documentation-only and update the four core workflow documents together so role definitions, harness mappings, and completion criteria stay aligned. `@qa` remains separate from `@reviewer`: QA validates runtime behavior, while reviewer validates standards and architecture.

**Tech Stack:** Markdown documentation, JSON policy docs

---

## File Map

- Modify: `I:\Project\Numbering\AGENTS.md`
  - add `@qa` to the operating model and harness mapping
- Modify: `I:\Project\Numbering\docs\architecture.md`
  - add `@qa` to team structure, workflow, handoffs, and completion criteria
- Modify: `I:\Project\Numbering\docs\exec_plans.md`
  - add `@qa` to the sub-agent roster and assignment rules, and record the change as a dated task entry
- Modify: `I:\Project\Numbering\docs\coding_standards.json`
  - add QA policy rules, role ownership, harness mapping, and completion checks

## Chunk 1: Core Workflow Documentation

### Task 1: Update top-level agent guidance

**Files:**
- Modify: `I:\Project\Numbering\AGENTS.md`

- [ ] **Step 1: Review the current operating model text**

Confirm where the role roster, harness mapping, and completion guidance currently live in `AGENTS.md` so the new `@qa` lane is inserted beside the existing ownership roles instead of being documented as an afterthought.

- [ ] **Step 2: Add the `@qa` role and mapping**

Update `AGENTS.md` so it:
- defines `@qa` as browser QA, runtime validation, request/response inspection, and screenshot evidence ownership,
- maps `@qa` to the harness `qa` agent type,
- states that user-visible or runtime-sensitive work should route through `@qa` before completion.

- [ ] **Step 3: Re-read the file for overlap ambiguity**

Check that `@qa` does not duplicate `@reviewer` language and that the file still makes implementation ownership boundaries clear.

- [ ] **Step 4: Commit only if the user explicitly requests it**

If and only if the user asks for a commit later, stage `AGENTS.md` together with the rest of the QA ownership documentation changes and create a non-amended commit.

### Task 2: Update the formal architecture doc

**Files:**
- Modify: `I:\Project\Numbering\docs\architecture.md`

- [ ] **Step 1: Add `@qa` to the team structure section**

Document the new role as runtime validation ownership without changing backend/frontend directory boundaries.

- [ ] **Step 2: Update workflow and handoff sections**

Insert `@qa` into the documented request lifecycle so browser-visible or runtime-sensitive work goes through QA before standards review completion.

- [ ] **Step 3: Update completion criteria**

Add explicit language requiring QA validation for touched user-facing or runtime-sensitive work, or an explicit recorded reason when QA is skipped.

- [ ] **Step 4: Commit only if the user explicitly requests it**

If and only if the user asks for a commit later, stage `docs/architecture.md` with the full QA ownership change set and create a non-amended commit.

## Chunk 2: Policy and Execution Records

### Task 3: Update exec plan governance

**Files:**
- Modify: `I:\Project\Numbering\docs\exec_plans.md`

- [ ] **Step 1: Expand the operating rule and roster**

Add `@qa` to the operating rule and sub-agent roster so runtime-sensitive work is explicitly routed through QA.

- [ ] **Step 2: Update harness assignment rules**

Map `@qa` to the `qa` harness agent type alongside existing role mappings.

- [ ] **Step 3: Record this change as a dated task entry**

Add a 2026-04-20 entry describing the QA ownership lane introduction, its scope, risks, and manual documentation verification.

- [ ] **Step 4: Commit only if the user explicitly requests it**

If and only if the user asks for a commit later, stage `docs/exec_plans.md` with the full QA ownership change set and create a non-amended commit.

### Task 4: Update coding standards policy

**Files:**
- Modify: `I:\Project\Numbering\docs\coding_standards.json`

- [ ] **Step 1: Add QA policy requirements**

Extend review and PM policy rules so user-facing or runtime-sensitive work requires QA consideration.

- [ ] **Step 2: Add the `qa` role definition and harness mapping**

Document what QA owns, what QA must not take over, add `qa` to the list of available harness agent types, and map the conceptual role to that agent type.

- [ ] **Step 3: Update completion verification requirements**

Require QA coverage or an explicit skip rationale before completion for touched user-facing or runtime-sensitive work.

- [ ] **Step 4: Commit only if the user explicitly requests it**

If and only if the user asks for a commit later, stage `docs/coding_standards.json` with the full QA ownership change set and create a non-amended commit.

### Task 5: Cross-document verification

**Files:**
- Review: `I:\Project\Numbering\AGENTS.md`
- Review: `I:\Project\Numbering\docs\architecture.md`
- Review: `I:\Project\Numbering\docs\exec_plans.md`
- Review: `I:\Project\Numbering\docs\coding_standards.json`

- [ ] **Step 1: Confirm mapping consistency**

Check that every touched workflow document maps `@qa` to the `qa` harness type and uses the same conceptual role name.

- [ ] **Step 2: Confirm ownership boundaries stay clear**

Check that `@qa` is described as runtime validation ownership and does not replace `@reviewer`, `@coder_backend`, or `@coder_frontend`.

- [ ] **Step 3: Confirm completion language is aligned**

Check that the updated docs now require QA validation or an explicit skip rationale for user-facing or runtime-sensitive work.

- [ ] **Step 4: Commit only if the user explicitly requests it**

If and only if the user asks for a commit later, stage the full QA ownership documentation change set and create a non-amended commit.
