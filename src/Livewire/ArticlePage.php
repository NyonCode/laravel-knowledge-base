<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use NyonCode\KnowledgeBase\Models\Article;
use NyonCode\KnowledgeBase\Services\KnowledgeBase;
use NyonCode\KnowledgeBase\Services\RendererRegistry;

/**
 * The page people actually came for.
 *
 * Carries the two things that keep a knowledge base honest: a visible "last
 * checked" date, so a reader can judge how much to trust it, and the "did
 * this help?" vote, which is the only signal that separates a page people
 * find from a page that works.
 */
class ArticlePage extends Component
{
    public Article $article;

    /** Set once the reader has voted, so the widget can thank them instead of asking again. */
    public ?bool $vote = null;

    public string $feedbackComment = '';

    public bool $askingWhy = false;

    public function mount(string $article, KnowledgeBase $kb): void
    {
        $found = $kb->article($article, auth()->user());

        // 404, never 403: telling an anonymous visitor that a slug exists but
        // is forbidden leaks exactly the thing that was worth hiding.
        abort_if($found === null, 404);

        $this->article = $found;

        $kb->countView($found);
    }

    public function helpful(KnowledgeBase $kb): void
    {
        $this->vote = true;
        $kb->recordFeedback($this->article, true, reader: auth()->user());
    }

    public function unhelpful(): void
    {
        $this->vote = false;

        // The bare "no" is recorded straight away — a reader who abandons the
        // comment box has still told us something, and losing that vote would
        // bias the score towards the pages people bother to complain about.
        app(KnowledgeBase::class)->recordFeedback(
            $this->article,
            false,
            reader: auth()->user()
        );

        $this->askingWhy = (bool) config('knowledge-base.feedback.ask_why', true);
    }

    public function sendComment(KnowledgeBase $kb): void
    {
        $this->validate([
            'feedbackComment' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $kb->recordFeedback(
            $this->article,
            false,
            $this->feedbackComment,
            auth()->user()
        );

        $this->askingWhy = false;
        $this->feedbackComment = '';
    }

    public function render(KnowledgeBase $kb, RendererRegistry $renderers): View
    {
        return view('knowledge-base::public.article', [
            // Asked of the renderer that matches this article's format: the
            // three read headings the same way today, but a fourth need not.
            'toc' => $renderers
                ->for($this->article->format)
                ->tableOfContents((string) $this->article->body_html),
            'related' => $kb->related($this->article, auth()->user()),
        ])->title($this->article->title);
    }
}
