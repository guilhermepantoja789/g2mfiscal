<x-app-layout>
    <div class="max-w-4xl mx-auto py-6 px-4" x-data="{ showManausAlert: {{ $isManaus ? 'true' : 'false' }} }">

        <div x-show="showManausAlert" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 backdrop-blur-sm transition-opacity">

            <div class="bg-white rounded-lg shadow-2xl max-w-md w-full mx-4 overflow-hidden transform transition-all scale-100 p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg leading-6 font-bold text-gray-900">Atenção: Configuração Manaus</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-600">Detectamos que sua empresa é de Manaus/AM.</p>
                            <p class="text-sm text-gray-600 mt-2 bg-yellow-50 p-3 rounded border border-yellow-200">
                                O <strong>Código de Tributação Municipal</strong> deve ser mantido como <strong class="text-red-600 text-lg">100</strong>.
                            </p>
                            <p class="text-sm text-gray-500 mt-2">
                                Alterar este valor pode gerar rejeição na API (RNG6110).
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="button" @click="showManausAlert = false"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 sm:w-auto sm:text-sm">
                        Entendi, manterei 100
                    </button>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Novo Serviço</h2>
            <a href="{{ route('servicos.index') }}" class="text-gray-600 hover:text-gray-800 flex items-center transition">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Voltar
            </a>
        </div>

        <div class="bg-white shadow rounded-lg p-6 border border-gray-200">
            <form action="{{ route('servicos.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Nome do Serviço (Apelido)</label>
                        <input type="text" name="nome" value="{{ old('nome') }}" required placeholder="Ex: Consultoria Mensal"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Código Interno (Opcional)</label>
                        <input type="text" name="codigo_interno" value="{{ old('codigo_interno') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>

                @include('servicos.partials.fiscal-wizard')

                <div>
                    <label class="block text-sm font-bold text-gray-700">Valor Unitário Padrão (R$)</label>
                    <input type="text" name="valor_unitario" value="{{ old('valor_unitario') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm money text-lg font-bold text-gray-800" placeholder="0,00">
                </div>

                <hr class="border-gray-200">

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Descrição Padrão do Serviço</label>
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-3 rounded-r-md">
                        <p class="text-sm text-blue-800">Use variáveis como <code>{MES_EXTENSO}</code>, <code>{ANO}</code> ou <code>[CAMPO]</code> na descrição.</p>
                    </div>
                    <textarea name="descricao" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Digite a descrição aqui...">{{ old('descricao') }}</textarea>
                </div>

                <div class="flex justify-end pt-6 border-t border-gray-200">
                    <button type="submit" class="bg-gray-900 hover:bg-black text-white font-bold py-3 px-8 rounded-lg shadow transition">
                        Salvar Serviço
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        g2mPageInit('servicos-create', function () {
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
                if (input.value) input.value = maskMoney(input.value.replace('.', ''));
            });
        });
    </script>
</x-app-layout>
