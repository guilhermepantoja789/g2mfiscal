<x-app-layout>
    <div class="max-w-6xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Não foi possível salvar as alterações:</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-gray-800">Editar Nota (Rascunho #{{ $nota->id }})</h2>
            <a href="{{ route('notas.show', $nota->id) }}" class="text-gray-500 hover:text-gray-700 font-medium transition">
                &larr; Cancelar
            </a>
        </div>

        <form action="{{ route('notas.update', $nota->id) }}" method="POST" class="space-y-8"
              x-data="{ retencao: '{{ old('tp_ret_issqn', $nota->tp_ret_issqn) }}' }">
            @csrf
            @method('PUT')

            <div class="bg-white shadow-sm rounded-lg p-6 border border-gray-200">
                <div class="flex justify-between items-end border-b border-gray-200 pb-4 mb-6">
                    <h3 class="text-lg font-bold text-gray-800">1. Dados do Tomador</h3>
                    <div class="w-1/2">
                        <label class="block text-xs font-bold text-blue-700 uppercase mb-1">Carregar Cliente (Substituir)</label>
                        <select id="select_cliente" name="cliente_id" class="block w-full text-sm rounded-md border-blue-300 bg-blue-50">
                            <option value="">-- Manter atual ou Selecionar --</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}"
                                        data-cnpj="{{ $cliente->documento }}"
                                        data-nome="{{ $cliente->razao_social }}"
                                        data-email="{{ $cliente->email }}"
                                        data-telefone="{{ $cliente->telefone }}"
                                        data-im="{{ $cliente->inscricao_municipal }}"
                                        data-cep="{{ $cliente->cep }}"
                                        data-endereco="{{ $cliente->logradouro }}"
                                        data-numero="{{ $cliente->numero }}"
                                        data-complemento="{{ $cliente->complemento }}"
                                        data-bairro="{{ $cliente->bairro }}"
                                        data-cidade="{{ $cliente->cidade_codigo }}"
                                        data-uf="{{ $cliente->uf }}"
                                    {{ (old('cliente_id', $nota->cliente_id) == $cliente->id) ? 'selected' : '' }}>
                                    {{ $cliente->razao_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CNPJ / CPF *</label>
                        <input type="text" name="tomador_cnpj" id="tomador_cnpj" value="{{ old('tomador_cnpj', $nota->tomador_cnpj) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Razão Social / Nome *</label>
                        <input type="text" name="tomador_nome" id="tomador_nome" value="{{ old('tomador_nome', $nota->tomador_nome) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">E-mail</label>
                        <input type="email" name="tomador_email" id="tomador_email" value="{{ old('tomador_email', $nota->tomador_email) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Telefone</label>
                        <input type="text" name="tomador_telefone" id="tomador_telefone"
                               value="{{ old('tomador_telefone', $nota->cliente->telefone ?? '') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Inscrição Municipal</label>
                        <input type="text" name="tomador_im" id="tomador_im"
                               value="{{ old('tomador_im', $nota->cliente->inscricao_municipal ?? '') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">CEP</label>
                            <input type="text" name="tomador_cep" id="tomador_cep"
                                   value="{{ old('tomador_cep', $nota->cliente->cep ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Endereço</label>
                            <input type="text" name="tomador_endereco" id="tomador_endereco"
                                   value="{{ old('tomador_endereco', $nota->cliente->logradouro ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Número</label>
                            <input type="text" name="tomador_numero" id="tomador_numero"
                                   value="{{ old('tomador_numero', $nota->cliente->numero ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bairro</label>
                            <input type="text" name="tomador_bairro" id="tomador_bairro"
                                   value="{{ old('tomador_bairro', $nota->cliente->bairro ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cidade (IBGE)</label>
                            <input type="text" name="tomador_cidade" id="tomador_cidade"
                                   value="{{ old('tomador_cidade', $nota->cliente->cidade_codigo ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">UF</label>
                            <input type="text" name="tomador_uf" id="tomador_uf" maxlength="2"
                                   value="{{ old('tomador_uf', $nota->cliente->uf ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-center uppercase">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6 border border-gray-200">
                <div class="flex justify-between items-center border-b border-gray-200 pb-4 mb-6">
                    <h3 class="text-lg font-bold text-gray-800">2. Detalhes do Serviço</h3>
                    <div class="w-1/3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Competência</label>
                        <input type="datetime-local" name="emissao"
                               value="{{ old('emissao', $nota->emissao ? $nota->emissao->format('Y-m-d\TH:i') : '') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Serviço Vinculado (Opcional)</label>
                    <select name="servico_id" id="servico_select" class="w-full rounded-md border-blue-300 text-sm">
                        <option value="">-- Selecione para preencher (Substituir) --</option>
                        @foreach($servicos as $servico)
                            <option value="{{ $servico->id }}"
                                    data-valor="{{ number_format($servico->valor_unitario, 2, ',', '.') }}"
                                    data-descricao="{{ $servico->descricao }}"
                                {{ (old('servico_id', $nota->servico_id) == $servico->id) ? 'selected' : '' }}>
                                {{ $servico->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discriminação *</label>
                        <textarea name="descricao" id="descricao" rows="4" class="w-full rounded-md border-gray-300 shadow-sm" required>{{ old('descricao', $nota->descricao) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valor Total (R$) *</label>
                        <input type="text" name="valor_servico" id="valor_servico"
                               value="{{ old('valor_servico', number_format($nota->valor_servico, 2, ',', '.')) }}" required
                               class="w-full rounded-md border-gray-300 shadow-sm text-lg font-bold text-right money">
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg border border-gray-300 p-6">
                <h3 class="text-md font-bold text-gray-700 mb-4 border-b border-gray-200 pb-2">3. Configuração Fiscal</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Situação Tributária</label>
                        <select name="trib_issqn" class="w-full rounded-md border-gray-300 shadow-sm">
                            <option value="1" {{ old('trib_issqn', $nota->trib_issqn) == '1' ? 'selected' : '' }}>1 - Operação tributável</option>
                            <option value="2" {{ old('trib_issqn', $nota->trib_issqn) == '2' ? 'selected' : '' }}>2 - Imunidade</option>
                            <option value="3" {{ old('trib_issqn', $nota->trib_issqn) == '3' ? 'selected' : '' }}>3 - Exportação</option>
                            <option value="4" {{ old('trib_issqn', $nota->trib_issqn) == '4' ? 'selected' : '' }}>4 - Não Incidência</option>
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

                <div x-show="['2', '3'].includes(retencao)" class="bg-white p-4 rounded border border-yellow-300 bg-yellow-50 mt-4">
                    <h4 class="text-sm font-bold text-yellow-800 mb-2">Impostos Retidos (Estimativa)</h4>
                    <p class="text-xs text-yellow-700 mb-4">Preencha a porcentagem (%) para calcular o valor, ou o valor (R$) para calcular a porcentagem.</p>

                    <div class="hidden sm:grid grid-cols-12 gap-4 text-xs font-bold text-gray-500 mb-2 border-b pb-1 uppercase">
                        <div class="col-span-4">Esfera</div>
                        <div class="col-span-4 text-right">Alíquota (%)</div>
                        <div class="col-span-4 text-right">Valor (R$)</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 md:gap-4 items-center mb-3">
                        <div class="md:col-span-4 text-sm font-medium text-gray-700">Federal</div>
                        <div class="md:col-span-4">
                            <input type="text" name="p_tot_trib_fed" id="p_fed" data-target="v_fed"
                                   value="{{ old('p_tot_trib_fed', number_format($nota->p_tot_trib_fed, 2, ',', '.')) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-percent money text-sm" placeholder="0,00">
                        </div>
                        <div class="md:col-span-4">
                            <input type="text" name="v_tot_trib_fed" id="v_fed" data-target="p_fed"
                                   value="{{ old('v_tot_trib_fed', number_format($nota->v_tot_trib_fed, 2, ',', '.')) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-value money text-sm" placeholder="0,00">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 md:gap-4 items-center mb-3">
                        <div class="md:col-span-4 text-sm font-medium text-gray-700">Estadual</div>
                        <div class="md:col-span-4">
                            <input type="text" name="p_tot_trib_est" id="p_est" data-target="v_est"
                                   value="{{ old('p_tot_trib_est', number_format($nota->p_tot_trib_est, 2, ',', '.')) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-percent money text-sm" placeholder="0,00">
                        </div>
                        <div class="md:col-span-4">
                            <input type="text" name="v_tot_trib_est" id="v_est" data-target="p_est"
                                   value="{{ old('v_tot_trib_est', number_format($nota->v_tot_trib_est, 2, ',', '.')) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-value money text-sm" placeholder="0,00">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 md:gap-4 items-center mb-3">
                        <div class="md:col-span-4 text-sm font-medium text-gray-700">Municipal</div>
                        <div class="md:col-span-4">
                            <input type="text" name="p_tot_trib_mun" id="p_mun" data-target="v_mun"
                                   value="{{ old('p_tot_trib_mun', number_format($nota->p_tot_trib_mun, 2, ',', '.')) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-percent money text-sm" placeholder="0,00">
                        </div>
                        <div class="md:col-span-4">
                            <input type="text" name="v_tot_trib_mun" id="v_mun" data-target="p_mun"
                                   value="{{ old('v_tot_trib_mun', number_format($nota->v_tot_trib_mun, 2, ',', '.')) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-value money text-sm" placeholder="0,00">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-6 border-t border-gray-200">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg shadow-lg">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ==========================================
            // 1. UTILITÁRIOS (MÁSCARAS E CALCULOS)
            // ==========================================
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

            // Aplica Máscara Dinheiro ao digitar
            const moneyInputs = document.querySelectorAll('.money');
            moneyInputs.forEach(input => {
                input.addEventListener('input', e => { e.target.value = maskMoney(e.target.value); });
                // Formata valor inicial se houver
                if(input.value) input.value = maskMoney(input.value.replace('.', ''));
            });

            // ==========================================
            // 2. LÓGICA DE DESCRIÇÃO INTELIGENTE
            // ==========================================
            const servicoSelect = document.getElementById('servico_select');
            const descricaoInput = document.getElementById('descricao');
            const emissaoInput = document.querySelector('input[name="emissao"]');

            function processarDescricao(template) {
                if (!template) return '';

                let data = new Date(emissaoInput.value);
                if (isNaN(data.getTime())) data = new Date();

                const meses = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];

                let texto = template
                    .replace(/{DIA}/g, String(data.getDate()).padStart(2, '0'))
                    .replace(/{MES}/g, String(data.getMonth() + 1).padStart(2, '0'))
                    .replace(/{ANO}/g, data.getFullYear())
                    .replace(/{MES_EXTENSO}/g, meses[data.getMonth()])
                    .replace(/{MES_ANTERIOR}/g, meses[data.getMonth() === 0 ? 11 : data.getMonth() - 1]);

                const regexVariaveis = /\[(.*?)\]/g;
                let match;
                while ((match = regexVariaveis.exec(texto)) !== null) {
                    const tagCompleta = match[0];
                    const nomeVariavel = match[1];
                    const valorUsuario = prompt(`Preencha o valor para ${nomeVariavel}:`, "");
                    if (valorUsuario !== null) {
                        texto = texto.replace(tagCompleta, valorUsuario);
                    }
                }
                return texto;
            }

            // Evento: Ao Selecionar Serviço
            servicoSelect.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                if (opt.value) {
                    document.getElementById('valor_servico').value = opt.getAttribute('data-valor');
                    document.getElementById('valor_servico').dispatchEvent(new Event('input'));

                    const templateDescricao = opt.getAttribute('data-descricao');
                    // Pergunta se quer sobrescrever a descrição atual se já tiver texto
                    if(descricaoInput.value.trim() !== '' && descricaoInput.value !== templateDescricao) {
                        if(confirm('Deseja substituir a descrição atual pela descrição padrão do serviço?')) {
                            descricaoInput.value = processarDescricao(templateDescricao);
                        }
                    } else {
                        descricaoInput.value = processarDescricao(templateDescricao);
                    }
                }
            });

            // ==========================================
            // 3. PREENCHIMENTO CLIENTE
            // ==========================================
            document.getElementById('select_cliente').addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                if (opt.value) {
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

            // ==========================================
            // 4. CÁLCULO IMPOSTOS
            // ==========================================
            const inputValorServico = document.getElementById('valor_servico');

            document.querySelectorAll('.calc-tax-percent').forEach(input => {
                input.addEventListener('change', function() {
                    const targetId = this.getAttribute('data-target');
                    const targetInput = document.getElementById(targetId);
                    const valorServico = getFloat(inputValorServico.value);
                    const percent = getFloat(this.value);
                    if (valorServico > 0) targetInput.value = formatFloat((valorServico * percent) / 100);
                });
            });

            document.querySelectorAll('.calc-tax-value').forEach(input => {
                input.addEventListener('change', function() {
                    const targetId = this.getAttribute('data-target');
                    const targetInput = document.getElementById(targetId);
                    const valorServico = getFloat(inputValorServico.value);
                    const valorImposto = getFloat(this.value);
                    if (valorServico > 0) targetInput.value = formatFloat((valorImposto / valorServico) * 100);
                });
            });
        });
    </script>
    </x-app-layout>
