# Agent Table of Contents

Start here, then follow the referenced files instead of adding new monolithic instructions in this file.

## Core References
- Architecture and directory boundaries: `docs/architecture.md`
- Hard coding constraints and review gates: `docs/coding_standards.json`
- Active execution plans, status, and tech debt: `docs/exec_plans.md`
- Agent lane selection cheatsheet: `docs/agent-routing-guide.md`
- Up-to-date library and framework docs: use the `context7` MCP server when current package behavior matters.

## Operating Model
- The main agent acts as `PM Agent` and orchestrates work.
- Delegate work to specialized sub-agents:
  - `@viewer` for read-only repository mapping, contract tracing, impact analysis, and evidence gathering before implementation or review.
  - `@coder_backend` for database, Laravel domain logic, controllers, requests, middleware, notifications, providers, and server-side integrations.
  - `@coder_frontend` for Inertia/React pages, components, client state, forms, and UI behavior. When the work changes user-facing UI, this lane must also follow the `frontend-skill` guidance so outputs stay aligned with the product brand and visual standards.
  - `@qa` for browser-based QA, smoke and regression checks, request/response inspection, console and network validation, and screenshot evidence when runtime proof is needed.
  - `@reviewer` for architectural drift checks, security review, linting, and standards validation.
- Harness mapping:
  - `@viewer` => spawn `explorer` for read-only analysis work
  - `@coder_backend` => spawn `worker`
  - `@coder_frontend` => spawn `worker`
  - `@qa` => spawn `qa`
  - `@reviewer` => spawn `@viewer` lane by default, or `default` when broader reasoning is needed
  - research, tracing, and repo investigation => spawn `@viewer` lane
- Do not finalize implementation until it has been reviewed against `docs/coding_standards.json`.
- When work changes user-visible behavior, browser flows, or request/response behavior, route it through `@qa` before presenting it as complete.

## Token-Aware Routing
- Do not spawn sub-agents for small, obvious, single-surface tasks when the main lane can complete the work directly.
- Use `viewer` only when repository mapping, contract tracing, or read-only evidence gathering is actually needed.
- Use `qa` when changed behavior needs runtime proof, focused verification, or regression evidence; do not make it a mandatory stop for every tiny edit.
- Use `@reviewer` when risk, scope, or standards concerns justify a review gate; avoid routine review loops for trivial documentation-only or one-line low-risk changes.
- Avoid chaining `viewer`, `qa`, and `@reviewer` together unless the task complexity, cross-surface impact, or failure risk justifies the extra token spend.

## Repo Notes
- `SalesCrm` is deprecated and must remain disabled unless the Product Owner explicitly asks to restore it.
