# Laravel Knowledge Base

A knowledge base you can put in front of customers, your team, or both — in one
installation. Diátaxis-shaped articles, search-first navigation, revisions,
reader feedback and a freshness clock. Ships Livewire 3/4 components and Blade
views you can publish and restyle.

```bash
composer require nyoncode/laravel-knowledge-base
php artisan vendor:publish --tag=knowledge-base-migrations
php artisan migrate
```

That is the whole install. The base mounts itself at `/napoveda`.

---

## What it is opinionated about

Three decisions are baked in, because a knowledge base that gets them wrong
fails quietly rather than loudly.

**Search first, tree second.** Readers arrive with a sentence, not a place in a
hierarchy. The landing page is one autofocused field; the category grid is what
catches the people whose sentence failed. `<livewire:kb.search />` puts the same
search behind ⌘K on every page of your app.

**Status and visibility are two questions.** Status is *our* readiness (draft →
in review → published). Visibility is *whose eyes* (internal / public). A
finished internal runbook is published **and** internal. One combined "publish"
button is how private notes reach the open web, so there isn't one.

**A page nobody has confirmed is suspect.** Every article carries a review
clock. Past its date it says so to the reader, in a banner, and appears in the
maintainer's queue. A knowledge base does not break — it rots, and this is the
only thing that surfaces it.

## The four kinds

Articles declare what they are for ([Diátaxis](https://diataxis.fr)), which
drives the badge, the reading order inside a category, and the one-line promise
under the title:

| Kind | For a reader who… |
|---|---|
| Tutorial | is learning, and needs a guaranteed-working first run |
| How-to | already has a goal and needs the steps |
| Reference | knows what they want and needs the exact value |
| Background | wants to understand why it works this way |

## Choose your editor

Markdown, TipTap or blocks — per installation, and the **format is stored per
article**, so switching the default later leaves everything written before it
rendering exactly as it did.

```php
// config/knowledge-base.php
'editors' => [
    'default' => 'markdown',   // 'tiptap' | 'blocks' | your own
    'drivers' => [
        NyonCode\KnowledgeBase\Editors\MarkdownEditor::class,
        NyonCode\KnowledgeBase\Editors\RichTextEditor::class,
        NyonCode\KnowledgeBase\Editors\BlockEditor::class,
    ],
],
```

Markdown is the default deliberately: no JavaScript, clean diffs between
revisions, and it outlives the next editor fashion. Pick another when the people
writing will not write markdown — a real reason, and the only good one.

TipTap ships as a shell, not a bundle. Bundling an editor would push a second
copy of ProseMirror into apps that already have one, so the host builds it,
exposes `window.kbEditor(el, { content, onChange })` and flips
`editors.tiptap.bundled` to `true`. Until then the driver is not offered at all
— an editor that renders an inert box is worse than one that is absent.

You do not have to write that adapter. The package ships it as **source** in
`resources/js/kb-editor.js` — every extension the editor's toolbar promises,
already wired to the contract. Point your bundler at it and install the TipTap
packages it imports; they stay your dependencies, at your version, which is the
whole point of not shipping a bundle.

```js
// vite.config.js
export default defineConfig({
    // Vite resolves a symlinked path repository to its real location, where
    // there is no node_modules to resolve @tiptap/* from.
    resolve: { preserveSymlinks: true },
    plugins: [laravel({
        input: [
            'vendor/nyoncode/laravel-knowledge-base/resources/js/kb-editor.js',
        ],
    })],
});
```

Your own is one class:

```php
final class NotionLikeEditor implements EditorDriver
{
    public function name(): string { return 'notion'; }
    public function label(): string { return 'Notion-like'; }
    public function format(): ContentFormat { return ContentFormat::Blocks; }
    public function view(): string { return 'editors.notion'; }
    public function isAvailable(): bool { return true; }
}
```

## Make it yours

Everything replaceable is a contract with a shipped default:

| Contract | Default | Replace it when… |
|---|---|---|
| `KnowledgeAudience` | gate ability, else any signed-in user | "internal" means customers, tenants, one admin… |
| `ArticleSearch` | portable SQL, weighted by column | you outgrow a few thousand articles → Scout |
| `ContentRenderer` | CommonMark / rich text / blocks | you want a syntax highlighter, or a fourth format |
| `EditorDriver` | markdown / TipTap / blocks | you have your own writing surface |

```php
$this->app->bind(KnowledgeAudience::class, MyTenantAudience::class);
```

**Read `KnowledgeAudience` before you ship.** The default is right for a
single-team back office and wrong for a customer portal, and the failure mode
is a leak. Two methods, both needed: `canSeeInternal()` styles the UI,
`scopeVisible()` is what actually keeps rows out of the answer — hiding a link
is not the same as not returning the row.

## Components

| Component | What it is |
|---|---|
| `<livewire:kb.home />` | search + category map |
| `<livewire:kb.search />` | ⌘K palette, drop it in your layout |
| `<livewire:kb.category :category="$c" />` | one collection, grouped by kind |
| `<livewire:kb.article :article="$slug" />` | the page, with contents and feedback |
| `<livewire:kb.admin.articles />` | maintenance queue |
| `<livewire:kb.admin.editor :article="$a" />` | write |

Registered under `kb.*`; register your own class under the same name last and
yours wins.

## Views

`php artisan vendor:publish --tag=knowledge-base-views`

Tailwind utility classes, no build step, dark mode throughout. The prose block
is marked `.kb-prose` so you can scope typography without fighting the rest.

## Testing

```bash
composer test
composer analyse
```

## License

MIT.
