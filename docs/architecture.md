# Architecture

## Purpose
This repository uses a Harness Engineering structure so the Product Owner can operate as orchestrator while the main agent executes through planning, delegation, implementation, and review.

## Stack
- Backend: Laravel 12, PHP 8.2, Sanctum, Spatie Permission
- Frontend: Inertia.js v2 with React 19 and TypeScript
- Styling: Tailwind CSS v3
- Testing: PHPUnit 11, TypeScript compiler checks, Laravel Pint
- External docs MCP: `context7` for current library/framework documentation lookups

## Team Structure
- `PM Agent`
  - Reads product requests.
  - Updates `docs/exec_plans.md`.
  - Breaks work into modules and assigns ownership.
  - Does not finalize implementation until review passes.
- `@viewer`
  - Owns read-only repository mapping, contract tracing, impact analysis, and evidence gathering before implementation or review.
- `@coder_backend`
  - Owns schema, Eloquent models, services, controllers, form requests, policies, jobs, console commands, and backend tests.
- `@coder_frontend`
  - Owns Inertia pages, React components, TypeScript types, client-side form behavior, navigation behavior, and frontend tests.
- `@qa`
  - Owns browser-based QA execution, smoke and regression walkthroughs, runtime request/response inspection, console and network validation, and screenshot evidence for user-facing changes.
  - Verifies runtime behavior without taking over backend or frontend implementation ownership.
- `@reviewer`
  - Validates completed work against `docs/coding_standards.json`.
  - Checks architectural drift, security, missing verification, and style/test issues.

## Harness Agent Mapping
- The harness exposes reusable sub-agent types, not permanent named teammates.
- Role names such as `@viewer`, `@coder_backend`, `@coder_frontend`, and `@reviewer` are operating roles that the `PM Agent` assigns onto harness sub-agents per task.
- Concrete mapping:
  - `@viewer` => `explorer` for read-only repository mapping and analysis
  - `@coder_backend` => `worker`
  - `@coder_frontend` => `worker`
  - `@qa` => `qa`
  - `@reviewer` => `@viewer` for read-only review and standards validation
  - `@reviewer` => `default` when the review needs broader reasoning, synthesis, or non-trivial adjudication
  - repository exploration, impact analysis, and code tracing => `@viewer`
- Sub-agents are spawned on demand, scoped to a task, and closed when their work is complete. They are not treated as permanently running actors.

## Workflow
1. Product Owner submits a request.
2. `PM Agent` records the work in `docs/exec_plans.md`.
3. `PM Agent` splits the work by ownership and delegates to the correct sub-agents.
4. `@coder_backend` and `@coder_frontend` implement only inside their assigned boundaries.
5. `@qa` validates runtime behavior when the task changes user-visible flows, browser interactions, or request/response behavior.
6. `@reviewer` validates the combined output against `docs/coding_standards.json`.
7. Only reviewed and passing work is presented as complete.

## Directory Boundaries

### Backend-owned areas
- `app/Http/Controllers/`
- `app/Http/Requests/`
- `app/Models/`
- `app/Policies/`
- `app/Services/`
- `app/Jobs/`
- `app/Console/`
- `bootstrap/`
- `config/`
- `database/`
- `routes/`
- `tests/Feature/`
- `tests/Unit/`

### Frontend-owned areas
- `resources/js/inertia/`
- `resources/js/app.tsx`
- `resources/css/`
- `tests/React/`

### Shared coordination areas
- `docs/`
- `AGENTS.md`
- route names, request/response contracts, shared TypeScript types, and Inertia props

## Repo-specific Rules
- This app uses a custom Inertia layout under `resources/js/inertia/`; do not assume the default Laravel `resources/js/Pages` layout.
- Preserve existing Laravel and Inertia patterns already present in sibling files.
- `SalesCrm` is deprecated and should stay disabled. Do not add new work for it unless explicitly requested.
- Do not introduce new top-level directories without approval.
- Do not change dependencies without approval.
- When package behavior or setup details may have changed, prefer `context7` for current external documentation before relying on model memory.

## Boundaries and Handoffs
- Backend changes that alter payload shape must be communicated as contract changes for frontend implementation.
- Frontend changes must consume existing named routes and shared props rather than inventing alternate data sources.
- User-facing changes and runtime contract changes should include explicit QA expectations when browser validation, request tracing, or screenshot evidence will be needed.
- Reviewer must block completion if a task crosses boundaries without explicit plan approval.
- The `PM Agent` should prefer `viewer` before coding when the task requires repo discovery, dependency tracing, or architectural impact analysis.
- The `PM Agent` should prefer parallel `worker` lanes only when file ownership is disjoint enough to avoid merge conflict churn.

## Completion Criteria
Work is only considered complete when:
- implementation matches the current execution plan,
- required QA validation has been run for touched user-facing or runtime-sensitive behavior, or the skip is explicitly justified,
- standards review passes,
- required verification commands pass,
- and no deprecated module is reactivated by accident.

## MCP Reload Checklist
When MCP configuration changes:
1. Reload or restart the local client that reads project MCP config.
2. Confirm `laravel-boost` and `context7` both appear as available MCP servers.
3. Confirm the client can invoke current-doc tooling from `context7`.
4. If a client keeps stale MCP state, restart the client process before assuming the config is broken.
