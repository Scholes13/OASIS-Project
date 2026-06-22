# Superpowers Migration Batch 1 Design

**Date:** 2026-04-20
**Decision:** Start the `docs/superpowers/` migration with a low-risk subset instead of a wholesale move.
**Recommendation:** Move the most recent and lightly referenced plan/spec files into `docs/plans/` and `docs/specs/`, then update the small number of known references.

---

## Context

`docs/superpowers/` is now navigable, but some files are good candidates to move into the newer canonical locations immediately. The safest first batch is the subset with either no external references or only a small number of references concentrated in `docs/exec_plans.md`.

## Batch Scope

Move these files:

- `docs/superpowers/plans/2026-04-20-opencode-model-routing.md` -> `docs/plans/2026-04-20-opencode-model-routing.md`
- `docs/superpowers/plans/2026-04-20-qa-ownership.md` -> `docs/plans/2026-04-20-qa-ownership.md`
- `docs/superpowers/plans/2026-04-20-activity-quick-status-timestamps.md` -> `docs/plans/2026-04-20-activity-quick-status-timestamps.md`
- `docs/superpowers/specs/2026-04-17-cashflow-local-qa-review.md` -> `docs/specs/2026-04-17-cashflow-local-qa-review.md`
- `docs/superpowers/specs/2026-04-17-cashflow-terminology-export-parity-design.md` -> `docs/specs/2026-04-17-cashflow-terminology-export-parity-design.md`
- `docs/superpowers/plans/2026-04-17-cashflow-terminology-export-parity.md` -> `docs/plans/2026-04-17-cashflow-terminology-export-parity.md`
- `docs/superpowers/specs/2026-04-17-activity-export-tagged-participants-design.md` -> `docs/specs/2026-04-17-activity-export-tagged-participants-design.md`
- `docs/superpowers/plans/2026-04-17-activity-export-tagged-participants.md` -> `docs/plans/2026-04-17-activity-export-tagged-participants.md`

## Acceptance Criteria

- migrated files live in `docs/plans/` or `docs/specs/`,
- `docs/exec_plans.md` points to the new locations for the moved 2026-04-17 artifacts,
- the moved plan with a known self-reference now points to its new path,
- remaining `docs/superpowers/` files stay untouched in this batch.
