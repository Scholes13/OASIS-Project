<?php

namespace Tests\Feature;

use App\Models\Core\User;
use App\Services\Modules\PurchaseRequest\UniversalPRNumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PRNumberingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pr_numbering_service_can_be_resolved(): void
    {
        $service = app(UniversalPRNumberingService::class);
        $this->assertInstanceOf(UniversalPRNumberingService::class, $service);
    }

    public function test_pr_numbering_service_throws_exception_without_business_unit(): void
    {
        $user = User::factory()->create();

        $service = app(UniversalPRNumberingService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Business unit not found or user has no access');

        $service->generatePRNumber($user);
    }
}
