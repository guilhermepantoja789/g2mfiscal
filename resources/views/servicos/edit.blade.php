<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6"
         x-data="{ showManausAlert: {{ (isset($isManaus) && $isManaus) ? 'true' : 'false' }} }">

        <div x-show="showManausAlert" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 backdrop-blur-sm transition-opacity">
            <div class="bg-white rounded-lg shadow-2xl max-w-md w-full mx-4 overflow-hidden p-6">
                <h3 class="text-lg font-bold text-gray-900">Atenção: Configuração Manaus</h3>
                <p class="text-sm text-gray-600 mt-2 bg-yellow-50 p-3 rounded border border-yellow-200">
                    O <strong>Código de Tributação Municipal</strong> deve ser mantido como <strong class="text-red-600">100</strong>.
                </p>
                <div class="mt-6 flex justify-end">
                    <button type="button" @click="showManausAlert = false"
                            class="inline-flex justify-center rounded-md bg-yellow-600 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-700">
                        Entendi, manterei 100
                    </button>
                </div>
            </div>
        </div>

        <div class="md:flex md:items-center md:justify-between mb-6">
            <h2 class="text-2xl font-bold leading-7 text-gray-900">Editar Serviço</h2>
            <a href="{{ route('servicos.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Voltar para a lista</a>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden border border-gray-200">
            <form action="{{ route('servicos.update', $servico->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700">Nome do Serviço (Apelido)</label>
                            <input type="text" name="nome" value="{{ old('nome', $servico->nome) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código Interno</label>
                            <input type="text" name="codigo_interno" value="{{ old('codigo_interno', $servico->codigo_interno) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>

                    @include('servicos.partials.fiscal-wizard')

                    <div>
                        <label class="block text-sm font-bold text-gray-700">Valor Unitário (R$)</label>
                        <input type="text" name="valor_unitario" value="{{ old('valor_unitario', number_format($servico->valor_unitario, 2, ',', '.')) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm money text-lg font-bold text-gray-800">
                    </div>

                    <hr class="border-gray-200">

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Descrição Padrão do Serviço</label>
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-3 mb-3 rounded-r-md text-sm text-blue-800">
                            Variáveis: <code>{MES_EXTENSO}</code>, <code>{ANO}</code>, <code>[CAMPO]</code>
                        </div>
                        <textarea name="descricao" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('descricao', $servico->descricao) }}</textarea>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 text-right border-t border-gray-200">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 font-bold shadow-sm">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        g2mPageInit('servicos-edit', function () {
            function maskMoney(val) {
                if (!val) return '';
                val = val.replace(/\D/g, '');
                val = (val / 100).toFixed(2) + '';
                val = val.replace('.', ',');
                val = val.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
                return val;
            }
            document.querySelectorAll('.money').forEach(input => {
                input.oninput = e => { e.target.value = maskMoney(e.target.value); };
            });
        });
    </script>
</x-app-layout>
