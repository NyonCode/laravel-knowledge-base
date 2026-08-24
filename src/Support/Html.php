<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Support;

use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * The HTML rules every renderer shares.
 *
 * One owner for the allow-list, the heading ids and the heading scan, so that
 * markdown, rich text and blocks cannot end up with three different ideas of
 * what is safe — which is the usual way the strictest one gets bypassed by
 * whichever was written last.
 */
final class Html
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function sanitize(string $html): string
    {
        return (self::$sanitizer ??= new HtmlSanitizer(self::config()))->sanitize($html);
    }

    /**
     * Give every h2/h3 an id so the contents can link to it.
     *
     * Ids are derived from the heading text and de-duplicated, because two
     * sections called "Notes" on one page would otherwise share an anchor and
     * the second would be unreachable.
     */
    public static function withHeadingIds(string $html): string
    {
        $seen = [];

        return (string) preg_replace_callback(
            '/<h([23])(?![^>]*\bid=)([^>]*)>(.*?)<\/h\1>/s',
            static function (array $m) use (&$seen): string {
                $text = trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES | ENT_HTML5));
                $slug = Str::slug($text) ?: 'section';

                $seen[$slug] = ($seen[$slug] ?? 0) + 1;

                if ($seen[$slug] > 1) {
                    $slug .= '-'.$seen[$slug];
                }

                return sprintf('<h%s%s id="%s">%s</h%s>', $m[1], $m[2], $slug, $m[3], $m[1]);
            },
            $html
        );
    }

    /**
     * @return array<int, array{level: int, id: string, title: string}>
     */
    public static function headings(string $html): array
    {
        preg_match_all(
            '/<h([23])[^>]*\bid="([^"]+)"[^>]*>(.*?)<\/h\1>/s',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        return array_map(
            static fn (array $m): array => [
                'level' => (int) $m[1],
                'id' => $m[2],
                'title' => trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES | ENT_HTML5)),
            ],
            $matches
        );
    }

    private static function config(): HtmlSanitizerConfig
    {
        return (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowElement('h2', ['id', 'class'])
            ->allowElement('h3', ['id', 'class'])
            ->allowElement('h4', ['id', 'class'])
            ->allowElement('table', ['class'])
            ->allowElement('thead')
            ->allowElement('tbody')
            ->allowElement('tr')
            ->allowElement('th', ['align', 'colspan', 'rowspan'])
            ->allowElement('td', ['align', 'colspan', 'rowspan'])
            ->allowElement('pre', ['class'])
            ->allowElement('code', ['class'])
            ->allowElement('span', ['class'])
            ->allowElement('div', ['class'])
            ->allowElement('figure', ['class'])
            ->allowElement('figcaption', ['class'])
            ->allowElement('a', ['href', 'title', 'class', 'id', 'target', 'rel'])
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height', 'loading'])
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->allowMediaSchemes(['https', 'http', 'data']);
    }
}
