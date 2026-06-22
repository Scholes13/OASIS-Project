# Superpowers Migration Batch 2 Design

**Date:** 2026-04-20
**Decision:** Complete the migration of remaining `docs/superpowers/specs` and `docs/superpowers/plans` artifacts into the canonical `docs/specs/` and `docs/plans/` directories.
**Recommendation:** Move all remaining dated plan and spec files, keep `docs/superpowers/` only as a navigation shim, and repair the handful of embedded self-references or example commands that still mention the old paths.

---

## Context

After Batch 1, the remaining `docs/superpowers/` files are older plan and spec artifacts. Leaving them split across two parallel hierarchies adds more confusion than value, so the next best step is to finish the migration and preserve only local index pages in `docs/superpowers/`.

## Acceptance Criteria

- all remaining dated spec files move into `docs/specs/`,
- all remaining dated plan files move into `docs/plans/`,
- `docs/superpowers/` keeps only navigation index files,
- embedded references for migrated files are updated where needed,
- no active references remain to migrated files under `docs/superpowers/`.
