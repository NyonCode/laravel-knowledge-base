<?php

declare(strict_types=1);

use NyonCode\KnowledgeBase\Editors\BlockEditor;
use NyonCode\KnowledgeBase\Editors\MarkdownEditor;
use NyonCode\KnowledgeBase\Editors\RichTextEditor;
use NyonCode\KnowledgeBase\Enums\Visibility;

return [

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    |
    | The knowledge base mounts itself under one prefix. Set `enabled` to false
    | to keep the models and services but route the pages yourself.
    |
    */

    'routes' => [
        'enabled' => true,
        'prefix' => 'napoveda',
        'name' => 'knowledge.',
        'middleware' => ['web'],

        // Writing lives in the host's admin, so it names those routes. Left
        // empty, the editor links render dead rather than throwing.
        'admin' => [
            'index' => 'admin.knowledge',
            'create' => 'admin.knowledge-create',
            'edit' => 'admin.knowledge-edit',
            'categories' => 'admin.knowledge-categories',
        ],

        /*
        | Reading surfaces
        |
        | The same base is usually needed twice: publicly for customers and
        | inside the admin for the team. Two *surfaces* over one set of
        | articles — they differ only in layout and in the routes they link
        | to. Without that, an article opened in the admin would link out to
        | the public site and drop the reader out of the context they were
        | working in.
        |
        | `prefix` names the surface's own read routes, `layout` its shell,
        | and `when` activates it for route names starting with that string.
        | A route can also say so outright: `->defaults('kb_surface', 'admin')`.
        */
        'surfaces' => [
            'public' => [
                'prefix' => null, // = routes.name
                'layout' => null, // = layouts.public
                'when' => null,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Layouts
    |--------------------------------------------------------------------------
    |
    | Which layout the full-page components render into. Null leaves Livewire's
    | own default in place, which is right for a fresh Livewire app and wrong
    | for one that already had layouts before installing this.
    |
    | Two, because the reading surfaces and the admin ones almost never want
    | the same chrome.
    |
    */

    'layouts' => [
        'public' => null,
        'admin' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audience
    |--------------------------------------------------------------------------
    |
    | Who may read internal articles. The default asks the `viewInternal`
    | gate ability and falls back to "any authenticated user" when no such
    | ability is defined, which is the right default for a single-team app and
    | the wrong one for a customer portal — bind your own implementation of
    | NyonCode\KnowledgeBase\Contracts\KnowledgeAudience there.
    |
    */

    'audience' => [
        'internal_ability' => 'knowledge.internal',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authoring
    |--------------------------------------------------------------------------
    */

    'authoring' => [
        // New articles start closed. A mistake then hides a page that should
        // have been public, instead of publishing one that should not be.
        'default_visibility' => Visibility::Internal->value,

        // How often an article should be confirmed as still true. Null turns
        // the freshness clock off; a number puts a "checked on" date on every
        // page and lists the stale ones for whoever maintains the base.
        'review_interval_days' => 180,

        // Keep at most this many revisions per article. Null keeps all.
        'max_revisions' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Editors
    |--------------------------------------------------------------------------
    |
    | How articles get written. Three drivers ship; add your own by
    | implementing NyonCode\KnowledgeBase\Contracts\EditorDriver and listing
    | it here.
    |
    | The choice is per installation, but the *format* is stored per article —
    | switching the default later leaves everything written before it
    | rendering exactly as it did.
    |
    | Markdown is the default deliberately: no JavaScript, clean diffs between
    | revisions, and it survives the next editor fashion. Pick another when the
    | people writing will not write markdown, which is a real reason and the
    | only good one.
    |
    */

    'editors' => [
        'default' => 'blocks',

        'drivers' => [
            MarkdownEditor::class,
            RichTextEditor::class,
            BlockEditor::class,
        ],

        /*
        | Nabídka jazyků pro code blok — v blokovém editoru i v TipTapu.
        |
        | Klíč je identifikátor jazyka, jak ho čeká zvýrazňovač (Torchlight,
        | highlight.js); hodnota je jen popisek v nabídce. Psalo se sem
        | volným polem a doplácela na to hlavně ta půlka, která se nezobrazí:
        | překlep (`js` vs `javascript`, `yml` vs `yaml`) zvýraznění tiše
        | vypne — blok se vykreslí, jen je černobílý, takže si toho autor
        | všimne až na hotové stránce.
        |
        | Seznam je konfigurace, ne konstanta: aplikace, která píše o Rustu,
        | si ho přepíše, a nabídka zůstane krátká i pro ty ostatní.
        */
        'languages' => [
            'php' => 'PHP',
            'blade' => 'Blade',
            'javascript' => 'JavaScript',
            'typescript' => 'TypeScript',
            'html' => 'HTML',
            'css' => 'CSS',
            'json' => 'JSON',
            'yaml' => 'YAML',
            'bash' => 'Shell',
            'sql' => 'SQL',
            'python' => 'Python',
            'xml' => 'XML',
            'markdown' => 'Markdown',
            'diff' => 'Diff',
            'text' => 'Text',
        ],

        'tiptap' => [
            // The package ships the Blade side and the Alpine glue, not TipTap
            // itself — bundling an editor would push a second ProseMirror into
            // apps that already have one. Bundle it, expose `window.kbEditor`,
            // then flip this to true and the driver appears in the picker.
            'bundled' => false,
        ],

        'blocks' => [
            // Typy nabídnuté v modálu, seskupené podle toho, co s nimi člověk
            // chce udělat. Každý potřebuje pohled `knowledge-base::blocks.<typ>`
            // pro render a `knowledge-base::editors.blocks.<typ>` pro editaci;
            // neznámý typ se **přeskočí**, nespadne — bloky přežívají kód,
            // který je uměl, takže odebrat typ je bezpečné.
            'types' => [
                'text' => ['heading', 'text', 'list', 'steps', 'quote', 'divider'],
                'highlight' => ['callout', 'definition', 'checklist'],
                'code' => ['code', 'terminal', 'table'],
                'media' => ['image', 'video', 'file'],
                'links' => ['article', 'faq'],
            ],

            /*
            | Výchozí stav zaškrtávátek `code` bloku.
            |
            | Zvýrazňovač hostitele má svoje globální nastavení (u Torchlightu
            | `config/torchlight.php`) a tohle ho má **zrcadlit**: blok, kde
            | autor nic nepřepnul, se pak vykreslí stejně, jako by tu volba
            | vůbec nebyla. Kdyby se ty dvě hodnoty rozešly, panel by ukazoval
            | jeden stav a stránka dělala druhý.
            */
            'code' => [
                'line_numbers' => false,
                'diff_indicators' => true,
            ],

            // Odkud se smí vložit přehrávač. Sanitizér `iframe` jinak zahazuje
            // a je to správně: je to cizí kód na naší stránce. Tenhle seznam
            // je jediné místo, kde se ta výjimka povoluje.
            'embed_hosts' => [
                'youtube.com', 'www.youtube.com', 'youtu.be',
                'vimeo.com', 'player.vimeo.com',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Images
    |--------------------------------------------------------------------------
    |
    | Where uploads land and what the gallery lists. Bind
    | NyonCode\KnowledgeBase\Contracts\ImageLibrary to your own media library
    | if you have one — the shipped implementation is a disk and a directory.
    |
    */

    'images' => [
        'disk' => 'public',
        'directory' => 'images',
        'max_kb' => 4096,
        'mimes' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    |
    | The shipped driver is plain SQL: portable, no extra service, good enough
    | for the few thousand articles a knowledge base actually holds. Weights
    | decide what wins when a term matches in more than one place — a hit in
    | the title almost always beats a hit in the body.
    |
    */

    'search' => [
        'min_length' => 2,
        'limit' => 20,
        'weights' => [
            'title' => 10,
            'excerpt' => 4,
            'body' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feedback
    |--------------------------------------------------------------------------
    */

    'feedback' => [
        'enabled' => true,
        // Ask for a written reason after a "no". The comment is where the next
        // article comes from; the vote alone only tells you something is wrong.
        'ask_why' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    |
    | Prefixed rather than generic (`articles` is a name half of every Laravel
    | app already uses).
    |
    */

    'tables' => [
        'categories' => 'kb_categories',
        'articles' => 'kb_articles',
        'revisions' => 'kb_article_revisions',
        'feedback' => 'kb_article_feedback',
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Swap any of these for your own subclass.
    |
    */

    'models' => [
        'user' => null, // defaults to the app's configured auth model
    ],
];
