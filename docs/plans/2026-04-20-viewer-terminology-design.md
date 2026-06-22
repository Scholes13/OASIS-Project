# Viewer Terminology Design

**Date:** 2026-04-20
**Decision:** Use `viewer` as the primary read-only lane term in repo workflow docs.
**Recommendation:** Keep `viewer` as the operational name for the team, while explicitly noting that the underlying harness commonly maps that lane to the `explorer` agent type.

---

## Context

The repo now has a user-facing routing guide and an explicit OpenCode `viewer` subagent, but some workflow docs still describe the read-only lane as `explorer`. That mismatch makes the docs harder to follow and weakens the value of the new routing guide.

## Goals

- make `viewer` the main team-facing term for read-only exploration,
- preserve the technical mapping to the harness `explorer` agent type,
- keep the change small and focused on workflow docs.

## Acceptance Criteria

- `AGENTS.md` uses `viewer` as the primary read-only lane term,
- `docs/exec_plans.md` reflects `viewer` in routing and dispatch guidance,
- `docs/agent-routing-guide.md` explains that `viewer` is the team-facing term and `explorer` is the common harness mapping.
