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

    /**
     * Text z bloku připravený k vysázení.
     *
     * Prozaická pole bloků drží HTML z editoru, jenže starší obsah (a obsah
     * ze seedu) je holý text. Rozlišuje se podle toho, jestli tam vůbec je
     * značka: bez ní se zalomení převedou na `<br>` a odstavec se obalí, jinak
     * by se z napsaných řádků stal jeden slepenec.
     *
     * Výsledek stejně prochází {@see self::sanitize()} v rendereru, takže
     * tohle není bezpečnostní rozhodnutí, jen sázecí.
     */
    public static function prose(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return str_contains($value, '<')
            ? $value
            : '<p>'.nl2br(e($value)).'</p>';
    }

    public static function sanitize(string $html): string
    {
        $clean = (self::$sanitizer ??= new HtmlSanitizer(self::config()))->sanitize($html);

        return self::filterStyles($clean);
    }

    /**
     * Z atributu `style` nechá jen to, co editor opravdu vyrábí.
     *
     * Barvu písma, podbarvení a velikost TipTap vykresluje inline a bez
     * povoleného `style` by z formátování nezbylo nic. Povolit `style` celý je
     * ale zbytečně široké: CSS umí načítat obsah zvenčí a v starších
     * prohlížečích i spouštět kód. Projde proto čtveřice vlastností a jen
     * hodnoty bez závorek a schémat — `url(...)` ani `expression(...)` se přes
     * to nedostane.
     */
    private static function filterStyles(string $html): string
    {
        return (string) preg_replace_callback(
            '/\sstyle="([^"]*)"/i',
            static function (array $match): string {
                $safe = [];

                foreach (explode(';', $match[1]) as $declaration) {
                    [$property, $value] = array_pad(explode(':', $declaration, 2), 2, '');

                    $property = mb_strtolower(trim($property));
                    $value = trim((string) $value);

                    $allowed = ['color', 'background-color', 'font-size', 'text-align'];

                    if (! in_array($property, $allowed, true) || $value === '') {
                        continue;
                    }

                    if (! preg_match('/^[#a-z0-9 ,.%-]+$/i', $value)) {
                        continue;
                    }

                    $safe[] = $property.': '.$value;
                }

                return $safe === [] ? '' : ' style="'.implode('; ', $safe).'"';
            },
            $html
        );
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
            ->allowElement('h2', ['id', 'class', 'style'])
            ->allowElement('h3', ['id', 'class', 'style'])
            ->allowElement('h4', ['id', 'class'])
            ->allowElement('table', ['class', 'style'])
            ->allowElement('colgroup')
            ->allowElement('col', ['style'])
            ->allowElement('p', ['class', 'style'])
            ->allowElement('thead')
            ->allowElement('tbody')
            ->allowElement('tr')
            ->allowElement('th', ['align', 'colspan', 'rowspan', 'style'])
            ->allowElement('td', ['align', 'colspan', 'rowspan', 'style'])
            ->allowElement('pre', ['class'])
            ->allowElement('code', ['class'])
            ->allowElement('span', ['class', 'style'])
            ->allowElement('mark', ['class', 'style'])
            ->allowElement('u')
            ->allowElement('sub')
            ->allowElement('sup')
            ->allowElement('div', ['class'])
            ->allowElement('figure', ['class'])
            ->allowElement('details', ['class', 'open'])
            ->allowElement('summary', ['class'])
            ->allowElement('dl', ['class'])
            ->allowElement('dt', ['class'])
            ->allowElement('dd', ['class'])
            // `iframe` je cizí kód na naší stránce, takže se povoluje jen
            // s pevnou sadou atributů — a **jen** proto, že adresu skládá
            // partial z whitelistu hostů, ne autor.
            ->allowElement('iframe', ['src', 'title', 'allowfullscreen', 'loading', 'class'])
            ->allowElement('figcaption', ['class'])
            ->allowElement('a', ['href', 'title', 'class', 'id', 'target', 'rel'])
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height', 'loading'])
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            // Bez tohohle sanitizér zahodí **každý relativní odkaz** — a to
            // je zrovna ten způsob, jakým se odkazuje uvnitř báze
            // (`/napoveda/jak-vydat-verzi`). Zmizí přitom potichu: text
            // zůstane, odkaz ne, takže si toho nikdo nevšimne.
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowMediaSchemes(['https', 'http', 'data'])
            ->allowMediaHosts(null);
    }
}
