<x-app-layout>
    <div class="space-y-6" data-turbo="false">
        <x-page-header
            title="PDV"
            subtitle="Venda com NFC-e (estoque e financeiro). Emissão avulsa em Fiscal → Cupons."
        >
            <x-slot name="actions">
                <a href="{{ route('documentos.index', ['tipo' => 'venda', 'canal' => 'nfce']) }}"
                   class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Ver vendas
                </a>
            </x-slot>
            <x-slot name="help">
                <x-help-panel id="pdv">
                    <ul class="list-disc space-y-1 pl-4 text-sm text-slate-700">
                        <li><kbd class="rounded border px-1 text-xs">F2</kbd> busca produto · <kbd class="rounded border px-1 text-xs">F4</kbd> cliente · <kbd class="rounded border px-1 text-xs">F8</kbd> pagamento · <kbd class="rounded border px-1 text-xs">F12</kbd> finalizar.</li>
                        <li>Ao finalizar, a venda gera documento, baixa estoque e emite NFC-e em fila.</li>
                        <li>Esta tela não usa Turbo Drive para preservar o estado do carrinho Alpine.</li>
                    </ul>
                </x-help-panel>
            </x-slot>
        </x-page-header>

    <div class="max-w-6xl mx-auto space-y-6"
         x-data="pdvApp()"
         x-init="init()"
         @keydown.f2.window.prevent="$refs.busca?.focus()"
         @keydown.f4.window.prevent="$refs.clienteBusca?.focus()"
         @keydown.f8.window.prevent="$refs.formaPagamento?.focus()"
         @keydown.f12.window.prevent="finalizar()"
         @keydown.escape.window="onEscape()">

        @if($errors->any())
            <div class="rounded-2xl bg-rose-50 border border-rose-200 p-5 text-rose-700 text-sm shadow-sm">
                <ul class="list-disc pl-4 space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="rounded-2xl border p-5 text-sm shadow-sm"
             x-show="vendaStatus"
             x-cloak
             :class="vendaStatus?.erro ? 'bg-rose-50 border-rose-200 text-rose-800' : (vendaStatus?.pode_imprimir ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-amber-50 border-amber-200 text-amber-900')">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="space-y-1">
                    <p class="font-bold text-sm uppercase tracking-wide" x-text="vendaStatus?.titulo"></p>
                    <p class="font-medium" x-text="vendaStatus?.mensagem"></p>
                    <p class="text-xs opacity-80" x-show="vendaStatus?.mensagem_erro" x-text="vendaStatus?.mensagem_erro"></p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a x-show="vendaStatus?.pode_imprimir && vendaStatus?.imprimir_url"
                       :href="vendaStatus?.imprimir_url"
                       target="_blank"
                       class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 shadow-sm transition-all">
                        Imprimir DANFE
                    </a>
                    <a x-show="vendaStatus?.nfce_url"
                       :href="vendaStatus?.nfce_url"
                       class="px-4 py-2 rounded-xl border border-current/20 text-xs font-bold hover:bg-white/60 transition-all">
                        Ver NFC-e
                    </a>
                    <a x-show="vendaStatus?.documento_url"
                       :href="vendaStatus?.documento_url"
                       class="px-4 py-2 rounded-xl border border-current/20 text-xs font-bold hover:bg-white/60 transition-all">
                        Ver documento
                    </a>
                    <button type="button" @click="dismissVendaStatus()" class="px-3 py-2 text-xs font-semibold underline opacity-70 hover:opacity-100">Fechar</button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <div class="lg:col-span-3 space-y-6">
                <div class="bg-white border border-gray-100 rounded-2xl p-5 sm:p-6 shadow-sm">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-2">Buscar produto (EAN / SKU / descrição)</label>
                    <div class="flex gap-2">
                        <input type="text"
                               x-ref="busca"
                               x-model="query"
                               @input.debounce.250ms="buscarDinamico()"
                               @keydown.enter.prevent="buscarOuAdicionar()"
                               placeholder="Escaneie ou digite — resultados ao digitar"
                               class="flex-1 rounded-xl border-gray-200 bg-gray-50 text-lg focus:border-blue-500 focus:ring-blue-500"
                               autocomplete="off">
                        <button type="button" @click="buscarOuAdicionar()" class="px-5 py-2.5 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-bold text-sm transition-all shadow-sm">Buscar</button>
                    </div>
                    <ul class="mt-3 divide-y divide-gray-100 max-h-48 overflow-y-auto border border-gray-100 rounded-xl bg-white" x-show="resultados.length" x-cloak>
                        <template x-for="p in resultados" :key="p.id">
                            <li>
                                <button type="button" @click="addProduto(p)" class="w-full text-left px-4 py-2.5 hover:bg-blue-50/50 flex justify-between gap-2 text-sm transition-colors">
                                    <span>
                                        <span class="font-medium text-gray-900" x-text="p.descricao"></span>
                                        <span class="text-gray-400 block text-xs" x-text="(p.sku || '') + (p.ean ? ' · ' + p.ean : '')"></span>
                                    </span>
                                    <span class="font-mono text-gray-700 font-semibold" x-text="fmt(p.preco_venda)"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                    <table class="min-w-full text-sm">
                        <thead>
                        <tr class="bg-gray-50/80">
                            <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Item</th>
                            <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider">Qtd</th>
                            <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider">Unit.</th>
                            <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                        <template x-for="(item, idx) in itens" :key="item.produto_id + '-' + idx">
                            <tr class="hover:bg-blue-50/30 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900" x-text="item.descricao"></td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" @click="item.quantidade = Math.max(0.001, +(item.quantidade - 1).toFixed(3))" class="w-7 h-7 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-600">−</button>
                                        <input type="number" step="0.001" min="0.001" x-model.number="item.quantidade" class="w-16 text-right rounded-lg border-gray-200 bg-gray-50 py-1 text-sm">
                                        <button type="button" @click="item.quantidade = +(item.quantidade + 1).toFixed(3)" class="w-7 h-7 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-600">+</button>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-gray-700" x-text="fmt(item.valor_unitario)"></td>
                                <td class="px-4 py-3 text-right font-mono font-extrabold text-gray-900" x-text="fmt(item.quantidade * item.valor_unitario)"></td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" @click="itens.splice(idx, 1)" class="text-rose-600 text-xs font-semibold hover:underline">Remover</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!itens.length">
                            <td colspan="5" class="px-4 py-12 text-center text-gray-400 font-medium">Carrinho vazio — busque um produto.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white border border-gray-100 rounded-2xl p-5 sm:p-6 shadow-sm space-y-5 sticky top-6">
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-5 border border-blue-100">
                        <p class="text-[11px] uppercase tracking-wider text-blue-600 font-bold mb-1">Total</p>
                        <p class="text-3xl sm:text-4xl font-extrabold text-blue-900 font-mono tracking-tight" x-text="fmt(subtotal)"></p>
                    </div>

                    <div class="space-y-3 border-t border-gray-100 pt-4">
                        <div class="flex items-center justify-between gap-2">
                            <label class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Cliente (opcional)</label>
                            <button type="button" @click="limparDestinatario()" class="text-[11px] font-semibold text-gray-500 hover:underline" x-show="clienteId || destDoc || destNome || clienteQuery" x-cloak>Limpar</button>
                        </div>
                        <input type="text"
                               x-ref="clienteBusca"
                               x-model="clienteQuery"
                               @input.debounce.250ms="buscarClientes()"
                               placeholder="Buscar cliente (nome ou CPF/CNPJ)"
                               class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm focus:border-blue-500 focus:ring-blue-500"
                               autocomplete="off">
                        <ul class="divide-y divide-gray-100 max-h-36 overflow-y-auto border border-gray-100 rounded-xl" x-show="clientesResultados.length" x-cloak>
                            <template x-for="c in clientesResultados" :key="c.id">
                                <li>
                                    <button type="button" @click="selecionarCliente(c)" class="w-full text-left px-3 py-2.5 hover:bg-blue-50/50 text-sm transition-colors">
                                        <span class="font-medium text-gray-900" x-text="c.razao_social"></span>
                                        <span class="block text-xs text-gray-400 font-mono" x-text="c.cnpj"></span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">CPF/CNPJ destinatário</label>
                            <input type="text" x-model="destDoc" @input="clienteId = null" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm font-mono" autocomplete="off">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Nome destinatário</label>
                            <input type="text" x-model="destNome" @input="clienteId = null" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm" autocomplete="off">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-2">Forma de pagamento</label>
                        <select x-ref="formaPagamento" x-model="formaId" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm focus:border-blue-500 focus:ring-blue-500 cursor-pointer">
                            <template x-for="f in formas" :key="f.id">
                                <option :value="String(f.id)" x-text="f.nome"></option>
                            </template>
                        </select>
                    </div>

                    <div class="text-sm text-gray-600 bg-gray-50 rounded-xl p-4 space-y-1 border border-gray-100" x-show="formaSelecionada" x-cloak>
                        <p>
                            <span class="text-gray-500">Liquidação:</span>
                            <span class="font-semibold" x-text="formaSelecionada?.tipo_liquidacao === 'avista' ? 'À vista' : ('Prazo · ' + formaSelecionada?.parcelas + 'x · D+' + formaSelecionada?.dias_recebimento)"></span>
                        </p>
                        <p x-show="formaSelecionada && formaSelecionada.juros_percentual > 0">
                            <span class="text-gray-500">Com juros:</span>
                            <span class="font-mono font-bold" x-text="fmt(totalComJuros)"></span>
                            <span class="text-xs text-gray-400" x-text="'(' + formaSelecionada.juros_percentual + '%)'"></span>
                        </p>
                    </div>

                    <p class="text-sm text-rose-600 font-medium" x-text="erro" x-show="erro" x-cloak></p>

                    <button type="button"
                            @click="finalizar()"
                            :disabled="enviando || !itens.length || !formaId"
                            class="w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 disabled:opacity-50 text-white font-bold text-sm uppercase tracking-wide shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 disabled:transform-none disabled:hover:shadow-md">
                        <span x-show="!enviando">Finalizar venda (NFC-e)</span>
                        <span x-show="enviando" x-cloak>Enviando…</span>
                    </button>

                    <div class="flex flex-wrap justify-center gap-2 pt-1">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-gray-500 text-[10px] font-bold tracking-wide">F2 busca</span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-gray-500 text-[10px] font-bold tracking-wide">F4 cliente</span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-gray-500 text-[10px] font-bold tracking-wide">F8 pagto</span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-gray-500 text-[10px] font-bold tracking-wide">F12 finalizar</span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-gray-500 text-[10px] font-bold tracking-wide">Esc limpa</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .animate-fade-in-up {
            animation: fadeInUp 0.4s ease-out forwards;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <script>
        function pdvApp() {
            return {
                query: '',
                resultados: [],
                itens: [],
                formas: @json($formas->values()),
                formaId: @json((string) ($formas->first()['id'] ?? '')),
                buscarUrl: @json($buscarUrl),
                finalizarUrl: @json($finalizarUrl),
                clientesBuscarUrl: @json($clientesBuscarUrl),
                enviando: false,
                erro: '',
                clienteQuery: '',
                clientesResultados: [],
                clienteId: null,
                destDoc: '',
                destNome: '',
                vendaStatus: null,
                _pollTimer: null,
                _pollStartedAt: 0,
                get subtotal() {
                    return this.itens.reduce((s, i) => s + (Number(i.quantidade) * Number(i.valor_unitario)), 0);
                },
                get formaSelecionada() {
                    return this.formas.find(f => String(f.id) === String(this.formaId)) || null;
                },
                get totalComJuros() {
                    const f = this.formaSelecionada;
                    if (!f) return this.subtotal;
                    const j = Number(f.juros_percentual || 0);
                    return Math.round(this.subtotal * (1 + j / 100) * 100) / 100;
                },
                init() {
                    this.$nextTick(() => this.$refs.busca?.focus());
                },
                fmt(v) {
                    return 'R$ ' + Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
                onEscape() {
                    if (this.clientesResultados.length || this.clienteQuery) {
                        this.clientesResultados = [];
                        this.clienteQuery = '';
                        return;
                    }
                    this.query = '';
                    this.resultados = [];
                },
                async buscarDinamico() {
                    const q = this.query.trim();
                    if (!q) {
                        this.resultados = [];
                        return;
                    }
                    try {
                        const res = await fetch(this.buscarUrl + '?q=' + encodeURIComponent(q), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        this.resultados = await res.json();
                    } catch (e) {
                        this.resultados = [];
                    }
                },
                async buscarOuAdicionar() {
                    this.erro = '';
                    const q = this.query.trim();
                    if (!q) return;
                    const res = await fetch(this.buscarUrl + '?q=' + encodeURIComponent(q), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    this.resultados = data;
                    if (data.length === 1 && (data[0].ean === q || data[0].sku === q)) {
                        this.addProduto(data[0]);
                    }
                },
                addProduto(p) {
                    const existing = this.itens.find(i => i.produto_id === p.id);
                    if (existing) {
                        existing.quantidade = +(Number(existing.quantidade) + 1).toFixed(3);
                    } else {
                        this.itens.push({
                            produto_id: p.id,
                            descricao: p.descricao,
                            quantidade: 1,
                            valor_unitario: Number(p.preco_venda),
                        });
                    }
                    this.query = '';
                    this.resultados = [];
                    this.$nextTick(() => this.$refs.busca?.focus());
                },
                async buscarClientes() {
                    const q = this.clienteQuery.trim();
                    if (!q) {
                        this.clientesResultados = [];
                        return;
                    }
                    try {
                        const res = await fetch(this.clientesBuscarUrl + '?q=' + encodeURIComponent(q), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        this.clientesResultados = await res.json();
                    } catch (e) {
                        this.clientesResultados = [];
                    }
                },
                selecionarCliente(c) {
                    this.clienteId = c.id;
                    this.destDoc = c.cnpj || '';
                    this.destNome = c.razao_social || '';
                    this.clienteQuery = c.razao_social || '';
                    this.clientesResultados = [];
                },
                limparDestinatario() {
                    this.clienteId = null;
                    this.destDoc = '';
                    this.destNome = '';
                    this.clienteQuery = '';
                    this.clientesResultados = [];
                },
                limparVenda() {
                    this.itens = [];
                    this.limparDestinatario();
                    this.query = '';
                    this.resultados = [];
                    this.erro = '';
                },
                dismissVendaStatus() {
                    this.stopPoll();
                    this.vendaStatus = null;
                },
                stopPoll() {
                    if (this._pollTimer) {
                        clearInterval(this._pollTimer);
                        this._pollTimer = null;
                    }
                },
                startPoll(statusUrl) {
                    this.stopPoll();
                    this._pollStartedAt = Date.now();
                    this._pollTimer = setInterval(() => this.pollStatus(statusUrl), 2000);
                    this.pollStatus(statusUrl);
                },
                async pollStatus(statusUrl) {
                    if (Date.now() - this._pollStartedAt > 120000) {
                        this.stopPoll();
                        if (this.vendaStatus) {
                            this.vendaStatus.mensagem = 'Ainda processando. Use os links para acompanhar.';
                        }
                        return;
                    }
                    try {
                        const res = await fetch(statusUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        this.applyStatus(data);
                        if (data.terminal) {
                            this.stopPoll();
                        }
                    } catch (e) {
                        // mantém poll
                    }
                },
                applyStatus(data) {
                    const erro = data.documento_status === 'erro' || data.nfce_status === 'rejeitada';
                    let titulo = 'Venda enviada';
                    let mensagem = 'Aguardando autorização da NFC-e…';
                    if (data.pode_imprimir) {
                        titulo = 'NFC-e autorizada';
                        mensagem = data.nfce_numero
                            ? ('Nota nº ' + data.nfce_numero + ' pronta para impressão.')
                            : 'DANFE disponível para impressão.';
                    } else if (erro) {
                        titulo = 'Falha na emissão';
                        mensagem = 'A venda não foi autorizada.';
                    } else if (data.nfce_status === 'pendente_transmissao') {
                        titulo = 'Contingência';
                        mensagem = 'NFC-e gerada em contingência — imprima o DANFE.';
                    }
                    this.vendaStatus = {
                        titulo,
                        mensagem,
                        mensagem_erro: data.mensagem_erro || null,
                        erro,
                        pode_imprimir: !!data.pode_imprimir,
                        imprimir_url: data.imprimir_url,
                        nfce_url: data.nfce_url,
                        documento_url: data.documento_url,
                    };
                },
                async finalizar() {
                    if (!this.itens.length || !this.formaId || this.enviando) return;
                    this.enviando = true;
                    this.erro = '';
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').content;
                        const body = {
                            forma_pagamento_id: Number(this.formaId),
                            itens: this.itens.map(i => ({
                                produto_id: i.produto_id,
                                quantidade: Number(i.quantidade),
                                valor_unitario: Number(i.valor_unitario),
                            })),
                            dest_doc: this.destDoc || null,
                            dest_nome: this.destNome || null,
                        };
                        if (this.clienteId) {
                            body.cliente_id = Number(this.clienteId);
                        }
                        const res = await fetch(this.finalizarUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(body),
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            this.erro = data.message || 'Falha ao finalizar venda.';
                            this.enviando = false;
                            return;
                        }
                        this.limparVenda();
                        this.vendaStatus = {
                            titulo: 'Venda enviada',
                            mensagem: 'Aguardando autorização da NFC-e…',
                            mensagem_erro: null,
                            erro: false,
                            pode_imprimir: false,
                            imprimir_url: null,
                            nfce_url: data.nfce_url,
                            documento_url: data.documento_url,
                        };
                        this.enviando = false;
                        this.$nextTick(() => this.$refs.busca?.focus());
                        if (data.status_url) {
                            this.startPoll(data.status_url);
                        }
                    } catch (e) {
                        this.erro = 'Erro de rede ao finalizar.';
                        this.enviando = false;
                    }
                },
            };
        }
    </script>
    </div>
</x-app-layout>
