<?php

namespace App\Support;

final class Money
{
    public static function add(string|int $left, string|int $right): string
    {
        return bcadd((string) $left, (string) $right, 2);
    }

    public static function subtract(string|int $left, string|int $right): string
    {
        $result = bcsub((string) $left, (string) $right, 2);

        return bccomp($result, '0', 2) === -1 ? '0.00' : $result;
    }

    public static function format(string|int|null $amount, string $currency = 'MAD'): string
    {
        $normalized = bcadd((string) ($amount ?? 0), '0', 2);
        [$whole] = explode('.', $normalized);
        $formatted = number_format((int) $whole, 0, ',', ' ');

        return $formatted.' '.($currency === 'MAD' ? 'DH' : $currency);
    }
}
