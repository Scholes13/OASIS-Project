# Agent Routing Guide Design

**Date:** 2026-04-20
**Decision:** Add a short, practical routing guide for the team.
**Recommendation:** Keep the guide as a single cheatsheet-style document focused on when to use `viewer`, `reviewer`, and `qa`, plus the preferred workflow order.

---

## Context

The repo now has clearer ownership lanes and OpenCode subagent routing, but team members still need a fast reference for choosing the right lane during day-to-day work. The biggest source of confusion is the difference between `viewer`, `reviewer`, and `qa`.

## Goals

- explain the purpose of `viewer`, `reviewer`, and `qa` in plain language,
- give concrete examples of when each lane should be used,
- document a simple recommended workflow order,
- keep the guide short enough to scan quickly.

## Format

The guide should be a single Markdown document in `docs/` with:

- a quick summary,
- one section per lane,
- a practical decision table,
- a recommended sequence for common work.

## Acceptance Criteria

- the guide exists in a stable path under `docs/`,
- the guide clearly distinguishes `viewer`, `reviewer`, and `qa`,
- the guide includes short examples and a recommended default sequence.
