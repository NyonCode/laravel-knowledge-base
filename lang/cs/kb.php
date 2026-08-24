<?php

declare(strict_types=1);

return [
    'kind' => [
        'tutorial' => 'Tutoriál',
        'how_to' => 'Návod',
        'reference' => 'Reference',
        'explanation' => 'Souvislosti',
    ],

    // Shown under the title so a reader knows what kind of page they opened
    // before they invest three minutes in the wrong one.
    'promise' => [
        'tutorial' => 'Projdi to od začátku do konce — na konci ti to bude fungovat.',
        'how_to' => 'Postup k jednomu konkrétnímu cíli. Předpokládá, že víš proč.',
        'reference' => 'K nahlédnutí: hodnoty, volby, limity. Bez vyprávění.',
        'explanation' => 'Proč to funguje takhle. Čti mimo klávesnici.',
    ],

    'format' => [
        'markdown' => 'Markdown',
        'rich-text' => 'Rich text',
        'blocks' => 'Bloky',
    ],

    'status' => [
        'draft' => 'Koncept',
        'in_review' => 'Ke kontrole',
        'published' => 'Publikováno',
        'archived' => 'Archivováno',
    ],

    'visibility' => [
        'internal' => 'Interní',
        'public' => 'Veřejné',
    ],

    'home' => [
        'title' => 'Znalostní báze',
        'lead' => 'Návody, postupy a reference ke všemu, co tu běží.',
        'placeholder' => 'Co potřebuješ udělat?',
        'popular' => 'Nejčtenější',
        'empty' => 'Zatím tu nic není.',
    ],

    'search' => [
        'results' => '{1} :count výsledek|[2,4] :count výsledky|[5,*] :count výsledků',
        'none' => 'Na „:term“ nic nesedí.',
        'none_hint' => 'Zkus méně slov, nebo projdi kategorie.',
        'open' => 'Hledat',
        'hint' => 'Piš pro hledání, ↑↓ pohyb, ↵ otevřít.',
    ],

    'article' => [
        'edit' => 'Upravit',
        'toc' => 'Na téhle stránce',
        'reading_time' => 'čtení :minutes min',
        'updated' => 'Upraveno :date',
        'reviewed' => 'Ověřeno :date',
        'stale' => 'Tuhle stránku už delší dobu nikdo neověřil — může být zastaralá.',
        'related' => 'Číst dál',
        'back' => 'Zpět do znalostní báze',
    ],

    'feedback' => [
        'question' => 'Pomohlo to?',
        'yes' => 'Ano',
        'no' => 'Ne',
        'thanks' => 'Díky, zapsáno.',
        'why' => 'Co chybělo?',
        'why_placeholder' => 'Krok, který nefungoval, věc, kterou jsi nenašel…',
        'send' => 'Odeslat',
        'sent' => 'Díky. Právě z tohohle se píše další verze.',
    ],

    'admin' => [
        'category' => 'Kategorie',
        'status' => 'Stav',
        'slug_warning' => 'Změna adresy rozbije odkazy, které už někdo sdílel.',
        'excerpt' => 'Shrnutí',
        'excerpt_hint' => 'Ukáže se ve výsledcích hledání a ve výpisech.',
        'visibility_label' => 'Vidí',
        'categories' => 'Kategorie',
        'new_category' => 'Nová kategorie',
        'category_name' => 'Název',
        'order' => 'Pořadí',
        'cancel' => 'Zrušit',
        'delete' => 'Smazat',
        'article_count' => '{0} bez článků|{1} :count článek|[2,4] :count články|[5,*] :count článků',
        'delete_category_confirm' => 'Smazat kategorii? Články zůstanou a půjde je zařadit jinam.',
        'needs_work' => 'Potřebuje pozornost',
        'all' => 'Všechny články',
        'stale' => 'Dlouho neověřené',
        'unhelpful' => 'Špatně hodnocené',
        'new' => 'Nový článek',
        'save' => 'Uložit',
        'saved' => 'Uloženo',
        'preview' => 'Náhled',
        'write' => 'Psát',
        'mark_reviewed' => 'Pořád platí',
        'note' => 'Co se změnilo?',
        'note_placeholder' => 'Uloží se k revizi, čtenářům se nezobrazí.',
        'empty' => 'Není co opravovat. Báze je v pořádku.',
    ],
];
