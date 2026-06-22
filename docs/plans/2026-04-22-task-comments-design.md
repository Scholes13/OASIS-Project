# Task Comments Design

**Date:** 2026-04-22
**Decision:** Add threaded-flat comments to Activity tasks with edit/delete and notification integration.

---

## Context

The Activity module currently has a placeholder Comments tab in `TaskDetailModal` that is disabled and shows "Coming soon". Users have no way to discuss tasks inside the system, forcing communication to WhatsApp/email which breaks context. Comments are the most fundamental collaboration feature missing compared to enterprise tools like Asana.

## Goals

- Allow participants and creators to post comments on tasks.
- Allow comment authors to edit and delete their own comments.
- Notify all task participants when a new comment is posted.
- Integrate with the existing notification center (bell, dropdown, archive page).
- Keep the implementation minimal and extensible for future @mentions and activity history.

## Non-Goals

- @mentions in comments (next phase).
- Rich text or markdown rendering (plain text only).
- File attachments on comments (already supported at task level).
- Threaded/nested replies (flat chronological list, like Asana default).
- Reactions/emoji.
- Email notification for comments (too frequent; in-app only).
- Comment edit history / versioning (edited_at timestamp is sufficient for phase 1).

## Backend Architecture

### Migration: `task_comments`

| Column | Type | Notes |
|---|---|---|
| `id` | bigIncrements | PK |
| `employee_task_id` | foreignId | FK to `employee_tasks.id`, cascade on delete (task deleted = comments destroyed) |
| `user_id` | foreignId | FK to `users.id`, set null on delete (preserve comment if user deactivated) |
| `body` | text | Comment content, required, min 1 char |
| `edited_at` | timestamp nullable | Set when author edits |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp nullable | Soft delete |

**Indexes:**
- `employee_task_id` (FK index, auto)
- `user_id` (FK index, auto)
- Composite: `(employee_task_id, created_at)` for efficient chronological listing

### Model: `App\Models\Modules\Activity\TaskComment`

- `task()` -> BelongsTo EmployeeTask
- `user()` -> BelongsTo User
- Uses `SoftDeletes`
- `edited_at` set on update

### Controller: `TaskCommentController`

Routes inside the authenticated activity task group:

- `POST /activity/task/{task}/comments` -> `store`
  - Validate: `body` required, string, min:1 (after trim), max:2000
  - Authorization: user must be creator or participant of the task
  - Task must not be in `cancelled` status (cancelled tasks are read-only)
  - Trim whitespace; reject blank/whitespace-only comments
  - Create comment
  - Dispatch `TaskCommentNotification` to all participants + creator except commenter
  - Return redirect back (Inertia)
  - **Rate limit:** `throttle:10,1` (max 10 comments per minute per user)

- `PUT /activity/task/{task}/comments/{comment}` -> `update`
  - Authorization: only comment author
  - Validate: `body` required, string, min:1 (after trim), max:2000
  - Update body, set `edited_at = now()`
  - Return redirect back

- `DELETE /activity/task/{task}/comments/{comment}` -> `destroy`
  - Authorization: only comment author
  - Soft delete
  - Return redirect back

### Task Deletion Policy

When a task is hard-deleted, all comments are cascade-deleted from the database. This is intentional — deleted tasks have no audit requirement for comments. The task itself is the unit of retention.

### Participant Removal Policy

When a participant is removed from a task:
- They lose the ability to post new comments or edit/delete existing ones.
- Their existing comments remain visible to current participants (the comment record stays, authorship preserved).
- They lose access to the task detail entirely (existing task visibility rules apply).

### Notification: `TaskCommentNotification`

