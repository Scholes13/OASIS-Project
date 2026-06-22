# Task Comments Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add flat comments to Activity tasks with edit/delete, rate limiting, and notification integration.

**Architecture:** New `task_comments` table with soft deletes. `TaskCommentController` handles CRUD with authorization (participants only, cancelled tasks read-only). `TaskCommentNotification` dispatches to participants via database + broadcast channels. Frontend replaces the placeholder Comments tab in `TaskDetailModal` with a working comment list and input.

**Tech Stack:** Laravel 12, PHP 8.2, Inertia.js v2, React 19, TypeScript, Tailwind CSS v3, PHPUnit 11, Vitest.

---

## Chunk 1: Backend — Migration, Model, Controller

### Task 1: Create migration and model

**Files:**
- Create: `database/migrations/modules/activity/2026_04_22_100000_create_task_comments_table.php`
- Create: `app/Models/Modules/Activity/TaskComment.php`
- Modify: `app/Models/Modules/Activity/EmployeeTask.php`

- [ ] **Step 1: Create migration**

```php
Schema::create('task_comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_task_id')->constrained('employee_tasks')->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->text('body');
    $table->timestamp('edited_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->index(['employee_task_id', 'created_at']);
});
```

- [ ] **Step 2: Create TaskComment model**

With: `SoftDeletes`, relationships `task()`, `user()`, fillable `['employee_task_id', 'user_id', 'body', 'edited_at']`.

- [ ] **Step 3: Add `comments()` relationship to EmployeeTask**

```php
public function comments(): HasMany
{
    return $this->hasMany(TaskComment::class, 'employee_task_id');
}
```

- [ ] **Step 4: Run migration**

Run: `php artisan migrate`

### Task 2: Write failing backend tests

**Files:**
- Create: `tests/Feature/Modules/Activity/TaskCommentTest.php`

- [ ] **Step 1: Write tests for comment CRUD + authorization**

Tests:
- participant can post comment
- creator can post comment
- non-participant denied
- cancelled task rejects comment
- whitespace-only comment rejected
- author can edit own comment (sets edited_at)
- author can delete own comment (soft delete)
- non-author cannot edit/delete
- comment list returns only non-deleted comments for task
- rate limit: 11th comment in 1 minute returns 429

- [ ] **Step 2: Run tests to confirm failure**

