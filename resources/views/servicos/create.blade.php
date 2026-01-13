<x-app-layout>
    <div class="max-w-4xl mx-auto py-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Novo Serviço</h2>
            <a href="{{ route('servicos.index') }}" class="text-gray-600 hover:text-gray-800 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Voltar
            </a>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <form action="{{ route('servicos.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome do Serviço (Apelido)</label>
                        <input type="text" name="nome" value="{{ old('nome') }}" required placeholder="Ex: Consultoria de TI"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Código Interno (Opcional)</label>
                        <input type="text" name="codigo_interno" value="{{ old('codigo_interno') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cód. Tributação Nacional</label>
                        <input type="text" name="codigo_tributacao_nacional" value="{{ old('codigo_tributacao_nacional') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <p class="text-xs text-gray-500 mt-1">Ex: 1.03.01</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cód. Tributação Municipal</label>
                        <input type="text" name="codigo_tributacao_municipal" value="{{ old('codigo_tributacao_municipal', $padraoMunicipal) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Código NBS (Opcional)</label>
                        <input type="text" name="codigo_nbs" value="{{ old('codigo_nbs') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Valor Unitário Padrão (R$)</label>
                    <input type="text" name="valor_unitario" value="{{ old('valor_unitario') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm money">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Descrição Padrão do Serviço</label>
                    <textarea name="descricao" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('descricao') }}</textarea>
                </div>

                <div class="flex justify-end pt-6 border-t">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">
                        Salvar Serviço
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
