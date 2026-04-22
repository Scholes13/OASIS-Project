# Agent Routing Guide

Use this guide when deciding which lane to use for repository exploration, validation, or review work.

## Terminology Note

In team-facing docs, `viewer` is the primary name for the read-only exploration lane. In the underlying harness, that lane is commonly implemented with the `explorer` agent type.

## Quick Summary

- `viewer`: read and understand
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

### `viewer`

Use `viewer` when you need read-only exploration before implementation or before deciding what to do next.

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

1. `viewer` to map context and dependencies.
2. `coder` or `debugger` to implement or fix the issue.
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
