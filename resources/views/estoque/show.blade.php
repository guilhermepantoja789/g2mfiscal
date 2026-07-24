<x-app-layout>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Kardex — {{ $produto->descricao }}</h2>
                <p class="text-sm text-gray-500">Saldo atual: {{ number_format($produto->estoque_atual, 3, ',', '.') }} {{ $produto->unidade }}</p>
            </div>
            <a href="{{ route('estoque.saldos') }}" class="text-sm text-gray-600">Voltar</a>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-lg border">
            <table class="min-w-full divide-y">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Data</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Qtd</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Saldo após</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Origem</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Documento</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($movimentacoes as $m)
                    <tr>
                        <td class="px-4 py-3 text-sm">{{ $m->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm uppercase">{{ $m->tipo }}</td>
                        <td class="px-4 py-3 text-sm">{{ number_format($m->quantidade, 3, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm">{{ number_format($m->saldo_apos, 3, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm">{{ $m->origem }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($m->documento_comercial_id)
                                <a href="{{ route('documentos.show', $m->documento_comercial_id) }}" class="text-blue-600">#{{ $m->documento_comercial_id }}</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Sem movimentos.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $movimentacoes->links() }}</div>
        </div>
    </div>
</x-app-layout>
