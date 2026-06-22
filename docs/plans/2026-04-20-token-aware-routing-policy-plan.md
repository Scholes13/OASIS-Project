# Token-Aware Routing Policy Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a lightweight routing policy that helps the team avoid unnecessary subagent usage while preserving the right quality gates.

**Architecture:** Update one top-level workflow file and one practical guide. Keep the top-level policy short and place the richer decision rules in the routing guide so the team gets both quick defaults and an easy reference.

**Tech Stack:** Markdown documentation

---

### Task 1: Add concise policy rules to `AGENTS.md`

**Files:**
- Modify: `I:\Project\Numbering\AGENTS.md`

**Step 1: Add a short token-aware routing section**

Document default rules for when not to spawn subagents and when `viewer`, `qa`, and `reviewer` are justified.

**Step 2: Keep it operational**

Use direct rules that can guide dispatch decisions in day-to-day work.

### Task 2: Expand the practical rules in the routing guide

**Files:**
- Modify: `I:\Project\Numbering\docs\agent-routing-guide.md`

**Step 1: Add a cost-aware routing section**

Explain when the main lane is enough and when a subagent should still be used.

**Step 2: Add examples by task size and risk**

Include concrete guidance for small, medium, and high-risk changes.

**Step 3: Re-read for balance**

Confirm the policy reduces waste without telling the team to skip important QA or review gates.

### Task 3: Verify the updated docs

**Files:**
- Review: `I:\Project\Numbering\AGENTS.md`
- Review: `I:\Project\Numbering\docs\agent-routing-guide.md`

**Step 1: Check policy consistency**

Confirm the short policy in `AGENTS.md` matches the detailed guidance in the routing guide.

**Step 2: Check practical clarity**

Confirm the examples make it clear when to stay in the main lane versus spawning `viewer`, `qa`, or `reviewer`.
