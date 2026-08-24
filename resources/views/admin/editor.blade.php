{{--
    Write / Preview, and a sidebar where the two publishing decisions live
    apart from each other. See ArticleEditor for why they are never one button.
--}}
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8" x-data>
    @include('knowledge-base::editors.image-picker')

    <form wire:submit="save">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <input
                type="text"
                wire:model.blur="title"
                placeholder="{{ __('knowledge-base::kb.admin.new') }}"
                class="min-w-0 flex-1 border-0 bg-transparent p-0 text-2xl font-semibold tracking-tight text-zinc-900 placeholder:text-zinc-300 focus:ring-0 dark:text-white"
            />
            <div class="flex items-center gap-2">
                @if ($article?->exists)
                    <button type="button" wire:click="markReviewed" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                        {{ __('knowledge-base::kb.admin.mark_reviewed') }}
                    </button>
                @endif
                <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-1.5 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
                    {{ __('knowledge-base::kb.admin.save') }}
                </button>
            </div>
        </div>
        @error('title') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror

        <div class="mt-6 lg:grid lg:grid-cols-[1fr_20rem] lg:gap-8">
            <div class="min-w-0">
                <div class="mb-2 flex gap-1 rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                    @foreach ([false => __('knowledge-base::kb.admin.write'), true => __('knowledge-base::kb.admin.preview')] as $value => $label)
                        <button
                            type="button"
                            wire:click="$set('preview', {{ $value ? 'true' : 'false' }})"
                            @class([
                                'flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition',
                                'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-white' => $preview === (bool) $value,
                                'text-zinc-500' => $preview !== (bool) $value,
                            ])
                        >{{ $label }}</button>
                    @endforeach
                </div>

                @if ($preview)
                    <div class="kb-prose prose prose-zinc min-h-[24rem] max-w-none rounded-xl border border-zinc-200 bg-white p-6 dark:prose-invert dark:border-zinc-800 dark:bg-zinc-900">
                        {!! $rendered !!}
                    </div>
                @else
                    {{-- Plochu vlastní ovladač: markdown, TipTap nebo bloky,
                         podle toho, co instalace nabízí a čím byl článek psaný.

                         `wire:key` s názvem ovladače je **nutný**, ne kosmetika:
                         rich-text plocha má uvnitř `wire:ignore` (jinak by morph
                         přepsal DOM, který drží ProseMirror, a kurzor by skákal),
                         jenže pak ji Livewire při přepnutí editoru nevymění a
                         zůstane viset místo nové plochy. Změněný klíč ten uzel
                         zahodí celý. --}}
                    <div wire:key="kb-surface-{{ $driver->name() }}">
                        @include($driver->view(), ['statePath' => 'body', 'article' => $article])
                    </div>
                @endif
                @error('body') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <aside class="mt-6 space-y-5 lg:mt-0">
                <div>
                    <label for="kb-slug" class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">URL</label>
                    <input id="kb-slug" type="text" wire:model="slug" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-1.5 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" />
                    @error('slug') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    @if ($article?->exists)
                        {{-- Said out loud, because the field looks editable and
                             changing it silently breaks every shared link. --}}
                        <p class="mt-1 text-xs text-zinc-500">{{ __('knowledge-base::kb.admin.slug_warning') }}</p>
                    @endif
                </div>

                <div>
                    <label for="kb-excerpt" class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">
                        {{ __('knowledge-base::kb.admin.excerpt') }}
                    </label>
                    <textarea id="kb-excerpt" rows="3" wire:model="excerpt" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('knowledge-base::kb.admin.excerpt_hint') }}</p>
                </div>

                @if ($editors->count() > 1 && ! $article?->exists)
                    {{-- Offered only while the article is new. Reopening a page
                         in a different editor would reinterpret its body. --}}
                    <div>
                        <label for="kb-editor" class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">Editor</label>
                        <select id="kb-editor" wire:model.live="editor" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            @foreach ($editors as $option)
                                <option value="{{ $option->name() }}">{{ $option->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Kind first: it is the decision that shapes how the page gets
                     written, so it belongs before the reader ever sees it. --}}
                <fieldset>
                    <legend class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Diátaxis</legend>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @foreach ($kinds as $option)
                            <button
                                type="button"
                                wire:click="$set('kind', '{{ $option->value }}')"
                                @class([
                                    'rounded-lg border px-3 py-2 text-left text-sm transition',
                                    'border-sky-500 bg-sky-50 dark:bg-sky-500/10' => $kind === $option->value,
                                    'border-zinc-200 hover:border-zinc-300 dark:border-zinc-800' => $kind !== $option->value,
                                ])
                            >
                                <span class="block font-medium text-zinc-900 dark:text-zinc-100">{{ $option->label() }}</span>
                            </button>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-zinc-500">
                        {{ \NyonCode\KnowledgeBase\Enums\ArticleKind::from($kind)->promise() }}
                    </p>
                </fieldset>

                <div>
                    <label for="kb-category" class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('knowledge-base::kb.admin.category') }}</label>
                    <select id="kb-category" wire:model="categoryId" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <option value="">—</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- The two halves of publishing, side by side and separately
                     labelled: status is our readiness, visibility is whose eyes. --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="kb-status" class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('knowledge-base::kb.admin.status') }}</label>
                        <select id="kb-status" wire:model="status" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            @foreach ($statuses as $option)
                                <option value="{{ $option->value }}">{{ $option->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="kb-visibility" class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('knowledge-base::kb.admin.visibility_label') }}</label>
                        <select id="kb-visibility" wire:model="visibility" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            @foreach ($visibilities as $option)
                                <option value="{{ $option->value }}">{{ $option->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="kb-note" class="block text-xs font-semibold uppercase tracking-wide text-zinc-500">
                        {{ __('knowledge-base::kb.admin.note') }}
                    </label>
                    <input id="kb-note" type="text" wire:model="note" placeholder="{{ __('knowledge-base::kb.admin.note_placeholder') }}" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" />
                </div>
            </aside>
        </div>
    </form>
</div>
