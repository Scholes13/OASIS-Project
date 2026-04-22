# QA Ownership Design

**Date:** 2026-04-20
**Decision:** Introduce a first-class `@qa` ownership lane in the repo harness model.
**Recommendation:** Keep `@qa` separate from `@reviewer` so runtime validation and standards review remain distinct gates.

---

## Context

The current harness model documents implementation ownership for backend and frontend work plus a reviewer gate for standards validation. That leaves browser QA, request/response inspection, console and network checks, and screenshot evidence as informal work. As OpenCode grows browser-oriented QA capability, the workflow needs an explicit ownership lane for those tasks.

## Goals

- add a dedicated `@qa` role to the harness operating model,
- define `@qa` as runtime validation ownership rather than implementation ownership,
- map `@qa` onto the harness `qa` agent type,
- make QA expectations explicit in completion criteria for user-facing or runtime-sensitive changes.

## Non-Goals

- changing backend or frontend directory ownership,
- replacing `@reviewer` with `@qa`,
- introducing a full browser automation stack in this design change,
- requiring QA on every low-risk non-user-facing task.

## Ownership Model

`@qa` owns browser-based smoke and regression walkthroughs, runtime request/response inspection, console and network validation, and screenshot evidence when user-facing behavior needs proof. `@qa` does not take over backend or frontend implementation, and it does not replace the standards role of `@reviewer`.

This separation keeps the workflow legible:

- `@coder_backend` and `@coder_frontend` build the change.
- `@qa` proves the change behaves correctly in runtime flows when browser evidence matters.
- `@reviewer` checks architectural, security, contract, and verification quality.

## Workflow Change

For tasks that touch user-visible behavior, browser interactions, or runtime request/response behavior, the PM should route work through `@qa` before completion. The updated sequence becomes:

1. product request and exec plan update,
2. implementation by ownership lane,
3. QA runtime validation where applicable,
4. reviewer standards validation,
5. completion only after both gates are satisfied or a skip is explicitly justified.

## Documentation Changes

The change should be reflected in:

- `AGENTS.md` for the high-level operating model and role mapping,
- `docs/architecture.md` for the formal team structure and completion criteria,
- `docs/exec_plans.md` for the sub-agent roster, assignment rules, and a dated record of the change,
- `docs/coding_standards.json` for PM duties, role definitions, harness mapping, and completion checks.

## Acceptance Criteria

- `@qa` appears as a documented ownership lane in all core workflow docs.
- `@qa` is consistently mapped to the harness `qa` agent type.
- docs distinguish `@qa` from `@reviewer` without overlap ambiguity.
- completion criteria explicitly mention QA for user-facing or runtime-sensitive work.
