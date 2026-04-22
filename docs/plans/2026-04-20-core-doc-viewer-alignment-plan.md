# Core Doc Viewer Alignment Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Align the remaining core harness docs so `viewer` is the primary read-only lane term everywhere the team looks first.

**Architecture:** Update the two remaining core workflow documents that still reflect the older read-only terminology. Keep the wording tight and preserve the technical mapping to the `explorer` harness type.

**Tech Stack:** Markdown documentation, JSON policy docs

---

### Task 1: Update `docs/architecture.md`

**Files:**
- Modify: `I:\Project\Numbering\docs\architecture.md`

**Step 1: Add `@viewer` to the team structure and mapping**

Describe `@viewer` as the read-only exploration lane and map it to the `explorer` harness type.

**Step 2: Update workflow and handoff language**

Replace read-only `explorer` wording with `viewer` where the docs talk about the team-facing lane.

### Task 2: Update `docs/coding_standards.json`

**Files:**
- Modify: `I:\Project\Numbering\docs\coding_standards.json`

**Step 1: Add a `viewer` role**

Define what `viewer` owns as a read-only analysis lane.

**Step 2: Align role-to-type mapping and rules**

Map `viewer` and researcher-style work to the `explorer` harness type while preserving reviewer escalation rules.

### Task 3: Verify consistency

**Files:**
- Review: `I:\Project\Numbering\AGENTS.md`
- Review: `I:\Project\Numbering\docs\architecture.md`
- Review: `I:\Project\Numbering\docs\exec_plans.md`
- Review: `I:\Project\Numbering\docs\coding_standards.json`
- Review: `I:\Project\Numbering\docs\agent-routing-guide.md`

**Step 1: Check term consistency**

Confirm `viewer` is now the primary operational term across the core docs.

**Step 2: Check technical mapping clarity**

Confirm the docs still make it clear that `viewer` usually maps to the `explorer` harness type.
