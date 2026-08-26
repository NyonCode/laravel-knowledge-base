<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Livewire\Admin;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use NyonCode\KnowledgeBase\Contracts\KnowledgeAudience;
use NyonCode\KnowledgeBase\Enums\ArticleStatus;
use NyonCode\KnowledgeBase\Models\Article;
use NyonCode\KnowledgeBase\Models\Category;
use NyonCode\KnowledgeBase\Support\Cast;
use NyonCode\KnowledgeBase\Support\Layouts;

/**
 * The editor's desk: every article, with the rot counted at the top.
 *
 * A knowledge base fails by rotting, not by being empty, so the state of the
 * base has to be visible without asking for it. That job belongs to the
 * **counts** — `Neověřené 12` sits above the list whatever the editor is
 * doing — not to a filtered landing state that stops warning the moment you
 * leave it.
 *
 * Proto tu není fronta jako zvláštní plocha. Dřív to byla čtyři rovnocenná
 * tlačítka, jenže „dlouho neověřené" i „špatně hodnocené" jsou **podmnožiny**
 * „potřebuje pozornost" — přepínač mezi celkem a jeho částí vypadá jako volba
 * mezi pohledy a žádná to není. Zbyla jedna osa: `reason` zužuje seznam na
 * jeden důvod a důvod se nese v řádku ({@see Article::attentionReasons()}).
 *
 * Že se vychází z celé báze, není kosmetika: hledání jinak prohledává výběr,
 * ne bázi, a zdravý článek se v něm nenajde — bez vysvětlení, proč ne.
 */
class ArticleList extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $term = '';

    /** Jeden z {@see Article::REASONS}, nebo prázdno pro celou bázi. */
    #[Url(except: '')]
    public string $reason = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $category = '';

    /**
     * Název právě uloženého článku, nebo prázdno.
     *
     * Editor po uložení odchází sem, takže potvrzení musí dorazit s ním —
     * jinak by se člověk vrátil na seznam a nevěděl, jestli uložení prošlo.
     * Nese název, ne jen „ano": na seznamu dvaceti řádků je „Uloženo" bez
     * jména sdělení o ničem.
     */
    public string $saved = '';

    public function mount(KnowledgeAudience $audience): void
    {
        abort_unless($audience->canManage(auth()->user()), 403);

        $this->saved = Cast::string(session('knowledge-base.saved', ''));

        // Adresa je vstup jako každý jiný. Nesmyslná hodnota nesmí skončit
        // ve stavu, ke kterému se nedá doklikat zpátky.
        if ($this->reason !== '' && ! in_array($this->reason, Article::REASONS, true)) {
            $this->reason = '';
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['term', 'status', 'category'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Zúží seznam na jeden důvod, nebo ho zase rozšíří na celou bázi.
     *
     * Klik na aktivní čip filtr zruší: jinak by se z jednoho důvodu nedalo
     * vrátit na celek jinak než dalším tlačítkem, a přesně tím to dřív bylo
     * přeplácané.
     */
    public function filterByReason(string $reason = ''): void
    {
        $reason = in_array($reason, Article::REASONS, true) ? $reason : '';

        $this->reason = $this->reason === $reason ? '' : $reason;

        $this->resetPage();
    }

    public function render(): View
    {
        return Layouts::admin(view('knowledge-base::admin.articles', [
            'articles' => $this->query()->paginate(20),
            'categories' => Category::query()->ordered()->pluck('name', 'id'),
            'statuses' => ArticleStatus::options(),
            'counts' => $this->counts(),
            'total' => Article::query()->count(),
        ]));
    }

    /**
     * Kolik článků čeká na kterém důvodu, plus kolik jich čeká dohromady.
     *
     * Tohle je poplach, který v seznamu všech článků chybí: bez něj by rot
     * byl jen pár odznaků rozházených po stránkovaném seznamu. Počítá se přes
     * celou bázi, ne přes to, co je zrovna vyfiltrované — poplach se filtrem
     * nesmí ztišit. A nula je odpověď („tímhle se bát nemusíš"), ne prázdné
     * místo, proto se čip s nulou pořád vykreslí, jen zešedne.
     *
     * @return array{queue: int, reasons: array<string, int>}
     */
    protected function counts(): array
    {
        $reasons = [];

        foreach ($this->reasonScopes() as $key => $scope) {
            $reasons[$key] = $scope(Article::query())->count();
        }

        return [
            'queue' => $this->queue(Article::query())->count(),
            'reasons' => $reasons,
        ];
    }

    /**
     * Proč článek leží ve frontě — jediné místo, kde ty podmínky žijí.
     *
     * Počet nad seznamem a filtr pod ním odpovídají na tutéž otázku; dvě
     * kopie téhož `where` se rozejdou při první úpravě prahu.
     *
     * @return array<string, Closure(Builder<Article>): Builder<Article>>
     */
    protected function reasonScopes(): array
    {
        return [
            Article::REASON_DRAFT => $this->onlyDrafts(...),
            Article::REASON_STALE => $this->onlyStale(...),
            Article::REASON_UNHELPFUL => $this->onlyUnhelpful(...),
        ];
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    protected function onlyDrafts(Builder $query): Builder
    {
        return $query->where('status', ArticleStatus::Draft->value);
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    protected function onlyStale(Builder $query): Builder
    {
        return $query->stale();
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    protected function onlyUnhelpful(Builder $query): Builder
    {
        return $query->needsAttention();
    }

    /**
     * Sjednocení všech důvodů — jedním dotazem, ne třemi dotazy sečtenými.
     *
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    protected function queue(Builder $query): Builder
    {
        return $query->where(function (Builder $inner): void {
            foreach ($this->reasonScopes() as $scope) {
                $inner->orWhere(fn (Builder $q) => $scope($q));
            }
        });
    }

    /** @return Builder<Article> */
    protected function query(): Builder
    {
        $reasons = $this->reasonScopes();

        return Article::query()
            ->with('category')
            ->when($this->term !== '', fn (Builder $q) => $q->where(
                fn (Builder $inner) => $inner
                    ->where('title', 'like', '%'.$this->term.'%')
                    ->orWhere('slug', 'like', '%'.$this->term.'%')
            ))
            ->when($this->status !== '', fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->category !== '', fn (Builder $q) => $q->where('category_id', $this->category))
            ->when($this->reason !== '', fn (Builder $q) => $reasons[$this->reason]($q))
            ->orderByDesc('updated_at');
    }
}
