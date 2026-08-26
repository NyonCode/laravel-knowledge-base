<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Support;

/**
 * Jak se v daném jazyce píše komentář.
 *
 * Existuje kvůli nápovědě u bloku kódu: anotace zvýrazňovače (`[tl! focus]`,
 * `[tl! ++]`) musí být **skutečný komentář v jazyce bloku**, jinak se
 * nezpracují a zůstanou v ukázce viset jako text. Nápověda, která to
 * neřekne — nebo to řekne obecně — pošle autora psát je holé, a on to zjistí
 * až na hotové stránce.
 *
 * Je to znalost o jazyce, ne o zvýrazňovači, takže sem patří i v balíčku,
 * který si žádný konkrétní zvýrazňovač nevybírá.
 */
final class CodeComment
{
    /**
     * Otevírací a uzavírací část komentáře; druhá je u většiny jazyků prázdná.
     *
     * @return array{0: string, 1: string}
     */
    public static function syntax(?string $language): array
    {
        return match (mb_strtolower(trim((string) $language))) {
            'html', 'xml', 'svg', 'blade', 'vue', 'svelte', 'markdown', 'md',
            'antlers', 'twig' => ['<!--', ' -->'],
            'bash', 'sh', 'shell', 'zsh', 'fish', 'python', 'py', 'ruby', 'rb',
            'perl', 'yaml', 'yml', 'toml', 'ini', 'conf', 'nginx', 'dockerfile',
            'docker', 'makefile', 'make', 'r', 'elixir', 'powershell', 'ps1',
            'graphql', 'gql' => ['#', ''],
            'sql', 'lua', 'haskell', 'hs', 'ada' => ['--', ''],
            'css', 'scss', 'sass', 'less', 'stylus' => ['/*', ' */'],
            // Prostý text bere Torchlight celý jako komentář, takže se anotace
            // píše holá — a jakákoli značka navíc by v ukázce zůstala vidět.
            '', 'text', 'plaintext', 'txt' => ['', ''],
            default => ['//', ''],
        };
    }

    /**
     * Ukázkový zápis anotace v daném jazyce, přesně jak se má napsat.
     */
    public static function annotation(?string $language, string $annotation): string
    {
        [$open, $close] = self::syntax($language);

        return trim($open.' '.$annotation.$close);
    }
}
