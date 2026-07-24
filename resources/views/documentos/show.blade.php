<x-app-layout>
    <div class="space-y-6">
        <div class="flex flex-wrap justify-between items-start gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Documento #{{ $documento->id }}</h2>
                <p class="text-sm text-gray-500">{{ strtoupper($documento->tipo) }} · {{ $documento->canal_fiscal }} · {{ $documento->status_label }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('documentos.index') }}" class="px-3 py-2 text-sm text-gray-600">Voltar</a>
                @if(in_array($documento->status, ['rascunho', 'erro']))
                    <form method="POST" action="{{ route('documentos.confirmar', $documento->id) }}">
                        @csrf
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-md font-bold text-sm"
                                onclick="return confirm('Confirmar e processar (fiscal/estoque/financeiro)?')">
                            Confirmar
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 text-green-800 border border-green-200 rounded-md px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 text-red-700 border border-red-200 rounded-md px-4 py-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif
        @if($documento->mensagem_erro)
            <div class="bg-red-50 text-red-700 border border-red-200 rounded-md px-4 py-3 text-sm">{{ $documento->mensagem_erro }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="bg-white border rounded-lg p-4 shadow-sm space-y-2">
                <h3 class="font-bold text-gray-800">Resumo</h3>
                <p class="text-sm">Parceiro: <strong>{{ $documento->cliente?->razao_social ?? $documento->fornecedor?->razao_social ?? '-' }}</strong></p>
                <p class="text-sm">Total: <strong>R$ {{ number_format($documento->valor_total, 2, ',', '.') }}</strong></p>
                <p class="text-sm">Pagamento: {{ $documento->forma_pagamento ?? '-' }} {{ $documento->pago_avista ? '(à vista)' : '' }}</p>
                @if($documento->chave_nfe)
                    <p class="text-sm font-mono break-all">Chave: {{ $documento->chave_nfe }}</p>
                @endif
                @if($documento->observacoes)
                    <p class="text-sm text-gray-600">{{ $documento->observacoes }}</p>
                @endif
            </div>

            <div class="bg-white border rounded-lg p-4 shadow-sm space-y-2">
                <h3 class="font-bold text-gray-800">Fiscal</h3>
                @if($documento->notaFiscal)
                    <a href="{{ route('notas.show', $documento->notaFiscal->id) }}" class="text-blue-600 text-sm hover:underline">
                        NFS-e #{{ $documento->notaFiscal->id }} — {{ $documento->notaFiscal->status }}
                    </a>
                @elseif($documento->nfce)
                    <a href="{{ route('nfces.show', $documento->nfce->id) }}" class="text-blue-600 text-sm hover:underline">
                        NFC-e #{{ $documento->nfce->id }} — {{ $documento->nfce->status }}
                    </a>
                @elseif($documento->canal_fiscal === 'nfe_entrada')
                    <p class="text-sm text-gray-600">NF-e entrada #{{ $documento->numero_nfe }} série {{ $documento->serie_nfe }}</p>
                @else
                    <p class="text-sm text-gray-500">Sem documento fiscal vinculado.</p>
                @endif
            </div>

            <div class="bg-white border rounded-lg p-4 shadow-sm space-y-2">
                <h3 class="font-bold text-gray-800">Financeiro / Estoque</h3>
                <p class="text-sm">{{ $documento->lancamentosFinanceiros->count() }} lançamento(s)</p>
                <p class="text-sm">{{ $documento->movimentacoesEstoque->count() }} movimento(s) de estoque</p>
            </div>
        </div>

        <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b font-bold text-gray-800">Itens</div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">Descrição</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">Qtd</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">Unitário</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">Total</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @foreach($documento->itens as $item)
                    <tr>
                        <td class="px-4 py-2 text-sm">{{ $item->descricao }}</td>
                        <td class="px-4 py-2 text-sm">{{ number_format($item->quantidade, 3, ',', '.') }}</td>
                        <td class="px-4 py-2 text-sm">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-sm">R$ {{ number_format($item->valor_total, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if($documento->lancamentosFinanceiros->isNotEmpty())
            <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b font-bold text-gray-800">Lançamentos</div>
                <ul class="divide-y">
                    @foreach($documento->lancamentosFinanceiros as $l)
                        <li class="px-4 py-3 text-sm flex justify-between">
                            <span>{{ strtoupper($l->tipo) }} — {{ $l->status_label }} — R$ {{ number_format($l->valor, 2, ',', '.') }}</span>
                            <a href="{{ route('lancamentos.index') }}" class="text-blue-600">Ver lista</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($documento->movimentacoesEstoque->isNotEmpty())
            <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b font-bold text-gray-800">Movimentos de estoque</div>
                <ul class="divide-y">
                    @foreach($documento->movimentacoesEstoque as $m)
                        <li class="px-4 py-3 text-sm">
                            {{ strtoupper($m->tipo) }} · {{ $m->produto?->descricao }} · {{ number_format($m->quantidade, 3, ',', '.') }}
                            → saldo {{ number_format($m->saldo_apos, 3, ',', '.') }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-app-layout>
