<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use NyonCode\KnowledgeBase\Contracts\KnowledgeAudience;
use NyonCode\KnowledgeBase\Enums\Visibility;
use NyonCode\KnowledgeBase\Models\Category;
use NyonCode\KnowledgeBase\Support\Cast;
use NyonCode\KnowledgeBase\Support\Layouts;
use NyonCode\KnowledgeBase\Support\Settings;

/**
 * Kategorie: seznam a editace na jedné obrazovce.
 *
 * Bez vlastního editoru schválně — kategorie má pět polí a druhá obrazovka
 * kvůli nim je klik navíc pokaždé, když se přejmenovává nebo přeřazuje. Řádek
 * se rozbalí na místě.
 *
 * Mazání kategorie **články nemaže**: `category_id` je `nullOnDelete`, takže
 * osiří a dají se zařadit jinam. Smazat kategorii je uklizení polic, ne
 * vyhození knih — a obráceně by se dalo omylem přijít o půlku báze.
 */
class CategoryList extends Component
{
    public ?int $editing = null;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $visibility = 'internal';

    public int $sortOrder = 0;

    public function mount(KnowledgeAudience $audience): void
    {
        abort_unless($audience->canManage(auth()->user()), 403);
    }

    public function edit(int $id): void
    {
        $category = Category::query()->findOrFail($id);

        $this->editing = Cast::int($category->getKey());
        $this->name = (string) $category->name;
        $this->slug = (string) $category->slug;
        $this->description = (string) $category->description;
        $this->visibility = $category->visibility->value;
        $this->sortOrder = (int) $category->sort_order;
    }

    public function create(): void
    {
        $this->reset(['editing', 'name', 'slug', 'description']);

        $this->visibility = Visibility::Internal->value;
        $this->sortOrder = Cast::int(Category::query()->max('sort_order')) + 10;
        $this->editing = 0;
    }

    public function save(): void
    {
        /** @var array<string, mixed> $data */
        $data = $this->validate();

        Category::query()->updateOrCreate(
            ['id' => $this->editing ?: null],
            [
                'name' => Cast::string($data['name']),
                'slug' => Cast::string($data['slug']),
                'description' => Cast::nullableString($data['description']),
                'visibility' => Cast::string($data['visibility']),
                'sort_order' => Cast::int($data['sortOrder']),
            ],
        );

        $this->cancel();
    }

    /**
     * Smaže kategorii, články nechá být.
     *
     * Osiřelé články zůstanou dohledatelné hledáním a dají se zařadit jinam;
     * kaskáda by z přejmenování polic udělala ztrátu obsahu.
     */
    public function delete(int $id): void
    {
        Category::query()->findOrFail($id)->delete();

        $this->cancel();
    }

    public function cancel(): void
    {
        $this->reset(['editing', 'name', 'slug', 'description', 'sortOrder']);
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique(
                    Settings::string('tables.categories', 'kb_categories'),
                    'slug'
                )->ignore($this->editing ?: null),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'visibility' => ['required', Rule::enum(Visibility::class)],
            'sortOrder' => ['required', 'integer', 'min:0'],
        ];
    }

    public function render(): View
    {
        return Layouts::admin(view('knowledge-base::admin.categories', [
            'categories' => Category::query()
                ->ordered()
                ->withCount('articles')
                ->get(),
            'visibilities' => Visibility::cases(),
        ]));
    }
}
