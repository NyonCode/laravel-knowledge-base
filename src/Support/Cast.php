<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Support;

/**
 * Zúžení `mixed` na jeden typ.
 *
 * Hodnoty z `validate()` nebo z `getAuthIdentifier()` jsou pro statickou
 * analýzu `mixed` a přímé přetypování na `string` na nich neprojde — právem:
 * `(string) $pole` je fatální chyba, ne prázdný řetězec.
 *
 * Tenhle helper ten předpoklad vysloví na jednom místě a nečekaný tvar
 * vrátí jako výchozí hodnotu, místo aby shodil požadavek.
 */
final class Cast
{
    public static function string(mixed $value, string $default = ''): string
    {
        return is_scalar($value) ? (string) $value : $default;
    }

    public static function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = (string) $value;

        return $value === '' ? null : $value;
    }

    public static function int(mixed $value, int $default = 0): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }

    public static function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
