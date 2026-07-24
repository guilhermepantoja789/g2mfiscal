<x-app-layout>
    <div class="space-y-6">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Vendas / Documentos</h2>
                <p class="text-sm text-gray-500">Centro comercial: estoque + fiscal + financeiro. Emissão avulsa fica em Fiscal.</p>
                <a href="{{ route('documentos.dashboard') }}" class="text-sm text-blue-600 hover:underline">← Painel de documentos</a>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('documentos.importar_xml') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-md font-bold text-sm">Importar XML NF-e</a>
                <a href="{{ route('documentos.create', ['tipo' => 'venda', 'canal' => 'nfce']) }}" class="px-4 py-2 bg-blue-600 text-white rounded-md font-bold text-sm">+ Venda NFC-e</a>
                <a href="{{ route('documentos.create', ['tipo' => 'venda', 'canal' => 'nfse']) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md font-bold text-sm">+ Venda NFS-e</a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 text-green-800 border border-green-200 rounded-md px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <select name="tipo" class="rounded-md border-gray-300">
                    <option value="">Tipo</option>
                    <option value="venda" @selected(request('tipo')==='venda')>Venda</option>
                    <option value="compra" @selected(request('tipo')==='compra')>Compra</option>
                </select>
                <select name="canal" class="rounded-md border-gray-300">
                    <option value="">Canal</option>
                    <option value="nfce" @selected(request('canal')==='nfce')>NFC-e</option>
                    <option value="nfse" @selected(request('canal')==='nfse')>NFS-e</option>
                    <option value="nfe_entrada" @selected(request('canal')==='nfe_entrada')>NF-e entrada</option>
                </select>
                <select name="status" class="rounded-md border-gray-300">
                    <option value="">Status</option>
                    @foreach(['rascunho','processando_fiscal','autorizado','erro','cancelado'] as $st)
                        <option value="{{ $st }}" @selected(request('status')===$st)>{{ $st }}</option>
                    @endforeach
                </select>
                <input type="date" name="de" value="{{ request('de') }}" class="rounded-md border-gray-300" title="De">
                <input type="date" name="ate" value="{{ request('ate') }}" class="rounded-md border-gray-300" title="Até">
                <button class="bg-gray-800 text-white rounded-md px-4">Filtrar</button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white border border-gray-100 rounded-lg p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Documentos no período</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $resumo['qtd'] }}</p>
            </div>
            <div class="bg-white border border-gray-100 rounded-lg p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Total</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">R$ {{ number_format($resumo['total'], 2, ',', '.') }}</p>
            </div>
            <div class="bg-white border border-gray-100 rounded-lg p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold mb-2">Por forma</p>
                <ul class="space-y-1 text-sm text-gray-700 max-h-24 overflow-y-auto">
                    @forelse($resumo['por_forma'] as $linha)
                        <li class="flex justify-between gap-2">
                            <span>{{ $linha->nome }}</span>
                            <span class="font-mono">{{ $linha->qtd }} · R$ {{ number_format($linha->total, 2, ',', '.') }}</span>
                        </li>
                    @empty
                        <li class="text-gray-400">Sem dados no filtro.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo / Canal</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parceiro</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pagamento</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @forelse($documentos as $doc)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-mono">{{ $doc->id }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $doc->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm">{{ strtoupper($doc->tipo) }} / {{ $doc->canal_fiscal }}</td>
                        <td class="px-4 py-3 text-sm">{{ $doc->cliente?->razao_social ?? $doc->fornecedor?->razao_social ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $doc->formaPagamentoRel?->nome ?? ($doc->forma_pagamento ?: '—') }}</td>
                        <td class="px-4 py-3 text-sm">{{ $doc->status_label }}</td>
                        <td class="px-4 py-3 text-sm">R$ {{ number_format($doc->valor_total, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('documentos.show', $doc->id) }}" class="text-blue-600 hover:underline">Abrir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Nenhum documento.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $documentos->links() }}</div>
        </div>
    </div>
</x-app-layout>