Run: `php artisan test tests/Feature/Modules/Activity/TaskCommentTest.php`
Expected: FAIL (routes and controller don't exist yet)

### Task 3: Implement controller and routes

**Files:**
- Create: `app/Http/Controllers/Modules/Activity/TaskCommentController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create TaskCommentController**

Methods:
- `store(Request $request, EmployeeTask $task)` — validate body, check participant, check not cancelled, create, notify
- `update(Request $request, EmployeeTask $task, TaskComment $comment)` — check author, validate, update body + edited_at
- `destroy(EmployeeTask $task, TaskComment $comment)` — check author, soft delete

- [ ] **Step 2: Add routes inside activity task group**

In `routes/web.php` inside the `task` prefix group (after line 320):

```php
Route::prefix('{task}/comments')->name('comments.')->whereNumber('task')->group(function () {
    Route::post('/', [TaskCommentController::class, 'store'])->name('store')->middleware('throttle:10,1');
    Route::put('/{comment}', [TaskCommentController::class, 'update'])->name('update')->whereNumber('comment');
    Route::delete('/{comment}', [TaskCommentController::class, 'destroy'])->name('destroy')->whereNumber('comment');
});
```

- [ ] **Step 3: Run tests**

Run: `php artisan test tests/Feature/Modules/Activity/TaskCommentTest.php`
Expected: PASS

### Task 4: Create notification class

**Files:**
- Create: `app/Notifications/Activity/TaskCommentNotification.php`

- [ ] **Step 1: Create TaskCommentNotification**

- `via()`: `['database', 'broadcast']`
- `toBroadcast()`: return BroadcastMessage with toArray payload
- `toArray()`: normalized payload with category `activity`, event `task_comment`, title, message (first 100 chars), action_url, priority `normal`, occurred_at, actor, entity

- [ ] **Step 2: Add notification dispatch to store method**

After creating comment, notify all participants + creator except commenter:

```php
$recipients = $task->participants->merge([$task->creator])->unique('id')->reject(fn ($u) => $u->id === $user->id);
foreach ($recipients as $recipient) {
    $recipient->notify(new TaskCommentNotification($comment, $user, $task));
}
```

- [ ] **Step 3: Write notification test**

Add to `TaskCommentTest.php`:
- notification dispatched to participants except commenter
- notification payload matches center contract

- [ ] **Step 4: Run all tests**

Run: `php artisan test tests/Feature/Modules/Activity/TaskCommentTest.php`
Expected: PASS

## Chunk 2: Load comments in task detail

### Task 5: Eager load comments in task detail

**Files:**
- Modify: `app/Http/Controllers/Modules/Activity/ActivityInertiaController.php`

- [ ] **Step 1: Add comments to eager load in `getSelectedTaskForModal`**

At line ~1575, add `'comments'` to the `with()` array:

```php
->with([
    'activityType',
    'subActivity',
    'participants',
    'creator',
    'department',
    'attachments',
    'comments' => fn ($q) => $q->with('user:id,name')->latest()->limit(50),
])
```

- [ ] **Step 2: Transform comments in task props**

Add `can_edit` and `can_delete` flags to each comment based on current user:

```php
$task->comments->map(fn ($c) => [
    'id' => $c->id,
    'user' => $c->user ? ['id' => $c->user->id, 'name' => $c->user->name] : null,
    'body' => $c->body,
    'edited_at' => $c->edited_at?->toISOString(),
    'created_at' => $c->created_at->toISOString(),
    'can_edit' => $c->user_id === auth()->id(),
    'can_delete' => $c->user_id === auth()->id(),
]);
```

- [ ] **Step 3: Add TypeScript type**

In `resources/js/inertia/types/index.ts` or a new `types/activity.ts`:

```typescript
export interface TaskComment {
    id: number;
    user: { id: number; name: string } | null;
    body: string;
    edited_at: string | null;
    created_at: string;
    can_edit: boolean;
    can_delete: boolean;
}
```

## Chunk 3: Frontend — Comment UI

### Task 6: Implement comment section in TaskDetailModal

**Files:**
- Modify: `resources/js/inertia/components/activity/TaskDetailModal.tsx`
- Create: `resources/js/inertia/components/activity/TaskCommentSection.tsx`

- [ ] **Step 1: Create TaskCommentSection component**

Features:
- Renders list of comments (chronological, oldest first)
- Each comment: user initial avatar, name, relative time, body, "(edited)" if edited_at
- Edit/delete dropdown menu for own comments (based on can_edit/can_delete)
- Comment input textarea + Post button at bottom
- Disabled state when task is cancelled
- Empty state: "No comments yet. Start the conversation."
- Uses `useForm` from Inertia for post/edit/delete

- [ ] **Step 2: Replace placeholder in TaskDetailModal**

Replace the disabled "Comments" tab content with `<TaskCommentSection>`.

- [ ] **Step 3: Run TypeScript check**

Run: `npx tsc --noEmit --pretty false`
Expected: PASS

### Task 7: Write frontend tests

**Files:**
- Create: `tests/React/Components/Activity/TaskCommentSection.test.tsx`

- [ ] **Step 1: Write tests**

Tests:
- renders comment list
- renders empty state when no comments
- shows edit/delete only for own comments
- post button disabled when input empty
- cancelled task disables input
- edited comment shows "(edited)"

- [ ] **Step 2: Run frontend tests**

Run: `npx vitest run tests/React/Components/Activity/TaskCommentSection.test.tsx`
Expected: PASS

## Chunk 4: Verification

### Task 8: Final verification

- [ ] **Step 1: Run all backend tests**

Run: `php artisan test tests/Feature/Modules/Activity/TaskCommentTest.php tests/Feature/Core/NotificationCenterTest.php`

- [ ] **Step 2: Run all frontend tests**

Run: `npx vitest run tests/React/Components/Activity/TaskCommentSection.test.tsx tests/React/Components/Layout/NotificationBell.test.tsx`

- [ ] **Step 3: Run formatting and type checks**

Run: `php vendor/bin/pint --dirty`
Run: `npx tsc --noEmit --pretty false`

- [ ] **Step 4: Build production assets**

Run: `npx vite build`

- [ ] **Step 5: Update exec_plans.md**

Add task comments entry with status `implemented`.
