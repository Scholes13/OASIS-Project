# Documentation Index

This repository uses a minimal harness-friendly documentation structure. Keep the top level focused on canonical workflow docs and move clearly historical material into `docs/archive/`.

## Canonical Docs

- `docs/architecture.md`: system boundaries, ownership, and harness mapping
- `docs/coding_standards.json`: review gates, role policy, and harness rules
- `docs/exec_plans.md`: active execution plans, status tracking, and tech debt notes
- `docs/agent-routing-guide.md`: practical lane selection and token-aware routing
- `docs/plans/`: design notes and implementation plans for current work

## Active Doc Areas

- `docs/specs/`: stable product and feature specs
- `docs/plans/`: design notes and implementation plans for current and historical work
- `docs/superpowers/`: navigation shim explaining the old path migration
- `docs/references/`: reusable references for humans and agents, including local harness and model-routing notes
- `docs/generated/`: generated documentation artifacts when a stable generator exists
- `docs/input/`: source input files used for documentation or migration work

## Archive

- `docs/archive/bug-fixes/`: historical bug-fix notes
- `docs/archive/tasks/`: historical task completion reports
- `docs/archive/history/`: older summaries, optimization notes, and one-off implementation writeups

## Guidelines

- Keep canonical workflow docs at the top of `docs/`.
- Put new design notes and implementation plans under `docs/plans/`.
- Prefer `docs/specs/` for new long-lived specs.
- Prefer `docs/archive/` over leaving historical reports at the top of `docs/`.
- Use `docs/superpowers/` only as a compatibility and navigation layer after the migration.
- Do not create new top-level documentation buckets unless there is a clear ongoing need.
