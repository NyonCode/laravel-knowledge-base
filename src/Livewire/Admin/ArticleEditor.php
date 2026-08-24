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

    /**
     * Bloky rozbalené pro editaci.
     *
     * V databázi je kanonický tvar `['type' => …, 'data' => […]]`, tady je
     * `data` splácnuté do řádku (`blockData.0.text`). Livewire umí bindovat
     * na cestu, ne na zanoření skrz JSON řetězec — a jedna hodnota nesmí mít
     * dvě podoby v úložišti jen proto, aby se dala pohodlně editovat.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $blockData = [];

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

        if ($this->format()->isStructured()) {
            $this->blockData = self::toEditing($this->body);
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
                'format' => $this->format(),
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
    // Strukturu mění server, ne JavaScript nad skrytým polem: jeden vlastník
    // pole znamená, že pořadí na obrazovce a pořadí v databázi se nemůžou
    // rozejít — což je porucha, kterou má každý podomácku psaný blokový editor.

    public function addBlock(string $type): void
    {
        $this->blockData[] = ['type' => $type];
    }

    public function moveBlock(int $index, int $delta): void
    {
        $target = $index + $delta;

        if (! isset($this->blockData[$index], $this->blockData[$target])) {
            return;
        }

        [$this->blockData[$index], $this->blockData[$target]] =
            [$this->blockData[$target], $this->blockData[$index]];
    }

    /**
     * Zkopíruje blok pod originál.
     *
     * U kroků a upozornění se stejná struktura opakuje a přeťukat ji znovu je
     * ta nejčastější práce navíc, kterou blokový editor umí ušetřit.
     */
    public function duplicateBlock(int $index): void
    {
        if (! isset($this->blockData[$index])) {
            return;
        }

        array_splice($this->blockData, $index + 1, 0, [$this->blockData[$index]]);
    }

    public function removeBlock(int $index): void
    {
        unset($this->blockData[$index]);

        $this->blockData = array_values($this->blockData);
    }

    /**
     * Kanonický tvar → řádky k editaci.
     *
     * `steps` se rozpadá na řádky textarey, protože číslování patří renderu:
     * autor píše kroky pod sebe, ne `1.`, `2.`, `3.`.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function toEditing(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return [];
        }

        $rows = [];

        foreach ($decoded as $block) {
            if (! is_array($block) || ! is_string($block['type'] ?? null)) {
                continue;
            }

            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            if (isset($data['items']) && is_array($data['items'])) {
                $data['lines'] = implode("\n", array_map(
                    static fn (mixed $item): string => is_string($item)
                        ? $item
                        : Cast::string(is_array($item) ? ($item['text'] ?? '') : ''),
                    $data['items']
                ));
                unset($data['items']);
            }

            /** @var array<string, mixed> $row */
            $row = ['type' => $block['type']] + $data;

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Řádky → kanonický tvar.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected static function toCanonical(array $rows): string
    {
        $blocks = [];

        foreach ($rows as $row) {
            $type = $row['type'] ?? null;

            if (! is_string($type)) {
                continue;
            }

            unset($row['type']);

            if (array_key_exists('lines', $row)) {
                $row['items'] = array_values(array_filter(
                    array_map('trim', explode("\n", Cast::string($row['lines']))),
                    static fn (string $line): bool => $line !== ''
                ));
                unset($row['lines']);
            }

            $blocks[] = ['type' => $type, 'data' => $row];
        }

        return (string) json_encode($blocks);
    }

    /** @return array<int, mixed> */
    protected static function decode(string $json): array
    {
        $decoded = json_decode($json, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    protected function format(): ContentFormat
    {
        return app(EditorRegistry::class)->get($this->editor)?->format()
            ?? ContentFormat::Markdown;
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
                        // Náhled ze živých bloků, ne z uloženého JSONu: jinak
                        // ukazuje minulost a nikdo mu pak nevěří.
                        ? self::decode(self::toCanonical($this->blockData))
                        : $this->body
                )
                : null,
        ]));
    }
}
