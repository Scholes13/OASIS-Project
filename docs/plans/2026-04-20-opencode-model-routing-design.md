# OpenCode Model Routing Design

**Date:** 2026-04-20
**Decision:** Route OpenCode work to three 9router combo models plus a single CX GPT primary model.
**Recommendation:** Keep `cx/gpt-5.4` as the main orchestrator model and route specialized subagents to `Coder`, `Debugger`, and `Viewer`.

---

## Context

The active OpenCode configuration currently mixes several 9router-backed models across the primary session and the global subagents. The user has consolidated model selection into three 9router combo endpoints named `Coder`, `Debugger`, and `Viewer`, while asking to keep only the CX GPT model as the extra direct model entry.

## Goals

- simplify the 9router model list in `opencode.json`,
- keep `cx/gpt-5.4` as the primary general-purpose model,
- route implementation agents to `9router/Coder`,
- route heavy debugging to `9router/Debugger`,
- route read-only analysis and validation lanes to `9router/Viewer`.

## Ownership Mapping

- Primary OpenCode session: `9router/cx/gpt-5.4`
- Implementation lanes:
  - `coder-backend` -> `9router/Coder`
  - `coder-frontend` -> `9router/Coder`
- Debug lane:
  - `debugger` -> `9router/Debugger`
- Viewer lanes:
  - `viewer` -> `9router/Viewer`
  - `reviewer` -> `9router/Viewer`
  - `qa` -> `9router/Viewer`
  - `agent.plan` -> `9router/Viewer`
  - `agent.explore` -> `9router/Viewer`

## Scope

This change updates only the global OpenCode configuration under `C:\Users\Administrator\.config\opencode\`. It does not change repository runtime behavior, app code, or the workspace MCP setup.

## Acceptance Criteria

- `opencode.json` lists only `Coder`, `Debugger`, `Viewer`, and `cx/gpt-5.4` under the `9router` provider.
- the primary model is `9router/cx/gpt-5.4`.
- an explicit `viewer` subagent exists and uses `9router/Viewer`.
- the relevant global subagent markdown files reference the requested combo models.
- config verification shows the new model IDs are present and assigned as intended.
