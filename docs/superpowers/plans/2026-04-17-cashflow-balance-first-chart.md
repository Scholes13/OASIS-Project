# Cashflow Balance-First Chart Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refine the cashflow projection chart so multi-period views emphasize projected balance and keep inflow/outflow as tooltip detail only.

**Architecture:** Keep the existing `ProjectionChartCard` structure and tooltip contract, but simplify the composed chart series in multi-period mode by removing the extra movement line. Protect the behavior with focused React coverage that proves the noisy helper series is gone while the balance axis remains.

**Tech Stack:** React 19, TypeScript, Recharts, Vitest, Testing Library

---

### Task 1: Lock the intended chart behavior with a failing test

**Files:**
- Modify: `tests/React/Pages/CashflowProjection/ProjectionChartCard.test.tsx`

- [ ] Add a focused multi-period chart test that asserts only the balance line renders when balance data is present.
- [ ] Run `npm exec vitest run tests/React/Pages/CashflowProjection/ProjectionChartCard.test.tsx --runInBand`.
- [ ] Confirm the new assertion fails because the legacy movement line still renders.

### Task 2: Simplify the multi-period chart to enterprise balance-first mode

**Files:**
- Modify: `resources/js/inertia/Pages/CashflowProjection/components/ProjectionChartCard.tsx`

- [ ] Remove the helper movement line and any now-unused derived data from the multi-period chart.
- [ ] Preserve the existing balance line, threshold reference line, legend entries, and tooltip values for inflow/outflow.
- [ ] Keep single-day chart behavior unchanged.

### Task 3: Verify the frontend contract

**Files:**
- Modify: `tests/React/Pages/CashflowProjection/ProjectionChartCard.test.tsx`

- [ ] Re-run `npm exec vitest run tests/React/Pages/CashflowProjection/ProjectionChartCard.test.tsx --runInBand` and confirm it passes.
- [ ] Run `npm exec tsc --noEmit --pretty false`.
- [ ] Review the touched files against `docs/coding_standards.json` before reporting completion.
