# Activity Admin Task Modal Design

**Goal:** Align Activity Admin task detail with the cleaner `My Tasks` modal journey while preserving the existing admin full-page route as a fallback for direct access.

## Approved Direction

- Activity Admin task detail uses the same modal shell and visual language as the existing `My Tasks` detail modal.
- The modal is the primary interaction path from Activity Admin task lists, especially department detail pages.
- The standalone admin detail page at `/activity/admin/task/{id}` remains available as a fallback for direct access and shared links, but it is no longer promoted inside the modal UI.
- The modal should feel enterprise-clean: minimal distractions, strong hierarchy, and utility-first labels.

## UX Notes

- Clicking a task title in Activity Admin should open a modal without losing table context.
- The modal should default to read-only behavior for admins. Existing quick actions only appear when they are valid for the current user and context.
- Admin UI should not introduce a second visual language for task detail. Reuse existing proven patterns first.
- `Open full page` is removed from the modal because it adds little value to the core daily workflow.

## Technical Direction

- Reuse `resources/js/inertia/components/activity/TaskDetailModal.tsx` by extending it with a small admin-specific mode instead of duplicating markup.
- Add URL-synced modal state to Activity Admin department detail so deep-link style state remains possible if needed, while keeping the full page route alive.
- Hydrate the selected admin task from the controller when the modal is requested from the URL.
- Keep the current full-page admin task detail page as a fallback surface.
