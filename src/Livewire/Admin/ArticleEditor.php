<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use NyonCode\KnowledgeBase\Contracts\KnowledgeAudience;
use NyonCode\KnowledgeBase\Enums\ArticleKind;
use NyonCode\KnowledgeBase\Enums\ArticleStatus;
use NyonCode\KnowledgeBase\Enums\ContentFormat;
use NyonCode\KnowledgeBase\Enums\Visibility;
use NyonCode\KnowledgeBase\Models\Article;
use NyonCode\KnowledgeBase\Models\Category;
use NyonCode\KnowledgeBase\Services\EditorRegistry;
use NyonCode\KnowledgeBase\Services\KnowledgeBase;
use NyonCode\KnowledgeBase\Services\RendererRegistry;
use NyonCode\KnowledgeBase\Support\Cast;
use NyonCode\KnowledgeBase\Support\Layouts;
use NyonCode\KnowledgeBase\Support\Settings;

/**
 * Write an article.
 *
 * Markdown with a live preview rather than a rich-text editor: the body is
 * stored as text, diffed between revisions and read by people editing it in a
 * pull request one day — a WYSIWYG that emits HTML makes all three worse.
 *
 * Publishing is deliberately two decisions, never one button. Status says
 * whether *we* are finished; visibility says *who* may read it. A single
 * "publish" control is how an internal runbook ends up on the open web.
 */
class ArticleEditor extends Component
{
    public ?Article $article = null;

    public string $title = '';

    public string $slug = '';

    public string $excerpt = '';

    public string $body = '';

    public ?int $categoryId = null;

    public string $kind = 'how-to';

    public string $status = 'draft';

    public string $visibility = 'internal';

    /**
     * Which surface is writing this article.
     *
     * Chosen once, when the article is created, and then followed rather than
     * re-decided: reopening a page in a different editor would reinterpret its
     * body — JSON blocks read as markdown come out as gibberish.
     */
    public string $editor = 'markdown';

    /** Why this edit happened — stored on the revision, not on the article. */
    public string $note = '';

    public bool $preview = false;

    public function mount(
        ?Article $article,
        KnowledgeAudience $audience,
        EditorRegistry $editors
    ): void {
        abort_unless($audience->canManage(auth()->user()), 403);

        $this->editor = $editors->default()->name();

        $this->visibility = Settings::string('authoring.default_visibility', Visibility::Internal->value);

        if ($article?->exists) {
            $this->article = $article;
            $this->title = (string) $article->title;
            $this->slug = (string) $article->slug;
            $this->excerpt = (string) $article->excerpt;
            $this->body = (string) $article->body;
            $this->categoryId = $article->category_id;
            $this->kind = $article->kind->value;
            $this->status = $article->status->value;
            $this->visibility = $article->visibility->value;

            // Follow the article, not the installation default.
            $this->editor = $editors->forFormat($article->format)->name();
        }
    }

    /**
     * The slug follows the title only while the article has never been saved.
     *
     * After that it is frozen: it is the address people bookmarked, and
     * silently rewriting it on a typo fix breaks every link ever shared.
     */
    public function updatedTitle(string $value): void
    {
        if ($this->article === null) {
            $this->slug = Str::slug($value);
        }
    }

    public function save(KnowledgeBase $kb): void
    {
        /** @var array<string, mixed> $data */
        $data = $this->validate();

        $article = $kb->save(
            $this->article ?? new Article,
            [
                'title' => Cast::string($data['title']),
                'slug' => Cast::string($data['slug']),
                'excerpt' => Cast::nullableString($data['excerpt']),
                'body' => Cast::string($data['body']),
                'category_id' => Cast::nullableInt($data['categoryId']),
                'kind' => Cast::string($data['kind']),
                'status' => Cast::string($data['status']),
                'visibility' => Cast::string($data['visibility']),
                'format' => app(EditorRegistry::class)->get($this->editor)?->format()
                    ?? ContentFormat::Markdown,
            ],
            auth()->user(),
            $this->note ?: null,
        );

        $this->article = $article;
        $this->note = '';

        $this->dispatch('kb-article-saved', id: $article->getKey());
    }

    // --- Blocks --------------------------------------------------------------
    //
    // Structure is changed here, on the server, and never by JavaScript
    // mutating a hidden field: one owner for the array is what keeps the order
    // on screen and the order in the database from disagreeing.

    public function addBlock(string $type): void
    {
        $blocks = $this->blocks();
        $blocks[] = ['type' => $type, 'data' => ['text' => '']];

        $this->body = (string) json_encode(array_values($blocks));
    }

    public function moveBlock(int $index, int $delta): void
    {
        $blocks = $this->blocks();
        $target = $index + $delta;

        if (! isset($blocks[$index], $blocks[$target])) {
            return;
        }

        [$blocks[$index], $blocks[$target]] = [$blocks[$target], $blocks[$index]];

        $this->body = (string) json_encode(array_values($blocks));
    }

    public function removeBlock(int $index): void
    {
        $blocks = $this->blocks();
        unset($blocks[$index]);

        $this->body = (string) json_encode(array_values($blocks));
    }

    /** @return array<int, mixed> */
    protected function blocks(): array
    {
        $decoded = json_decode($this->body, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    /** Say "still true" without editing a word. */
    public function markReviewed(KnowledgeBase $kb): void
    {
        if ($this->article !== null) {
            $kb->markReviewed($this->article);
            $this->article->refresh();
        }
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'required',
                'string',
                'max:200',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique(
                    Settings::string('tables.articles', 'kb_articles'),
                    'slug'
                )->ignore($this->article?->getKey()),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'categoryId' => [
                'nullable',
                Rule::exists(Settings::string('tables.categories', 'kb_categories'), 'id'),
            ],
            'kind' => ['required', Rule::enum(ArticleKind::class)],
            'status' => ['required', Rule::enum(ArticleStatus::class)],
            'visibility' => ['required', Rule::enum(Visibility::class)],
        ];
    }

    public function render(EditorRegistry $editors, RendererRegistry $renderers): View
    {
        $driver = $editors->get($this->editor) ?? $editors->default();

        return Layouts::admin(view('knowledge-base::admin.editor', [
            'categories' => Category::query()->ordered()->pluck('name', 'id'),
            'kinds' => ArticleKind::cases(),
            'statuses' => ArticleStatus::cases(),
            'visibilities' => Visibility::cases(),
            'driver' => $driver,
            'editors' => $editors->available(),
            'rendered' => $this->preview
                ? $renderers->render(
                    $driver->format(),
                    $driver->format()->isStructured()
                        ? $this->blocks()
                        : $this->body
                )
                : null,
        ]));
    }
}
