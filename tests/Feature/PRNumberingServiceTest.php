<?php

namespace Tests\Feature;

use App\Services\Modules\WNS\PRNumberingService;
use App\Models\User;
use App\Models\Department;
use App\Models\BusinessUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PRNumberingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pr_numbering_service_can_be_resolved()
    {
        $service = app(PRNumberingService::class);
        $this->assertInstanceOf(PRNumberingService::class, $service);
    }

    public function test_pr_numbering_service_throws_exception_without_wns_business_unit()
    {
        $user = User::factory()->create();
        
        $service = app(PRNumberingService::class);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('WNS business unit not found');
        
        $service->generatePRNumber($user);
    }
}