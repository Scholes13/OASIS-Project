# Minimal Docs Structure Design

**Date:** 2026-04-20
**Decision:** Migrate the repository documentation toward a minimal harness-friendly structure without copying a full article template.
**Recommendation:** Keep a small canonical core at the top of `docs/`, move clearly historical material into `docs/archive/`, and add lightweight landing pages for `specs`, `references`, and `generated` content.

---

## Context

The repository already has the most important harness documents, but the `docs/` directory still mixes canonical workflow docs, active plans, historical implementation notes, bug-fix reports, and old summary files in a mostly flat layout. That makes discovery harder for both humans and agents.

## Goals

- keep the core harness docs easy to find,
- reduce clutter in the top level of `docs/`,
- move clearly historical material into `docs/archive/`,
- add stable landing pages for `specs`, `references`, and `generated` content without forcing premature migrations.

## Target Structure

```text
AGENTS.md
docs/
  INDEX.md
  architecture.md
  coding_standards.json
  exec_plans.md
  agent-routing-guide.md
  plans/
  specs/
  references/
  generated/
  archive/
  superpowers/
  input/
```

## Migration Scope

This first migration batch should:

- rewrite `docs/INDEX.md` around the new canonical structure,
- create landing pages for `docs/specs/`, `docs/references/`, `docs/generated/`, and `docs/archive/`,
- move the legacy bug-fix directory into `docs/archive/bug-fixes/`,
- move the legacy task-report directory into `docs/archive/tasks/`,
- move clearly historical flat docs into `docs/archive/history/`,
- update any obvious broken internal references caused by those moves.

## Acceptance Criteria

- the top of `docs/` highlights only canonical or still-active categories,
- archived material lives under `docs/archive/`,
- `docs/INDEX.md` matches the new layout,
- no remaining markdown references point at the pre-archive bug-fix and task locations.
