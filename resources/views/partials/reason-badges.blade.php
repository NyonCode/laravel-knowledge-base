{{-- Proč článek leží ve frontě údržby.

     Důvod patří do řádku, ne jen do výběru nad tabulkou: ten řekne, co je
     vybrané, ne čím se provinil zrovna tenhle článek. Dřív se musel dekódovat
     z barvy ve dvou sloupcích — a koncept, který do fronty patří taky, neměl
     barvu žádnou. Odznak nese i míru, ne jen jméno: „22 % · 41 hlasů" říká,
     jestli to hoří, kdežto „špatně hodnocené" jen že se to stalo. --}}
@php
    $accents = [
        \NyonCode\KnowledgeBase\Models\Article::REASON_DRAFT => 'bg-zinc-100 text-zinc-700 ring-zinc-500/20 dark:bg-zinc-500/10 dark:text-zinc-300 dark:ring-zinc-400/20',
        \NyonCode\KnowledgeBase\Models\Article::REASON_STALE => 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-400/20',
        \NyonCode\KnowledgeBase\Models\Article::REASON_UNHELPFUL => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-400/20',
    ];

    $since = $article->uncheckedSince();
    $votes = $article->helpful_count + $article->unhelpful_count;

    $labels = [
        \NyonCode\KnowledgeBase\Models\Article::REASON_DRAFT => __('knowledge-base::kb.admin.reason_draft'),
        \NyonCode\KnowledgeBase\Models\Article::REASON_STALE => $since === null
            ? __('knowledge-base::kb.admin.reason_stale_never')
            : __('knowledge-base::kb.admin.reason_stale', [
                'since' => $since->diffForHumans([
                    'parts' => 1,
                    'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
                ]),
            ]),
        \NyonCode\KnowledgeBase\Models\Article::REASON_UNHELPFUL => trans_choice(
            'knowledge-base::kb.admin.reason_unhelpful',
            $votes,
            ['score' => (string) $article->helpfulness(), 'count' => (string) $votes],
        ),
    ];

    $reasons = $article->attentionReasons();
@endphp
@if ($reasons === [])
    <span class="text-zinc-400">—</span>
@else
    <div class="flex flex-wrap items-center gap-1.5">
        @foreach ($reasons as $reason)
            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $accents[$reason] }}">
                {{ $labels[$reason] }}
            </span>
        @endforeach
    </div>
@endif
