# Minimal Docs Structure Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Reorganize the repository documentation into a smaller, more agent-friendly structure while preserving the current core workflow docs.

**Architecture:** Keep canonical harness docs at the root of `docs/`, introduce lightweight landing pages for future structure, and move clearly historical docs into `docs/archive/`. Avoid moving active workflow docs or renaming everything at once.

**Tech Stack:** Markdown documentation, JSON policy docs

---

### Task 1: Establish the new top-level documentation layout

**Files:**
- Modify: `I:\Project\Numbering\docs\INDEX.md`
- Create: `I:\Project\Numbering\docs\specs\index.md`
- Create: `I:\Project\Numbering\docs\references\index.md`
- Create: `I:\Project\Numbering\docs\generated\index.md`
- Create: `I:\Project\Numbering\docs\archive\index.md`

**Step 1: Rewrite the docs index**

Replace the legacy long-form index with a simpler canonical index that highlights core workflow docs and the new directory buckets.

**Step 2: Add landing pages**

Create short `index.md` files for `specs`, `references`, `generated`, and `archive` so the structure is navigable immediately.

### Task 2: Archive clearly historical documents

**Files:**
- Move: `I:\Project\Numbering\docs\bug-fixes\*` -> `I:\Project\Numbering\docs\archive\bug-fixes\*`
- Move: `I:\Project\Numbering\docs\tasks\*` -> `I:\Project\Numbering\docs\archive\tasks\*`
- Move selected historical flat docs -> `I:\Project\Numbering\docs\archive\history\*`

**Step 1: Move historical directories**

Archive the old bug-fix and task completion directories under `docs/archive/`.

**Step 2: Move flat historical docs**

Archive top-level summary, optimization, and cleanup docs that are no longer canonical workflow references.

### Task 3: Repair references and verify the migration

**Files:**
- Modify: `I:\Project\Numbering\docs\archive\history\v2.2-UPDATE-SUMMARY.md`
- Review: `I:\Project\Numbering\docs\INDEX.md`
- Review: `I:\Project\Numbering\docs\archive\index.md`

**Step 1: Update obvious moved references**

Repair any remaining markdown references that still point to the pre-archive bug-fix and task locations.

**Step 2: Verify moved paths**

Check that the new archive structure exists and the old top-level directories are gone.

**Step 3: Verify link targets by search**

Search the repo for lingering references to the old task and bug-fix locations and confirm none remain.
