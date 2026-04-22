# QA Heavy Model Routing Design

**Date:** 2026-04-20
**Decision:** Route the `qa` lane to the `Debugger` combo instead of the `Viewer` combo.
**Recommendation:** Keep exploration and reviewer work on `Viewer`, but move QA to `Debugger` so runtime validation and bug reproduction get a heavier reasoning lane.

---

## Context

The current OpenCode routing sends `qa` to `9router/Viewer`. That is efficient, but it undershoots the desired QA posture for this setup. The user wants QA to align with heavier reasoning models alongside debugging work.

## Acceptance Criteria

- `qa` uses `9router/Debugger`,
- `viewer` and `reviewer` remain on `9router/Viewer`,
- `coder` lanes remain on `9router/Coder`,
- reference docs reflect the updated QA routing.
