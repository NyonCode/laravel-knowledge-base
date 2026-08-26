<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use NyonCode\KnowledgeBase\Database\Factories\ArticleFactory;
use NyonCode\KnowledgeBase\Enums\ArticleKind;
use NyonCode\KnowledgeBase\Enums\ArticleStatus;
use NyonCode\KnowledgeBase\Enums\ContentFormat;
use NyonCode\KnowledgeBase\Enums\Visibility;
use NyonCode\KnowledgeBase\Support\Settings;

/**
 * One page of the knowledge base.
 *
 * Markdown is the source of truth; `body_html` is a cache of its render, kept
 * because pages are read far more often than written (see the migration).
 *
 * @property int $id
 * @property int|null $category_id
 * @property string $slug
 * @property string $title
 * @property string|null $excerpt
 * @property string|null $body
 * @property string|null $body_html
 * @property ArticleKind $kind
 * @property ContentFormat $format
 * @property ArticleStatus $status
 * @property Visibility $visibility
 * @property int|null $author_id
 * @property Carbon|null $published_at
 * @property Carbon|null $reviewed_at
 * @property int|null $review_interval_days
 * @property int $views_count
 * @property int $helpful_count
 * @property int $unhelpful_count
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category|null $category
 * @property-read Collection<int, ArticleRevision> $revisions
 * @property-read Collection<int, ArticleFeedback> $feedback
 */
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    /**
     * Kolik čtenářů musí stránku vidět, než se její hodnocení bere vážně.
     *
     * Bez prahu by každou novou stránku shodil jeden nespokojený hlas.
     */
    public const ATTENTION_MIN_VIEWS = 20;

    /** Proč článek leží ve frontě údržby. Pořadí je pořadí odznaků i čipů. */
    public const REASON_DRAFT = 'draft';

    public const REASON_STALE = 'stale';

    public const REASON_UNHELPFUL = 'unhelpful';

    public const REASONS = [self::REASON_DRAFT, self::REASON_STALE, self::REASON_UNHELPFUL];

    protected $guarded = [];

    protected $casts = [
        'kind' => ArticleKind::class,
        'format' => ContentFormat::class,
        'status' => ArticleStatus::class,
        'visibility' => Visibility::class,
        'published_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'review_interval_days' => 'integer',
        'views_count' => 'integer',
        'helpful_count' => 'integer',
        'unhelpful_count' => 'integer',
        'sort_order' => 'integer',
    ];

    public function getTable(): string
    {
        return Settings::string('tables.articles', 'kb_articles');
    }

    protected static function booted(): void
    {
        static::creating(function (self $article) {
            if (blank($article->slug)) {
                $article->slug = Str::slug((string) $article->title);
            }
        });
    }

    protected static function newFactory(): ArticleFactory
    {
        return ArticleFactory::new();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** @return HasMany<ArticleRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(ArticleRevision::class, 'article_id')->latest('id');
    }

    /** @return HasMany<ArticleFeedback, $this> */
    public function feedback(): HasMany
    {
        return $this->hasMany(ArticleFeedback::class, 'article_id');
    }

    /** @return BelongsTo<Model, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Settings::userModel(), 'author_id');
    }

    // --- Reading -------------------------------------------------------------

    /** Live for somebody — the audience decides for whom. */
    public function isLive(): bool
    {
        return $this->status->isLive();
    }

    public function isPublic(): bool
    {
        return $this->visibility->isPublic() && $this->isLive();
    }

    /**
     * Has anybody confirmed lately that this is still true?
     *
     * A knowledge base does not fail loudly. It rots: the page stays up, keeps
     * ranking, keeps being read, and quietly describes last year's product.
     * The review clock is the only thing that surfaces that, so "never
     * reviewed" counts as stale rather than as fine.
     */
    public function isStale(): bool
    {
        $interval = $this->review_interval_days
            ?? Settings::nullableInt('authoring.review_interval_days');

        if ($interval === null) {
            return false;
        }

        $checked = $this->reviewed_at ?? $this->published_at ?? $this->updated_at;

        return $checked === null
            || $checked->addDays((int) $interval)->isPast();
    }

    public function reviewDueAt(): ?Carbon
    {
        $interval = $this->review_interval_days
            ?? Settings::nullableInt('authoring.review_interval_days');

        $checked = $this->reviewed_at ?? $this->published_at;

        return $interval === null || $checked === null
            ? null
            : $checked->copy()->addDays((int) $interval);
    }

    /**
     * How readers rated it, 0–100, or null when nobody has voted.
     *
     * Null and zero are different answers and the UI must not merge them:
     * "nobody said" is not "everybody said no".
     */
    public function helpfulness(): ?int
    {
        $total = $this->helpful_count + $this->unhelpful_count;

        return $total === 0
            ? null
            : (int) round($this->helpful_count / $total * 100);
    }

    /**
     * Read a lot and rated badly — the maintenance backlog, in order.
     *
     * Views alone say a page is findable; the score says it works. Only the
     * two together point at the page worth fixing first.
     *
     * Porovnávají se hlasy, ne zaokrouhlené procento: `helpfulness()` je číslo
     * na displej a stránka se 49,7 % se po zaokrouhlení tváří jako padesát.
     * {@see scopeNeedsAttention()} vyslovuje tutéž podmínku v SQL.
     */
    public function needsAttention(): bool
    {
        return $this->unhelpful_count > $this->helpful_count
            && $this->views_count >= self::ATTENTION_MIN_VIEWS;
    }

    /**
     * Odkdy článek nikdo nepotvrdil, nebo null, když ho nepotvrdil nikdy.
     *
     * Bez `updated_at`, který {@see isStale()} bere jako poslední záchranu:
     * editace není ověření a v odznaku by tvrdila, že se článek kontroloval.
     */
    public function uncheckedSince(): ?Carbon
    {
        return $this->reviewed_at ?? $this->published_at;
    }

    /**
     * Proč článek leží ve frontě údržby — prázdné pole znamená, že neleží.
     *
     * Čte tytéž hodiny jako {@see scopeStale()}, tedy společnou lhůtu, ne
     * vlastní lhůtu článku, kterou ctí {@see isStale()} pro čtenáře. Frontu
     * vybírá dotaz a tohle jen vysvětluje řádek, který dotaz vrátil — kdyby
     * si každý počítal po svém, seznam by uměl ukázat článek bez důvodu.
     *
     * Zastaralost se u konceptu nehlásí: nedopsaná stránka nehnije, chybí.
     *
     * @return list<string>
     */
    public function attentionReasons(): array
    {
        $reasons = [];

        if ($this->status === ArticleStatus::Draft) {
            $reasons[] = self::REASON_DRAFT;
        }

        if ($this->isLive() && self::isPastReviewClock($this->uncheckedSince() ?? $this->updated_at)) {
            $reasons[] = self::REASON_STALE;
        }

        if ($this->needsAttention()) {
            $reasons[] = self::REASON_UNHELPFUL;
        }

        return $reasons;
    }

    /** Je tenhle okamžik za společnou ověřovací lhůtou? Bez hodin nikdy. */
    protected static function isPastReviewClock(?Carbon $checked): bool
    {
        $interval = (int) (Settings::nullableInt('authoring.review_interval_days') ?? 0);

        return $interval > 0
            && ($checked === null || $checked->copy()->addDays($interval)->isPast());
    }

    /** Rough reading time in minutes, never zero — "0 min" reads as broken. */
    public function readingMinutes(): int
    {
        $words = str_word_count(strip_tags((string) $this->body_html));

        return max(1, (int) ceil($words / 200));
    }

    // --- Scopes --------------------------------------------------------------

    /** @param  Builder<self>  $query */
    public function scopeLive(Builder $query): void
    {
        $query->where('status', ArticleStatus::Published->value);
    }

    /** @param  Builder<self>  $query */
    public function scopePublic(Builder $query): void
    {
        $query->live()->where('visibility', Visibility::Public->value);
    }

    /** @param  Builder<self>  $query */
    public function scopeOfKind(Builder $query, ArticleKind $kind): void
    {
        $query->where('kind', $kind->value);
    }

    /**
     * Reading order: by the reader's likely intent, then by hand.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('title');
    }

    /**
     * Past its review date — the list whoever maintains the base works from.
     *
     * Expressed in SQL rather than by filtering {@see isStale()} in PHP: the
     * stale list is a page of its own and paginating a filtered collection
     * means loading every article to show ten.
     *
     * @param  Builder<self>  $query
     */
    public function scopeStale(Builder $query): void
    {
        $interval = (int) (Settings::nullableInt('authoring.review_interval_days') ?? 0);

        if ($interval <= 0) {
            // No clock configured: nothing is stale, and an unfiltered query
            // would claim everything is.
            $query->whereRaw('1 = 0');

            return;
        }

        $cutoff = now()->subDays($interval);

        // Stejná trojice jako v isStale(): čerstvě vydaná stránka, kterou
        // ještě nikdo nepotvrdil, není zastaralá — jen nová. Hledat prázdné
        // `reviewed_at` by ji do fronty poslalo v den vydání.
        $query->live()->whereRaw(
            'coalesce(reviewed_at, published_at, updated_at) <= ?',
            [$cutoff]
        );
    }

    /**
     * Read a lot and rated badly, in SQL.
     *
     * Totéž pravidlo jako {@see needsAttention()}, jen vyslovené v dotazu:
     * fronta údržby je stránkovaná, takže filtrovat v PHP by znamenalo načíst
     * celou bázi kvůli dvaceti řádkům.
     *
     * @param  Builder<self>  $query
     */
    public function scopeNeedsAttention(Builder $query): void
    {
        $query
            ->whereColumn('unhelpful_count', '>', 'helpful_count')
            ->where('views_count', '>=', self::ATTENTION_MIN_VIEWS);
    }
}
