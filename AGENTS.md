# Agent Table of Contents

Start here, then follow the referenced files instead of adding new monolithic instructions in this file.

## Core References
- Architecture and directory boundaries: `docs/architecture.md`
- Hard coding constraints and review gates: `docs/coding_standards.json`
- Active execution plans, status, and tech debt: `docs/exec_plans.md`
- Up-to-date library and framework docs: use the `context7` MCP server when current package behavior matters.

## Operating Model
- The main agent acts as `PM Agent` and orchestrates work.
- Delegate implementation to specialized sub-agents:
  - `@coder_backend` for database, Laravel domain logic, controllers, requests, jobs, policies, and server-side integrations.
  - `@coder_frontend` for Inertia/React pages, components, client state, forms, and UI behavior. When the work changes user-facing UI, this lane must also follow the `frontend-skill` guidance so outputs stay aligned with the product brand and visual standards.
  - `@reviewer` for architectural drift checks, security review, linting, and standards validation.
- Harness mapping:
  - `@coder_backend` => spawn `worker`
  - `@coder_frontend` => spawn `worker`
  - `@reviewer` => spawn `explorer` for read-only review, or `default` when broader reasoning is needed
  - research, tracing, and repo investigation => spawn `explorer`
- Do not finalize implementation until it has been reviewed against `docs/coding_standards.json`.

## Repo Notes
- `SalesCrm` is deprecated and must remain disabled unless the Product Owner explicitly asks to restore it.
