<x-app-layout>
    <div class="max-w-5xl mx-auto py-6">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Nova Nota Fiscal</h1>
            <a href="{{ route('notas.index') }}" class="text-gray-500 hover:underline">Cancelar</a>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-hidden border border-gray-200">
            <form action="{{ route('notas.store') }}" method="POST" class="p-6 space-y-8">
                @csrf

                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="flex justify-between items-end border-b border-gray-300 pb-2 mb-4">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center">
                            <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-2">1</span>
                            Dados do Tomador
                        </h2>
                        <div class="w-1/2">
                            <label class="block text-xs font-bold text-blue-700 uppercase mb-1">Carregar Cliente Cadastrado</label>
                            <select id="select_cliente" class="block w-full text-sm rounded-md border-blue-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-blue-50">
                                <option value="">-- Selecione para preencher --</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}"
                                            data-cnpj="{{ $cliente->cnpj }}"
                                            data-nome="{{ $cliente->razao_social }}"
                                            data-email="{{ $cliente->email ?? '' }}"
                                            data-telefone="{{ $cliente->telefone ?? '' }}"
                                            data-im="{{ $cliente->inscricao_municipal ?? '' }}"
                                            data-cep="{{ $cliente->cep ?? '' }}"
                                            data-endereco="{{ $cliente->logradouro ?? '' }}"
                                            data-numero="{{ $cliente->numero ?? '' }}"
                                            data-complemento="{{ $cliente->complemento ?? '' }}"
                                            data-bairro="{{ $cliente->bairro ?? '' }}"
                                            data-cidade="{{ $cliente->cidade_codigo ?? '' }}"
                                            data-uf="{{ $cliente->uf ?? '' }}">
                                        {{ $cliente->razao_social }} ({{ $cliente->cnpj }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">CNPJ / CPF *</label>
                            <input type="text" name="tomador_cnpj" id="tomador_cnpj" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="00000000000000">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Razão Social / Nome *</label>
                            <input type="text" name="tomador_nome" id="tomador_nome" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">E-mail</label>
                            <input type="email" name="tomador_email" id="tomador_email"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Telefone</label>
                            <input type="text" name="tomador_telefone" id="tomador_telefone"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Inscrição Municipal</label>
                            <input type="text" name="tomador_im" id="tomador_im"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <h3 class="text-sm font-bold text-gray-500 mb-3">Endereço do Tomador</h3>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">CEP</label>
                                <input type="text" name="tomador_cep" id="tomador_cep"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700">Logradouro (Rua/Av)</label>
                                <input type="text" name="tomador_endereco" id="tomador_endereco"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Número</label>
                                <input type="text" name="tomador_numero" id="tomador_numero"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Complemento</label>
                                <input type="text" name="tomador_complemento" id="tomador_complemento"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Bairro</label>
                                <input type="text" name="tomador_bairro" id="tomador_bairro"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Cód. IBGE Cidade</label>
                                <input type="text" name="tomador_cidade" id="tomador_cidade" placeholder="Ex: 1302603"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">UF</label>
                                <input type="text" name="tomador_uf" id="tomador_uf" maxlength="2" placeholder="AM"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="flex justify-between items-end border-b border-gray-300 pb-2 mb-4">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center">
                            <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs mr-2">2</span>
                            Dados do Serviço
                        </h2>
                        <div class="w-1/2">
                            <label class="block text-xs font-bold text-blue-700 uppercase mb-1">Preencher com meus serviços</label>
                            <select id="select_servico" class="block w-full text-sm rounded-md border-blue-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-blue-50">
                                <option value="">-- Selecione --</option>
                                @foreach($servicos as $servico)
                                    <option value="{{ $servico->id }}"
                                            data-valor="{{ $servico->valor_unitario }}"
                                            data-codigo="{{ $servico->codigo_tributacao_municipal ?? $servico->codigo_nbs }}"
                                            data-descricao="{{ $servico->descricao }}"
                                            data-iss-retido="{{ $servico->iss_retido ? '1' : '0' }}">
                                        {{ $servico->nome }} ({{ $servico->codigo_interno ?? 'S/N' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Valor (R$) *</label>
                            <input type="number" step="0.01" name="valor_servico" id="valor_servico" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código (Municipal/NBS) *</label>
                            <input type="text" name="codigo_servico" id="codigo_servico" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Situação Tributária</label>
                            <select name="tributacao_iss" id="tributacao_iss" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="1">1 - Tributável</option>
                                <option value="2">2 - Exportação</option>
                                <option value="3">3 - Imune</option>
                                <option value="4">4 - Isento</option>
                            </select>
                        </div>
                    </div>

{{--                    <div class="mb-4">--}}
{{--                        <label class="inline-flex items-center">--}}
{{--                            <input type="checkbox" name="iss_retido" id="iss_retido" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">--}}
{{--                            <span class="ml-2 text-sm text-gray-700 font-bold">ISS Retido pelo Tomador?</span>--}}
{{--                        </label>--}}
{{--                    </div>--}}

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Discriminação *</label>
                        <textarea name="descricao" id="descricao_servico" rows="4" required
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded shadow-lg transform transition hover:scale-105">
                        Emitir Nota Fiscal
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- AUTOMACAO CLIENTES ---
            const selectCliente = document.getElementById('select_cliente');

            selectCliente.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                if (opt.value) {
                    // Função auxiliar para preencher se o valor não for nulo
                    const setVal = (id, attr) => {
                        const el = document.getElementById(id);
                        if(el) el.value = opt.getAttribute(attr) || '';
                    };

                    setVal('tomador_cnpj', 'data-cnpj');
                    setVal('tomador_nome', 'data-nome');
                    setVal('tomador_email', 'data-email');
                    setVal('tomador_telefone', 'data-telefone');
                    setVal('tomador_im', 'data-im');

                    setVal('tomador_cep', 'data-cep');
                    setVal('tomador_endereco', 'data-endereco');
                    setVal('tomador_numero', 'data-numero');
                    setVal('tomador_complemento', 'data-complemento');
                    setVal('tomador_bairro', 'data-bairro');
                    setVal('tomador_cidade', 'data-cidade');
                    setVal('tomador_uf', 'data-uf');
                }
            });

            // --- AUTOMACAO SERVICOS ---
            const selectServico = document.getElementById('select_servico');

            selectServico.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                if (opt.value) {
                    document.getElementById('valor_servico').value = opt.getAttribute('data-valor');
                    document.getElementById('codigo_servico').value = opt.getAttribute('data-codigo');
                    document.getElementById('descricao_servico').value = opt.getAttribute('data-descricao');

                    const isRetido = opt.getAttribute('data-iss-retido') === '1';
                    document.getElementById('iss_retido').checked = isRetido;
                }
            });
        });
    </script>
</x-app-layout>
