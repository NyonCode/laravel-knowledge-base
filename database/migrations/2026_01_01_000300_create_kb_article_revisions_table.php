<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every saved version of an article.
 *
 * The editorial layer of a knowledge base is not a nice-to-have: the sentence
 * somebody deleted last Tuesday is usually the one that mattered, and without
 * history the only recovery is memory. Stores the markdown, never the render —
 * the renderer changes, the source does not.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('knowledge-base.tables.revisions'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained(
                config('knowledge-base.tables.articles')
            )->cascadeOnDelete();

            $table->foreignId('author_id')->nullable();
            $table->string('title');
            $table->longText('body')->nullable();

            /** Why it changed — the one thing a diff cannot tell you. */
            $table->string('note')->nullable();

            $table->timestamps();

            $table->index(['article_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('knowledge-base.tables.revisions'));
    }
};
