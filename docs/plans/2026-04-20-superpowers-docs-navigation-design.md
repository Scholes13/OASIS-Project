# Superpowers Docs Navigation Design

**Date:** 2026-04-20
**Decision:** Keep `docs/superpowers/` in place for now, but make it navigable and clearly transitional.
**Recommendation:** Add local index pages and update the top-level docs index so `docs/superpowers/` is treated as a legacy-but-active area rather than a hidden side structure.

---

## Context

The top-level docs structure is now cleaner, but `docs/superpowers/specs/` and `docs/superpowers/plans/` still contain active material that cannot be moved wholesale without creating noisy reference churn. The safest next step is to improve navigation and mark the area as transitional.

## Goals

- make `docs/superpowers/` discoverable,
- explain its role relative to `docs/specs/` and `docs/plans/`,
- avoid breaking references inside existing historical plan files.

## Acceptance Criteria

- `docs/superpowers/` has local index pages,
- `docs/INDEX.md` and `docs/specs/index.md` describe `docs/superpowers/` clearly,
- no spec or plan files need path rewrites in this batch.
