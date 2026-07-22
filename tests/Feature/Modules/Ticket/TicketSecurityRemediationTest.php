<?php

namespace Tests\Feature\Modules\Ticket;

use App\Exports\Modules\Ticket\TicketExport;
use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Ticket\KnowledgeArticle;
use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketCategory;
use App\Notifications\Ticket\TicketCommentNotification;
use App\Notifications\Ticket\TicketStatusChangedNotification;
use App\Services\Modules\Ticket\KnowledgeBaseService;
use App\Services\Modules\Ticket\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketSecurityRemediationTest extends TestCase
{
    use RefreshDatabase;

    protected BusinessUnit $businessUnit;

    protected BusinessUnit $otherBusinessUnit;

    protected Department $department;

    protected Department $otherDepartment;

    protected Position $position;

    protected User $requester;

    protected User $admin;

    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);

        $this->businessUnit = BusinessUnit::create(['name' => 'Main BU', 'code' => 'MBU', 'is_active' => true]);
        $this->otherBusinessUnit = BusinessUnit::create(['name' => 'Other BU', 'code' => 'OBU', 'is_active' => true]);
        $this->department = $this->createDepartment($this->businessUnit, 'Main Department', 'MDP');
        $this->otherDepartment = $this->createDepartment($this->otherBusinessUnit, 'Other Department', 'ODP');
        $this->position = Position::create([
            'department_id' => $this->department->id,
            'name' => 'Staff',
            'code' => 'STF',
            'level' => 'staff',
            'access_level' => 'staff',
            'hierarchy_level' => 3,
            'is_active' => true,
        ]);
        $this->requester = $this->createUser('Requester', 'requester@example.com');
        $this->admin = $this->createUser('IT Admin', 'admin@example.com', true);
        $this->ticket = Ticket::create([
            'business_unit_id' => $this->businessUnit->id,
            'ticket_number' => 'IT.MBU/202607/001',
            'title' => 'Security ticket',
            'description' => 'Security test',
            'requester_id' => $this->requester->id,
            'department_id' => $this->department->id,
            'status' => 'waiting',
            'priority' => 'medium',
            'created_by' => $this->requester->id,
        ]);
    }

    #[Test]
    public function it_sanitizes_article_content_on_write(): void
    {
        $article = app(KnowledgeBaseService::class)->createArticle([
            'title' => 'Unsafe article',
            'content' => '<script>alert(1)</script><img src=x onerror=alert(2)>Safe text',
            'is_published' => true,
        ], $this->admin, $this->businessUnit->id);

        $this->assertSame('alert(1)Safe text', $article->content);
        $this->assertStringNotContainsString('<', $article->content);
    }

    #[Test]
    public function requester_payload_only_contains_published_same_business_unit_articles_and_safe_user_fields(): void
    {
        $published = $this->createArticle($this->businessUnit, 'Published', true);
        $draft = $this->createArticle($this->businessUnit, 'Draft', false);
        $foreign = $this->createArticle($this->otherBusinessUnit, 'Foreign', true);
        $this->ticket->knowledgeArticles()->attach([$published->id, $draft->id, $foreign->id]);

        $this->actingAs($this->requester)
            ->withSession(['current_business_unit_id' => $this->businessUnit->id])
            ->get(route('it-support.my-tickets.show', $this->ticket))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('ticket.requester', 2)
                ->where('ticket.requester.id', $this->requester->id)
                ->where('ticket.requester.name', $this->requester->name)
                ->has('ticket.knowledge_articles', 1)
                ->where('ticket.knowledge_articles.0.id', $published->id)
                ->missing('ticket.requester.email'));
    }

    #[Test]
    public function it_rejects_cross_business_unit_ticket_references_in_request_and_service(): void
    {
        $foreignCategory = TicketCategory::create([
            'business_unit_id' => $this->otherBusinessUnit->id,
            'name' => 'Foreign category',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->withSession(['current_business_unit_id' => $this->businessUnit->id])
            ->put(route('it-support.admin.tickets.update', $this->ticket), [
                'title' => $this->ticket->title,
                'description' => $this->ticket->description,
                'priority' => 'medium',
                'category_id' => $foreignCategory->id,
                'department_id' => $this->otherDepartment->id,
            ])
            ->assertSessionHasErrors(['category_id', 'department_id']);

        $this->expectException(\Exception::class);
        app(TicketService::class)->updateTicket($this->ticket, ['category_id' => $foreignCategory->id]);
    }

    #[Test]
    public function it_rejects_cross_business_unit_or_draft_article_links_in_service(): void
    {
        $foreign = $this->createArticle($this->otherBusinessUnit, 'Foreign', true);
        $draft = $this->createArticle($this->businessUnit, 'Draft', false);
        $service = app(KnowledgeBaseService::class);

        foreach ([$foreign, $draft] as $article) {
            try {
                $service->linkArticleToTicket($this->ticket, $article->id);
                $this->fail('Unsafe article link was accepted.');
            } catch (\Exception) {
                $this->assertDatabaseMissing('ticket_knowledge_article', [
                    'ticket_id' => $this->ticket->id,
                    'knowledge_article_id' => $article->id,
                ]);
            }
        }
    }

    #[Test]
    public function notifications_use_recipient_specific_ticket_routes(): void
    {
        $comment = $this->ticket->comments()->create([
            'user_id' => $this->admin->id,
            'content' => 'Update',
            'is_private' => false,
        ]);

        $status = new TicketStatusChangedNotification($this->ticket, 'in_progress');
        $commentNotification = new TicketCommentNotification($comment, $this->admin, $this->ticket);

        $this->assertSame(route('it-support.my-tickets.show', $this->ticket), $status->toArray($this->requester)['action_url']);
        $this->assertSame(route('it-support.admin.tickets.show', $this->ticket), $status->toArray($this->admin)['action_url']);
        $this->assertSame(route('it-support.my-tickets.show', $this->ticket), $commentNotification->toArray($this->requester)['action_url']);
        $this->assertSame(route('it-support.admin.tickets.show', $this->ticket), $commentNotification->toArray($this->admin)['action_url']);
    }

    #[Test]
    public function report_page_and_exports_require_selected_business_unit_report_access(): void
    {
        $otherPosition = Position::create([
            'department_id' => $this->otherDepartment->id,
            'name' => 'Other Staff',
            'code' => 'OST',
            'level' => 'staff',
            'access_level' => 'staff',
            'hierarchy_level' => 3,
            'is_active' => true,
        ]);
        $this->admin->businessUnits()->create([
            'business_unit_id' => $this->otherBusinessUnit->id,
            'department_id' => $this->otherDepartment->id,
            'position_id' => $otherPosition->id,
            'is_primary' => false,
            'is_active' => true,
            'is_it_support_admin' => true,
            'is_it_support_report_access' => true,
        ]);

        foreach (['it-support.admin.reporting', 'it-support.admin.reporting.exportExcel', 'it-support.admin.reporting.exportPdf'] as $routeName) {
            $this->actingAs($this->admin)
                ->withSession(['current_business_unit_id' => $this->businessUnit->id])
                ->get(route($routeName))
                ->assertForbidden();
        }

        $this->admin->businessUnits()
            ->where('business_unit_id', $this->businessUnit->id)
            ->update(['is_it_support_report_access' => true]);

        $this->actingAs($this->admin)
            ->withSession(['current_business_unit_id' => $this->businessUnit->id])
            ->get(route('it-support.admin.reporting'))
            ->assertOk();
    }

    #[Test]
    public function ticket_export_writes_untrusted_values_as_explicit_strings(): void
    {
        $this->ticket->update(['title' => '=HYPERLINK("https://example.test","click")']);
        $export = new TicketExport([$this->businessUnit->id], now()->subDay(), now()->addDay());
        $spreadsheet = new Spreadsheet;
        $method = new \ReflectionMethod($export, 'buildTicketsSheet');
        $method->invoke($export, $spreadsheet);
        $cell = $spreadsheet->getSheetByName('Tickets')->getCell('C2');

        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertSame('=HYPERLINK("https://example.test","click")', $cell->getValue());
    }

    protected function createDepartment(BusinessUnit $businessUnit, string $name, string $code): Department
    {
        return Department::create(['business_unit_id' => $businessUnit->id, 'name' => $name, 'code' => $code, 'is_active' => true]);
    }

    protected function createUser(string $name, string $email, bool $isAdmin = false): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone_number' => '081234567890',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->position->id,
            'global_role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
            'is_it_support_admin' => $isAdmin,
            'is_it_support_report_access' => false,
        ]);

        return $user;
    }

    protected function createArticle(BusinessUnit $businessUnit, string $title, bool $published): KnowledgeArticle
    {
        return KnowledgeArticle::create([
            'business_unit_id' => $businessUnit->id,
            'title' => $title,
            'slug' => strtolower($title).'-'.$businessUnit->id,
            'content' => $title.' content',
            'is_published' => $published,
            'author_id' => $this->admin->id,
            'published_at' => $published ? now() : null,
        ]);
    }
}
