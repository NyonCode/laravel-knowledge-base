<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Contracts;

use Illuminate\Http\UploadedFile;
use NyonCode\KnowledgeBase\Services\DiskImageLibrary;

/**
 * Kam se ukládají obrázky a co je už uložené.
 *
 * Za kontraktem, protože o disku, adresáři a limitech rozhoduje hostitel:
 * jedna aplikace má S3, druhá lokální `public`, třetí vlastní knihovnu médií
 * s právy a metadaty. Balíček, který by to rozhodl za ně, by v půlce případů
 * ukládal vedle.
 *
 * Výchozí implementace ({@see DiskImageLibrary})
 * píše na disk z konfigurace, takže bez jediného řádku navíc to funguje —
 * a kdo má vlastní knihovnu, vymění jednu vazbu.
 */
interface ImageLibrary
{
    /**
     * Uloží nahraný soubor a vrátí adresu, kterou lze rovnou dát do `src`.
     *
     * Ověření typu a velikosti je na volajícím, ne tady: limity zná
     * konfigurace a hlásit je má formulář, ne výjimka z úložiště.
     */
    public function store(UploadedFile $file): string;

    /**
     * Nedávno nahrané obrázky pro výběr z galerie, nejnovější první.
     *
     * Galerie schválně není stránkovaná: kdo hledá obrázek z loňska, hledá ho
     * ve správci souborů. Tohle je „ten, co jsem před chvílí nahrál".
     *
     * @return array<int, array{url: string, name: string}>
     */
    public function recent(int $limit = 60): array;
}
