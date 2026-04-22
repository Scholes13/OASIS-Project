# Token-Aware Routing Policy Design

**Date:** 2026-04-20
**Decision:** Add a lightweight token-efficiency policy to the repo workflow docs.
**Recommendation:** Keep the policy short in `AGENTS.md` and put the practical decision rules in `docs/agent-routing-guide.md`.

---

## Context

The repo now has clearer lanes for `viewer`, `qa`, and `reviewer`, plus specialized OpenCode model routing. Without an explicit policy, teams may over-dispatch subagents for small or obvious tasks, which increases token usage without improving outcomes.

## Goals

- reduce unnecessary subagent dispatch,
- preserve QA and review quality where risk justifies it,
- document when the main lane is enough,
- give the team practical cost-aware routing rules.

## Policy Shape

`AGENTS.md` should contain a short default policy with a few simple rules:

- do not spawn subagents for small, obvious, single-surface tasks,
- use `viewer` only when repository mapping or read-only tracing is actually needed,
- use `qa` and `reviewer` as gates for meaningful behavior or risk changes, not for every tiny edit,
- avoid chaining `viewer`, `qa`, and `reviewer` together unless the task complexity or risk justifies it.

`docs/agent-routing-guide.md` should then expand those rules into a quick cost-aware routing section with examples for small, medium, and high-risk tasks.

## Acceptance Criteria

- `AGENTS.md` includes a concise token-aware routing section,
- `docs/agent-routing-guide.md` explains when not to spawn subagents,
- the policy clearly balances token efficiency with verification quality.
