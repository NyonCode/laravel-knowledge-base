{{-- Řádek se rozbalí na místě; druhá obrazovka kvůli pěti polím je klik navíc
     pokaždé, když se něco přejmenovává. --}}
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                {{ __('knowledge-base::kb.admin.categories') }}
            </h1>
            <a href="{{ \NyonCode\KnowledgeBase\Support\Routes::adminIndex() }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200">
                &larr; {{ __('knowledge-base::kb.admin.all') }}
            </a>
        </div>
        <button type="button" wire:click="create" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
            {{ __('knowledge-base::kb.admin.new_category') }}
        </button>
    </div>

    @if ($editing !== null)
        <form wire:submit="save" class="mt-6 grid gap-4 rounded-xl border border-zinc-200 bg-zinc-50 p-5 sm:grid-cols-2 dark:border-zinc-800 dark:bg-zinc-900/50">
            <div>
                <label for="kbc-name" class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('knowledge-base::kb.admin.category_name') }}</label>
                <input id="kbc-name" type="text" wire:model="name" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" />
                @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="kbc-slug" class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">URL</label>
                <input id="kbc-slug" type="text" wire:model="slug" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-1.5 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" />
                @error('slug') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="kbc-desc" class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('knowledge-base::kb.home.lead') }}</label>
                <input id="kbc-desc" type="text" wire:model="description" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" />
            </div>
            <div>
                <label for="kbc-vis" class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('knowledge-base::kb.visibility.public') }}</label>
                <select id="kbc-vis" wire:model="visibility" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    @foreach ($visibilities as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="kbc-order" class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('knowledge-base::kb.admin.order') }}</label>
                <input id="kbc-order" type="number" wire:model="sortOrder" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" />
            </div>
            <div class="flex items-center gap-2 sm:col-span-2">
                <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-1.5 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
                    {{ __('knowledge-base::kb.admin.save') }}
                </button>
                <button type="button" wire:click="cancel" class="text-sm text-zinc-500 hover:underline">
                    {{ __('knowledge-base::kb.admin.cancel') }}
                </button>
            </div>
        </form>
    @endif

    <div class="mt-6 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-950">
                @forelse ($categories as $category)
                    <tr wire:key="kbc-{{ $category->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-900">
                        <td class="px-4 py-3">
                            <button type="button" wire:click="edit({{ $category->id }})" class="font-medium text-zinc-900 hover:text-sky-700 dark:text-zinc-100">
                                {{ $category->name }}
                            </button>
                            <div class="text-xs text-zinc-500">{{ $category->description }}</div>
                        </td>
                        <td class="px-4 py-3 text-zinc-500">{{ $category->visibility->label() }}</td>
                        <td class="px-4 py-3 text-zinc-500">
                            {{ trans_choice('knowledge-base::kb.admin.article_count', $category->articles_count, ['count' => $category->articles_count]) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{-- Články se nemažou, jen osiří – proto je potvrzení
                                 věcné a ne strašidelné. --}}
                            <button
                                type="button"
                                wire:click="delete({{ $category->id }})"
                                wire:confirm="{{ __('knowledge-base::kb.admin.delete_category_confirm') }}"
                                class="text-zinc-400 transition hover:text-rose-600"
                                aria-label="{{ __('knowledge-base::kb.admin.delete') }}"
                            >✕</button>
                        </td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-16 text-center text-zinc-500">{{ __('knowledge-base::kb.home.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
