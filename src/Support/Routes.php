<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Support;

use Illuminate\Support\Facades\Route;
use NyonCode\KnowledgeBase\Models\Article;
use NyonCode\KnowledgeBase\Models\Category;

/**
 * Where the package's own links point.
 *
 * Views must not build route names themselves. The reading routes are mounted
 * by the package but can be turned off and re-declared by the host, and the
 * **writing** routes are always the host's — it decides that articles are
 * edited at `/admin/knowledge`, not us. Hard-coded names are how a published
 * view breaks the moment somebody mounts the base somewhere else.
 *
 * Every lookup degrades to `#` rather than throwing: a missing admin route
 * should render a dead link on one button, not a 500 on the whole page.
 */
final class Routes
{
    public static function home(): string
    {
        return self::to(self::readName('home'));
    }

    public static function category(Category $category): string
    {
        return self::to(self::readName('category'), $category);
    }

    public static function article(Article $article): string
    {
        return self::to(self::readName('article'), ['slug' => $article->slug]);
    }

    public static function adminIndex(): string
    {
        return self::to(Settings::string('routes.admin.index'));
    }

    public static function adminCategories(): string
    {
        return self::to(Settings::string('routes.admin.categories'));
    }

    public static function adminEdit(?Article $article = null): string
    {
        return $article === null
            ? self::to(Settings::string('routes.admin.create'))
            : self::to(Settings::string('routes.admin.edit'), $article);
    }

    private static function readName(string $suffix): string
    {
        return Settings::string('routes.name', 'knowledge.').$suffix;
    }

    private static function to(string $name, mixed $parameters = []): string
    {
        return $name !== '' && Route::has($name)
            ? route($name, $parameters)
            : '#';
    }
}
