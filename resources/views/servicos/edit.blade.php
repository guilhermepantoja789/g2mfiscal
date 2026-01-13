<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <div class="md:flex md:items-center md:justify-between mb-6">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Editar Serviço
            </h2>
            <a href="{{ route('servicos.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                &larr; Voltar para a lista
            </a>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <form action="{{ route('servicos.update', $servico->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome do Serviço</label>
                            <input type="text" name="nome" value="{{ old('nome', $servico->nome) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código Interno</label>
                            <input type="text" name="codigo_interno" value="{{ old('codigo_interno', $servico->codigo_interno) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cód. Trib. Nacional</label>
                            <input type="text" name="codigo_tributacao_nacional" value="{{ old('codigo_tributacao_nacional', $servico->codigo_tributacao_nacional) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cód. Trib. Municipal</label>
                            <input type="text" name="codigo_tributacao_municipal" value="{{ old('codigo_tributacao_municipal', $servico->codigo_tributacao_municipal) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código NBS</label>
                            <input type="text" name="codigo_nbs" value="{{ old('codigo_nbs', $servico->codigo_nbs) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Valor Unitário (R$)</label>
                        <input type="text" name="valor_unitario" value="{{ number_format($servico->valor_unitario, 2, ',', '.') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm money">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Descrição</label>
                        <textarea name="descricao" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('descricao', $servico->descricao) }}</textarea>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 text-right border-t border-gray-200">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
