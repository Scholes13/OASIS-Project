<?php

namespace Tests\Feature\Core;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected BusinessUnit $businessUnit;

    protected Department $department;

    protected Position $position;

    protected User $user;

    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);

        Role::findOrCreate('user');

        $this->businessUnit = BusinessUnit::factory()->create([
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'business_unit_id' => $this->businessUnit->id,
            'code' => 'OPS',
            'name' => 'Operations',
            'is_active' => true,
        ]);

        $this->position = Position::query()
            ->where('department_id', $this->department->id)
            ->where('code', 'STAFF_'.strtoupper($this->department->code))
            ->firstOrFail();

        $this->user = $this->createUser('notifications.owner@example.com');
        $this->otherUser = $this->createUser('notifications.other@example.com');
    }

    #[Test]
    public function dashboard_shares_unread_notification_count(): void
    {
        $this->seedNotification($this->user, [
            'title' => 'Unread notification',
            'message' => 'Unread message',
        ]);

        $this->seedNotification($this->user, [
            'title' => 'Read notification',
            'message' => 'Read message',
        ])->markAsRead();

        $response = $this->actingAs($this->user)
            ->withSession($this->sessionPayload())
            ->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('notifications', fn (Assert $notifications) => $notifications
                ->where('unread_count', 1)
                ->etc()
            )
        );
    }

    #[Test]
    public function recent_notifications_endpoint_returns_only_the_current_users_notifications(): void
    {
        $this->seedNotification($this->user, [
            'title' => 'Newest notification',
            'message' => 'Newest message',
            'occurred_at' => now()->toISOString(),
        ]);

        $this->seedNotification($this->otherUser, [
            'title' => 'Other users notification',
            'message' => 'Other message',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->sessionPayload())
            ->getJson(route('notifications.recent'));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Newest notification');
    }

    #[Test]
    public function notifications_archive_page_renders_for_the_authenticated_user(): void
    {
        $this->seedNotification($this->user, [
            'title' => 'Archive notification',
            'message' => 'Archive message',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->sessionPayload())
            ->get(route('notifications.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Notifications/Index')
            ->has('notifications.data', 1)
        );
    }

    #[Test]
    public function notifications_archive_can_filter_unread_items(): void
    {
        $this->seedNotification($this->user, [
            'title' => 'Unread notification',
            'message' => 'Unread message',
        ]);

        $this->seedNotification($this->user, [
            'title' => 'Read notification',
            'message' => 'Read message',
        ])->markAsRead();

        $response = $this->actingAs($this->user)
            ->withSession($this->sessionPayload())
            ->get(route('notifications.index', ['filter' => 'unread']));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('filters.active', 'unread')
            ->has('notifications.data', 1)
            ->where('notifications.data.0.read_at', null)
        );
    }

    #[Test]
    public function opening_a_notification_marks_it_as_read_and_redirects_to_its_action_url(): void
    {
        $notification = $this->seedNotification($this->user, [
            'title' => 'Open me',
            'message' => 'Open message',
            'action_url' => route('activity.task.index'),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->sessionPayload())
            ->get(route('notifications.open', $notification->id));

        $response->assertRedirect(route('activity.task.index'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    #[Test]
    public function opening_another_users_notification_is_forbidden(): void
    {
        $notification = $this->seedNotification($this->otherUser, [
            'title' => 'Private notification',
            'message' => 'Private message',
            'action_url' => route('activity.task.index'),
        ]);

        $this->actingAs($this->user)
            ->withSession($this->sessionPayload())
            ->get(route('notifications.open', $notification->id))
            ->assertForbidden();
    }

    #[Test]
    public function opening_a_notification_without_an_action_url_falls_back_to_the_archive_page(): void
    {
        $notification = $this->seedNotification($this->user, [
            'title' => 'No action notification',
            'message' => 'No action message',
            'action_url' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->sessionPayload())
            ->get(route('notifications.open', $notification->id));

        $response->assertRedirect(route('notifications.index'));
        $response->assertSessionHas('warning');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    #[Test]
    public function mark_all_read_marks_the_authenticated_users_unread_notifications_as_read(): void
    {
        $first = $this->seedNotification($this->user, [
            'title' => 'First unread',
            'message' => 'First message',
        ]);
        $second = $this->seedNotification($this->user, [
            'title' => 'Second unread',
            'message' => 'Second message',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->sessionPayload())
            ->post(route('notifications.mark-all-read', ['filter' => 'all']));

        $response->assertRedirect(route('notifications.index', ['filter' => 'all']));
        $this->assertNotNull($first->fresh()->read_at);
        $this->assertNotNull($second->fresh()->read_at);
    }

    protected function createUser(string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->position->id,
            'email_verified_at' => now(),
        ]);

        $user->assignRole('user');

        UserBusinessUnit::create([
            'user_id' => $user->id,
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function seedNotification(User $user, array $overrides = []): \Illuminate\Notifications\DatabaseNotification
    {
        $notification = new class($overrides) extends Notification
        {
            /**
             * @param  array<string, mixed>  $overrides
             */
            public function __construct(protected array $overrides) {}

            public function via(object $notifiable): array
            {
                return ['database'];
            }

            /**
             * @return array<string, mixed>
             */
            public function toArray(object $notifiable): array
            {
                return array_merge([
                    'category' => 'system',
                    'event' => 'test_notification',
                    'title' => 'Test notification',
                    'message' => 'Test message',
                    'action_url' => route('dashboard'),
                    'priority' => 'normal',
                    'occurred_at' => now()->toISOString(),
                ], $this->overrides);
            }
        };

        $user->notify($notification);

        return $user->notifications()->latest()->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    protected function sessionPayload(): array
    {
        return [
            'current_business_unit_id' => $this->businessUnit->id,
            'current_business_unit_code' => $this->businessUnit->code,
            'current_business_unit_name' => $this->businessUnit->name,
            'current_business_unit_logo' => $this->businessUnit->logo,
            'current_department_id' => $this->department->id,
        ];
    }
}
