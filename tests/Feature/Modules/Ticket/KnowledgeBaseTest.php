<?php

namespace Tests\Feature\Modules\Ticket;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Modules\Ticket\KnowledgeArticle;
use App\Models\Modules\Ticket\KnowledgeCategory;
use App\Services\Modules\Ticket\KnowledgeBaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $regularUser;

    protected BusinessUnit $businessUnit;

    protected Department $department;

    protected Position $position;

    protected KnowledgeCategory $kbCategory;

    protected KnowledgeBaseService $kbService;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);

        $this->businessUnit = BusinessUnit::create([
            'name' => 'Test BU',
            'code' => 'TBU',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'name' => 'Test Dept',
            'code' => 'TDP',
            'business_unit_id' => $this->businessUnit->id,
            'is_active' => true,
        ]);

        $this->position = Position::create([
            'department_id' => $this->department->id,
            'name' => 'Staff',
            'code' => 'STF',
            'level' => 'staff',
            'access_level' => 'staff',
            'hierarchy_level' => 3,
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'IT Admin',
            'email' => 'itadmin@example.com',
            'phone_number' => '081234567800',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->position->id,
            'global_role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->admin->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
            'is_it_support_admin' => true,
        ]);

        $this->regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'phone_number' => '081234567801',
            'password' => bcrypt('password'),
            'primary_department_id' => $this->department->id,
            'primary_position_id' => $this->position->id,
            'global_role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->regularUser->businessUnits()->create([
            'business_unit_id' => $this->businessUnit->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->kbCategory = KnowledgeCategory::create([
            'business_unit_id' => $this->businessUnit->id,
            'name' => 'Getting Started',
            'slug' => 'getting-started',
            'order' => 1,
        ]);

        $this->kbService = app(KnowledgeBaseService::class);
    }

    #[Test]
    public function it_allows_admin_to_create_article(): void
    {
        $article = $this->kbService->createArticle([
            'title' => 'How to Reset Password',
            'content' => 'Step 1: Go to settings. Step 2: Click reset.',
            'category_id' => $this->kbCategory->id,
            'is_published' => true,
            'meta_description' => 'Password reset guide',
            'tags' => ['password', 'reset'],
        ], $this->admin, $this->businessUnit->id);

        $this->assertDatabaseHas('ticket_knowledge_articles', [
            'id' => $article->id,
            'title' => 'How to Reset Password',
            'business_unit_id' => $this->businessUnit->id,
            'author_id' => $this->admin->id,
            'is_published' => true,
            'slug' => 'how-to-reset-password',
        ]);

        $this->assertNotNull($article->published_at);
    }

    #[Test]
    public function it_allows_users_to_browse_published_articles(): void
    {
        // Create published article
        KnowledgeArticle::create([
            'business_unit_id' => $this->businessUnit->id,
            'title' => 'Published Article',
            'slug' => 'published-article',
            'content' => 'This is published content',
            'category_id' => $this->kbCategory->id,
            'is_published' => true,
            'author_id' => $this->admin->id,
            'published_at' => now(),
            'views_count' => 0,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->get(route('it-support.knowledge'));

        $response->assertOk();
    }

    #[Test]
    public function it_hides_draft_articles_from_browse(): void
    {
        // Create a draft article
        KnowledgeArticle::create([
            'business_unit_id' => $this->businessUnit->id,
            'title' => 'Draft Article',
            'slug' => 'draft-article',
            'content' => 'This is draft content',
            'category_id' => $this->kbCategory->id,
            'is_published' => false,
            'author_id' => $this->admin->id,
            'views_count' => 0,
        ]);

        // Create a published article
        KnowledgeArticle::create([
            'business_unit_id' => $this->businessUnit->id,
            'title' => 'Published Article',
            'slug' => 'published-article',
            'content' => 'This is published content',
            'category_id' => $this->kbCategory->id,
            'is_published' => true,
            'author_id' => $this->admin->id,
            'published_at' => now(),
            'views_count' => 0,
        ]);

        // Regular user should only see published articles
        $publishedArticles = KnowledgeArticle::where('business_unit_id', $this->businessUnit->id)
            ->where('is_published', true)
            ->get();

        $allArticles = KnowledgeArticle::where('business_unit_id', $this->businessUnit->id)->get();

        $this->assertCount(1, $publishedArticles);
        $this->assertCount(2, $allArticles);
        $this->assertSame('Published Article', $publishedArticles->first()->title);

        // Verify the article route returns 404 for draft
        $response = $this->actingAs($this->regularUser)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->get(route('it-support.knowledge.article', 'draft-article'));

        $response->assertNotFound();
    }

    #[Test]
    public function it_tracks_article_views(): void
    {
        $article = KnowledgeArticle::create([
            'business_unit_id' => $this->businessUnit->id,
            'title' => 'Trackable Article',
            'slug' => 'trackable-article',
            'content' => 'Content to track views',
            'category_id' => $this->kbCategory->id,
            'is_published' => true,
            'author_id' => $this->admin->id,
            'published_at' => now(),
            'views_count' => 0,
        ]);

        $this->assertSame(0, $article->views_count);

        // View the article
        $response = $this->actingAs($this->regularUser)
            ->withSession([
                'current_business_unit_id' => $this->businessUnit->id,
                'current_department_id' => $this->department->id,
            ])
            ->get(route('it-support.knowledge.article', 'trackable-article'));

        $response->assertOk();

        // Check view was tracked
        $article->refresh();
        $this->assertSame(1, $article->views_count);

        $this->assertDatabaseHas('ticket_article_views', [
            'article_id' => $article->id,
            'user_id' => $this->regularUser->id,
        ]);
    }

    #[Test]
    public function it_searches_articles_by_keyword(): void
    {
        // Create articles with different content
        KnowledgeArticle::create([
            'business_unit_id' => $this->businessUnit->id,
            'title' => 'VPN Setup Guide',
            'slug' => 'vpn-setup-guide',
            'content' => 'How to configure VPN on your laptop',
            'category_id' => $this->kbCategory->id,
            'is_published' => true,
            'author_id' => $this->admin->id,
            'published_at' => now(),
            'views_count' => 5,
        ]);

        KnowledgeArticle::create([
            'business_unit_id' => $this->businessUnit->id,
            'title' => 'Printer Installation',
            'slug' => 'printer-installation',
            'content' => 'How to install network printer',
            'category_id' => $this->kbCategory->id,
            'is_published' => true,
            'author_id' => $this->admin->id,
            'published_at' => now(),
            'views_count' => 3,
        ]);

        KnowledgeArticle::create([
            'business_unit_id' => $this->businessUnit->id,
            'title' => 'Draft VPN Article',
            'slug' => 'draft-vpn-article',
            'content' => 'Unpublished VPN content',
            'category_id' => $this->kbCategory->id,
            'is_published' => false,
            'author_id' => $this->admin->id,
            'views_count' => 0,
        ]);

        // Search for "VPN" — should find only published articles
        $results = $this->kbService->searchArticles($this->businessUnit->id, 'VPN');

        $this->assertCount(1, $results);
        $this->assertSame('VPN Setup Guide', $results->first()->title);

        // Search for "printer" — should find the printer article
        $printerResults = $this->kbService->searchArticles($this->businessUnit->id, 'printer');

        $this->assertCount(1, $printerResults);
        $this->assertSame('Printer Installation', $printerResults->first()->title);

        // Search for non-existent term
        $noResults = $this->kbService->searchArticles($this->businessUnit->id, 'xyznonexistent');

        $this->assertCount(0, $noResults);
    }
}
