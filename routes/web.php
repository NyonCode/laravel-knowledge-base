<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use NyonCode\KnowledgeBase\Livewire\ArticlePage;
use NyonCode\KnowledgeBase\Livewire\CategoryPage;
use NyonCode\KnowledgeBase\Livewire\KnowledgeHome;

/*
|--------------------------------------------------------------------------
| Knowledge base
|--------------------------------------------------------------------------
|
| Three routes and no more: a search-first landing page, a category, an
| article. Deeper URLs (a category segment in front of the article slug) were
| deliberately not taken — an article that moves between categories would
| change address, and the whole point of a knowledge base is that its links
| keep working in somebody else's bookmark for years.
|
| Turn these off with `knowledge-base.routes.enabled` and mount the same
| components yourself if the URLs need to look different.
|
*/

if (! config('knowledge-base.routes.enabled', true)) {
    return;
}

Route::middleware(config('knowledge-base.routes.middleware', ['web']))
    ->prefix((string) config('knowledge-base.routes.prefix', 'napoveda'))
    ->name((string) config('knowledge-base.routes.name', 'knowledge.'))
    ->group(function (): void {
        Route::get('/', KnowledgeHome::class)->name('home');
        Route::get('/kategorie/{category:slug}', CategoryPage::class)->name('category');
        Route::get('/{article:slug}', ArticlePage::class)->name('article');
    });
