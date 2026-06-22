# OpenCode Model Routing Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Simplify OpenCode model routing so implementation, debugging, and viewer-style work use the new 9router combo models while the primary session stays on `cx/gpt-5.4`.

**Architecture:** Update the global OpenCode config in two places: the provider model list in `opencode.json`, and the individual global subagent definitions in `~/.config/opencode/agents/`. Keep the change minimal by only touching model IDs and preserving all existing prompts and permissions.

**Tech Stack:** OpenCode JSON config, OpenCode markdown subagent definitions

---

## File Map

- Modify: `C:\Users\Administrator\.config\opencode\opencode.json`
  - reduce the `9router` model list to `Coder`, `Debugger`, `Viewer`, and `cx/gpt-5.4`
  - route the primary model to `9router/cx/gpt-5.4`
  - route `agent.plan` and `agent.explore` to `9router/Viewer`
- Modify: `C:\Users\Administrator\.config\opencode\agents\coder-backend.md`
  - change the subagent model to `9router/Coder`
- Modify: `C:\Users\Administrator\.config\opencode\agents\coder-frontend.md`
  - change the subagent model to `9router/Coder`
- Modify: `C:\Users\Administrator\.config\opencode\agents\debugger.md`
  - change the subagent model to `9router/Debugger`
- Modify: `C:\Users\Administrator\.config\opencode\agents\reviewer.md`
  - change the subagent model to `9router/Viewer`
- Modify: `C:\Users\Administrator\.config\opencode\agents\qa.md`
  - change the subagent model to `9router/Viewer`
- Create: `C:\Users\Administrator\.config\opencode\agents\viewer.md`
  - add an explicit read-only exploration agent that uses `9router/Viewer`

## Chunk 1: Provider and Primary Routing

### Task 1: Simplify the 9router provider list

**Files:**
- Modify: `C:\Users\Administrator\.config\opencode\opencode.json`

- [ ] **Step 1: Replace the 9router model map**

Keep only these model IDs in the `9router.models` object:
- `Coder`
- `Debugger`
- `Viewer`
- `cx/gpt-5.4`

- [ ] **Step 2: Set the primary model**

Ensure the top-level `model` value is `9router/cx/gpt-5.4`.

- [ ] **Step 3: Update the built-in routing lanes**

Set both `agent.plan.model` and `agent.explore.model` to `9router/Viewer`.

## Chunk 2: Subagent Model Overrides

### Task 2: Move implementation lanes to `Coder`

**Files:**
- Modify: `C:\Users\Administrator\.config\opencode\agents\coder-backend.md`
- Modify: `C:\Users\Administrator\.config\opencode\agents\coder-frontend.md`

- [ ] **Step 1: Update backend coder model**

Set the YAML frontmatter `model` in `coder-backend.md` to `9router/Coder`.

- [ ] **Step 2: Update frontend coder model**

Set the YAML frontmatter `model` in `coder-frontend.md` to `9router/Coder`.

### Task 3: Move heavy debugging to `Debugger`

**Files:**
- Modify: `C:\Users\Administrator\.config\opencode\agents\debugger.md`

- [ ] **Step 1: Update debugger model**

Set the YAML frontmatter `model` in `debugger.md` to `9router/Debugger`.

### Task 4: Move viewer-style lanes to `Viewer`

**Files:**
- Modify: `C:\Users\Administrator\.config\opencode\agents\reviewer.md`
- Modify: `C:\Users\Administrator\.config\opencode\agents\qa.md`
- Create: `C:\Users\Administrator\.config\opencode\agents\viewer.md`

- [ ] **Step 1: Update reviewer model**

Set the YAML frontmatter `model` in `reviewer.md` to `9router/Viewer`.

- [ ] **Step 2: Update QA model**

Set the YAML frontmatter `model` in `qa.md` to `9router/Viewer`.

- [ ] **Step 3: Add an explicit viewer agent**

Create `viewer.md` as a read-only subagent with `model: 9router/Viewer` so viewer/explorer work can be invoked explicitly, not only through the built-in `explore` lane.

## Chunk 3: Verification

### Task 5: Verify the final routing

**Files:**
- Review: `C:\Users\Administrator\.config\opencode\opencode.json`
- Review: `C:\Users\Administrator\.config\opencode\agents\coder-backend.md`
- Review: `C:\Users\Administrator\.config\opencode\agents\coder-frontend.md`
- Review: `C:\Users\Administrator\.config\opencode\agents\debugger.md`
- Review: `C:\Users\Administrator\.config\opencode\agents\reviewer.md`
- Review: `C:\Users\Administrator\.config\opencode\agents\qa.md`
- Review: `C:\Users\Administrator\.config\opencode\agents\viewer.md`

- [ ] **Step 1: Check the model IDs are present**

Confirm the `9router` provider now exposes only `Coder`, `Debugger`, `Viewer`, and `cx/gpt-5.4` in `opencode.json`.

- [ ] **Step 2: Check subagent assignments**

Confirm each touched subagent file now references the intended combo model.

- [ ] **Step 3: Check the viewer agent exists**

Confirm `viewer.md` exists, is read-only, and points to `9router/Viewer`.

- [ ] **Step 4: Validate runtime availability**

Run `curl.exe -s "http://localhost:20128/v1/models"` and confirm the response includes `Coder`, `Debugger`, `Viewer`, and `cx/gpt-5.4`.

- [ ] **Step 5: Validate OpenCode config readability**

Read back the touched config sections and confirm there are no malformed edits.
