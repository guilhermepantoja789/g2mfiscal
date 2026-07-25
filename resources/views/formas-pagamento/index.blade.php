<x-app-layout>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Formas de pagamento</h2>
                <p class="text-sm text-gray-500">Flags de liquidação (sem gateway). Definem vencimentos, parcelas e juros no financeiro.</p>
            </div>
            <a href="{{ route('formas-pagamento.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold text-sm">+ Nova forma</a>
        </div>

        @if(session('success'))
            <div class="bg-green-50 text-green-800 border border-green-200 rounded-md px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código (tPag)</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Liquidação</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parcelas / Juros</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Conta</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @forelse($formas as $forma)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $forma->nome }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-gray-600">{{ $forma->codigo }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $forma->isAvista() ? 'À vista' : 'Prazo' }}
                            @if($forma->dias_recebimento > 0)
                                <span class="text-gray-400">(D+{{ $forma->dias_recebimento }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $forma->parcelas }}x
                            @if((float) $forma->juros_percentual > 0)
                                · {{ number_format($forma->juros_percentual, 2, ',', '.') }}%
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 font-mono">
                            {{ $forma->contaContabil?->codigo ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($forma->ativo)
                                <span class="text-emerald-700 font-medium">Ativa</span>
                            @else
                                <span class="text-gray-400">Inativa</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm space-x-2">
                            <a href="{{ route('formas-pagamento.edit', $forma) }}" class="text-blue-600 hover:underline">Editar</a>
                            <form action="{{ route('formas-pagamento.destroy', $forma) }}" method="POST" class="inline" onsubmit="return confirm('Remover esta forma?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Nenhuma forma cadastrada.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
