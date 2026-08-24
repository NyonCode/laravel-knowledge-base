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
        'default' => 'markdown',

        'drivers' => [
            MarkdownEditor::class,
            RichTextEditor::class,
            BlockEditor::class,
        ],

        'tiptap' => [
            // The package ships the Blade side and the Alpine glue, not TipTap
            // itself — bundling an editor would push a second ProseMirror into
            // apps that already have one. Bundle it, expose `window.kbEditor`,
            // then flip this to true and the driver appears in the picker.
            'bundled' => false,
        ],

        'blocks' => [
            // Block types offered in the picker. Each needs a matching
            // `knowledge-base::blocks.<type>` view; an unknown type renders
            // nothing rather than throwing, so removing one is safe.
            'types' => ['heading', 'text', 'callout', 'steps', 'code', 'image'],
        ],
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
