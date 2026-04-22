# Superpowers Docs Navigation Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Make the remaining `docs/superpowers/` area easier to navigate without doing a noisy path migration.

**Architecture:** Add local `index.md` files and update the top-level docs index so the relationship between canonical docs, new `specs/` and `plans/` directories, and the older `superpowers/` area is explicit.

**Tech Stack:** Markdown documentation

---

### Task 1: Add local indexes under `docs/superpowers/`

**Files:**
- Create: `I:\Project\Numbering\docs\superpowers\index.md`
- Create: `I:\Project\Numbering\docs\superpowers\specs\index.md`
- Create: `I:\Project\Numbering\docs\superpowers\plans\index.md`

**Step 1: Describe the area clearly**

Explain that `docs/superpowers/` holds legacy-but-active spec and plan artifacts created before the docs cleanup.

**Step 2: Point to preferred future locations**

Make it clear that new long-lived specs should prefer `docs/specs/` and new design/plan notes should prefer `docs/plans/`.

### Task 2: Update top-level indexes

**Files:**
- Modify: `I:\Project\Numbering\docs\INDEX.md`
- Modify: `I:\Project\Numbering\docs\specs\index.md`

**Step 1: Clarify the transitional role**

Describe `docs/superpowers/` as a still-active but gradually consolidating area.

**Step 2: Keep migration scope explicit**

Make clear that this batch improves navigation only and does not move those files yet.

### Task 3: Record and verify the cleanup

**Files:**
- Modify: `I:\Project\Numbering\docs\exec_plans.md`

**Step 1: Record the navigation cleanup**

Add a brief implemented entry noting that `docs/superpowers/` now has index pages and clearer guidance.

**Step 2: Verify no path migration occurred**

Search for `docs/superpowers/specs/` and `docs/superpowers/plans/` references and confirm they remain valid.
