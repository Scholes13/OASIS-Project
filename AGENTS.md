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
  - `@viewer` => spawn `viewer` for focused read-only analysis work
  - broad repository discovery => spawn `explore`
  - `@coder_backend` => spawn `coder-backend`
  - `@coder_frontend` => spawn `coder-frontend`
  - unclear root-cause bugs => spawn `debugger`
  - `@qa` => spawn `qa`
  - `@reviewer` => spawn `reviewer`
  - research or synthesis outside specialist lanes => spawn `general`
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

## File Size & Write Operation Standards (Project-Wide)

### Hard size caps per file type
| Category | Soft target | Hard cap |
|---|---:|---:|
| React component | 250 | 400 |
| Inertia page | 300 | 500 |
| Custom hook | 80 | 150 |
| PHP controller | 250 | 500 |
| PHP service | 200 | 350 |
| PHP model | 250 | 400 |
| PHP action class | 150 | 300 |
| Constants/types/data | exempted | exempted |

Files that exceed the hard cap MUST be split into focused modules in the next refactor pass. Generated files (manifests, lockfiles) are exempted.

### Chunked write protocol (MANDATORY for all sub-agents and main lane)
- Maximum 350 lines per single `write` or `edit` operation. Recommended 300 lines or less.
- For NEW files larger than 300 lines: write the first 250-300 line chunk, then append remaining content in 250-300 line follow-up operations.
- For EDITING existing files: use surgical edits that change only what is needed. Never rewrite an entire file to change a small section.
- For LARGE refactors: split into multiple small focused edits (e.g., one method body at a time, one section at a time).
- Reasoning: server enforces a per-operation timeout; oversized writes fail and waste tokens. Multiple small operations are faster and more reliable than one large operation.

### Forbidden patterns
- Minified/compressed JSX into single 4000+ character lines to artificially reduce file line count. JSX must use multi-line formatting with each prop on its own line for elements with 3+ props.
- Full file replacement when only 5-20 lines need to change.
- Skipping verification (`php artisan test`, `npm run build`, `npx tsc`) after refactor work.

## Refactor Patterns (project-established, follow when splitting oversized files)

### Backend split patterns
- **Action classes** under `app/Actions/Modules/<Module>/<Resource>/` for write operations (Create, Update, Destroy, MarkX, Resubmit, etc). Each action class < 300 lines. Constructor DI of services. Single `execute()` method returning result array.
- **Query/Read services** under `app/Services/Modules/<Module>/<Resource>/<Resource>QueryService.php` for index/show/form-data queries. < 350 lines.
- **Document/Export services** under same path for PDF/CSV/XLS generation. Stream response from service.
- **Shared services** under `app/Services/Modules/<Module>/Shared/` when 2+ resources need the same logic (e.g., `PdfGenerationService`, `RequestFormDataProvider`, `ApprovalAuthorityResolver`).
- **Controllers** become thin orchestrators: validate request → call action/service → return response. Auth/policy checks remain in controller.
- **Models** stay focused on relationships, scopes, accessors. Move conditional business logic to dedicated `Resolver` services with thin proxy methods on the model for backward compatibility.

### Frontend split patterns
- **Page components** (Inertia pages) under 500 lines. Split heavy sections into focused sub-components in `resources/js/inertia/components/<module>/<resource>/` (e.g., `PurchaseRequestHeader`, `PurchaseRequestSummaryPanel`).
- **Modals** as standalone components with explicit prop contracts. Reusable modals (e.g., `ApprovalDecisionModal` covers approve+reject via `mode` prop).
- **Custom hooks** under `resources/js/inertia/hooks/` for shared state patterns (e.g., `useDebouncedSearch`, `usePagination`, `useClickOutside`, `useActivityFilters`).
- **Pure utilities** under `resources/js/inertia/lib/` (e.g., `formatters.ts`, `dateFilters.ts`, `<module>Constants.ts`, `<module>Calculators.ts`).
- **Type extraction** to `resources/js/inertia/types/<module>.ts` (data-file exempt from line caps).

### Verification gates per refactor
1. `php -l <file>` after every PHP edit
2. `vendor/bin/pint --dirty` before commit
3. `php artisan test --filter="<Module>"` for focused module
4. `php artisan test` full suite — ensure baseline preserved
5. `npx tsc --noEmit --pretty false` — only pre-existing `echo.ts` errors acceptable
6. `npm run build` clean
7. `php artisan route:list` — confirm route parity preserved
8. Read tail of touched files — confirm under hard cap

### Commit style for refactor work
- `refactor(<module>): split <files> below <cap>` with breakdown of before/after line counts in body, list of new modules, deviations + reasoning.
- Group commits by concern: backend / frontend / docs separate.
- One refactor batch = one commit when files are tightly coupled. Multiple commits when files are independent.
