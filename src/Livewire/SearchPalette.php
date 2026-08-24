<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use NyonCode\KnowledgeBase\Services\KnowledgeBase;

/**
 * Search from anywhere — the ⌘K palette.
 *
 * Drop `<livewire:kb.search />` in a layout and the whole base is one
 * keystroke away from every page of the host app. That is the difference
 * between a knowledge base people use and one they remember exists.
 *
 * Results are fetched on a debounce rather than per keystroke: a knowledge
 * base is read from phones on bad connections, and a request per character
 * makes the field feel slower than it is.
 */
class SearchPalette extends Component
{
    public bool $open = false;

    public string $term = '';

    public function updatedOpen(bool $value): void
    {
        if (! $value) {
            $this->term = '';
        }
    }

    public function render(KnowledgeBase $kb): View
    {
        return view('knowledge-base::public.search-palette', [
            'results' => trim($this->term) === ''
                ? collect()
                : $kb->find($this->term, auth()->user()),
        ]);
    }
}
