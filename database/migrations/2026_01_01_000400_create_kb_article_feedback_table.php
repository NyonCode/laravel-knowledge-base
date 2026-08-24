<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Did this help?" — the only signal that tells you which pages are lying.
 *
 * Traffic says a page is found; this says it worked. The two together are the
 * whole maintenance backlog: high views plus a poor score is the page to fix
 * first, and no other metric surfaces it.
 *
 * `reader_hash` is a salted hash of session and address, never the address
 * itself — enough to stop one person voting fifty times, not enough to be
 * personal data worth keeping.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('knowledge-base.tables.feedback'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained(
                config('knowledge-base.tables.articles')
            )->cascadeOnDelete();

            $table->foreignId('user_id')->nullable();
            $table->boolean('helpful');

            /** Optional: what was missing. This is where the next article comes from. */
            $table->text('comment')->nullable();

            $table->string('reader_hash', 64)->nullable();
            $table->timestamps();

            // One vote per reader per article — the unique index is the rule,
            // not a check in PHP that two tabs can race past.
            $table->unique(['article_id', 'reader_hash']);
            $table->index(['article_id', 'helpful']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('knowledge-base.tables.feedback'));
    }
};
