# QA Heavy Model Routing Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Move the OpenCode QA lane onto the heavier `Debugger` combo while keeping the rest of the combo routing intact.

**Architecture:** Update the global QA subagent definition and the model routing reference doc. Keep all other combo assignments unchanged.

**Tech Stack:** OpenCode subagent markdown, Markdown documentation

---

### Task 1: Update the QA subagent model

**Files:**
- Modify: `C:\Users\Administrator\.config\opencode\agents\qa.md`

**Step 1: Change the QA model**

Set the QA subagent `model` field to `9router/Debugger`.

### Task 2: Update documentation

**Files:**
- Modify: `I:\Project\Numbering\docs\references\opencode-model-routing.md`

**Step 1: Update the current routing reference**

Record that QA now uses `9router/Debugger` rather than `9router/Viewer`.

### Task 3: Verify final mapping

**Files:**
- Review: `C:\Users\Administrator\.config\opencode\agents\qa.md`
- Review: `C:\Users\Administrator\.config\opencode\agents\debugger.md`
- Review: `C:\Users\Administrator\.config\opencode\agents\reviewer.md`
- Review: `C:\Users\Administrator\.config\opencode\agents\viewer.md`

**Step 1: Confirm QA is on `Debugger` and the other lanes remain unchanged**
