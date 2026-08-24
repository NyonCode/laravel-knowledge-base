<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Providers;

use Livewire\Livewire;
use NyonCode\KnowledgeBase\Contracts\ArticleSearch;
use NyonCode\KnowledgeBase\Contracts\KnowledgeAudience;
use NyonCode\KnowledgeBase\Contracts\MarkdownRenderer;
use NyonCode\KnowledgeBase\Livewire\Admin\ArticleEditor;
use NyonCode\KnowledgeBase\Livewire\Admin\ArticleList;
use NyonCode\KnowledgeBase\Livewire\ArticlePage;
use NyonCode\KnowledgeBase\Livewire\CategoryPage;
use NyonCode\KnowledgeBase\Livewire\KnowledgeHome;
use NyonCode\KnowledgeBase\Livewire\SearchPalette;
use NyonCode\KnowledgeBase\Services\CommonMarkRenderer;
use NyonCode\KnowledgeBase\Services\DatabaseArticleSearch;
use NyonCode\KnowledgeBase\Services\EditorRegistry;
use NyonCode\KnowledgeBase\Services\GateKnowledgeAudience;
use NyonCode\KnowledgeBase\Services\KnowledgeBase;
use NyonCode\KnowledgeBase\Services\RendererRegistry;
use NyonCode\KnowledgeBase\Services\Renderers\BlockRenderer;
use NyonCode\KnowledgeBase\Services\Renderers\RichTextRenderer;
use NyonCode\LaravelPackageToolkit\Contracts\Packable;
use NyonCode\LaravelPackageToolkit\Packager;
use NyonCode\LaravelPackageToolkit\PackageServiceProvider;

/**
 * Package registration.
 *
 * Everything swappable is bound to a contract with a shipped default, so a
 * host app can replace the audience, the search driver or the renderer
 * without forking anything. Livewire components register only when Livewire
 * is installed — the package must still boot in an app that renders the base
 * from plain controllers.
 */
final class KnowledgeBaseServiceProvider extends PackageServiceProvider implements Packable
{
    public function configure(Packager $packager): void
    {
        $packager
            ->name('knowledge-base')
            ->hasConfig()
            ->hasMigrations()
            ->hasViews()
            ->hasTranslations()
            ->hasRoutes()
            ->hasComponentNamespace('kb', 'NyonCode\\KnowledgeBase\\View\\Components')
            ->hasAbout()
            ->registeredPackage(function (): void {
                $this->app->singleton(MarkdownRenderer::class, CommonMarkRenderer::class);
                $this->app->singleton(KnowledgeAudience::class, GateKnowledgeAudience::class);
                $this->app->singleton(ArticleSearch::class, DatabaseArticleSearch::class);

                // One renderer per format. Registered last wins, so a host that
                // binds its own markdown renderer (to run a highlighter, say)
                // simply registers after these.
                $this->app->singleton(RendererRegistry::class, fn ($app) => new RendererRegistry([
                    $app->make(CommonMarkRenderer::class),
                    $app->make(RichTextRenderer::class),
                    $app->make(BlockRenderer::class),
                ]));

                $this->app->singleton(EditorRegistry::class, fn ($app) => new EditorRegistry(
                    collect((array) config('knowledge-base.editors.drivers', []))
                        ->map(fn (string $driver) => $app->make($driver))
                        ->all()
                ));

                $this->app->singleton(KnowledgeBase::class);
                $this->app->alias(KnowledgeBase::class, 'knowledge-base');
            })
            ->bootedPackage(function (): void {
                $this->registerLivewireComponents();
            });
    }

    /**
     * Names are prefixed `kb.` so they cannot collide with the host's own
     * components, and they are what a host overrides when it wants a different
     * article page: register your class under the same name last and it wins.
     */
    private function registerLivewireComponents(): void
    {
        if (! class_exists(Livewire::class)) {
            return;
        }

        foreach ([
            'kb.home' => KnowledgeHome::class,
            'kb.search' => SearchPalette::class,
            'kb.category' => CategoryPage::class,
            'kb.article' => ArticlePage::class,
            'kb.admin.articles' => ArticleList::class,
            'kb.admin.editor' => ArticleEditor::class,
        ] as $name => $class) {
            Livewire::component($name, $class);
        }
    }
}
