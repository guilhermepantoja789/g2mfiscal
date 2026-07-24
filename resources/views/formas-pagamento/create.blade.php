@php
    $isEdit = isset($forma);
@endphp
<x-app-layout>
    <div class="max-w-2xl mx-auto space-y-6">
        <h2 class="text-2xl font-bold text-gray-800">{{ $isEdit ? 'Editar forma de pagamento' : 'Nova forma de pagamento' }}</h2>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-md p-4 text-sm">
                <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST"
              action="{{ $isEdit ? route('formas-pagamento.update', $forma) : route('formas-pagamento.store') }}"
              class="bg-white shadow rounded-lg p-6 space-y-4">
            @csrf
            @if($isEdit) @method('PUT') @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" required value="{{ old('nome', $forma->nome ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Código NFC-e (tPag)</label>
                    <select name="codigo" class="mt-1 block w-full rounded-md border-gray-300" required>
                        @foreach(['01' => '01 — Dinheiro', '03' => '03 — Crédito', '04' => '04 — Débito', '17' => '17 — PIX', '99' => '99 — Outros'] as $cod => $label)
                            <option value="{{ $cod }}" @selected(old('codigo', $forma->codigo ?? '01') === $cod)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tipo liquidação</label>
                    <select name="tipo_liquidacao" class="mt-1 block w-full rounded-md border-gray-300" required>
                        <option value="avista" @selected(old('tipo_liquidacao', $forma->tipo_liquidacao ?? 'avista') === 'avista')>À vista</option>
                        <option value="prazo" @selected(old('tipo_liquidacao', $forma->tipo_liquidacao ?? '') === 'prazo')>Prazo</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Dias para recebimento (D+N)</label>
                    <input type="number" min="0" name="dias_recebimento" value="{{ old('dias_recebimento', $forma->dias_recebimento ?? 0) }}" class="mt-1 block w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Parcelas</label>
                    <input type="number" min="1" max="48" name="parcelas" value="{{ old('parcelas', $forma->parcelas ?? 1) }}" class="mt-1 block w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Juros (%)</label>
                    <input type="number" step="0.01" min="0" name="juros_percentual" value="{{ old('juros_percentual', $forma->juros_percentual ?? 0) }}" class="mt-1 block w-full rounded-md border-gray-300">
                </div>
            </div>

            <div class="flex flex-wrap gap-6 pt-2">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="ativo" value="1" @checked(old('ativo', $forma->ativo ?? true))>
                    Ativa
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="gera_lancamento" value="1" @checked(old('gera_lancamento', $forma->gera_lancamento ?? true))>
                    Gera lançamento financeiro
                </label>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t">
                <a href="{{ route('formas-pagamento.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Cancelar</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md font-bold text-sm">Salvar</button>
            </div>
        </form>
    </div>
</x-app-layout>
