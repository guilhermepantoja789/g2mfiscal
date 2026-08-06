<x-app-layout>
    <div class="max-w-5xl mx-auto space-y-6" x-data="docForm()">
        <h2 class="text-2xl font-bold text-gray-800">
            Novo Documento —
            {{ strtoupper($tipo) }} /
            {{ $canal === 'nfse' ? 'NFS-e' : ($canal === 'nfce' ? 'NFC-e' : 'NF-e Entrada') }}
        </h2>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-md p-4 text-sm">
                <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('documentos.store') }}" class="bg-white shadow rounded-lg p-6 space-y-6"
              @submit="beforeSubmit()">
            @csrf
            <input type="hidden" name="tipo" value="{{ $tipo }}">
            <input type="hidden" name="canal_fiscal" value="{{ $canal }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($canal === 'nfse' || $canal === 'nfce')
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cliente</label>
                        <select name="cliente_id" class="mt-1 block w-full rounded-md border-gray-300" @if($canal==='nfse') required @endif>
                            <option value="">—</option>
                            @foreach($clientes as $c)
                                <option value="{{ $c->id }}" @selected(old('cliente_id')==$c->id)>{{ $c->razao_social }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                        <select name="fornecedor_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">—</option>
                            @foreach($fornecedores as $f)
                                <option value="{{ $f->id }}" @selected(old('fornecedor_id')==$f->id)>{{ $f->razao_social }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="md:col-span-2 space-y-3" x-data>
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-medium text-gray-700">Pagamentos</label>
                        <button type="button" @click="addPagamento()" class="text-sm text-blue-600 font-semibold">+ Forma</button>
                    </div>
                    <template x-for="(pag, pidx) in pagamentos" :key="pag._key">
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-2 border rounded-md p-3">
                            <div class="md:col-span-3">
                                <label class="text-xs text-gray-500">Forma</label>
                                <select :name="`pagamentos[${pidx}][forma_pagamento_id]`" x-model="pag.forma_pagamento_id"
                                        class="block w-full rounded-md border-gray-300 text-sm" required>
                                    @foreach($formas as $forma)
                                        <option value="{{ $forma->id }}">{{ $forma->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500">Valor</label>
                                <input type="number" step="0.01" min="0.01"
                                       :name="`pagamentos[${pidx}][valor]`" x-model="pag.valor" required
                                       class="block w-full rounded-md border-gray-300 text-sm">
                            </div>
                            <div class="flex items-end">
                                <button type="button" @click="removePagamento(pidx)" class="text-xs text-red-600"
                                        x-show="pagamentos.length > 1">Remover</button>
                            </div>
                        </div>
                    </template>
                    <p class="text-xs text-gray-500">
                        Soma dos pagamentos deve ser igual ao total dos itens.
                        Restante: <span class="font-mono font-semibold" x-text="fmt(restante)"></span>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Vencimento (opcional)</label>
                    <input type="date" name="vencimento" value="{{ old('vencimento') }}" class="mt-1 block w-full rounded-md border-gray-300">
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-bold text-gray-800">Itens</h3>
                    <button type="button" @click="addItem()" class="text-sm text-blue-600 font-semibold">+ Item</button>
                </div>

                <template x-for="(item, index) in items" :key="index">
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-2 border rounded-md p-3 mb-2">
                        @if($canal === 'nfse')
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500">Serviço</label>
                                <select :name="`itens[${index}][servico_id]`" class="block w-full rounded-md border-gray-300 text-sm" x-model="item.servico_id" @change="onServico(index)" required>
                                    <option value="">Selecione</option>
                                    @foreach($servicos as $s)
                                        <option value="{{ $s->id }}" data-nome="{{ $s->nome }}" data-valor="{{ $s->valor_unitario }}">{{ $s->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500">Produto</label>
                                <select :name="`itens[${index}][produto_id]`" class="block w-full rounded-md border-gray-300 text-sm" x-model="item.produto_id" @change="onProduto(index)">
                                    <option value="">Selecione / avulso</option>
                                    @foreach($produtos as $p)
                                        <option value="{{ $p->id }}"
                                                data-desc="{{ preg_replace('/\s+/u', ' ', $p->descricao ?? '') }}"
                                                data-valor="{{ $p->preco_venda }}"
                                                data-ncm="{{ $p->ncm }}"
                                                data-cfop="{{ $p->cfop }}"
                                                data-csosn="{{ $p->csosn }}"
                                                data-un="{{ $p->unidade }}">{{ $p->descricao }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="md:col-span-2">
                            <label class="text-xs text-gray-500">Descrição</label>
                            <input type="text" :name="`itens[${index}][descricao]`" x-model="item.descricao" required class="block w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Qtd</label>
                            <input type="number" step="0.001" :name="`itens[${index}][quantidade]`" x-model="item.quantidade" required class="block w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Unitário</label>
                            <input type="number" step="0.01" :name="`itens[${index}][valor_unitario]`" x-model="item.valor_unitario" required class="block w-full rounded-md border-gray-300 text-sm">
                        </div>
                        @if($canal !== 'nfse')
                            <input type="hidden" :name="`itens[${index}][ncm]`" x-model="item.ncm">
                            <input type="hidden" :name="`itens[${index}][cfop]`" x-model="item.cfop">
                            <input type="hidden" :name="`itens[${index}][csosn]`" x-model="item.csosn">
                            <input type="hidden" :name="`itens[${index}][unidade]`" x-model="item.unidade">
                        @endif
                        <div class="md:col-span-6 text-right">
                            <button type="button" @click="removeItem(index)" class="text-xs text-red-600" x-show="items.length > 1">Remover</button>
                        </div>
                    </div>
                </template>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Observações</label>
                <textarea name="observacoes" rows="2" class="mt-1 block w-full rounded-md border-gray-300">{{ old('observacoes') }}</textarea>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('documentos.index') }}" class="px-4 py-2 text-gray-600">Cancelar</a>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-md font-bold">Salvar rascunho</button>
            </div>
        </form>
    </div>

    <script>
        function docForm() {
            const primeiraForma = @json((string) ($formas->first()?->id ?? ''));
            return {
                items: [{ produto_id: '', servico_id: '', descricao: '', quantidade: 1, valor_unitario: 0, ncm: '', cfop: '5102', csosn: '102', unidade: 'UN' }],
                pagamentos: [{ _key: 1, forma_pagamento_id: primeiraForma, valor: '' }],
                _pagKey: 1,
                get totalItens() {
                    return this.items.reduce((s, i) => s + (Number(i.quantidade) * Number(i.valor_unitario)), 0);
                },
                get somaPagamentos() {
                    return this.pagamentos.reduce((s, p) => s + (Number(p.valor) || 0), 0);
                },
                get restante() {
                    return Math.round((this.totalItens - this.somaPagamentos) * 100) / 100;
                },
                fmt(v) {
                    return 'R$ ' + Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
                addItem() {
                    this.items.push({ produto_id: '', servico_id: '', descricao: '', quantidade: 1, valor_unitario: 0, ncm: '', cfop: '5102', csosn: '102', unidade: 'UN' });
                },
                removeItem(i) { this.items.splice(i, 1); },
                addPagamento() {
                    const rest = this.restante > 0 ? this.restante.toFixed(2) : '';
                    this._pagKey += 1;
                    this.pagamentos.push({ _key: this._pagKey, forma_pagamento_id: primeiraForma, valor: rest });
                },
                removePagamento(i) {
                    if (this.pagamentos.length <= 1) return;
                    this.pagamentos.splice(i, 1);
                },
                beforeSubmit() {
                    if (this.pagamentos.length === 1 && !(Number(this.pagamentos[0].valor) > 0)) {
                        this.pagamentos[0].valor = this.totalItens.toFixed(2);
                    }
                },
                onProduto(i) {
                    const sel = document.querySelectorAll(`[name="itens[${i}][produto_id]"]`)[0];
                    const opt = sel?.selectedOptions?.[0];
                    if (!opt || !opt.value) return;
                    this.items[i].descricao = opt.dataset.desc || '';
                    this.items[i].valor_unitario = opt.dataset.valor || 0;
                    this.items[i].ncm = opt.dataset.ncm || '';
                    this.items[i].cfop = opt.dataset.cfop || '5102';
                    this.items[i].csosn = opt.dataset.csosn || '102';
                    this.items[i].unidade = opt.dataset.un || 'UN';
                    if (this.pagamentos.length === 1 && !this.pagamentos[0].valor) {
                        this.pagamentos[0].valor = this.totalItens.toFixed(2);
                    }
                },
                onServico(i) {
                    const sel = document.querySelectorAll(`[name="itens[${i}][servico_id]"]`)[0];
                    const opt = sel?.selectedOptions?.[0];
                    if (!opt || !opt.value) return;
                    this.items[i].descricao = opt.dataset.nome || '';
                    this.items[i].valor_unitario = opt.dataset.valor || 0;
                    if (this.pagamentos.length === 1 && !this.pagamentos[0].valor) {
                        this.pagamentos[0].valor = this.totalItens.toFixed(2);
                    }
                }
            }
        }
    </script>
</x-app-layout>
