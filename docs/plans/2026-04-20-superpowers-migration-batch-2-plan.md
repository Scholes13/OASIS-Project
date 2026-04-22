# Superpowers Migration Batch 2 Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Finish the migration of dated `docs/superpowers/` plan and spec artifacts into `docs/plans/` and `docs/specs/`.

**Architecture:** Move all remaining dated files while preserving filenames, then repair the few known self-references and embedded example commands that mention the old locations. Keep only `index.md` shims under `docs/superpowers/`.

**Tech Stack:** Markdown documentation

---

### Task 1: Move the remaining plan artifacts

**Files:**
- Move all dated files from `I:\Project\Numbering\docs\superpowers\plans\` into `I:\Project\Numbering\docs\plans\`

**Step 1: Preserve filenames and change only parent directories**

### Task 2: Move the remaining spec artifacts

**Files:**
- Move all dated files from `I:\Project\Numbering\docs\superpowers\specs\` into `I:\Project\Numbering\docs\specs\`

**Step 1: Preserve filenames and change only parent directories**

### Task 3: Repair embedded path references and verify

**Files:**
- Modify moved plan files with self-references or embedded old paths
- Modify `I:\Project\Numbering\docs\INDEX.md`
- Modify `I:\Project\Numbering\docs\specs\index.md`
- Modify `I:\Project\Numbering\docs\superpowers\index.md`
- Modify `I:\Project\Numbering\docs\superpowers\specs\index.md`
- Modify `I:\Project\Numbering\docs\superpowers\plans\index.md`

**Step 1: Update known self-references and example commands**

**Step 2: Convert `docs/superpowers/` into a navigation-only shim**

**Step 3: Verify no active references remain to migrated files under the old paths**
