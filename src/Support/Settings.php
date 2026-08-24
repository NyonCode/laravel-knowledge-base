<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

/**
 * Typovaný přístup ke konfiguraci balíčku.
 *
 * `config()` vrací `mixed`, takže každé jeho použití je buď přetypování na
 * místě, nebo tichý předpoklad. Tady se ten předpoklad vysloví jednou a
 * statická analýza zbytek balíčku vidí typovaně.
 *
 * Prázdný řetězec se u `nullableString()` počítá jako nevyplněno: publikovaná
 * konfigurace se často upravuje ručně a `''` znamená „nenastaveno", ne
 * „nastaveno na prázdno".
 */
final class Settings
{
    public static function string(string $key, string $default = ''): string
    {
        $value = config('knowledge-base.'.$key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    public static function nullableString(string $key): ?string
    {
        $value = config('knowledge-base.'.$key);

        if (! is_scalar($value)) {
            return null;
        }

        $value = (string) $value;

        return $value === '' ? null : $value;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = config('knowledge-base.'.$key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    public static function nullableInt(string $key): ?int
    {
        $value = config('knowledge-base.'.$key);

        return is_numeric($value) ? (int) $value : null;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = config('knowledge-base.'.$key, $default);

        return is_bool($value) ? $value : (bool) $value;
    }

    /**
     * @param  array<array-key, mixed>  $default
     * @return array<array-key, mixed>
     */
    public static function array(string $key, array $default = []): array
    {
        $value = config('knowledge-base.'.$key, $default);

        return is_array($value) ? $value : $default;
    }

    /**
     * @return array<string, int>
     */
    public static function weights(string $key): array
    {
        $weights = [];

        foreach (self::array($key) as $column => $weight) {
            if (is_string($column) && is_numeric($weight)) {
                $weights[$column] = (int) $weight;
            }
        }

        return $weights;
    }

    /**
     * Model uživatele, pod kterým běží autorství a hlasy.
     *
     * Balíček nemá vlastní `User`: v jedné aplikaci je to `App\Models\User`,
     * v druhé něco jiného, a v obou už existuje. Bere se z konfigurace
     * balíčku, jinak z té, kterou používá autentizace.
     *
     * @return class-string<Model>
     */
    public static function userModel(): string
    {
        $configured = self::nullableString('models.user');

        if ($configured !== null && is_a($configured, Model::class, true)) {
            return $configured;
        }

        $default = config('auth.providers.users.model');

        /** @var class-string<Model> $model */
        $model = is_string($default) && is_a($default, Model::class, true)
            ? $default
            : User::class;

        return $model;
    }

    /**
     * @return array<int, string>
     */
    public static function strings(string $key): array
    {
        return array_values(array_filter(
            array_map(
                static fn (mixed $v): ?string => is_string($v) ? $v : null,
                self::array($key)
            ),
        ));
    }
}
