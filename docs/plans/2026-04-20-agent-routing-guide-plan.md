# Agent Routing Guide Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a concise team-facing routing cheatsheet that explains when to use `viewer`, `reviewer`, and `qa`.

**Architecture:** Create one documentation file under `docs/` and keep it tightly scoped to routing decisions, examples, and workflow order. No code or runtime behavior changes are involved.

**Tech Stack:** Markdown documentation

---

### Task 1: Add the routing cheatsheet

**Files:**
- Create: `I:\Project\Numbering\docs\agent-routing-guide.md`

**Step 1: Draft the guide structure**

Write sections for overview, lane-by-lane guidance, quick decision rules, and recommended workflow order.

**Step 2: Add practical examples**

Include short examples showing when to use `viewer`, `reviewer`, and `qa`.

**Step 3: Keep the guide scannable**

Use short paragraphs, bullets, and a simple table so the guide works as a cheatsheet.

**Step 4: Re-read for overlap clarity**

Confirm the guide clearly separates exploration, validation, and review responsibilities.

**Step 5: Commit only if explicitly requested**

If the user later asks for a commit, stage the guide and commit it without amend.
