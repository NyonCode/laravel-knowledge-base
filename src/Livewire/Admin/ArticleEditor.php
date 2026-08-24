<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use NyonCode\KnowledgeBase\Contracts\ImageLibrary;
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
use NyonCode\KnowledgeBase\Support\BlockHtml;
use NyonCode\KnowledgeBase\Support\Cast;
use NyonCode\KnowledgeBase\Support\Layouts;
use NyonCode\KnowledgeBase\Support\Routes;
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
    use WithFileUploads;

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

    /** Otevřený výběr typu bloku. */
    public bool $blockPicker = false;

    /** Otevřený výběr obrázku. */
    public bool $imagePicker = false;

    /**
     * Kam obrázek půjde: index bloku, nebo `null` pro TipTap.
     *
     * Jeden výběr obsluhuje obě plochy — dvě skoro stejné modálky by se
     * rozešly při první úpravě jedné z nich.
     */
    public ?int $imageTarget = null;

    /** @var mixed dočasně nahraný soubor */
    public $imageUpload = null;

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
        // Blokový editor drží rozepsaný obsah v `blockData`; `body` je tvar,
        // který se ukládá. Bez převedení by se uložilo to, co v `body` leželo
        // předtím — u nového článku prázdno (a validace to zamítne jako
        // nevyplněné tělo), u článku přepnutého z markdownu jeho starý text.
        if ($this->format() === ContentFormat::Blocks) {
            $this->body = self::toCanonical($this->blockData);
        }

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

    /**
     * Články, na které jde odkázat – bez toho, který se právě edituje.
     *
     * @return array<string, string> slug => titulek
     */
    public function linkableArticles(): array
    {
        /** @var array<string, string> $articles */
        $articles = Article::query()
            ->when(
                $this->article?->exists,
                fn (Builder $query) => $query->whereKeyNot(Cast::int($this->article?->getKey()))
            )
            ->orderBy('title')
            ->pluck('title', 'slug')
            ->all();

        return $articles;
    }

    /**
     * Články pro našeptávač `@` v textu.
     *
     * Zmínka vloží **obyčejný odkaz**, ne zvláštní uzel: je to způsob, jak
     * odkaz najít bez opisování adresy, ne nový druh obsahu. Uložený článek
     * tak nese `<a>`, kterému rozumí čtenář, sanitizér i vyhledávání.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function mentionTargets(): array
    {
        return array_map(
            static fn (string $title, string $slug): array => [
                'label' => $title,
                'url' => Routes::articlePath($slug),
            ],
            array_values($this->linkableArticles()),
            array_keys($this->linkableArticles()),
        );
    }

    // --- Obrázky ---------------------------------------------------------------

    public function openImagePicker(?int $index = null): void
    {
        $this->imageTarget = $index;
        $this->imagePicker = true;
    }

    public function closeImagePicker(): void
    {
        $this->reset(['imagePicker', 'imageTarget', 'imageUpload']);
    }

    /**
     * Nahraný soubor rovnou uloží a použije.
     *
     * Bez potvrzovacího kroku schválně: přetáhnout soubor **je** to potvrzení
     * a druhé kliknutí by z rychlé věci udělalo formulář.
     */
    public function updatedImageUpload(): void
    {
        $this->validate([
            'imageUpload' => [
                'required',
                'image',
                'mimes:'.implode(',', Settings::strings('images.mimes')),
                'max:'.Settings::int('images.max_kb', 4096),
            ],
        ]);

        // Po validaci `image` to soubor je, ale typ vlastnosti je `mixed`
        // (Livewire tam drží i řetězec z předchozího requestu).
        if (! $this->imageUpload instanceof UploadedFile) {
            return;
        }

        $this->useImage(app(ImageLibrary::class)->store($this->imageUpload));
    }

    /**
     * Vloží adresu tam, odkud se výběr otevřel.
     *
     * Bloku se zapíše přímo do pole; TipTap DOM nevlastní Livewire (drží ho
     * ProseMirror pod `wire:ignore`), takže tam jde událost a vložení si
     * obstará editor sám.
     */
    public function useImage(string $url): void
    {
        if ($this->imageTarget !== null) {
            $this->blockData[$this->imageTarget]['src'] = $url;
        } else {
            $this->dispatch('kb-image-picked', url: $url);
        }

        $this->closeImagePicker();
    }

    // --- Blocks --------------------------------------------------------------
    //
    // Strukturu mění server, ne JavaScript nad skrytým polem: jeden vlastník
    // pole znamená, že pořadí na obrazovce a pořadí v databázi se nemůžou
    // rozejít — což je porucha, kterou má každý podomácku psaný blokový editor.

    public function addBlock(string $type): void
    {
        $this->blockData[] = ['type' => $type] + self::blockDefaults($type);
        $this->blockPicker = false;
    }

    /**
     * Výchozí hodnoty polí bloku.
     *
     * Doplňují se při vložení **i při načtení uloženého článku**, a to kvůli
     * zaškrtávátkům: chybějící volba znamená „nech to na globálním nastavení",
     * jenže prázdný checkbox tvrdí „vypnuto". U `diffIndicators`, které jsou
     * globálně zapnuté, by panel ukazoval pravý opak toho, co stránka dělá.
     * Doplněná hodnota je stejná jako to globální nastavení
     * ({@see config('knowledge-base.editors.blocks.code')}), takže se render
     * nemění — jen se přestane lhát.
     *
     * Jazyk se doplňuje **jen u nového** bloku: u uloženého je prázdno volba
     * autora (blok bez zvýraznění), ne chybějící údaj.
     *
     * @return array<string, mixed>
     */
    protected static function blockDefaults(string $type, bool $fresh = true): array
    {
        if ($type !== 'code') {
            return [];
        }

        $options = Settings::array('editors.blocks.code');

        $defaults = [
            'line_numbers' => (bool) ($options['line_numbers'] ?? false),
            'diff_indicators' => (bool) ($options['diff_indicators'] ?? true),
        ];

        if ($fresh) {
            $languages = array_keys(Settings::array('editors.languages'));
            $defaults['language'] = (string) ($languages[0] ?? 'text');
        }

        return $defaults;
    }

    /**
     * Nabídka typů, seskupená podle toho, co s nimi chce člověk udělat.
     *
     * Sedmnáct tlačítek v řadě je seznam, ve kterém se nedá vybírat; skupiny
     * z toho dělají nabídku. Typ bez pohledu na render se **nenabídne** —
     * jinak by se dal vložit blok, který se na stránce neobjeví.
     *
     * @return array<string, array<int, string>>
     */
    public function blockTypes(): array
    {
        $groups = [];

        foreach (Settings::array('editors.blocks.types') as $group => $types) {
            $usable = array_values(array_filter(
                is_array($types) ? $types : [],
                static fn (mixed $type): bool => is_string($type)
                    && view()->exists('knowledge-base::blocks.'.$type),
            ));

            if ($usable !== []) {
                $groups[(string) $group] = $usable;
            }
        }

        return $groups;
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

    /**
     * Přesune blok na místo `$gap` (0..n, počítáno v poli **před** vyjmutím).
     *
     * Volá to přetažení; tlačítka ↑↓ zůstávají, protože bez myši by jinak
     * pořadí nešlo změnit vůbec.
     */
    public function moveBlockTo(int $from, int $gap): void
    {
        if (! isset($this->blockData[$from])) {
            return;
        }

        $block = $this->blockData[$from];

        array_splice($this->blockData, $from, 1);

        // Vyjmutí posunulo všechno za původní pozicí o jedna doleva, takže
        // mezera za ním má po vyjmutí o jedna nižší index.
        $target = $gap > $from ? $gap - 1 : $gap;

        array_splice($this->blockData, max(0, $target), 0, [$block]);
    }

    public function removeBlock(int $index): void
    {
        unset($this->blockData[$index]);

        $this->blockData = array_values($this->blockData);
    }

    /**
     * Kanonický tvar → řádky k editaci.
     *
     * Seznamy (`steps`, `list`, `checklist`) se rozpadají na řádky textarey,
     * protože odrážky i číslování patří renderu: autor píše položky pod sebe,
     * ne `1.`, `2.`, `3.`.
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

            /** @var array<string, mixed> $data */
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            // Tabulka a seznamy se dnes editují jako HTML; starší tvar se
            // převede při otevření, ne migrací (viz BlockHtml).
            $data = BlockHtml::upgrade($block['type'], $data);

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
            $row = ['type' => $block['type']]
                + $data
                + self::blockDefaults($block['type'], fresh: false);

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

        // Prázdno se vrací prázdné, ne jako `[]`: pravidlo `required` by
        // dvouznakový řetězec vzalo a uložila by se stránka bez obsahu.
        return $blocks === [] ? '' : (string) json_encode($blocks);
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
            'gallery' => $this->imagePicker
                ? app(ImageLibrary::class)->recent()
                : [],
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
