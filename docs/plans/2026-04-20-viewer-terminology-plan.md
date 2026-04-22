# Viewer Terminology Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Align repo workflow docs so `viewer` becomes the primary read-only lane term while preserving the technical `explorer` mapping.

**Architecture:** Update the top-level agent instructions, the execution-plan workflow doc, and the routing guide together. Keep the wording precise: `viewer` is the conceptual lane, `explorer` is the usual harness agent type underneath it.

**Tech Stack:** Markdown documentation

---

### Task 1: Update top-level terminology

**Files:**
- Modify: `I:\Project\Numbering\AGENTS.md`

**Step 1: Introduce `@viewer` as the read-only lane**

Add a concise role description and use it in the harness mapping guidance.

**Step 2: Keep technical mapping explicit**

State that `viewer` commonly maps to the harness `explorer` type for read-only work.

### Task 2: Update execution-plan workflow wording

**Files:**
- Modify: `I:\Project\Numbering\docs\exec_plans.md`

**Step 1: Add `@viewer` to the roster and mapping rules**

Reflect the read-only lane directly in the workflow doc.

**Step 2: Add concise dispatch guidance**

Document when to avoid unnecessary subagent chains and when `viewer` is actually justified.

### Task 3: Clarify the routing guide

**Files:**
- Modify: `I:\Project\Numbering\docs\agent-routing-guide.md`

**Step 1: Add a terminology note**

Explain that the team says `viewer`, while the harness may expose `explorer` as the underlying agent type.

**Step 2: Keep usage guidance unchanged in spirit**

The guide should stay practical and not force extra process on small work.
