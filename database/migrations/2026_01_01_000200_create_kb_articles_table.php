<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The article itself.
 *
 * `body` is markdown (the source of truth, editable and diffable) and
 * `body_html` is its render, cached because a knowledge base is read orders
 * of magnitude more often than it is written. Rendering on read would put a
 * markdown parse plus a sanitiser pass in front of every page view.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('knowledge-base.tables.articles'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained(
                config('knowledge-base.tables.categories')
            )->nullOnDelete();

            // Slug is the permanent address. Renaming a title must not break
            // links, so the two are separate fields from the first migration.
            $table->string('slug')->unique();
            $table->string('title');

            // Written, not derived: the first paragraph of a reference page is
            // a terrible summary, and this is what search results show.
            $table->string('excerpt', 500)->nullable();

            $table->longText('body')->nullable();
            $table->longText('body_html')->nullable();

            // Which editor wrote it, and therefore which renderer can read it.
            // Per article, never global: switching the default editor must not
            // reinterpret everything written before the switch.
            $table->string('format', 16)->default('markdown');

            $table->string('kind', 24)->default('how-to');
            $table->string('status', 16)->default('draft');
            $table->string('visibility', 16)->default('internal');

            $table->foreignId('author_id')->nullable();
            $table->timestamp('published_at')->nullable();

            // Freshness, the half of a knowledge base that rots silently.
            // A page nobody has confirmed in a year is a liability, so the
            // review clock is a column and not a convention.
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedSmallInteger('review_interval_days')->nullable();

            // Denormalised counters. A page view must not write to a second
            // table just to keep a number that is only ever read in aggregate.
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('unhelpful_count')->default(0);

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // The listing query: everything live in one category, in order.
            $table->index(['category_id', 'status', 'visibility', 'sort_order']);
            $table->index(['status', 'visibility', 'published_at']);
            $table->index('kind');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('knowledge-base.tables.articles'));
    }
};