- `via()`: `['database', 'broadcast']`
- No email (comments are too frequent for email)
- Dispatched directly via `$user->notify()` (not through `EmailNotificationService` since there is no mail channel)
- **Spam mitigation:** If the same user posts multiple comments on the same task within 60 seconds, only the first comment triggers notifications. Subsequent rapid comments are batched — the notification title becomes "{commenter_name} posted {count} comments on {task_title}".
- Payload (matches existing notification center contract exactly):
  - `category`: `activity`
  - `event`: `task_comment`
  - `type`: `task_comment`
  - `title`: `"{commenter_name} commented on {task_title}"`
  - `message`: first 100 chars of comment body (trimmed)
  - `action_url`: `route('activity.task.show', $task)`
  - `priority`: `normal`
  - `occurred_at`: comment `created_at` ISO string
  - `actor`: `{ id, name }` of commenter
  - `entity`: `{ type: 'activity_task', id, title }` of task

### Authorization

- Only task creator and participants can post comments.
- Only comment author can edit/delete their own comments.
- Task must belong to the user's current business unit (enforced by existing task visibility rules).
- **Cancelled tasks are read-only** — no new comments, edits, or deletes allowed. UI disables the comment input and action menus.
- **Removed participants** lose write access immediately but their existing comments remain visible to current participants.
- Cross-BU access is blocked by the existing task scoping — a user can only see tasks in their current BU context.

## Frontend Architecture

### TaskDetailModal Changes

Replace the disabled Comments placeholder tab with a working comment section:

**Comment List:**
- Chronological order (oldest first, newest at bottom)
- Each comment shows: user avatar initial, user name, relative timestamp, body text
- If `edited_at` is set, show "(edited)" label
- If comment is by current user, show edit/delete action menu (three-dot or icon)
- Soft-deleted comments are not shown

**Comment Input:**
- Textarea at the bottom of the comment list
- "Post" button, disabled when empty or submitting
- Uses Inertia `useForm` for submission
- Clears after successful post

**Edit Mode:**
- Clicking edit replaces the comment body with a textarea pre-filled with current text
- Save/Cancel buttons
- Uses Inertia `useForm` for PUT request

**Delete:**
- Confirm dialog before delete
- Uses Inertia router.delete

### Props

Comments loaded as part of task detail props, capped at **50 most recent**. If the task has more than 50 comments, a "Load earlier comments" link fetches the next page via a JSON endpoint (`GET /activity/task/{task}/comments?before={id}`).

Shape:
```typescript
interface TaskComment {
    id: number;
    user: { id: number; name: string } | null; // null if user was deleted
    body: string;
    edited_at: string | null;
    created_at: string;
    can_edit: boolean;   // server-computed: true if current user is author
    can_delete: boolean; // server-computed: true if current user is author
}
```

### Real-time

New comments arrive via the existing Echo private channel (`App.Models.Core.User.{id}`). The `useNotifications` hook already handles broadcast notifications. When a `task_comment` notification arrives, the bell badge increments. User can click to navigate to the task.

Full real-time comment streaming (seeing comments appear without refresh) is not in scope for phase 1. User refreshes or re-opens the task detail to see new comments.

## Testing Strategy

### Backend
- Comment CRUD: store, update, destroy
- Authorization: only participants can comment, only author can edit/delete
- Removed participant cannot post new comment
- Non-member / cross-BU user denied
- Cancelled task rejects new comments
- Whitespace-only comment rejected
- Rate limiting: 11th comment in 1 minute returns 429
- Notification: dispatched to participants except commenter
- Notification payload matches center contract (category, event, title, message, action_url, priority, occurred_at)
- Soft delete: comment hidden but exists in DB
- Task deletion cascades comments

### Frontend
- Comment list renders (chronological, oldest first)
- Post comment form works
- Edit/delete actions visible only for own comments
- Empty state when no comments
- Cancelled task: comment input disabled
- Edited comment shows "(edited)" label
- Loading/submitting state on Post button

## Acceptance Criteria

- Task participants can post, edit, and delete comments.
- All participants receive in-app notification when a comment is posted.
- Comments appear in the notification center under category "activity".
- Clicking a comment notification navigates to the task detail.
- Deleted comments are soft-deleted and hidden from UI.
- Edited comments show "(edited)" indicator.
