<?php

namespace App\Services\Modules\CashflowProjection;

use InvalidArgumentException;

final class CashflowMoney
{
    public const MAX = '9999999999999999.99';

    public static function normalize(int|float|string|null $value): string
    {
        $value = trim((string) ($value ?? '0'));

        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $value, $matches)) {
            throw new InvalidArgumentException('Invalid decimal(18,2) monetary value.');
        }

        $whole = ltrim($matches[1], '0');
        $whole = $whole === '' ? '0' : $whole;

        if (strlen($whole) > 16) {
            throw new InvalidArgumentException('Invalid decimal(18,2) monetary value.');
        }

        $decimal = str_pad($matches[2] ?? '', 2, '0');

        return $whole.'.'.$decimal;
    }

    public static function normalizeSigned(int|float|string|null $value): string
    {
        $value = trim((string) ($value ?? '0'));
        $negative = str_starts_with($value, '-');
        $normalized = self::normalize($negative ? substr($value, 1) : $value);

        return $negative && $normalized !== '0.00' ? '-'.$normalized : $normalized;
    }

    public static function toMinor(int|float|string|null $value): int
    {
        [$whole, $decimal] = explode('.', self::normalize($value));

        return ((int) $whole * 100) + (int) $decimal;
    }

    public static function fromMinor(int $minor): string
    {
        if ($minor === PHP_INT_MIN) {
            throw new InvalidArgumentException('Monetary total exceeds supported integer range.');
        }

        $sign = $minor < 0 ? '-' : '';
        $absolute = abs($minor);

        return $sign.intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function add(int|float|string|null ...$values): string
    {
        return self::fromMinor(self::sumMinor($values));
    }

    /** @param iterable<int|float|string|null> $values */
    public static function sumMinor(iterable $values): int
    {
        $total = 0;

        foreach ($values as $value) {
            $total = self::addMinor($total, self::toMinor($value));
        }

        return $total;
    }

    public static function addMinor(int ...$values): int
    {
        $total = 0;

        foreach ($values as $value) {
            if (($value > 0 && $total > PHP_INT_MAX - $value)
                || ($value < 0 && $total < PHP_INT_MIN - $value)) {
                throw new InvalidArgumentException('Monetary total exceeds supported integer range.');
            }

            $total += $value;
        }

        return $total;
    }
}
