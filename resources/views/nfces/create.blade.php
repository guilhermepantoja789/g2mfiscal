<x-app-layout>
    <div class="max-w-6xl mx-auto space-y-8 animate-fade-in-up">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('nfces.index') }}" class="group flex items-center justify-center w-12 h-12 bg-white rounded-2xl hover:bg-gray-50 transition-colors border border-gray-200 shadow-sm">
                    <svg class="w-5 h-5 text-gray-500 group-hover:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Nova NFC-e (emissão manual)</h2>
                    <p class="mt-1 text-sm text-gray-500">Simples Nacional / consumidor final (AM) — sem estoque ou financeiro.</p>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="rounded-2xl bg-rose-50 border border-rose-200 p-5 shadow-sm">
                <h3 class="text-sm font-bold text-rose-800 uppercase tracking-wide mb-2">Corrija os campos abaixo</h3>
                <ul class="list-disc pl-5 text-sm text-rose-700 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('nfces.store') }}" method="POST" class="space-y-6"
              x-data="nfceAvulsaForm()"
              x-init="init()">
            @csrf

            <!-- 1. Itens (lista) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 px-6 sm:px-8 py-5 border-b border-gray-100">
                    <div class="flex items-center">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center mr-3 text-sm font-extrabold">1</div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Itens</h3>
                            <p class="text-xs text-gray-500" x-text="itens.length + (itens.length === 1 ? ' item' : ' itens') + ' · Total ' + fmt(total)"></p>
                        </div>
                    </div>
                    <button type="button" @click="addItem()"
                            class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-50 text-blue-700 border border-blue-100 font-bold text-sm hover:bg-blue-100 transition-all">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Adicionar item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100">
                                <th class="px-3 py-3 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider w-10">#</th>
                                <th class="px-3 py-3 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider min-w-[180px]">Descrição</th>
                                <th class="px-3 py-3 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider w-28">NCM</th>
                                <th class="px-3 py-3 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider w-24">CFOP</th>
                                <th class="px-3 py-3 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider w-24">CSOSN</th>
                                <th class="px-3 py-3 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider w-16">Un</th>
                                <th class="px-3 py-3 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider w-24">Qtd</th>
                                <th class="px-3 py-3 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider w-28">Unit.</th>
                                <th class="px-3 py-3 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider w-28">Total</th>
                                <th class="px-3 py-3 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <template x-for="(item, idx) in itens" :key="item._key">
                                <tr class="hover:bg-blue-50/30 transition-colors align-top">
                                    <td class="px-3 py-2.5 text-gray-400 font-semibold tabular-nums" x-text="idx + 1"></td>
                                    <td class="px-3 py-2.5">
                                        <input type="text" :name="'itens[' + idx + '][descricao]'" x-model="item.descricao" required
                                               placeholder="Descrição do produto"
                                               class="block w-full min-w-[160px] rounded-lg border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <input type="text" :name="'itens[' + idx + '][ncm]'" x-model="item.ncm" required
                                               placeholder="00000000"
                                               class="block w-full rounded-lg border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 text-sm font-mono">
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <select :name="'itens[' + idx + '][cfop]'" x-model="item.cfop"
                                                class="block w-full rounded-lg border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 text-sm cursor-pointer">
                                            <option value="5102">5102</option>
                                            <option value="5405">5405</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <select :name="'itens[' + idx + '][csosn]'" x-model="item.csosn"
                                                class="block w-full rounded-lg border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 text-sm cursor-pointer">
                                            <option value="102">102</option>
                                            <option value="500">500</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <input type="text" :name="'itens[' + idx + '][unidade]'" x-model="item.unidade" required
                                               class="block w-full rounded-lg border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <input type="number" step="0.001" :name="'itens[' + idx + '][quantidade]'" x-model.number="item.quantidade" required
                                               class="block w-full rounded-lg border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 text-sm text-right">
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <input type="number" step="0.01" :name="'itens[' + idx + '][valor_unitario]'" x-model.number="item.valor_unitario" required
                                               placeholder="0,00"
                                               class="block w-full rounded-lg border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 text-sm text-right">
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono font-extrabold text-gray-900 whitespace-nowrap pt-3.5"
                                        x-text="fmt((Number(item.quantidade) || 0) * (Number(item.valor_unitario) || 0))"></td>
                                    <td class="px-3 py-2.5 text-center">
                                        <button type="button" @click="removeItem(idx)" x-show="itens.length > 1" x-cloak
                                                class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition-colors"
                                                title="Remover item">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50/60 border-t border-gray-100">
                                <td colspan="8" class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total</td>
                                <td class="px-3 py-3 text-right font-mono font-extrabold text-gray-900 whitespace-nowrap" x-text="fmt(total)"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="px-6 sm:px-8 py-3 border-t border-gray-100 bg-gray-50/40">
                    <button type="button" @click="addItem()"
                            class="text-sm font-semibold text-blue-700 hover:text-blue-900 hover:underline inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Nova linha
                    </button>
                </div>
            </div>

            <!-- 2. Pagamento -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                <div class="flex items-center border-b border-gray-100 pb-4 mb-6">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center mr-3 text-sm font-extrabold">2</div>
                    <h3 class="text-lg font-bold text-gray-900">Pagamento</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Forma de pagamento</label>
                        <select name="t_pag" class="block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 sm:text-sm cursor-pointer">
                            <option value="01" @selected(old('t_pag', '01') === '01')>01 — Dinheiro</option>
                            <option value="03" @selected(old('t_pag') === '03')>03 — Crédito</option>
                            <option value="04" @selected(old('t_pag') === '04')>04 — Débito</option>
                            <option value="17" @selected(old('t_pag') === '17')>17 — PIX</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Troco (dinheiro)</label>
                        <input type="number" step="0.01" name="v_troco" value="{{ old('v_troco') }}"
                               placeholder="Opcional"
                               class="block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>
            </div>

            <!-- 3. Destinatário -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-6 gap-2">
                    <div class="flex items-center">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center mr-3 text-sm font-extrabold">3</div>
                        <h3 class="text-lg font-bold text-gray-900">Destinatário <span class="text-sm font-medium text-gray-400">(opcional)</span></h3>
                    </div>
                    <button type="button" @click="limparDest()" class="text-xs font-semibold text-gray-500 hover:text-gray-800 hover:underline" x-show="clienteQuery || destDoc || destNome" x-cloak>Limpar</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Buscar cliente cadastrado</label>
                        <input type="text"
                               x-model="clienteQuery"
                               @input.debounce.250ms="buscar()"
                               placeholder="Nome ou CPF/CNPJ"
                               class="block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               autocomplete="off">
                        <ul class="mt-2 divide-y divide-gray-100 max-h-36 overflow-y-auto border border-gray-100 rounded-xl bg-white shadow-sm" x-show="resultados.length" x-cloak>
                            <template x-for="c in resultados" :key="c.id">
                                <li>
                                    <button type="button" @click="selecionar(c)" class="w-full text-left px-4 py-2.5 hover:bg-blue-50/50 text-sm transition-colors">
                                        <span class="font-medium text-gray-900" x-text="c.razao_social"></span>
                                        <span class="block text-xs text-gray-400 font-mono" x-text="c.cnpj"></span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">CPF/CNPJ destinatário</label>
                        <input type="text" name="dest_doc" x-model="destDoc"
                               class="block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Nome destinatário</label>
                        <input type="text" name="dest_nome" x-model="destNome"
                               class="block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-2 border-t border-gray-100">
                <p class="text-sm font-semibold text-gray-600" x-text="'Total da nota: ' + fmt(total)"></p>
                <div class="flex flex-col-reverse sm:flex-row gap-3">
                    <a href="{{ route('nfces.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 text-gray-600 font-bold text-sm hover:text-gray-900 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl hover:from-emerald-600 hover:to-emerald-700 shadow-md hover:shadow-lg font-bold text-sm uppercase tracking-wide transition-all transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        Emitir NFC-e
                    </button>
                </div>
            </div>
        </form>
    </div>

    <style>.animate-fade-in-up {
            animation: fadeInUp 0.4s ease-out forwards;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <script>
        function nfceAvulsaForm() {
            const oldItens = @json(old('itens'));
            return {
                clientesBuscarUrl: @json(route('clientes.buscar')),
                clienteQuery: '',
                resultados: [],
                destDoc: @json(old('dest_doc', '')),
                destNome: @json(old('dest_nome', '')),
                _keySeq: 0,
                itens: [],
                init() {
                    if (Array.isArray(oldItens) && oldItens.length) {
                        this.itens = oldItens.map((i) => this.normalizeItem(i));
                    } else {
                        this.itens = [this.emptyItem()];
                    }
                },
                emptyItem() {
                    return this.normalizeItem({
                        descricao: '',
                        ncm: '',
                        cfop: '5102',
                        csosn: '102',
                        unidade: 'UN',
                        quantidade: 1,
                        valor_unitario: '',
                    });
                },
                normalizeItem(i) {
                    this._keySeq += 1;
                    return {
                        _key: this._keySeq,
                        descricao: i.descricao ?? '',
                        ncm: i.ncm ?? '',
                        cfop: i.cfop ?? '5102',
                        csosn: i.csosn ?? '102',
                        unidade: i.unidade ?? 'UN',
                        quantidade: i.quantidade !== undefined && i.quantidade !== null && i.quantidade !== '' ? Number(i.quantidade) : 1,
                        valor_unitario: i.valor_unitario !== undefined && i.valor_unitario !== null && i.valor_unitario !== '' ? Number(i.valor_unitario) : '',
                    };
                },
                addItem() {
                    this.itens.push(this.emptyItem());
                    this.$nextTick(() => {
                        const inputs = this.$root.querySelectorAll('input[name$="[descricao]"]');
                        const last = inputs[inputs.length - 1];
                        last?.focus();
                    });
                },
                removeItem(idx) {
                    if (this.itens.length <= 1) return;
                    this.itens.splice(idx, 1);
                },
                get total() {
                    return this.itens.reduce((s, i) => s + ((Number(i.quantidade) || 0) * (Number(i.valor_unitario) || 0)), 0);
                },
                fmt(v) {
                    return 'R$ ' + Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
                async buscar() {
                    const q = this.clienteQuery.trim();
                    if (!q) {
                        this.resultados = [];
                        return;
                    }
                    try {
                        const res = await fetch(this.clientesBuscarUrl + '?q=' + encodeURIComponent(q), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        this.resultados = await res.json();
                    } catch (e) {
                        this.resultados = [];
                    }
                },
                selecionar(c) {
                    this.destDoc = c.cnpj || '';
                    this.destNome = c.razao_social || '';
                    this.clienteQuery = c.razao_social || '';
                    this.resultados = [];
                },
                limparDest() {
                    this.clienteQuery = '';
                    this.resultados = [];
                    this.destDoc = '';
                    this.destNome = '';
                },
            };
        }
    </script>
</x-app-layout>
