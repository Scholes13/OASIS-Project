# Core Doc Viewer Alignment Design

**Date:** 2026-04-20
**Decision:** Align the remaining core workflow documents to use `viewer` as the primary read-only lane term.
**Recommendation:** Update `docs/architecture.md` and `docs/coding_standards.json` so the repo has one operational term for read-only exploration while preserving the technical harness mapping to `explorer`.

---

## Context

`AGENTS.md`, `docs/exec_plans.md`, and `docs/agent-routing-guide.md` already treat `viewer` as the primary team-facing read-only lane. `docs/architecture.md` and `docs/coding_standards.json` still use the older `explorer` terminology, which leaves the core harness docs inconsistent.

## Goals

- make `viewer` the consistent read-only lane name across core workflow docs,
- preserve the underlying harness `explorer` type where technical mapping matters,
- keep the change limited to terminology and role mapping clarity.

## Acceptance Criteria

- `docs/architecture.md` uses `viewer` as the primary read-only lane name,
- `docs/coding_standards.json` defines a `viewer` role and maps it to `explorer`,
- reviewer fallback and repo-discovery rules remain clear after the rename.
