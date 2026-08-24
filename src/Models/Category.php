<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use NyonCode\KnowledgeBase\Database\Factories\CategoryFactory;
use NyonCode\KnowledgeBase\Enums\Visibility;

/**
 * A collection of articles.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property Visibility $visibility
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'visibility' => Visibility::class,
        'sort_order' => 'integer',
    ];

    public function getTable(): string
    {
        return config('knowledge-base.tables.categories', 'kb_categories');
    }

    protected static function booted(): void
    {
        // The slug is the address, so it is filled once and then left alone:
        // regenerating it on every rename would break every link ever shared.
        static::creating(function (self $category) {
            if (blank($category->slug)) {
                $category->slug = Str::slug((string) $category->name);
            }
        });
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'category_id')->orderBy('sort_order');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @param  Builder<self>  $query */
    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /** @param  Builder<self>  $query */
    public function scopePublic(Builder $query): void
    {
        $query->where('visibility', Visibility::Public->value);
    }

    /** @param  Builder<self>  $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }
}
