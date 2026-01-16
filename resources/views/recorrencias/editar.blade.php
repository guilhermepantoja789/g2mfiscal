<x-app-layout>
    <div class="max-w-6xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-gray-800">Editar Agendamento</h2>
            <a href="{{ route('recorrencias.index') }}" class="text-gray-500 hover:text-gray-700 font-medium transition">
                &larr; Voltar
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
                <div class="flex">
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Erro ao salvar:</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('recorrencias.update', $recorrencia->id) }}" method="POST" class="space-y-8"
              x-data="{ retencao: '{{ old('tp_ret_issqn', $recorrencia->tp_ret_issqn) }}' }">
            @csrf
            @method('PUT')

            <div class="bg-blue-50 border border-blue-200 shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-bold text-blue-900 border-b border-blue-200 pb-2 mb-4">
                    1. Regra de Repetição
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-blue-800 mb-1">Nome da Regra</label>
                        <input type="text" name="descricao_recorrencia"
                               value="{{ old('descricao_recorrencia', $recorrencia->descricao_recorrencia) }}"
                               class="w-full rounded-md border-blue-300 focus:ring-blue-500">
                    </div>

                    <div class="flex flex-col gap-3 mt-2">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="ativo" value="1" class="sr-only peer" {{ $recorrencia->ativo ? 'checked' : '' }}>
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            <span class="ms-3 text-sm font-medium text-gray-900">Agendamento Ativo?</span>
                        </label>

                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="emitir_automaticamente" value="1" class="sr-only peer" {{ $recorrencia->emitir_automaticamente ? 'checked' : '' }}>
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            <span class="ms-3 text-sm font-medium text-gray-900">Emitir Automaticamente?</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-blue-900">Frequência</label>
                        <select name="frequencia" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="mensal" {{ $recorrencia->frequencia == 'mensal' ? 'selected' : '' }}>Mensal</option>
                            <option value="semanal" {{ $recorrencia->frequencia == 'semanal' ? 'selected' : '' }}>Semanal</option>
                            <option value="anual" {{ $recorrencia->frequencia == 'anual' ? 'selected' : '' }}>Anual</option>
                            <option value="unico" {{ $recorrencia->frequencia == 'unico' ? 'selected' : '' }}>Uma única vez</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-blue-900">Próxima Execução</label>
                        <input type="date" name="proxima_execucao"
                               value="{{ old('proxima_execucao', optional($recorrencia->proxima_execucao)->format('Y-m-d')) }}"
                               min="{{ date('Y-m-d') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-blue-900">Data Fim (Opcional)</label>
                        <input type="date" name="data_fim"
                               value="{{ old('data_fim', optional($recorrencia->data_fim)->format('Y-m-d')) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6 border border-gray-200">
                <div class="flex justify-between items-end border-b border-gray-200 pb-4 mb-6">
                    <h3 class="text-lg font-bold text-gray-800">2. Dados do Tomador</h3>
                    <div class="w-1/2">
                        <label class="block text-xs font-bold text-blue-700 uppercase mb-1">Vinculado ao Cliente:</label>
                        <select id="select_cliente" name="cliente_id" class="block w-full text-sm rounded-md border-blue-300 bg-blue-50">
                            <option value="">-- Avulso / Selecione --</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}"
                                        data-cnpj="{{ $cliente->cnpj }}"
                                        data-nome="{{ $cliente->razao_social }}"
                                        data-email="{{ $cliente->email }}"
                                        data-telefone="{{ $cliente->telefone }}"
                                        data-im="{{ $cliente->inscricao_municipal }}"
                                        data-cep="{{ $cliente->cep }}"
                                        data-endereco="{{ $cliente->logradouro }}"
                                        data-numero="{{ $cliente->numero }}"
                                        data-bairro="{{ $cliente->bairro }}"
                                        data-cidade="{{ $cliente->cidade_codigo }}"
                                        data-uf="{{ $cliente->uf }}"
                                    {{ (old('cliente_id', $recorrencia->cliente_id) == $cliente->id) ? 'selected' : '' }}>
                                    {{ $cliente->razao_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CNPJ / CPF *</label>
                        <input type="text" name="tomador_cnpj" id="tomador_cnpj" value="{{ old('tomador_cnpj', $recorrencia->tomador_cnpj) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Razão Social / Nome *</label>
                        <input type="text" name="tomador_nome" id="tomador_nome" value="{{ old('tomador_nome', $recorrencia->tomador_nome) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">E-mail</label>
                        <input type="email" name="tomador_email" id="tomador_email" value="{{ old('tomador_email', $recorrencia->tomador_email) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Telefone</label>
                        <input type="text" name="tomador_telefone" id="tomador_telefone" value="{{ old('tomador_telefone', $recorrencia->tomador_telefone) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Inscrição Municipal</label>
                        <input type="text" name="tomador_im" id="tomador_im" value="{{ old('tomador_im', $recorrencia->tomador_im) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">CEP</label>
                            <input type="text" name="tomador_cep" id="tomador_cep" value="{{ old('tomador_cep', $recorrencia->tomador_cep) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Endereço</label>
                            <input type="text" name="tomador_endereco" id="tomador_endereco" value="{{ old('tomador_endereco', $recorrencia->tomador_endereco) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Número</label>
                            <input type="text" name="tomador_numero" id="tomador_numero" value="{{ old('tomador_numero', $recorrencia->tomador_numero) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bairro</label>
                            <input type="text" name="tomador_bairro" id="tomador_bairro" value="{{ old('tomador_bairro', $recorrencia->tomador_bairro) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cidade (IBGE)</label>
                            <input type="text" name="tomador_cidade" id="tomador_cidade" value="{{ old('tomador_cidade', $recorrencia->tomador_cidade) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">UF</label>
                            <input type="text" name="tomador_uf" id="tomador_uf" maxlength="2" value="{{ old('tomador_uf', $recorrencia->tomador_uf) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-center uppercase">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6 border border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 border-b border-gray-200 pb-2 mb-6">3. Detalhes do Serviço</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discriminação *</label>
                        <textarea name="descricao_servico" rows="4" class="w-full rounded-md border-gray-300 shadow-sm" required placeholder="Use {MES} e {ANO} para substituição automática">{{ old('descricao_servico', $recorrencia->descricao_servico) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valor Total (R$) *</label>
                        <input type="text" name="valor_servico" id="valor_servico"
                               value="{{ old('valor_servico', number_format($recorrencia->valor_servico, 2, ',', '.')) }}"
                               required class="w-full rounded-md border-gray-300 shadow-sm text-lg font-bold text-right money">
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg border border-gray-300 p-6">
                <h3 class="text-md font-bold text-gray-700 mb-4 border-b border-gray-200 pb-2">4. Configuração Fiscal</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Situação Tributária</label>
                        <select name="trib_issqn" class="w-full rounded-md border-gray-300 shadow-sm">
                            <option value="1" {{ old('trib_issqn', $recorrencia->trib_issqn) == '1' ? 'selected' : '' }}>1 - Operação tributável</option>
                            <option value="2" {{ old('trib_issqn', $recorrencia->trib_issqn) == '2' ? 'selected' : '' }}>2 - Imunidade</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Retenção</label>
                        <select name="tp_ret_issqn" x-model="retencao" class="w-full rounded-md border-gray-300 shadow-sm">
                            <option value="1">1 - Não Retido</option>
                            <option value="2">2 - Retido pelo Tomador</option>
                            <option value="3">3 - Retido pelo Intermediário</option>
                        </select>
                    </div>
                </div>

                <div x-show="['2', '3'].includes(retencao)" x-cloak class="bg-white p-4 rounded border border-yellow-300 bg-yellow-50 mt-4">
                    <h4 class="text-sm font-bold text-yellow-800 mb-2">Impostos Retidos (Estimativa)</h4>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 md:gap-4 items-center mb-3">
                        <div class="md:col-span-4 text-sm font-medium text-gray-700">Federal (%)</div>
                        <div class="md:col-span-4">
                            <input type="text" name="p_tot_trib_fed" id="p_fed" data-target="v_fed"
                                   value="{{ old('p_tot_trib_fed', number_format($recorrencia->p_tot_trib_fed, 2, ',', '.')) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-percent money text-sm">
                        </div>
                        <div class="md:col-span-4">
                            <input type="text" id="v_fed" data-target="p_fed"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-value money text-sm bg-gray-100" readonly>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 md:gap-4 items-center mb-3">
                        <div class="md:col-span-4 text-sm font-medium text-gray-700">Estadual (%)</div>
                        <div class="md:col-span-4">
                            <input type="text" name="p_tot_trib_est" id="p_est" data-target="v_est"
                                   value="{{ old('p_tot_trib_est', number_format($recorrencia->p_tot_trib_est, 2, ',', '.')) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-percent money text-sm">
                        </div>
                        <div class="md:col-span-4">
                            <input type="text" id="v_est" data-target="p_est"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-value money text-sm bg-gray-100" readonly>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 md:gap-4 items-center mb-3">
                        <div class="md:col-span-4 text-sm font-medium text-gray-700">Municipal/ISS (%)</div>
                        <div class="md:col-span-4">
                            <input type="text" name="p_tot_trib_mun" id="p_mun" data-target="v_mun"
                                   value="{{ old('p_tot_trib_mun', number_format($recorrencia->p_tot_trib_mun, 2, ',', '.')) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-percent money text-sm">
                        </div>
                        <div class="md:col-span-4">
                            <input type="text" id="v_mun" data-target="p_mun"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-value money text-sm bg-gray-100" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-6">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg shadow-lg">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // --- 1. MÁSCARAS ---
            function maskMoney(val) {
                if(!val) return '';
                val = val.replace(/\D/g, '');
                val = (val / 100).toFixed(2) + '';
                val = val.replace('.', ',');
                val = val.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
                return val;
            }

            function getFloat(val) {
                if(!val) return 0;
                return parseFloat(val.replace(/\./g, '').replace(',', '.')) || 0;
            }

            function formatFloat(val) {
                return val.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            const moneyInputs = document.querySelectorAll('.money');
            moneyInputs.forEach(input => {
                input.addEventListener('input', e => { e.target.value = maskMoney(e.target.value); });
                // Dispara máscara inicial se tiver valor e não estiver formatado
                if(input.value && input.value.indexOf(',') === -1) {
                    input.value = maskMoney(input.value.replace('.', ''));
                }
            });

            // --- 2. PREENCHIMENTO CLIENTE ---
            const selCliente = document.getElementById('select_cliente');

            function preencherCliente() {
                const opt = selCliente.options[selCliente.selectedIndex];
                if(opt.value){
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
                    setVal('tomador_bairro', 'data-bairro');
                    setVal('tomador_cidade', 'data-cidade');
                    setVal('tomador_uf', 'data-uf');
                }
            }

            selCliente.addEventListener('change', preencherCliente);

            // CORREÇÃO: Forçar preenchimento se houver cliente selecionado mas campos estiverem vazios
            // (Caso de registros antigos do banco que não tinham os campos de endereço)
            const inputNome = document.getElementById('tomador_nome');
            if(selCliente.value && !inputNome.value) {
                preencherCliente();
            }

            // --- 3. CÁLCULO DE IMPOSTOS ---
            const inputValorServico = document.getElementById('valor_servico');

            // Função de cálculo isolada para reutilização
            function calcularImposto(inputPercent) {
                const targetId = inputPercent.getAttribute('data-target');
                const targetInput = document.getElementById(targetId);
                const valorServico = getFloat(inputValorServico.value);
                const percent = getFloat(inputPercent.value);

                if (valorServico > 0) {
                    targetInput.value = formatFloat((valorServico * percent) / 100);
                } else {
                    targetInput.value = '0,00';
                }
            }

            // Evento: % -> Valor
            document.querySelectorAll('.calc-tax-percent').forEach(input => {
                input.addEventListener('input', function() { calcularImposto(this); }); // Mudado para 'input' para tempo real
                input.addEventListener('change', function() { calcularImposto(this); });
            });

            // Recalcula tudo ao mudar o valor total
            inputValorServico.addEventListener('input', function() {
                if(getFloat(this.value) > 0) {
                    document.querySelectorAll('.calc-tax-percent').forEach(el => calcularImposto(el));
                }
            });

            // CORREÇÃO: Trigger inicial agressivo para garantir que os valores apareçam
            // Espera um pouco para garantir que o AlpineJS iniciou
            setTimeout(() => {
                document.querySelectorAll('.calc-tax-percent').forEach(el => calcularImposto(el));
            }, 300);
        });
    </script>
    <style> [x-cloak] { display: none !important; } </style>
</x-app-layout>
