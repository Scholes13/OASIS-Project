# Notification Center Design

**Date:** 2026-04-20
**Decision:** Add a global in-app notification center using Laravel database notifications and a modern enterprise Inertia UI.
**Recommendation:** Reuse Laravel native notifications for the backend, standardize the notification payload shape, and ship a desktop-first notification center with a navbar bell, recent dropdown, and full archive page.

---

## Context

This repository already uses Laravel notifications for purchasing approvals, activity backdate flows, and admin task alerts. The application does not yet expose a unified in-app notification center, unread badge, or archive page, so important events are fragmented across email, database rows, and page-local flows. The requested feature is an enterprise-style notification center that feels modern, stays high-signal, and initially focuses on awareness rather than real-time collaboration.

## Goals

- add a global notification center available from the authenticated Inertia layout,
- show unread notification count in the navbar,
- provide a recent-notifications dropdown and a full notifications page,
- mark notifications as read when the user opens a notification item,
- include a high-signal "Enterprise Core Pack" of notification categories,
- add activity tag notifications as knowledge notifications without approval or acknowledgement workflows,
- preserve the existing email flow for enterprise-critical notifications where email is already appropriate.

## Non-Goals

- introducing WebSockets, Echo, Pusher, or any realtime transport,
- creating a separate custom notifications table,
- adding per-user notification preferences in phase 1,
- notifying users for every small edit or low-value activity change,
- introducing inline approval actions inside the notification dropdown,
- changing deprecated `SalesCrm` behavior.

## Product Direction

The notification center should behave like a modern enterprise workspace, not like a social activity feed. Notifications should be sparse, high-signal, and clearly actionable. Copy should stay in English and communicate three things quickly: who triggered the event, what happened, and where the user should go next.

The approved baseline is the `Enterprise Core Pack`:

- `Activity`
  - user tagged in an activity,
  - relevant task status changes for creators and participants,
  - relevant due date changes for users involved in the task.
- `Purchasing`
  - PR approval requested,
  - PR approved, rejected, completed,
  - ST approval requested,
  - ST approved, rejected, completed,
  - purchasing admin task assigned,
  - targeted SLA warnings.
- `Backdate`
  - request submitted for approvers,
  - request approved or rejected for requester.
- `System`
  - important announcements or alerts authored by the application.

## UX Surface

Phase 1 adds three user-facing surfaces:

1. a `NotificationBell` in the authenticated navbar,
2. a `NotificationDropdown` showing the most recent notifications,
3. a full `Notifications/Index` page for archive, filtering, and bulk mark-as-read.

The bell should show an unread badge capped at `99+`. The dropdown should open from the bell, list the newest items first, and expose a footer link to `View all notifications`. Each row should show a category icon, a short title, a supporting message, and relative time. Unread rows should be visually distinct but restrained.

The full page should provide a predictable enterprise archive with filters for:

- `All`
- `Unread`
- `Activity`
- `Purchasing`
- `Backdate`
- `System`

The full page should support pagination, empty states, and `Mark all as read`. Opening the page itself must not mark everything as read.

## Activity Tag Behavior

Tagging in Activity is a knowledge notification, not an approval flow.

- When user `A` tags user `B` in an activity, `B` receives an in-app notification.
- Example title: `You were tagged by Amanda in Activity Budget Review`
- Opening the notification marks it as read and routes the user into the relevant activity detail or dashboard surface.
- There is no `Approve`, `Reject`, or `Acknowledge` action in phase 1.

This keeps the phase-1 experience minimal, traceable, and consistent with the approved requirement.

## Backend Architecture

The backend should continue using Laravel's built-in database notifications. This repository already stores database notifications and already uses notification classes for purchasing and activity flows. The new work should standardize payloads and expose a consistent read/list contract to the frontend instead of inventing a second persistence model.

Each notification `toArray()` payload should converge on the following shape where applicable:

- `category`
- `event`
- `title`
- `message`
- `action_url`
- `actor` with enough summary data for display,
- `entity` with enough summary data for routing or labels,
- `priority`
- `occurred_at`

