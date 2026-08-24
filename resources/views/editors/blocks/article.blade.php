{{-- Vybírá se ze seznamu, nepíše se slug: odkaz na neexistující článek se
     jinak pozná až tím, že někdo skončí na 404. --}}
<select wire:model.blur="blockData.{{ $index }}.slug"
    class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100">
    <option value="">—</option>
    @foreach ($this->linkableArticles() as $slug => $title)
        <option value="{{ $slug }}">{{ $title }}</option>
    @endforeach
</select>
