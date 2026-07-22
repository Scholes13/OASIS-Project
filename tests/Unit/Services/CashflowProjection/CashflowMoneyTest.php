<?php

namespace Tests\Unit\Services\CashflowProjection;

use App\Services\Modules\CashflowProjection\CashflowMoney;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CashflowMoneyTest extends TestCase
{
    public function test_adds_decimal_cents_without_float_loss(): void
    {
        $this->assertSame('0.03', CashflowMoney::add('0.01', '0.02'));
        $this->assertSame(CashflowMoney::MAX, CashflowMoney::add('9999999999999999.97', '0.02'));
    }

    public function test_accepts_decimal_18_2_maximum(): void
    {
        $this->assertSame('9999999999999999.99', CashflowMoney::normalize('9999999999999999.99'));
        $this->assertSame('1.20', CashflowMoney::normalize('00000000000000001.2'));
    }

    public function test_rejects_total_that_would_promote_integer_addition_to_float(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CashflowMoney::add(...array_fill(0, 10, CashflowMoney::MAX));
    }

    public function test_checked_minor_addition_supports_signed_totals(): void
    {
        $this->assertSame(-1, CashflowMoney::addMinor(1, -2));
    }

    public function test_rejects_unformattable_minimum_integer(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CashflowMoney::fromMinor(PHP_INT_MIN);
    }

    #[DataProvider('invalidMoneyProvider')]
    public function test_rejects_invalid_scale_or_range(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        CashflowMoney::normalize($value);
    }

    public static function invalidMoneyProvider(): array
    {
        return [
            'three decimals' => ['0.001'],
            'max plus fractional digit' => ['9999999999999999.999'],
            'above decimal 18 range' => ['10000000000000000.00'],
            'negative' => ['-0.01'],
            'scientific notation' => ['1e2'],
        ];
    }
}
