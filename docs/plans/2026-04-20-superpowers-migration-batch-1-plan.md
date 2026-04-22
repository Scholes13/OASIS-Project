# Superpowers Migration Batch 1 Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Move a first low-risk batch of `docs/superpowers/` specs and plans into the newer canonical `docs/specs/` and `docs/plans/` locations.

**Architecture:** Keep the batch intentionally small: move the most recent or lightly referenced files, then repair the few known references. Do not touch older heavily self-referential artifacts yet.

**Tech Stack:** Markdown documentation

---

### Task 1: Move the selected plan files

**Files:**
- Move: `I:\Project\Numbering\docs\superpowers\plans\2026-04-20-opencode-model-routing.md`
- Move: `I:\Project\Numbering\docs\superpowers\plans\2026-04-20-qa-ownership.md`
- Move: `I:\Project\Numbering\docs\superpowers\plans\2026-04-20-activity-quick-status-timestamps.md`
- Move: `I:\Project\Numbering\docs\superpowers\plans\2026-04-17-cashflow-terminology-export-parity.md`
- Move: `I:\Project\Numbering\docs\superpowers\plans\2026-04-17-activity-export-tagged-participants.md`

**Step 1: Move without renaming the filenames**

Keep filenames stable and only change the parent directory to minimize reference churn.

### Task 2: Move the selected spec files

**Files:**
- Move: `I:\Project\Numbering\docs\superpowers\specs\2026-04-17-cashflow-local-qa-review.md`
- Move: `I:\Project\Numbering\docs\superpowers\specs\2026-04-17-cashflow-terminology-export-parity-design.md`
- Move: `I:\Project\Numbering\docs\superpowers\specs\2026-04-17-activity-export-tagged-participants-design.md`

**Step 1: Move the selected specs into `docs/specs/`**

Keep the filenames unchanged.

### Task 3: Repair references and verify

**Files:**
- Modify: `I:\Project\Numbering\docs\exec_plans.md`
- Modify: moved plan files if they contain self-references

**Step 1: Update known `exec_plans` references**

Point the 2026-04-17 entries at the new `docs/specs/` and `docs/plans/` locations.

**Step 2: Update any known self-references**

Repair the moved activity export tagged participants plan so it points to its new path.

**Step 3: Verify moved-path references**

Search for the moved filenames under their old `docs/superpowers/` paths and confirm no active references remain for the migrated subset.
