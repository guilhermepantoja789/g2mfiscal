<x-app-layout>
    <div class="flex h-full min-h-0 flex-col gap-2"
         data-turbo="false"
         x-data="pdvApp()"
         x-init="init()"
         @keydown.f2.window.prevent="$refs.busca?.focus()"
         @keydown.f4.window.prevent="$refs.clienteBusca?.focus()"
         @keydown.f8.window.prevent="$refs.pagamentosBox?.querySelector('select')?.focus()"
         @keydown.f10.window.prevent="$dispatch('pdv-toggle-opera')"
         @keydown.f12.window.prevent="finalizar()"
         @keydown.escape.window="onEscape()">

        {{-- Top bar --}}
        <div class="flex shrink-0 flex-wrap items-center justify-between gap-2">
            <div class="min-w-0">
                <h2 class="text-lg font-bold tracking-tight text-slate-900">PDV</h2>
                <p class="truncate text-[11px] text-slate-500">
                    <span x-show="$store.pdv.opera" x-cloak>Modo operação · tela cheia</span>
                    <span x-show="!$store.pdv.opera" x-cloak>Venda com NFC-e · estoque e financeiro</span>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-1.5">
                <button type="button"
                        @click="$dispatch('pdv-toggle-opera')"
                        class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition"
                        :class="$store.pdv.opera
                            ? 'border-brand/30 bg-brand-soft text-brand'
                            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                        :title="$store.pdv.opera ? 'Sair do modo operação (F10)' : 'Entrar no modo operação (F10)'">
                    <x-icon name="computer-desktop" class="h-4 w-4" />
                    <span x-text="$store.pdv.opera ? 'Sair tela cheia' : 'Tela cheia'"></span>
                    <kbd class="rounded border border-current/20 px-1 py-0.5 font-mono text-[10px] opacity-70">F10</kbd>
                </button>
                <a href="{{ route('documentos.index', ['tipo' => 'venda', 'canal' => 'nfce']) }}"
                   class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                    Vendas
                </a>
                <x-help-panel id="pdv">
                    <ul class="list-disc space-y-1 pl-4 text-sm text-slate-700">
                        <li><kbd class="rounded border px-1 text-xs">F2</kbd> busca · <kbd class="rounded border px-1 text-xs">F4</kbd> cliente · <kbd class="rounded border px-1 text-xs">F8</kbd> pagamento · <kbd class="rounded border px-1 text-xs">F12</kbd> finalizar.</li>
                        <li><kbd class="rounded border px-1 text-xs">F10</kbd> modo operação (tela cheia, sem menu).</li>
                        <li>↑/↓ navega resultados · Enter adiciona.</li>
                        <li>A página não rola: só o carrinho e o painel lateral.</li>
                    </ul>
                </x-help-panel>
            </div>
        </div>

        @if($errors->any())
            <div class="shrink-0 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                <ul class="list-disc space-y-1 pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Status pós-venda --}}
        <div class="shrink-0 rounded-xl border px-3 py-2 text-sm shadow-sm"
             x-show="vendaStatus"
             x-cloak
             :class="vendaStatus?.erro ? 'bg-rose-50 border-rose-200 text-rose-800' : (vendaStatus?.pode_imprimir ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-amber-50 border-amber-200 text-amber-900')">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0 space-y-0.5">
                    <p class="text-[11px] font-bold uppercase tracking-wide" x-text="vendaStatus?.titulo"></p>
                    <p class="truncate font-medium" x-text="vendaStatus?.mensagem"></p>
                    <p class="truncate text-xs opacity-80" x-show="vendaStatus?.mensagem_erro" x-text="vendaStatus?.mensagem_erro"></p>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <a x-show="vendaStatus?.pode_imprimir && vendaStatus?.imprimir_url"
                       :href="vendaStatus?.imprimir_url"
                       target="_blank"
                       class="rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-bold text-white hover:bg-emerald-700">
                        Imprimir
                    </a>
                    <a x-show="vendaStatus?.nfce_url"
                       :href="vendaStatus?.nfce_url"
                       class="rounded-lg border border-current/20 px-2.5 py-1 text-xs font-bold hover:bg-white/60">
                        NFC-e
                    </a>
                    <a x-show="vendaStatus?.documento_url"
                       :href="vendaStatus?.documento_url"
                       class="rounded-lg border border-current/20 px-2.5 py-1 text-xs font-bold hover:bg-white/60">
                        Doc
                    </a>
                    <button type="button" @click="dismissVendaStatus()" class="px-2 py-1 text-xs font-semibold underline opacity-70 hover:opacity-100">Fechar</button>
                </div>
            </div>
        </div>

        {{-- Área principal (sem scroll de página) --}}
        <div class="grid min-h-0 flex-1 grid-cols-1 gap-3 lg:grid-cols-12">
            {{-- Busca + carrinho --}}
            <div class="flex min-h-0 flex-col gap-2 lg:col-span-8">
                <div class="relative shrink-0 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        Produto <span class="font-normal normal-case text-slate-400">(EAN · SKU · descrição)</span>
                    </label>
                    <div class="flex gap-2">
                        <input type="text"
                               x-ref="busca"
                               x-model="query"
                               @input.debounce.250ms="buscarDinamico()"
                               @keydown.enter.prevent="buscarOuAdicionar()"
                               @keydown.arrow-down.prevent="moverResultado(1)"
                               @keydown.arrow-up.prevent="moverResultado(-1)"
                               placeholder="Escaneie ou digite…"
                               class="min-w-0 flex-1 rounded-xl border-slate-200 bg-slate-50 text-base font-medium text-slate-900 placeholder:text-slate-400 focus:border-brand focus:ring-brand sm:text-lg"
                               autocomplete="off">
                        <button type="button"
                                @click="buscarOuAdicionar()"
                                class="shrink-0 rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800">
                            Buscar
                        </button>
                    </div>

                    <div class="absolute left-3 right-3 top-full z-30 mt-1 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
                         x-show="query.trim() && (buscando || resultados.length || buscaVazia)"
                         x-cloak>
                        <p class="px-4 py-2.5 text-sm text-slate-400" x-show="buscando" x-cloak>Buscando…</p>
                        <p class="px-4 py-2.5 text-sm text-slate-500" x-show="!buscando && buscaVazia" x-cloak>
                            Nenhum produto para “<span class="font-medium" x-text="query.trim()"></span>”.
                        </p>
                        <ul class="max-h-52 divide-y divide-slate-100 overflow-y-auto" x-show="!buscando && resultados.length" x-cloak>
                            <template x-for="(p, idx) in resultados" :key="p.id">
                                <li>
                                    <button type="button"
                                            @click="addProduto(p)"
                                            @mouseenter="resultadoIndex = idx"
                                            class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-left text-sm transition-colors"
                                            :class="resultadoIndex === idx ? 'bg-brand-soft' : 'hover:bg-slate-50'">
                                        <span class="min-w-0">
                                            <span class="block truncate font-semibold text-slate-900" x-text="p.descricao"></span>
                                            <span class="mt-0.5 flex flex-wrap gap-x-2 text-xs text-slate-400">
                                                <span x-show="p.sku" x-text="'SKU ' + p.sku"></span>
                                                <span x-show="p.ean" x-text="'EAN ' + p.ean"></span>
                                                <span x-show="p.estoque_atual != null" x-text="'Est. ' + Number(p.estoque_atual).toLocaleString('pt-BR')"></span>
                                            </span>
                                        </span>
                                        <span class="shrink-0 font-mono text-sm font-bold text-brand" x-text="fmt(p.preco_venda)"></span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-3 py-2">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            Carrinho
                            <span class="ml-1 font-semibold normal-case text-slate-400" x-text="itens.length ? (itens.length + (itens.length === 1 ? ' item' : ' itens')) : ''"></span>
                        </p>
                        <button type="button"
                                @click="limparVenda()"
                                x-show="itens.length"
                                x-cloak
                                class="text-xs font-semibold text-rose-600 hover:underline">
                            Limpar
                        </button>
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="sticky top-0 z-10 bg-slate-50/95 backdrop-blur">
                            <tr class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="px-3 py-2 text-left">Item</th>
                                <th class="px-3 py-2 text-right">Qtd</th>
                                <th class="px-3 py-2 text-right">Unit.</th>
                                <th class="px-3 py-2 text-right">Total</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                            <template x-for="(item, idx) in itens" :key="item.produto_id + '-' + idx">
                                <tr class="transition-colors hover:bg-brand-soft/40">
                                    <td class="px-3 py-2.5 font-medium text-slate-900" x-text="item.descricao"></td>
                                    <td class="px-3 py-2.5 text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button"
                                                    @click="item.quantidade = Math.max(0.001, +(item.quantidade - 1).toFixed(3))"
                                                    class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">−</button>
                                            <input type="number" step="0.001" min="0.001" x-model.number="item.quantidade"
                                                   class="w-14 rounded-lg border-slate-200 bg-slate-50 py-1 text-right text-sm tabular-nums">
                                            <button type="button"
                                                    @click="item.quantidade = +(item.quantidade + 1).toFixed(3)"
                                                    class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">+</button>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono tabular-nums text-slate-600" x-text="fmt(item.valor_unitario)"></td>
                                    <td class="px-3 py-2.5 text-right font-mono text-sm font-extrabold tabular-nums text-slate-900" x-text="fmt(item.quantidade * item.valor_unitario)"></td>
                                    <td class="px-3 py-2.5 text-right">
                                        <button type="button" @click="itens.splice(idx, 1)" class="text-xs font-semibold text-rose-600 hover:underline">×</button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="!itens.length">
                                <td colspan="5" class="px-4 py-10 text-center">
                                    <p class="text-sm font-medium text-slate-400">Carrinho vazio</p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        <kbd class="rounded border border-slate-200 bg-slate-50 px-1">F2</kbd> busca produto
                                    </p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Painel lateral --}}
            <div class="flex min-h-0 flex-col lg:col-span-4">
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="min-h-0 flex-1 space-y-3 overflow-y-auto p-3 sm:p-4">
                        <div class="rounded-xl border border-brand/20 bg-brand-soft p-4">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-brand">Total</p>
                            <p class="mt-0.5 font-mono text-3xl font-extrabold tracking-tight text-brand tabular-nums xl:text-4xl" x-text="fmt(subtotal)"></p>
                        </div>

                        <div class="space-y-2 border-t border-slate-100 pt-3">
                            <div class="flex items-center justify-between gap-2">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Cliente</label>
                                <button type="button"
                                        @click="limparDestinatario()"
                                        class="text-[11px] font-semibold text-slate-500 hover:underline"
                                        x-show="clienteId || destDoc || destNome || clienteQuery"
                                        x-cloak>Limpar</button>
                            </div>
                            <input type="text"
                                   x-ref="clienteBusca"
                                   x-model="clienteQuery"
                                   @input.debounce.250ms="buscarClientes()"
                                   placeholder="Nome ou CPF/CNPJ"
                                   class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-brand focus:ring-brand"
                                   autocomplete="off">
                            <ul class="max-h-28 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-100"
                                x-show="clientesResultados.length"
                                x-cloak>
                                <template x-for="c in clientesResultados" :key="c.id">
                                    <li>
                                        <button type="button" @click="selecionarCliente(c)" class="w-full px-3 py-2 text-left text-sm transition-colors hover:bg-brand-soft/60">
                                            <span class="font-medium text-slate-900" x-text="c.razao_social"></span>
                                            <span class="block font-mono text-xs text-slate-400" x-text="c.cnpj"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="mb-0.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">CPF/CNPJ</label>
                                    <input type="text" x-model="destDoc" @input="clienteId = null"
                                           class="w-full rounded-lg border-slate-200 bg-slate-50 font-mono text-sm" autocomplete="off">
                                </div>
                                <div>
                                    <label class="mb-0.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Nome</label>
                                    <input type="text" x-model="destNome" @input="clienteId = null"
                                           class="w-full rounded-lg border-slate-200 bg-slate-50 text-sm" autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-3" x-ref="pagamentosBox">
                            <div class="mb-1.5 flex items-center justify-between">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Pagamentos</label>
                                <button type="button" @click="addPagamento()" class="text-xs font-bold text-brand hover:text-brand-hover">+ Forma</button>
                            </div>
                            <div class="space-y-2">
                                <template x-for="(pag, idx) in pagamentos" :key="pag._key">
                                    <div class="space-y-1.5 rounded-xl border border-slate-100 bg-slate-50 p-2.5">
                                        <div class="flex gap-2">
                                            <select x-model="pag.forma_pagamento_id"
                                                    class="min-w-0 flex-1 cursor-pointer rounded-lg border-slate-200 bg-white text-sm focus:border-brand focus:ring-brand">
                                                <template x-for="f in formas" :key="f.id">
                                                    <option :value="String(f.id)" x-text="f.nome"></option>
                                                </template>
                                            </select>
                                            <button type="button"
                                                    @click="removePagamento(idx)"
                                                    x-show="pagamentos.length > 1"
                                                    class="px-2 text-sm font-bold text-rose-500 hover:text-rose-700"
                                                    title="Remover">×</button>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <input type="number" step="0.01" min="0.01" x-model.number="pag.valor"
                                                   class="w-full rounded-lg border-slate-200 bg-white text-right font-mono text-sm"
                                                   placeholder="Valor">
                                            <template x-if="formaCodigo(pag.forma_pagamento_id) === '01'">
                                                <input type="number" step="0.01" min="0" x-model.number="pag.v_troco"
                                                       class="w-24 rounded-lg border-slate-200 bg-white text-right font-mono text-sm"
                                                       placeholder="Troco">
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="mt-1.5 flex justify-between text-xs">
                                <span class="text-slate-500">Restante</span>
                                <span class="font-mono font-bold tabular-nums"
                                      :class="Math.abs(restantePagamento) < 0.01 ? 'text-emerald-600' : 'text-amber-600'"
                                      x-text="fmt(restantePagamento)"></span>
                            </div>
                        </div>

                        <p class="text-sm font-medium text-rose-600" x-text="erro" x-show="erro" x-cloak></p>
                    </div>

                    <div class="shrink-0 border-t border-slate-100 p-3">
                        <button type="button"
                                @click="finalizar()"
                                :disabled="enviando || !itens.length || !pagamentosValidos"
                                class="w-full rounded-xl bg-brand py-3.5 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:bg-brand-hover disabled:opacity-50 disabled:shadow-none">
                            <span x-show="!enviando">Finalizar · F12</span>
                            <span x-show="enviando" x-cloak>Enviando…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Dock (dentro do fluxo — sem fixed, sem rolagem extra) --}}
        <div class="flex shrink-0 items-center justify-center gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-white px-2 py-1.5 shadow-sm sm:gap-2">
            <button type="button" @click="$refs.busca?.focus()"
                    class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-100">
                <kbd class="rounded border border-slate-200 bg-slate-50 px-1 font-mono text-[10px] text-slate-500">F2</kbd> Busca
            </button>
            <button type="button" @click="$refs.clienteBusca?.focus()"
                    class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-100">
                <kbd class="rounded border border-slate-200 bg-slate-50 px-1 font-mono text-[10px] text-slate-500">F4</kbd> Cliente
            </button>
            <button type="button" @click="$refs.pagamentosBox?.querySelector('select')?.focus()"
                    class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-100">
                <kbd class="rounded border border-slate-200 bg-slate-50 px-1 font-mono text-[10px] text-slate-500">F8</kbd> Pagto
            </button>
            <button type="button" @click="finalizar()"
                    class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-brand-soft px-2 py-1 text-[11px] font-bold text-brand hover:bg-brand/15">
                <kbd class="rounded border border-brand/30 bg-white px-1 font-mono text-[10px] text-brand">F12</kbd> Finalizar
            </button>
            <button type="button" @click="$dispatch('pdv-toggle-opera')"
                    class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-100">
                <kbd class="rounded border border-slate-200 bg-slate-50 px-1 font-mono text-[10px] text-slate-500">F10</kbd>
                <span x-text="$store.pdv.opera ? 'Sair' : 'Tela cheia'"></span>
            </button>
            <button type="button" @click="onEscape()"
                    class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-100">
                <kbd class="rounded border border-slate-200 bg-slate-50 px-1 font-mono text-[10px] text-slate-500">Esc</kbd> Limpar
            </button>
        </div>
    </div>

    <script>
        function pdvApp() {
            return {
                query: '',
                resultados: [],
                resultadoIndex: -1,
                buscando: false,
                buscaVazia: false,
                itens: [],
                formas: @json($formas->values()),
                pagamentos: [{
                    _key: 1,
                    forma_pagamento_id: @json((string) ($formas->first()['id'] ?? '')),
                    valor: null,
                    v_troco: null,
                }],
                _pagKey: 1,
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
                get somaPagamentos() {
                    return this.pagamentos.reduce((s, p) => s + (Number(p.valor) || 0), 0);
                },
                get restantePagamento() {
                    return Math.round((this.subtotal - this.somaPagamentos) * 100) / 100;
                },
                get pagamentosValidos() {
                    if (!this.pagamentos.length || this.subtotal <= 0) return false;
                    if (this.pagamentos.some(p => !p.forma_pagamento_id)) return false;
                    if (this.pagamentos.length === 1) {
                        const v = Number(this.pagamentos[0].valor);
                        if (!(v > 0)) return true;
                        return Math.abs(v - this.subtotal) < 0.01;
                    }
                    if (this.pagamentos.some(p => !(Number(p.valor) > 0))) return false;
                    return Math.abs(this.restantePagamento) < 0.01;
                },
                formaCodigo(id) {
                    const f = this.formas.find(x => String(x.id) === String(id));
                    return f ? String(f.codigo) : '';
                },
                digitsOnly(v) {
                    return String(v || '').replace(/\D/g, '');
                },
                matchExato(p, q) {
                    if (!p || !q) return false;
                    if (p.sku === q || p.ean === q) return true;
                    const qd = this.digitsOnly(q);
                    if (qd && p.ean && this.digitsOnly(p.ean) === qd) return true;
                    return false;
                },
                addPagamento() {
                    const rest = this.restantePagamento > 0 ? this.restantePagamento : 0;
                    this._pagKey += 1;
                    this.pagamentos.push({
                        _key: this._pagKey,
                        forma_pagamento_id: String(this.formas[0]?.id || ''),
                        valor: rest > 0 ? rest : null,
                        v_troco: null,
                    });
                },
                removePagamento(idx) {
                    if (this.pagamentos.length <= 1) return;
                    this.pagamentos.splice(idx, 1);
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
                    this.resultadoIndex = -1;
                    this.buscaVazia = false;
                    this.erro = '';
                },
                moverResultado(delta) {
                    if (!this.resultados.length) return;
                    const len = this.resultados.length;
                    if (this.resultadoIndex < 0) {
                        this.resultadoIndex = delta > 0 ? 0 : len - 1;
                        return;
                    }
                    this.resultadoIndex = (this.resultadoIndex + delta + len) % len;
                },
                async fetchProdutos(q) {
                    const res = await fetch(this.buscarUrl + '?q=' + encodeURIComponent(q), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) {
                        throw new Error('Falha ao buscar produtos (' + res.status + ').');
                    }
                    const data = await res.json();
                    if (!Array.isArray(data)) {
                        throw new Error('Resposta inválida da busca.');
                    }
                    return data;
                },
                async buscarDinamico() {
                    const q = this.query.trim();
                    this.resultadoIndex = -1;
                    this.buscaVazia = false;
                    if (!q) {
                        this.resultados = [];
                        this.buscando = false;
                        return;
                    }
                    this.buscando = true;
                    try {
                        const data = await this.fetchProdutos(q);
                        if (this.query.trim() !== q) return;
                        this.resultados = data;
                        this.buscaVazia = data.length === 0;
                        this.resultadoIndex = data.length ? 0 : -1;
                        this.erro = '';
                    } catch (e) {
                        if (this.query.trim() !== q) return;
                        this.resultados = [];
                        this.buscaVazia = false;
                        this.erro = e.message || 'Erro ao buscar produtos.';
                    } finally {
                        if (this.query.trim() === q) {
                            this.buscando = false;
                        }
                    }
                },
                async buscarOuAdicionar() {
                    this.erro = '';
                    const q = this.query.trim();
                    if (!q) return;

                    if (this.resultados.length && this.resultadoIndex >= 0 && this.resultadoIndex < this.resultados.length) {
                        this.addProduto(this.resultados[this.resultadoIndex]);
                        return;
                    }

                    this.buscando = true;
                    try {
                        const data = await this.fetchProdutos(q);
                        this.resultados = data;
                        this.buscaVazia = data.length === 0;
                        this.resultadoIndex = data.length ? 0 : -1;

                        if (data.length === 1) {
                            this.addProduto(data[0]);
                        }
                    } catch (e) {
                        this.resultados = [];
                        this.buscaVazia = false;
                        this.erro = e.message || 'Erro ao buscar produtos.';
                    } finally {
                        this.buscando = false;
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
                    this.resultadoIndex = -1;
                    this.buscaVazia = false;
                    this.erro = '';
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
                        if (!res.ok) {
                            this.clientesResultados = [];
                            return;
                        }
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
                    this.resultadoIndex = -1;
                    this.buscaVazia = false;
                    this.erro = '';
                    this.pagamentos = [{
                        _key: ++this._pagKey,
                        forma_pagamento_id: String(this.formas[0]?.id || ''),
                        valor: null,
                        v_troco: null,
                    }];
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
                    if (!this.itens.length || !this.pagamentosValidos || this.enviando) return;
                    if (this.pagamentos.length === 1 && !(Number(this.pagamentos[0].valor) > 0)) {
                        this.pagamentos[0].valor = Math.round(this.subtotal * 100) / 100;
                    }
                    if (!this.pagamentosValidos) {
                        this.erro = 'Ajuste os valores das formas de pagamento.';
                        return;
                    }
                    this.enviando = true;
                    this.erro = '';
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').content;
                        const body = {
                            pagamentos: this.pagamentos.map(p => ({
                                forma_pagamento_id: Number(p.forma_pagamento_id),
                                valor: Number(p.valor),
                                v_troco: this.formaCodigo(p.forma_pagamento_id) === '01' && p.v_troco
                                    ? Number(p.v_troco)
                                    : null,
                            })),
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
</x-app-layout>
