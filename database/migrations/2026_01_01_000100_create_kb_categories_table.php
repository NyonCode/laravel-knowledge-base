<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Collections of articles.
 *
 * Self-nesting, but the UI renders two levels: a knowledge base whose tree is
 * deeper than that has stopped being navigable and its readers use search
 * anyway. The column allows more so nobody has to migrate to reorganise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('knowledge-base.tables.categories'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained(
                config('knowledge-base.tables.categories')
            )->nullOnDelete();

            $table->string('slug')->unique();
            $table->string('name');
            $table->string('description')->nullable();

            // Icon and colour are the category's identity in a grid of cards —
            // people navigate by shape long before they read the label.
            $table->string('icon')->nullable();
            $table->string('color')->nullable();

            // A category is only as visible as its least private article is
            // allowed to be; this is the ceiling, checked before the article's.
            $table->string('visibility', 16)->default('internal');

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['parent_id', 'sort_order']);
            $table->index('visibility');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('knowledge-base.tables.categories'));
    }
};
