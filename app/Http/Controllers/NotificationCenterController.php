<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationCenterController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = (string) $request->query('filter', 'all');

        if ($filter === 'unread') {
            $query = $request->user()
                ->unreadNotifications()
                ->latest();
        } else {
            $query = $request->user()
                ->notifications()
                ->latest();

            if (in_array($filter, ['activity', 'purchasing', 'backdate', 'system'], true)) {
                $query->whereRaw("json_extract(data, '$.category') = ?", [$filter]);
            }
        }

        $notifications = $query->paginate(15)
            ->through(fn (DatabaseNotification $notification): array => $this->formatNotification($notification));

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'filters' => [
                'active' => $filter,
            ],
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => $this->formatNotification($notification))
            ->values();

        return response()->json([
            'data' => $notifications,
        ]);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()
            ->route('notifications.index', ['filter' => $request->query('filter', 'all')])
            ->with('success', 'All notifications marked as read.');
    }

    public function open(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless(
            $notification->notifiable_type === $request->user()::class
            && (int) $notification->notifiable_id === $request->user()->id,
            403
        );

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $actionUrl = $notification->data['action_url'] ?? null;

        if (! is_string($actionUrl) || trim($actionUrl) === '') {
            return redirect()
                ->route('notifications.index')
                ->with('warning', 'Notification link is unavailable.');
        }

        // Extract the path (and query string) from the stored URL so the
        // redirect always targets the current host.  Stored action_urls
        // may contain a different host (e.g. localhost vs 127.0.0.1)
        // which would cause a cross-origin redirect and session loss.
        $parsed = parse_url($actionUrl);
        $path = ($parsed['path'] ?? '/').
            (isset($parsed['query']) ? '?'.$parsed['query'] : '').
            (isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '');

        return redirect()->to($path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatNotification(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->data['title'] ?? $notification->data['message'] ?? 'Notification',
            'message' => $notification->data['message'] ?? '',
            'category' => $notification->data['category'] ?? 'system',
            'event' => $notification->data['event'] ?? ($notification->data['type'] ?? 'notification'),
            'action_url' => $notification->data['action_url'] ?? null,
            'priority' => $notification->data['priority'] ?? 'normal',
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString(),
            'occurred_at' => $notification->data['occurred_at'] ?? $notification->created_at?->toISOString(),
        ];
    }
}
