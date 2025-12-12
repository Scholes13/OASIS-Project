<?php

namespace Tests\Unit\Services;

use App\Services\Modules\Purchasing\Admin\PriceEfficiencyService;
use Tests\TestCase;

class PriceEfficiencyServiceTest extends TestCase
{
    protected PriceEfficiencyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PriceEfficiencyService;
    }

    public function test_calculate_savings_with_positive_savings(): void
    {
        $result = $this->service->calculateSavings(10000, 8500);

        $this->assertEquals(1500, $result['savings_amount']);
        $this->assertEquals(15, $result['savings_percentage']);
    }

    public function test_calculate_savings_with_negative_savings(): void
    {
        $result = $this->service->calculateSavings(10000, 12000);

        $this->assertEquals(-2000, $result['savings_amount']);
        $this->assertEquals(-20, $result['savings_percentage']);
    }

    public function test_calculate_savings_with_zero_estimated_price(): void
    {
        $result = $this->service->calculateSavings(0, 0);

        $this->assertEquals(0, $result['savings_amount']);
        $this->assertEquals(0, $result['savings_percentage']);
    }

    public function test_calculate_savings_with_equal_prices(): void
    {
        $result = $this->service->calculateSavings(10000, 10000);

        $this->assertEquals(0, $result['savings_amount']);
        $this->assertEquals(0, $result['savings_percentage']);
    }

    public function test_calculate_savings_rounds_to_two_decimals(): void
    {
        $result = $this->service->calculateSavings(10000, 8333.33);

        $this->assertEquals(1666.67, $result['savings_amount']);
        $this->assertEquals(16.67, $result['savings_percentage']);
    }
}
