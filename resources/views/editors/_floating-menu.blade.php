{{--
    Nabídka vložení na prázdném řádku.

    Objeví se tam, kde autor stojí a neví, co dál — proto nabízí **typy
    obsahu**, ne formátování: to má bublina a panel. V blokovém editoru se
    nezobrazuje, tam tuhle roli hraje paleta bloků.
--}}
<div x-ref="floating" class="kb-floating" role="toolbar" aria-label="{{ __('knowledge-base::kb.editor.insert') }}">
    @foreach ([
        ['heading', __('knowledge-base::kb.editor.style_h2'), "c => c.setNode('heading', { level: 2 })"],
        ['list', __('knowledge-base::kb.editor.block.list'), 'c => c.toggleBulletList()'],
        ['task', __('knowledge-base::kb.editor.task_list'), 'c => c.toggleTaskList()'],
        ['table', __('knowledge-base::kb.editor.table'), 'c => c.insertTable({ rows: 3, cols: 3, withHeaderRow: true })'],
        ['code', __('knowledge-base::kb.editor.style_code'), 'c => c.setCodeBlock()'],
        ['quote', __('knowledge-base::kb.editor.style_quote'), 'c => c.setBlockquote()'],
        ['rule', __('knowledge-base::kb.editor.horizontal_rule'), 'c => c.setHorizontalRule()'],
    ] as [$key, $label, $command])
        <button type="button" x-on:click="run({{ $command }})" class="kb-floating-item">{{ $label }}</button>
    @endforeach
    <button type="button" x-on:click="$wire.openImagePicker()" class="kb-floating-item">{{ __('knowledge-base::kb.editor.image') }}</button>
</div>
