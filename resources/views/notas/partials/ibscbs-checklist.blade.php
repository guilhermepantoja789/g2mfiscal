{{-- Checklist NBS/IBS na nota — NBS vem do serviço --}}
<div class="md:col-span-2 space-y-3">
    <x-help-panel id="nota-ibscbs" title="IBS/CBS e NBS nesta nota" :open="true">
        <ul class="list-disc list-inside space-y-1 text-sm">
            <li>O <strong>código NBS (cNBS)</strong> é enviado a partir do <strong>serviço</strong> selecionado — não há campo NBS na nota.</li>
            <li>Se o serviço estiver sem NBS válido (9 dígitos), a emissão será rejeitada pela SEFIN quando houver IBS/CBS.</li>
            <li>cIndOp / CST abaixo são overrides opcionais; vazio = usa o padrão do serviço.</li>
        </ul>
    </x-help-panel>

    <div id="nbs-checklist" class="rounded-md border px-3 py-2 text-sm"
         data-ok-class="border-emerald-200 bg-emerald-50 text-emerald-900"
         data-warn-class="border-amber-300 bg-amber-50 text-amber-900">
        <p class="font-medium">Checklist NBS do serviço</p>
        <p id="nbs-checklist-msg" class="text-xs mt-1 text-gray-600">Selecione um serviço do catálogo para validar o cNBS.</p>
        <a id="nbs-checklist-link" href="#" class="hidden text-xs font-bold underline mt-1 inline-block">Editar serviço e informar NBS →</a>
    </div>
</div>
