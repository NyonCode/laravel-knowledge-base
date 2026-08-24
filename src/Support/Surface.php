<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Support;

use Illuminate\Support\Facades\Route;

/**
 * Kde se zrovna čte.
 *
 * Tatáž báze bývá potřeba na dvou místech: veřejně pro zákazníky a uvnitř
 * administrace pro tým. Jsou to **dvě čtecí plochy téhož obsahu**, ne dvě
 * aplikace — liší se jen layoutem a adresami, na které si odkazují.
 *
 * Bez tohohle pojmu by odkaz z článku otevřeného v adminu vyhodil člověka na
 * veřejný web a ztratil mu kontext, ve kterém pracoval. Plocha se proto
 * nehádá z URL, ale řekne ji route (`->defaults('kb_surface', …)`) nebo
 * pravidlo `when` v konfiguraci.
 */
final class Surface
{
    public const PUBLIC = 'public';

    /**
     * Aktuální plocha.
     *
     * Pořadí je záměrné: explicitní zápis na route vyhrává nad odhadem podle
     * názvu, protože jedna výjimka nesmí nutit přepisovat pravidlo.
     */
    public static function current(): string
    {
        $route = Route::current();

        $explicit = $route?->defaults['kb_surface'] ?? null;

        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        $name = (string) ($route?->getName() ?? '');

        foreach (Settings::array('routes.surfaces') as $surface => $config) {
            $when = is_array($config) ? ($config['when'] ?? null) : null;

            if (is_string($when) && $when !== '' && str_starts_with($name, $when)) {
                return (string) $surface;
            }
        }

        return self::PUBLIC;
    }

    /**
     * Předpona názvů rout téhle plochy (`knowledge.`, `admin.napoveda.`).
     *
     * Prázdná znamená „plocha nemá vlastní routy" — odkazy pak spadnou na
     * veřejné, což je pořád lepší než odkaz nikam.
     */
    public static function routePrefix(): string
    {
        $prefix = Settings::nullableString(
            'routes.surfaces.'.self::current().'.prefix'
        );

        return $prefix ?? Settings::string('routes.name', 'knowledge.');
    }

    /** Layout téhle plochy, nebo `null` pro výchozí čtenářský. */
    public static function layout(): ?string
    {
        return Settings::nullableString(
            'routes.surfaces.'.self::current().'.layout'
        ) ?? Settings::nullableString('layouts.public');
    }
}
