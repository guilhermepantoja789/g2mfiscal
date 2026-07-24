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
                            <p class="text-sm text-gray-600">
                                Detectamos que sua empresa é de Manaus/AM.
                            </p>
                            <p class="text-sm text-gray-600 mt-2 bg-yellow-50 p-3 rounded border border-yellow-200">
                                O <strong>Código de Tributação Municipal</strong> deve ser mantido como <strong class="text-red-600 text-lg">100</strong>.
                            </p>
                            <p class="text-sm text-gray-500 mt-2">
                                Testes indicam que alterar este valor para outros códigos (mesmo que válidos na lista) pode gerar erros de rejeição na API (RNG6110).
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="button" @click="showManausAlert = false"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:w-auto sm:text-sm">
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

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-gray-50 p-4 rounded-md border border-gray-200">
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Cód. Tributação Nacional *</label>

                        <input type="text" list="lista-nacional" name="codigo_tributacao_nacional"
                               value="{{ old('codigo_tributacao_nacional') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               placeholder="Digite código ou descrição...">

                        <datalist id="lista-nacional">
                            @foreach($codigosNacionais as $item)
                                <option value="{{ $item->codigo }}">
                                    {{ $item->item_lc116 }} - {{ Str::limit($item->descricao, 60) }}
                                </option>
                            @endforeach
                        </datalist>

                        <p class="text-xs text-gray-500 mt-1">Selecione na lista ou digite (Ex: 1.03.01 ou 010301).</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Cód. Tributação Municipal *</label>
                        <input type="text" name="codigo_tributacao_municipal" value="{{ old('codigo_tributacao_municipal', $padraoMunicipal) }}" required
                               class="mt-1 block w-full rounded-md shadow-sm {{ $isManaus ? 'border-yellow-400 bg-yellow-50 font-bold text-yellow-800' : 'border-gray-300' }}">
                        @if($isManaus)
                            <p class="text-xs text-yellow-600 mt-1 font-bold">⚠ Recomendado: 100</p>
                        @else
                            <p class="text-xs text-gray-500 mt-1">Código do serviço na prefeitura.</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Código NBS (Opcional)</label>
                        <input type="text" name="codigo_nbs" value="{{ old('codigo_nbs') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>

                <div class="bg-indigo-50 p-4 rounded-md border border-indigo-200 space-y-4">
                    <div>
                        <h3 class="text-sm font-bold text-indigo-900">IBS/CBS (Reforma Tributária)</h3>
                        <p class="text-xs text-indigo-700 mt-1">Obrigatório na DPS a partir de 03/08/2026. Defaults sugeridos para serviços à distância em Manaus.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700">Finalidade (finNFSe) *</label>
                            <select name="fin_nfse" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="0" {{ old('fin_nfse', '0') === '0' ? 'selected' : '' }}>0 - NFS-e regular</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700">Indicador da operação (cIndOp) *</label>
                            <select name="c_ind_op" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                @foreach($indOps as $indOp)
                                    <option value="{{ $indOp->codigo }}" {{ old('c_ind_op', '100301') == $indOp->codigo ? 'selected' : '' }}>
                                        {{ $indOp->codigo }} — {{ \Illuminate\Support\Str::limit($indOp->descricao, 70) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700">CST / Classificação Tributária *</label>
                            <select name="class_trib_pair" id="class_trib_pair" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    onchange="const p=this.value.split('|'); document.getElementById('cst_ibscbs').value=p[0]||''; document.getElementById('c_class_trib').value=p[1]||'';">
                                @php
                                    $oldPair = old('cst_ibscbs', '000').'|'.old('c_class_trib', '000001');
                                @endphp
                                @foreach($classTribs as $ct)
                                    @php $pair = $ct->cst.'|'.$ct->c_class_trib; @endphp
                                    <option value="{{ $pair }}" {{ $oldPair === $pair ? 'selected' : '' }}>
                                        {{ $ct->cst }}/{{ $ct->c_class_trib }} — {{ \Illuminate\Support\Str::limit($ct->descricao, 70) }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="cst_ibscbs" id="cst_ibscbs" value="{{ old('cst_ibscbs', '000') }}">
                            <input type="hidden" name="c_class_trib" id="c_class_trib" value="{{ old('c_class_trib', '000001') }}">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700">Valor Unitário Padrão (R$)</label>
                    <input type="text" name="valor_unitario" value="{{ old('valor_unitario') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm money text-lg font-bold text-gray-800" placeholder="0,00">
                </div>

                <hr class="border-gray-200">

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Descrição Padrão do Serviço</label>

                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-3 rounded-r-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-bold text-blue-800">Use Variáveis Dinâmicas</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p class="mb-2">Você pode usar códigos especiais no texto que serão substituídos automaticamente na hora de emitir a nota baseados na <strong>Data de Competência</strong>:</p>
                                    <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 list-disc list-inside mb-2 font-mono text-xs bg-white p-2 rounded border border-blue-100">
                                        <li>{MES} <span class="text-gray-500">- Ex: 02</span></li>
                                        <li>{MES_EXTENSO} <span class="text-gray-500">- Ex: Fevereiro</span></li>
                                        <li>{ANO} <span class="text-gray-500">- Ex: 2026</span></li>
                                        <li>{MES_ANTERIOR} <span class="text-gray-500">- Ex: Janeiro</span></li>
                                        <li class="col-span-1 sm:col-span-2 text-indigo-600 font-bold">[QUALQUER_COISA]  <span class="text-gray-500 font-normal">- Abre uma caixa perguntando o valor.</span></li>
                                        <li class="col-span-1 sm:col-span-2 text-indigo-600 font-bold">*USAR COLCHETES PRA CODIGOS CUSTOMIZADOS* <span class="text-gray-500 font-normal">- [CODIGO].</span></li>
                                    </ul>
                                    <p class="text-xs italic">Exemplo: "Serviço ref. {MES_EXTENSO}/{ANO}. Parcela [NUMERO]."</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <textarea name="descricao" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Digite a descrição aqui...">{{ old('descricao') }}</textarea>
                </div>

                <div class="flex justify-end pt-6 border-t border-gray-200">
                    <button type="submit" class="bg-gray-900 hover:bg-black text-white font-bold py-3 px-8 rounded-lg shadow transition transform hover:-translate-y-0.5">
                        Salvar Serviço
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pair = document.getElementById('class_trib_pair');
            if (pair && pair.value) {
                const p = pair.value.split('|');
                const cst = document.getElementById('cst_ibscbs');
                const cls = document.getElementById('c_class_trib');
                if (cst) cst.value = p[0] || '';
                if (cls) cls.value = p[1] || '';
            }

            function maskMoney(val) {
                if(!val) return '';
                val = val.replace(/\D/g, '');
                val = (val / 100).toFixed(2) + '';
                val = val.replace('.', ',');
                val = val.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
                return val;
            }

            const moneyInputs = document.querySelectorAll('.money');
            moneyInputs.forEach(input => {
                input.addEventListener('input', e => { e.target.value = maskMoney(e.target.value); });
                if(input.value) input.value = maskMoney(input.value.replace('.', ''));
            });
        });
    </script>
    </x-app-layout>
