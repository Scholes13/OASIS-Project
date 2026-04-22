# Activity Calendar Owner Visibility Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make activity calendar entries clearly show task ownership with compact avatars or initials, especially in month view.

**Architecture:** Keep the existing FullCalendar integration and improve only event rendering. Reuse the current task participant payload to derive a primary visible owner plus overflow count, and preserve status/type signals by separating them visually from owner identity.

**Tech Stack:** React 19, TypeScript, Inertia.js, FullCalendar, Vitest, Testing Library

---

## Chunk 1: Calendar owner rendering

### Task 1: Add regression coverage for owner visibility

**Files:**
- Modify: `tests/React/Components/Activity/ActivityCalendarCreateEntry.test.tsx`
- Modify: `resources/js/inertia/components/activity/ActivityCalendar.tsx`

- [ ] Step 1: Write a failing test that renders mocked calendar event content for month view and expects a participant initial or avatar fallback plus overflow count.
- [ ] Step 2: Run the focused Vitest file and confirm the new assertion fails for the missing owner marker.
- [ ] Step 3: Implement compact owner rendering in `ActivityCalendar.tsx` for month and richer owner display for week/day.
- [ ] Step 4: Re-run the focused Vitest file and confirm it passes.
- [ ] Step 5: Run `npm exec tsc --noEmit --pretty false` and confirm the touched frontend code remains type-safe.

### Task 2: Self-review for enterprise scanability

**Files:**
- Modify: `resources/js/inertia/components/activity/ActivityCalendar.tsx`

- [ ] Step 1: Check that status dot, title, and owner marker are visually separated in month view.
- [ ] Step 2: Check that tasks with no participants still render a stable fallback.
- [ ] Step 3: Check that multiple participants collapse to one visible owner and `+n`.
