<x-app-layout>
    <div class="max-w-6xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

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

        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-gray-800">Nova Nota Fiscal (Rascunho)</h2>
            <a href="{{ route('notas.index') }}" class="text-gray-500 hover:text-gray-700 font-medium transition">
                &larr; Cancelar
            </a>
        </div>

        <form action="{{ route('notas.store') }}" method="POST" class="space-y-8"
              x-data="{ retencao: '{{ old('tp_ret_issqn', '1') }}' }">
            @csrf

            <div class="bg-white shadow-sm rounded-lg p-6 border border-gray-200">
                <div class="flex justify-between items-end border-b border-gray-200 pb-4 mb-6">
                    <h3 class="text-lg font-bold text-gray-800">1. Dados do Tomador</h3>
                    <div class="w-1/2">
                        <label class="block text-xs font-bold text-blue-700 uppercase mb-1">Carregar Cliente</label>
                        <select id="select_cliente" name="cliente_id" class="block w-full text-sm rounded-md border-blue-300 bg-blue-50">
                            <option value="">-- Selecione para preencher --</option>
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
                                        data-complemento="{{ $cliente->complemento }}"
                                        data-bairro="{{ $cliente->bairro }}"
                                        data-cidade="{{ $cliente->cidade_codigo }}"
                                        data-uf="{{ $cliente->uf }}"
                                    {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
                                    {{ $cliente->razao_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CNPJ / CPF *</label>
                        <input type="text" name="tomador_cnpj" id="tomador_cnpj" value="{{ old('tomador_cnpj') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Razão Social / Nome *</label>
                        <input type="text" name="tomador_nome" id="tomador_nome" value="{{ old('tomador_nome') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">E-mail</label>
                        <input type="email" name="tomador_email" id="tomador_email" value="{{ old('tomador_email') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Telefone</label>
                        <input type="text" name="tomador_telefone" id="tomador_telefone" value="{{ old('tomador_telefone') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Inscrição Municipal</label>
                        <input type="text" name="tomador_im" id="tomador_im" value="{{ old('tomador_im') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">CEP</label>
                            <input type="text" name="tomador_cep" id="tomador_cep" value="{{ old('tomador_cep') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Endereço</label>
                            <input type="text" name="tomador_endereco" id="tomador_endereco" value="{{ old('tomador_endereco') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Número</label>
                            <input type="text" name="tomador_numero" id="tomador_numero" value="{{ old('tomador_numero') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bairro</label>
                            <input type="text" name="tomador_bairro" id="tomador_bairro" value="{{ old('tomador_bairro') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cidade (IBGE)</label>
                            <input type="text" name="tomador_cidade" id="tomador_cidade" value="{{ old('tomador_cidade') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">UF</label>
                            <input type="text" name="tomador_uf" id="tomador_uf" maxlength="2" value="{{ old('tomador_uf') }}"
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
                        <input type="datetime-local" name="emissao" value="{{ old('emissao', now()->format('Y-m-d\TH:i')) }}"
                               class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                </div>

                <div class="mb-6">
                    <select name="servico_id" id="servico_select" class="w-full rounded-md border-blue-300 text-sm">
                        <option value="">-- Preencher com Catálogo (Opcional) --</option>
                        @foreach($servicos as $servico)
                            <option value="{{ $servico->id }}"
                                    data-valor="{{ number_format($servico->valor_unitario, 2, ',', '.') }}"
                                    data-descricao="{{ $servico->descricao }}"
                                    data-c-ind-op="{{ $servico->c_ind_op }}"
                                    data-cst="{{ $servico->cst_ibscbs }}"
                                    data-c-class-trib="{{ $servico->c_class_trib }}"
                                    data-fin-nfse="{{ $servico->fin_nfse ?? '0' }}">
                                {{ $servico->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discriminação *</label>
                        <textarea name="descricao" id="descricao" rows="4" class="w-full rounded-md border-gray-300 shadow-sm" required>{{ old('descricao') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valor Total (R$) *</label>
                        <input type="text" name="valor_servico" id="valor_servico" value="{{ old('valor_servico') }}" required
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
                            <option value="1" {{ old('trib_issqn') == '1' ? 'selected' : '' }}>1 - Operação tributável</option>
                            <option value="2" {{ old('trib_issqn') == '2' ? 'selected' : '' }}>2 - Imunidade</option>
                            <option value="3" {{ old('trib_issqn') == '3' ? 'selected' : '' }}>3 - Exportação de serviço</option>
                            <option value="4" {{ old('trib_issqn') == '4' ? 'selected' : '' }}>4 - Não Incidência</option>
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4 mt-4 border-t border-gray-200 pt-4">
                    <div class="md:col-span-2">
                        <h4 class="text-sm font-bold text-indigo-800 mb-2">IBS/CBS (Reforma)</h4>
                        <p class="text-xs text-gray-500 mb-3">Preenchidos automaticamente pelo serviço; ajuste se necessário.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">cIndOp</label>
                        <select name="c_ind_op" id="c_ind_op" class="w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">— usar padrão do serviço —</option>
                            @foreach($indOps as $indOp)
                                <option value="{{ $indOp->codigo }}" {{ old('c_ind_op') == $indOp->codigo ? 'selected' : '' }}>
                                    {{ $indOp->codigo }} — {{ \Illuminate\Support\Str::limit($indOp->descricao, 60) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CST / cClassTrib</label>
                        <select id="nota_class_trib_pair" class="w-full rounded-md border-gray-300 shadow-sm"
                                onchange="const p=this.value.split('|'); document.getElementById('cst_ibscbs').value=p[0]||''; document.getElementById('c_class_trib').value=p[1]||'';">
                            <option value="">— usar padrão do serviço —</option>
                            @foreach($classTribs as $ct)
                                @php $pair = $ct->cst.'|'.$ct->c_class_trib; @endphp
                                <option value="{{ $pair }}" {{ old('cst_ibscbs') === $ct->cst && old('c_class_trib') === $ct->c_class_trib ? 'selected' : '' }}>
                                    {{ $ct->cst }}/{{ $ct->c_class_trib }} — {{ \Illuminate\Support\Str::limit($ct->descricao, 50) }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="cst_ibscbs" id="cst_ibscbs" value="{{ old('cst_ibscbs') }}">
                        <input type="hidden" name="c_class_trib" id="c_class_trib" value="{{ old('c_class_trib') }}">
                        <input type="hidden" name="fin_nfse" id="fin_nfse" value="{{ old('fin_nfse', '0') }}">
                        <input type="hidden" name="ind_dest" value="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Consumo pessoal (indFinal)</label>
                        <select name="ind_final" id="ind_final" class="w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">— automático (CPF=sim / CNPJ=não) —</option>
                            <option value="0" {{ old('ind_final') === '0' ? 'selected' : '' }}>0 - Não</option>
                            <option value="1" {{ old('ind_final') === '1' ? 'selected' : '' }}>1 - Sim</option>
                        </select>
                    </div>
                </div>

                <div x-show="['2', '3'].includes(retencao)" x-cloak class="bg-white p-4 rounded border border-yellow-300 bg-yellow-50 mt-4">
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
                            <input type="text" name="p_tot_trib_fed" id="p_fed" data-target="v_fed" value="{{ old('p_tot_trib_fed') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-percent money text-sm" placeholder="0,00">
                        </div>
                        <div class="md:col-span-4">
                            <input type="text" name="v_tot_trib_fed" id="v_fed" data-target="p_fed" value="{{ old('v_tot_trib_fed') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-value money text-sm" placeholder="0,00">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 md:gap-4 items-center mb-3">
                        <div class="md:col-span-4 text-sm font-medium text-gray-700">Estadual</div>
                        <div class="md:col-span-4">
                            <input type="text" name="p_tot_trib_est" id="p_est" data-target="v_est" value="{{ old('p_tot_trib_est') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-percent money text-sm" placeholder="0,00">
                        </div>
                        <div class="md:col-span-4">
                            <input type="text" name="v_tot_trib_est" id="v_est" data-target="p_est" value="{{ old('v_tot_trib_est') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-value money text-sm" placeholder="0,00">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 md:gap-4 items-center mb-3">
                        <div class="md:col-span-4 text-sm font-medium text-gray-700">Municipal</div>
                        <div class="md:col-span-4">
                            <input type="text" name="p_tot_trib_mun" id="p_mun" data-target="v_mun" value="{{ old('p_tot_trib_mun') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-percent money text-sm" placeholder="0,00">
                        </div>
                        <div class="md:col-span-4">
                            <input type="text" name="v_tot_trib_mun" id="v_mun" data-target="p_mun" value="{{ old('v_tot_trib_mun') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-right calc-tax-value money text-sm" placeholder="0,00">
                        </div>
                    </div>
                </div>
            </div>

            @if(env('FEATURE_FINANCEIRO', false))
                <div class="bg-indigo-50 rounded-lg border border-indigo-200 p-6 mt-6 mb-6" x-data="{ gerarFinanceiro: false }">
                    <div class="flex justify-between items-center border-b border-indigo-200 pb-2 mb-4">
                        <h3 class="text-md font-bold text-indigo-900 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Financeiro / Cobrança
                        </h3>

                        <label for="gerar_cobranca" class="flex items-center cursor-pointer select-none">
                            <div class="relative">
                                <input type="checkbox" id="gerar_cobranca" name="gerar_cobranca" class="sr-only" x-model="gerarFinanceiro">
                                <div class="block bg-gray-300 w-10 h-6 rounded-full transition-colors duration-200" :class="{ 'bg-indigo-600': gerarFinanceiro }"></div>
                                <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform duration-200 ease-in-out" :class="{ 'translate-x-4': gerarFinanceiro }"></div>
                            </div>
                            <div class="ml-3 text-sm font-medium text-gray-700">
                                Gerar Boleto/Pix
                            </div>
                        </label>
                    </div>

                    <div x-show="gerarFinanceiro" x-transition.opacity class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-indigo-900 mb-1">Data de Vencimento *</label>
                            <input type="date" name="vencimento"
                                   value="{{ now()->addDays(3)->format('Y-m-d') }}"
                                   class="w-full rounded-md border-indigo-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div class="bg-white p-4 rounded border border-indigo-100 text-sm text-indigo-600 shadow-sm">
                            <p><strong>Previsão:</strong> Ao salvar, uma cobrança será criada no status <strong>Pendente</strong>.</p>
                            <p class="mt-1 text-xs text-indigo-400">Integração bancária: <strong>Desativada (Modo Teste)</strong></p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex justify-end pt-6 border-t border-gray-200">
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-bold py-3 px-8 rounded-lg shadow-lg">
                    Salvar Rascunho
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

            // Aplica Máscara Dinheiro
            const moneyInputs = document.querySelectorAll('.money');
            moneyInputs.forEach(input => {
                input.addEventListener('input', e => { e.target.value = maskMoney(e.target.value); });
                if(input.value) input.value = maskMoney(input.value.replace('.', ''));
            });

            // ==========================================
            // 2. LÓGICA DE DESCRIÇÃO INTELIGENTE (NOVO)
            // ==========================================
            const servicoSelect = document.getElementById('servico_select');
            const descricaoInput = document.getElementById('descricao');
            const emissaoInput = document.querySelector('input[name="emissao"]'); // Campo Data Competência

            function processarDescricao(template) {
                if (!template) return '';

                // 1. Pega a data de competência selecionada
                let data = new Date(emissaoInput.value);
                if (isNaN(data.getTime())) data = new Date(); // Fallback para hoje se inválida

                const meses = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];

                // 2. Substitui as Tags de Data
                let texto = template
                    .replace(/{DIA}/g, String(data.getDate()).padStart(2, '0'))
                    .replace(/{MES}/g, String(data.getMonth() + 1).padStart(2, '0'))
                    .replace(/{ANO}/g, data.getFullYear())
                    .replace(/{MES_EXTENSO}/g, meses[data.getMonth()])
                    .replace(/{MES_ANTERIOR}/g, meses[data.getMonth() === 0 ? 11 : data.getMonth() - 1]); // Trata Janeiro -> Dezembro

                // 3. Substitui Tags Interativas [VARIAVEL]
                // Procura por qualquer coisa entre colchetes ex: [PARCELA] ou [PROJETO]
                const regexVariaveis = /\[(.*?)\]/g;
                let match;

                // Usamos um loop para perguntar pro usuário
                // Nota: O prompt bloqueia a execução, o que é útil aqui para obrigar o preenchimento
                while ((match = regexVariaveis.exec(texto)) !== null) {
                    const tagCompleta = match[0]; // ex: [NUMERO_PARCELA]
                    const nomeVariavel = match[1]; // ex: NUMERO_PARCELA

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
                    // Preenche Valor
                    document.getElementById('valor_servico').value = opt.getAttribute('data-valor');
                    document.getElementById('valor_servico').dispatchEvent(new Event('input')); // Dispara máscara

                    // Preenche Descrição Inteligente
                    const templateDescricao = opt.getAttribute('data-descricao');
                    descricaoInput.value = processarDescricao(templateDescricao);

                    const cIndOp = opt.getAttribute('data-c-ind-op') || '';
                    const cst = opt.getAttribute('data-cst') || '';
                    const cClass = opt.getAttribute('data-c-class-trib') || '';
                    const fin = opt.getAttribute('data-fin-nfse') || '0';
                    const cIndEl = document.getElementById('c_ind_op');
                    if (cIndEl && cIndOp) cIndEl.value = cIndOp;
                    const cstEl = document.getElementById('cst_ibscbs');
                    const classEl = document.getElementById('c_class_trib');
                    const pairEl = document.getElementById('nota_class_trib_pair');
                    if (cstEl) cstEl.value = cst;
                    if (classEl) classEl.value = cClass;
                    if (pairEl && cst && cClass) pairEl.value = cst + '|' + cClass;
                    const finEl = document.getElementById('fin_nfse');
                    if (finEl) finEl.value = fin;
                }
            });

            // Evento: Ao Mudar Data (Opcional: Atualizar se o usuário quiser)
            // Não atualizamos automaticamente aqui para não apagar algo que o usuário já editou manualmente.
            // Mas se quiser, pode descomentar a linha abaixo:
            /*
            emissaoInput.addEventListener('change', function() {
               if(servicoSelect.value) servicoSelect.dispatchEvent(new Event('change'));
            });
            */

            // ==========================================
            // 3. PREENCHIMENTO CLIENTE E IMPOSTOS
            // ==========================================

            // Auto-preenchimento Cliente
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

            // Cálculo Reverso de Impostos
            const inputValorServico = document.getElementById('valor_servico');

            // % -> Valor
            document.querySelectorAll('.calc-tax-percent').forEach(input => {
                input.addEventListener('change', function() {
                    const targetId = this.getAttribute('data-target');
                    const targetInput = document.getElementById(targetId);
                    const valorServico = getFloat(inputValorServico.value);
                    const percent = getFloat(this.value);

                    if (valorServico > 0) {
                        targetInput.value = formatFloat((valorServico * percent) / 100);
                    }
                });
            });

            // Valor -> %
            document.querySelectorAll('.calc-tax-value').forEach(input => {
                input.addEventListener('change', function() {
                    const targetId = this.getAttribute('data-target');
                    const targetInput = document.getElementById(targetId);
                    const valorServico = getFloat(inputValorServico.value);
                    const valorImposto = getFloat(this.value);

                    if (valorServico > 0) {
                        targetInput.value = formatFloat((valorImposto / valorServico) * 100);
                    }
                });
            });

            // Recalcula impostos se o valor total mudar
            inputValorServico.addEventListener('input', function() {
                if(getFloat(this.value) > 0) {
                    document.querySelectorAll('.calc-tax-percent').forEach(el => {
                        if(getFloat(el.value) > 0) el.dispatchEvent(new Event('change'));
                    });
                }
            });

        });
    </script>
    </x-app-layout>
