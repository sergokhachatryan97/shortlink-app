<?php

namespace App\Support;

/**
 * Stringify monetary amounts without padding fake decimals (e.g. 10 not 10.00).
 */
final class MoneyDisplay
{
    public static function plainDecimal(float|string|int|null $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $f = is_numeric($value) ? (float) $value : 0.0;
        if (is_nan($f) || is_infinite($f)) {
            return '0';
        }

        $s = sprintf('%.10F', $f);
        $s = rtrim(rtrim($s, '0'), '.');

        return $s === '' || $s === '-' ? '0' : $s;
    }
}
