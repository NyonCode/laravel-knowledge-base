{{--
    Výběr obrázku: přetáhnout, kliknout, nebo vybrat z toho, co už je nahrané.

    Tři cesty schválně — každá je pro jiný okamžik. Přetažení když je soubor
    po ruce, klik když se hledá v adresáři, galerie když se týž obrázek dává
    do třetího návodu.

    Jedna modálka pro TipTap i pro bloky. Dvě skoro stejné by se rozešly při
    první úpravě jedné z nich.
--}}
@if ($imagePicker)
    <div
        class="fixed inset-0 z-50 flex items-start justify-center bg-zinc-900/40 p-4 pt-[10vh] backdrop-blur-sm"
        x-on:click.self="$wire.closeImagePicker()"
        x-on:keydown.escape.window="$wire.closeImagePicker()"
        role="dialog"
        aria-modal="true"
        data-testid="kb-image-picker"
    >
        <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3 dark:border-zinc-800">
                <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('knowledge-base::kb.editor.image_title') }}</h2>
                <button type="button" wire:click="closeImagePicker" class="rounded p-1 text-zinc-400 transition hover:text-zinc-700 dark:hover:text-zinc-200" aria-label="{{ __('knowledge-base::kb.admin.cancel') }}">&times;</button>
            </div>

            <div class="p-5">
                {{-- Dropzone. Přetažené soubory se prostrčí do `<input>`, ne do
                     vlastního uploadu: tím je nahrávání pořád Livewiru a
                     nevzniká druhá cesta, která se chová jinak. --}}
                <label
                    x-data="{ over: false }"
                    x-on:dragover.prevent="over = true"
                    x-on:dragleave.prevent="over = false"
                    x-on:drop.prevent="
                        over = false
                        if ($event.dataTransfer.files.length) {
                            $refs.file.files = $event.dataTransfer.files
                            $refs.file.dispatchEvent(new Event('change', { bubbles: true }))
                        }
                    "
                    :class="over ? 'border-sky-500 bg-sky-50 dark:bg-sky-500/10' : 'border-zinc-300 dark:border-zinc-700'"
                    class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-10 text-center transition"
                >
                    <input type="file" x-ref="file" wire:model="imageUpload" accept="image/*" class="sr-only" />

                    <div wire:loading.remove wire:target="imageUpload">
                        <p class="font-medium text-zinc-700 dark:text-zinc-200">{{ __('knowledge-base::kb.editor.drop_here') }}</p>
                        <p class="mt-1 text-sm text-zinc-500">{{ __('knowledge-base::kb.editor.or_click') }}</p>
                    </div>

                    <div wire:loading wire:target="imageUpload" class="flex items-center gap-2 text-sm text-zinc-500">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                        </svg>
                        {{ __('knowledge-base::kb.editor.uploading') }}
                    </div>
                </label>

                @error('imageUpload')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror

                <p class="mt-2 text-xs text-zinc-500">
                    {{ __('knowledge-base::kb.editor.limits', [
                        'mimes' => implode(', ', \NyonCode\KnowledgeBase\Support\Settings::strings('images.mimes')),
                        'size' => round(\NyonCode\KnowledgeBase\Support\Settings::int('images.max_kb', 4096) / 1024, 1),
                    ]) }}
                </p>

                @if ($gallery !== [])
                    <h3 class="mb-2 mt-6 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                        {{ __('knowledge-base::kb.editor.gallery') }}
                    </h3>
                    <div class="grid max-h-64 grid-cols-4 gap-2 overflow-y-auto sm:grid-cols-6">
                        @foreach ($gallery as $image)
                            <button
                                type="button"
                                wire:key="kb-img-{{ md5($image['url']) }}"
                                wire:click="useImage(@js($image['url']))"
                                title="{{ $image['name'] }}"
                                class="group aspect-square overflow-hidden rounded-lg border border-zinc-200 transition hover:border-sky-500 dark:border-zinc-800"
                            >
                                <img src="{{ $image['url'] }}" alt="{{ $image['name'] }}" loading="lazy"
                                     class="h-full w-full object-cover transition group-hover:scale-105" />
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
