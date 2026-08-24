<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Support;

/**
 * Starší tvary bloků převedené na HTML, se kterým umí pracovat editor.
 *
 * Tabulka se dřív psala jako řádky se svislítky a seznam jako řádek na
 * položku. Šlo to rychle napsat, ale nedalo se v tom nic zvýraznit ani na nic
 * odkázat — a tabulka se dala rozbít jedním svislítkem navíc. Dnes jsou to
 * plnohodnotné editory nad HTML.
 *
 * Převádí se **při otevření k editaci**, ne migrací databáze: blok, kterého se
 * nikdo nedotkne, nemá důvod se měnit, a vykreslovací šablony proto rozumí
 * obojímu. Kdo blok uloží, uloží ho už v novém tvaru.
 */
final class BlockHtml
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function upgrade(string $type, array $data): array
    {
        if (filled($data['html'] ?? null)) {
            return $data;
        }

        /** @var array<int, mixed> $items */
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];

        $html = match ($type) {
            'table' => self::table(Cast::string($data['rows'] ?? '')),
            'list' => self::listing('ul', $items),
            'steps' => self::listing('ol', $items),
            'checklist' => self::tasks($items),
            default => null,
        };

        if ($html === null || $html === '') {
            return $data;
        }

        $data['html'] = $html;
        unset($data['rows'], $data['items']);

        return $data;
    }

    /** Řádky se svislítky → `<table>`; první řádek je hlavička. */
    private static function table(string $rows): ?string
    {
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $rows)),
            static fn (string $line): bool => $line !== '',
        ));

        if ($lines === []) {
            return null;
        }

        $cells = static fn (string $line, string $tag): string => implode('', array_map(
            static fn (string $cell): string => "<{$tag}><p>".e(trim($cell))."</p></{$tag}>",
            explode('|', $line),
        ));

        $head = $cells(array_shift($lines), 'th');
        $body = implode('', array_map(
            static fn (string $line): string => '<tr>'.$cells($line, 'td').'</tr>',
            $lines,
        ));

        return '<table><tbody><tr>'.$head.'</tr>'.$body.'</tbody></table>';
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private static function listing(string $tag, array $items): ?string
    {
        $rows = self::texts($items);

        if ($rows === []) {
            return null;
        }

        return "<{$tag}>".implode('', array_map(
            // Položka mohla nést inline formátování už dřív, tak se
            // nezaescapuje — sestavený blok stejně projde sanitizérem.
            static fn (string $text): string => '<li><p>'.$text.'</p></li>',
            $rows,
        ))."</{$tag}>";
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private static function tasks(array $items): ?string
    {
        $rows = self::texts($items);

        if ($rows === []) {
            return null;
        }

        // Nezaškrtnuté: stav se u návodu neukládá, takže „hotovo" by tvrdilo
        // něco, co o čtenáři nikdo neví.
        return '<ul data-type="taskList">'.implode('', array_map(
            static fn (string $text): string => '<li data-checked="false">'
                .'<label><input type="checkbox"></label>'
                .'<div><p>'.$text.'</p></div></li>',
            $rows,
        )).'</ul>';
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, string>
     */
    private static function texts(array $items): array
    {
        return array_values(array_filter(array_map(
            static fn (mixed $item): string => trim(is_string($item)
                ? $item
                : Cast::string(is_array($item) ? ($item['text'] ?? '') : '')),
            $items,
        ), static fn (string $text): bool => $text !== ''));
    }
}
