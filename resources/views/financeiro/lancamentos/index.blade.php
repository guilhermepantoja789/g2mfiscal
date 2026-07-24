<x-app-layout>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Lançamentos financeiros</h2>
                <a href="{{ route('financeiro.dashboard') }}" class="text-sm text-blue-600 hover:underline">← Painel financeiro</a>
            </div>
            <a href="{{ route('lancamentos.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold">Novo lançamento</a>
        </div>

        @if(session('success'))
            <div class="bg-green-50 text-green-800 border border-green-200 rounded-md px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 text-red-800 border border-red-200 rounded-md px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif

        <div class="bg-white p-4 rounded-lg shadow-sm">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <select name="tipo" class="rounded-md border-gray-300">
                    <option value="">Tipo</option>
                    <option value="receber" @selected(request('tipo')==='receber')>Receber</option>
                    <option value="pagar" @selected(request('tipo')==='pagar')>Pagar</option>
                </select>
                <select name="status" class="rounded-md border-gray-300">
                    <option value="">Status</option>
                    <option value="aberto" @selected(request('status')==='aberto')>Aberto</option>
                    <option value="pago" @selected(request('status')==='pago')>Pago</option>
                    <option value="cancelado" @selected(request('status')==='cancelado')>Cancelado</option>
                </select>
                <button class="bg-gray-800 text-white rounded-md">Filtrar</button>
            </form>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-lg border">
            <table class="min-w-full divide-y">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">#</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Parceiro</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Valor</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Vencimento</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Status</th>
                    <th class="px-4 py-3 text-right text-xs uppercase text-gray-500">Ações</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($lancamentos as $l)
                    <tr>
                        <td class="px-4 py-3 text-sm font-mono">{{ $l->id }}</td>
                        <td class="px-4 py-3 text-sm uppercase">{{ $l->tipo }}</td>
                        <td class="px-4 py-3 text-sm">{{ $l->cliente?->razao_social ?? $l->fornecedor?->razao_social ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">R$ {{ number_format($l->valor, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm">{{ $l->vencimento?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $l->status_label }}</td>
                        <td class="px-4 py-3 text-right text-sm space-x-2 whitespace-nowrap">
                            @if($l->documento_comercial_id)
                                <a href="{{ route('documentos.show', $l->documento_comercial_id) }}" class="text-blue-600">Doc</a>
                            @endif
                            @if($l->status === 'aberto')
                                <a href="{{ route('lancamentos.edit', $l->id) }}" class="text-indigo-600 hover:underline">Editar</a>
                                <form method="POST" action="{{ route('lancamentos.baixar', $l->id) }}" class="inline">
                                    @csrf
                                    <button class="text-emerald-600 hover:underline">Baixar</button>
                                </form>
                                <form method="POST" action="{{ route('lancamentos.cancelar', $l->id) }}" class="inline" onsubmit="return confirm('Cancelar este lançamento?')">
                                    @csrf
                                    <button class="text-rose-600 hover:underline">Cancelar</button>
                                </form>
                                @if(!empty($featureAsaas) && $l->tipo === 'receber' && $l->cliente_id && !$l->cobranca_id)
                                    <form method="POST" action="{{ route('lancamentos.gerar_cobranca', $l->id) }}" class="inline">
                                        @csrf
                                        <button class="text-amber-600 hover:underline">Gerar cobrança</button>
                                    </form>
                                @endif
                            @endif
                            @if($l->status === 'pago')
                                <form method="POST" action="{{ route('lancamentos.estornar', $l->id) }}" class="inline" onsubmit="return confirm('Estornar a baixa?')">
                                    @csrf
                                    <button class="text-amber-700 hover:underline">Estornar baixa</button>
                                </form>
                                <form method="POST" action="{{ route('lancamentos.cancelar', $l->id) }}" class="inline" onsubmit="return confirm('Cancelar lançamento já baixado?')">
                                    @csrf
                                    <button class="text-rose-600 hover:underline">Cancelar</button>
                                </form>
                            @endif
                            @if($l->cobranca_id && !empty($featureAsaas))
                                <a href="{{ route('cobrancas.index') }}" class="text-gray-500">Cobrança</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Nenhum lançamento.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $lancamentos->links() }}</div>
        </div>
    </div>
</x-app-layout>