Legacy keys can remain temporarily if needed for compatibility, but all touched notification classes in this feature should emit the normalized fields so the frontend can render a consistent list with minimal branching.

## Read, List, And Routing Contract

The application should expose authenticated notification endpoints that support:

- fetching recent notifications for the dropdown,
- listing paginated notifications for the archive page,
- marking a single notification as read,
- marking all notifications as read.

For notification-open behavior, phase 1 should use an authenticated controller action that:

1. verifies the notification belongs to the current user,
2. marks it as read if it is unread,
3. redirects to the notification's `action_url`,
4. falls back safely if the `action_url` is missing or invalid.

This keeps read semantics consistent and avoids frontend-only race conditions.

## Shared Inertia Data

`HandleInertiaRequests` should expose a lightweight unread count in shared props for authenticated users. It should not ship full notification lists globally because that would bloat every Inertia response. Dropdown content should be loaded lazily through a focused route or fetched only when needed.

## Event Emitters And Domain Touchpoints

The feature will need backend emitters in the following areas:

- Activity task create or update flows when participants are newly added,
- Activity task status or due-date update flows when a relevant user should be notified,
- purchasing approval request and result notifications that already exist but need normalized payloads,
- purchasing admin task assignment notifications,
- activity backdate notifications,
- a minimal system notification publishing surface if a safe existing admin surface already exists; otherwise the system category can initially remain contract-ready without authoring UI in this phase.

Activity tagging should only notify newly tagged users. Updating a task without changing tagged participants must not re-notify everyone. Likewise, due-date and status notifications should avoid obvious duplicate spam when the value did not meaningfully change.

Current repository mapping matters here: the existing task create and update participant sync logic lives in `ActivityInertiaController`, not in `TaskService`. Phase-1 implementation should either add notification emission at those controller mutation points or first refactor that flow into a shared service and then emit there. The design does not assume `TaskService` is currently the source of truth for participant writes.

The current backdate notification route payload also needs a correctness fix because the existing `BackdateRequestSubmitted` notification points to an invalid named route. That fix should be treated as part of the notification-center baseline so the unified center does not normalize broken links.

Stock Request coverage also requires explicit follow-up work because the repository already has `ApprovalRequested` but does not yet emit the full approved, rejected, and completed requester-facing notifications described in the enterprise pack. Likewise, the existing purchasing admin `TaskAssigned` notification class must be wired to a real assignment mutation path if it is going to appear in the notification center.

## Error Handling

- Notification-open routes must never expose another user's notification.
- Missing or malformed `action_url` should redirect to the notifications archive with a flash warning instead of throwing a user-facing error.
- If dropdown fetch fails, the UI should degrade gracefully and allow users to open the full archive page.
- Bulk mark-as-read should operate only on the authenticated user's notifications.

## Testing Strategy

Backend verification should cover:

- unread count sharing,
- recent list and archive list authorization,
- notification open marks-as-read behavior,
- mark-all-as-read behavior,
- activity tag notification emission only for newly tagged users,
- any touched purchasing and backdate payload normalization.

Frontend verification should cover:

- bell badge rendering,
- dropdown open and recent-list rendering,
- archive page empty, unread, and filtered states,
- mark-all-as-read UI behavior,
- type-safe notification rendering without `any`.

QA should validate the user-facing flows end to end because the feature changes shared authenticated navigation and cross-module runtime behavior.

## Acceptance Criteria

- Authenticated users can see a global notification bell with unread count.
- Users can open a dropdown with recent notifications.
- Users can open a full notifications page with filtering and pagination.
- Opening a notification marks it as read and redirects correctly.
- Activity tagging sends a knowledge notification to newly tagged users.
- Touched purchasing and backdate notifications render correctly in the unified center.
- The current invalid backdate notification route is corrected.
- Stock Request result notifications and purchasing admin task assignment notifications are only listed as complete when they are actually emitted by runtime flows.
- Copy is in English and visually consistent with a modern enterprise UI.
- No realtime dependency or custom notification table is introduced.
