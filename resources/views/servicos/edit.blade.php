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
            <form action="{{ route('servicos.update', $servico->id) }}" method="POST"
                  x-data="{
                      issRetido: {{ $servico->iss_retido ? 'true' : 'false' }},
                      maskMoney(e) {
                          let value = e.target.value.replace(/\D/g, '');
                          value = (value / 100).toFixed(2) + '';
                          value = value.replace('.', ',');
                          value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
                          e.target.value = value;
                      }
                  }">
                @csrf
                @method('PUT')

                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Identificação</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Nome do Serviço (Apelido)</label>
                            <input type="text" name="nome" value="{{ old('nome', $servico->nome) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Valor Unitário (R$)</label>
                            <input type="text" name="valor_unitario" value="{{ old('valor_unitario', number_format($servico->valor_unitario, 2, ',', '.')) }}" required @input="maskMoney"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código Interno</label>
                            <input type="text" name="codigo_interno" value="{{ old('codigo_interno', $servico->codigo_interno) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-gray-50">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-100 rounded-md p-2 mr-3">
                            <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">Configuração Fiscal</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código Tributação Nacional (LC 116)</label>
                            <input type="text" name="codigo_tributacao_nacional" value="{{ old('codigo_tributacao_nacional', $servico->codigo_tributacao_nacional) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código Tributação Municipal</label>
                            <input type="text" name="codigo_tributacao_municipal" value="{{ old('codigo_tributacao_municipal', $servico->codigo_tributacao_municipal) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Descrição do Serviço (XML)</label>
                            <textarea name="descricao" rows="3" required
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">{{ old('descricao', $servico->descricao) }}</textarea>
                        </div>

                        <div class="md:col-span-2 border-t border-gray-200 pt-4 mt-2">
                            <div class="flex items-start mb-4">
                                <div class="flex items-center h-5">
                                    <input id="iss_retido" name="iss_retido" type="checkbox" x-model="issRetido"
                                           class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="iss_retido" class="font-medium text-gray-700">ISS Retido na Fonte</label>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500">Alíq. ISS (%)</label>
                                    <input type="text" name="aliquota_iss" value="{{ old('aliquota_iss', number_format($servico->aliquota_iss, 2, ',', '.')) }}" @input="maskMoney"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500">Alíq. PIS (%)</label>
                                    <input type="text" name="aliquota_pis" value="{{ old('aliquota_pis', number_format($servico->aliquota_pis, 2, ',', '.')) }}" @input="maskMoney"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500">Alíq. COFINS (%)</label>
                                    <input type="text" name="aliquota_cofins" value="{{ old('aliquota_cofins', number_format($servico->aliquota_cofins, 2, ',', '.')) }}" @input="maskMoney"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500">Alíq. INSS (%)</label>
                                    <input type="text" name="aliquota_inss" value="{{ old('aliquota_inss', number_format($servico->aliquota_inss, 2, ',', '.')) }}" @input="maskMoney"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500">Alíq. IR (%)</label>
                                    <input type="text" name="aliquota_ir" value="{{ old('aliquota_ir', number_format($servico->aliquota_ir, 2, ',', '.')) }}" @input="maskMoney"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500">Alíq. CSLL (%)</label>
                                    <input type="text" name="aliquota_csll" value="{{ old('aliquota_csll', number_format($servico->aliquota_csll, 2, ',', '.')) }}" @input="maskMoney"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3 bg-gray-50 text-right sm:px-6 border-t border-gray-200">
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
