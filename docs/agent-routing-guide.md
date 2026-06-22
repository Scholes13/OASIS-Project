# Agent Routing Guide

Use this guide when deciding which lane to use for repository exploration, validation, or review work.

## Terminology Note

In team-facing docs, `viewer` is the primary name for the focused read-only analysis lane. In the current harness, use `viewer` for validation and contract tracing, and `explore` for broader repository discovery.

## Quick Summary

- `viewer`: read and understand
- `explore`: find files and map unfamiliar areas quickly
- `qa`: verify behavior and gather evidence
- `reviewer`: audit the result and report risks

## Cost-Aware Routing

Default to the main lane when the task is small, obvious, and low risk.

Stay in the main lane when:
- the change is confined to one file or one obvious location,
- the task is documentation-only or a tiny wording cleanup,
- the required action is a straightforward edit with no meaningful ambiguity,
- extra exploration or review would cost more tokens than the task itself.

Spawn a subagent when:
- `viewer` is needed to map an unfamiliar area or trace a contract safely,
- `debugger` is needed because the root cause is unclear or logic is heavy,
- `qa` is needed because changed behavior needs pass or fail evidence,
- `reviewer` is needed because the task carries architecture, contract, security, or regression risk.

Avoid this pattern on low-risk work:
- `viewer -> qa -> reviewer`

Use that full chain only when the task crosses layers, changes user-facing behavior, or has enough risk that each gate provides distinct value.

## When to Use Each Lane

### `viewer` / `explore`

Use `viewer` when you need focused read-only analysis before implementation or before deciding what to do next. Use `explore` when the task is broad repository discovery, file finding, or codebase mapping.

Best for:
- finding relevant files quickly
- tracing request and response flow
- understanding contracts between backend and frontend
- inspecting diffs, logs, or outputs
- identifying likely impact before code changes

Good prompts:
- `trace the purchase request approval flow end to end`
- `find which files own the stock request status transition`
- `inspect this diff and summarize the contract changes`

Expected output:
- what was inspected
- key findings
- open questions
- recommended next lane

### `qa`

Use `qa` when you need evidence that behavior works correctly, especially after implementation or while reproducing a reported issue.

Best for:
- smoke tests
- regression checks
- focused test execution
- validation of changed flows
- collecting pass or fail evidence
- checking for missing verification coverage

Good prompts:
- `verify the new purchase request flow and list the tests run`
- `run focused validation for this modal regression`
- `check whether this fix has any obvious regression gaps`

Expected output:
- tests run
- smoke test results
- coverage gaps
- pass or fail status
- remaining risk

### `reviewer`

Use `reviewer` when implementation exists and you need a standards-focused audit before calling the work complete.

Best for:
- code review
- architecture boundary checks
- security review
- contract validation
- error handling review
- test coverage and verification review

Good prompts:
- `review this patch for bugs and risks`
- `check whether this work violates coding standards`
- `audit this change for contract drift and missing tests`

Expected output:
- findings with severity
- affected files or areas
- recommendations
- risk summary

## Quick Decision Table

| If you need to... | Use |
| --- | --- |
| understand the system first | `viewer` |
| prove behavior works | `qa` |
| judge whether the change is safe and ready | `reviewer` |

## Recommended Default Sequence

For most non-trivial work, use this order:

1. `viewer` or `explore` to map context and dependencies.
2. `coder-backend`, `coder-frontend`, or `debugger` to implement or fix the issue.
3. `qa` to validate behavior and gather evidence.
4. `reviewer` to perform the final standards and risk gate.

## By Task Size

### Small and obvious

Examples:
- one-line docs fix
- rename in one known file
- tiny copy tweak in an existing UI component

Default:
- stay in the main lane
- do not spawn subagents unless something becomes ambiguous

### Medium but contained

Examples:
- a fix across 2-4 related files
- a UI behavior adjustment with focused verification needs
- a backend contract tweak with one downstream consumer

Default:
- use `viewer` if context is unclear
- use `qa` if behavior changed
- use `reviewer` if risk is meaningful

### Large, cross-surface, or risky

Examples:
- backend and frontend both change
- user-facing workflow changes
- complex debugging with uncertain root cause
- security-sensitive or contract-sensitive work

Default:
- `viewer` for mapping if needed
- `coder` or `debugger` for implementation
- `qa` for evidence
- `reviewer` for final gate

## Short Rule of Thumb

- `viewer`: what is happening?
- `qa`: does it work?
- `reviewer`: is it safe and ready?

## Refactor-Specific Routing

When the task is to bring a file under hard cap (see `AGENTS.md` File Size & Write Operation Standards):

- **`explore`**: survey full repo for files >= 500 / 300 lines, produce inventory + suggested splits per top-N file. Use this BEFORE refactor batches.
- **`coder-backend`** + **`coder-frontend`** in parallel: dispatch with explicit hard caps + suggested extractions + chunked write protocol reminder. Token-efficient when scopes don't overlap (e.g., backend services vs frontend components).
- **Resume tasks** (same `task_id`): use when an agent stopped mid-work. Brief them on remaining state, not from scratch.
- **Small refactor (1 file < 800 lines)**: stay in main lane with `apply_patch` / `edit` operations.
- **Large refactor (3+ files or 1 file > 1000 lines)**: dispatch coder agent with detailed instructions, then verify + commit per logical group.

Refactor agent prompt MUST include:
1. Hard cap target (e.g., < 500 lines)
2. Suggested extractions with proposed file names + line counts
3. Reference patterns already on disk
4. Chunked write protocol reminder
5. Verification commands list
6. Multi-line JSX requirement (frontend)
7. NO API/contract change rule
8. Surgical edit only — NEVER full rewrite
