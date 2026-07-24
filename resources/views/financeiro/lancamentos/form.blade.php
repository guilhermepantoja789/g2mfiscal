@php
    $isEdit = isset($lancamento);
@endphp
<x-app-layout>
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $isEdit ? 'Editar lançamento' : 'Novo lançamento' }}</h2>
            <a href="{{ route('lancamentos.index') }}" class="text-sm text-blue-600 hover:underline">← Lançamentos</a>
        </div>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-md p-4 text-sm">
                <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST"
              action="{{ $isEdit ? route('lancamentos.update', $lancamento->id) : route('lancamentos.store') }}"
              class="bg-white shadow rounded-lg p-6 space-y-4">
            @csrf
            @if($isEdit) @method('PUT') @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tipo</label>
                    <select name="tipo" required class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="receber" @selected(old('tipo', $lancamento->tipo ?? 'receber') === 'receber')>Receber</option>
                        <option value="pagar" @selected(old('tipo', $lancamento->tipo ?? '') === 'pagar')>Pagar</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Valor</label>
                    <input type="number" step="0.01" min="0.01" name="valor" required
                           value="{{ old('valor', $lancamento->valor ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Vencimento</label>
                    <input type="date" name="vencimento" required
                           value="{{ old('vencimento', isset($lancamento) ? $lancamento->vencimento?->format('Y-m-d') : now()->format('Y-m-d')) }}"
                           class="mt-1 block w-full rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Forma de pagamento</label>
                    <select name="forma_pagamento_id" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">—</option>
                        @foreach($formas as $f)
                            <option value="{{ $f->id }}" @selected((string) old('forma_pagamento_id', $lancamento->forma_pagamento_id ?? '') === (string) $f->id)>{{ $f->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Cliente (a receber)</label>
                    <select name="cliente_id" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">—</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c->id }}" @selected((string) old('cliente_id', $lancamento->cliente_id ?? '') === (string) $c->id)>{{ $c->razao_social }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Fornecedor (a pagar)</label>
                    <select name="fornecedor_id" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">—</option>
                        @foreach($fornecedores as $f)
                            <option value="{{ $f->id }}" @selected((string) old('fornecedor_id', $lancamento->fornecedor_id ?? '') === (string) $f->id)>{{ $f->razao_social }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Descrição</label>
                    <input type="text" name="descricao" maxlength="255"
                           value="{{ old('descricao', $lancamento->descricao ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300">
                </div>
            </div>

            @unless($isEdit)
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="pago_avista" value="1" @checked(old('pago_avista'))>
                    Já liquidado (cria como pago)
                </label>
            @endunless

            <div class="flex justify-end gap-2 pt-4 border-t">
                <a href="{{ route('lancamentos.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Cancelar</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md font-bold text-sm">Salvar</button>
            </div>
        </form>
    </div>
</x-app-layout>
