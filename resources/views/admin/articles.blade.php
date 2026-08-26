{{-- Celá báze, s rotem sečteným nahoře. Viz ArticleList. --}}
@php
    use NyonCode\KnowledgeBase\Enums\ArticleStatus;
    use NyonCode\KnowledgeBase\Models\Article;
    use NyonCode\KnowledgeBase\Support\Routes;

    // Čip pojmenovává celou hromadu, odznak v řádku jeden konkrétní článek —
    // proto „Dlouho neověřené" nahoře a „Neověřeno 14 měsíců" dole.
    $chipLabels = [
        Article::REASON_DRAFT => __('knowledge-base::kb.status.draft'),
        Article::REASON_STALE => __('knowledge-base::kb.admin.stale'),
        Article::REASON_UNHELPFUL => __('knowledge-base::kb.admin.unhelpful'),
    ];
@endphp
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                {{ __('knowledge-base::kb.admin.all') }}
            </h1>
            {{-- Stav celé báze, ne toho, co je zrovna vyfiltrované: poplach se
                 filtrem nesmí ztišit. --}}
            <p class="mt-1 text-sm text-zinc-500">
                {{ trans_choice('knowledge-base::kb.admin.article_count', $total) }}
                ·
                <span @class(['font-medium text-amber-700 dark:text-amber-400' => $counts['queue'] > 0])>
                    {{ trans_choice('knowledge-base::kb.admin.summary_attention', $counts['queue']) }}
                </span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ Routes::adminCategories() }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                {{ __('knowledge-base::kb.admin.categories') }}
            </a>
            <a href="{{ Routes::adminEdit() }}" wire:navigate class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
                {{ __('knowledge-base::kb.admin.new') }}
            </a>
        </div>
    </div>

    {{-- Potvrzení z editoru. Sám zmizí: je to odpověď na uložení, ne stav
         báze — a po chvíli už jen zabírá místo nad seznamem. --}}
    @if ($saved !== '')
        <div
            x-data="{ shown: true }"
            x-init="setTimeout(() => shown = false, 5000)"
            x-show="shown"
            x-transition.opacity
            class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
        >
            {{ __('knowledge-base::kb.admin.saved') }} — <span class="font-medium">{{ $saved }}</span>
        </div>
    @endif

    {{-- Dvě řady, každá s jednou úlohou: co je špatně, a jak seznam zúžit.
         Čipy jsou důvody, ne pohledy — nula je taky odpověď, proto se čip s ní
         vykreslí a zešedne, a klik na aktivní čip filtr zruší. --}}
    <div class="mt-6 flex flex-wrap items-center gap-2">
        @foreach ($counts['reasons'] as $key => $count)
            @php $active = $reason === $key; @endphp
            <button
                type="button"
                wire:click="filterByReason('{{ $key }}')"
                @disabled($count === 0 && ! $active)
                aria-pressed="{{ $active ? 'true' : 'false' }}"
                title="{{ $active ? __('knowledge-base::kb.admin.clear_reason') : '' }}"
                @class([
                    'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium transition',
                    'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $active,
                    'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300' => ! $active && $count > 0,
                    'bg-zinc-50 text-zinc-400 dark:bg-zinc-900 dark:text-zinc-600' => ! $active && $count === 0,
                ])
            >
                {{ $chipLabels[$key] }}
                <span @class([
                    'rounded px-1.5 py-0.5 text-xs tabular-nums',
                    'bg-white/20 dark:bg-zinc-900/10' => $active,
                    'bg-white dark:bg-zinc-950/40' => ! $active,
                ])>{{ $count }}</span>
                @if ($active)
                    <span aria-hidden="true" class="text-xs opacity-70">✕</span>
                @endif
            </button>
        @endforeach
    </div>

    <div class="mt-2 flex flex-wrap items-center gap-2">
        <select wire:model.live="status" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <option value="">{{ __('knowledge-base::kb.admin.filter_status') }}</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="category" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <option value="">{{ __('knowledge-base::kb.admin.filter_category') }}</option>
            @foreach ($categories as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>

        {{-- Hledá se vždycky přes celou bázi. Kdyby se hledalo uvnitř výběru,
             zdravý článek by se nenašel a nikde by nestálo proč. --}}
        <input
            type="search"
            wire:model.live.debounce.300ms="term"
            placeholder="{{ __('knowledge-base::kb.search.open') }}…"
            class="ml-auto rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
        />
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
            <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2.5 font-medium">{{ __('knowledge-base::kb.admin.column_article') }}</th>
                    <th class="px-4 py-2.5 font-medium">{{ __('knowledge-base::kb.admin.column_reason') }}</th>
                    <th class="px-4 py-2.5 font-medium">{{ __('knowledge-base::kb.admin.column_reviewed') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-950">
                @forelse ($articles as $article)
                    <tr wire:key="kb-{{ $article->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-900">
                        <td class="px-4 py-3">
                            <a href="{{ Routes::adminEdit($article) }}" wire:navigate class="font-medium text-zinc-900 hover:text-sky-700 dark:text-zinc-100">
                                {{ $article->title }}
                            </a>
                            <div class="mt-0.5 flex items-center gap-2 text-xs text-zinc-500">
                                @include('knowledge-base::partials.kind-badge', ['kind' => $article->kind])
                                <span>{{ $article->category?->name }}</span>
                                <span>{{ $article->visibility->label() }}</span>
                                {{-- Vydaný článek je norma a koncept už hlásí odznak
                                     důvodu. Zbývají stavy, které by jinak nebyly
                                     vidět nikde. --}}
                                @unless (in_array($article->status, [ArticleStatus::Published, ArticleStatus::Draft], true))
                                    <span>{{ $article->status->label() }}</span>
                                @endunless
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @include('knowledge-base::partials.reason-badges', ['article' => $article])
                        </td>
                        <td class="px-4 py-3 text-zinc-500">
                            {{ $article->reviewed_at?->isoFormat('D. M. YYYY') ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-16 text-center text-zinc-500">
                            {{ $reason === ''
                                ? __('knowledge-base::kb.home.empty')
                                : __('knowledge-base::kb.admin.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $articles->links() }}</div>
</div>
