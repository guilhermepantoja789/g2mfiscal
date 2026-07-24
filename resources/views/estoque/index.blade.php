<x-app-layout>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Saldos de estoque</h2>
                <a href="{{ route('estoque.dashboard') }}" class="text-sm text-blue-600 hover:underline">← Painel de estoque</a>
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg shadow-sm">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar produto..." class="w-full border-gray-300 rounded-md">
                <button class="bg-gray-800 text-white px-4 rounded-md">Buscar</button>
            </form>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-lg border">
            <table class="min-w-full divide-y">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Produto</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Saldo</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Custo médio</th>
                    <th class="px-4 py-3 text-right text-xs uppercase text-gray-500">Kardex</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($produtos as $p)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium">{{ $p->descricao }}</td>
                        <td class="px-4 py-3 text-sm">{{ number_format($p->estoque_atual, 3, ',', '.') }} {{ $p->unidade }}</td>
                        <td class="px-4 py-3 text-sm">R$ {{ number_format($p->custo_medio, 4, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('estoque.show', $p->id) }}" class="text-blue-600 hover:underline">Extrato</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Nenhum produto com estoque.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $produtos->links() }}</div>
        </div>
    </div>
</x-app-layout>
