<x-app-layout>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Produtos</h2>
                <a href="{{ route('produtos.dashboard') }}" class="text-sm text-blue-600 hover:underline">← Painel de produtos</a>
            </div>
            <a href="{{ route('produtos.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold text-sm">+ Novo Produto</a>
        </div>

        <div class="bg-white p-4 rounded-lg shadow-sm">
            <form action="{{ route('produtos.index') }}" method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar descrição, SKU ou EAN..." class="w-full border-gray-300 rounded-md">
                <button class="bg-gray-800 text-white px-4 rounded-md">Buscar</button>
            </form>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU / EAN</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Preço</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estoque</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @forelse($produtos as $produto)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ $produto->descricao }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $produto->sku ?? '-' }} / {{ $produto->ean ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">R$ {{ number_format($produto->preco_venda, 2, ',', '.') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ number_format($produto->estoque_atual, 3, ',', '.') }} {{ $produto->unidade }}</td>
                        <td class="px-6 py-4 text-right text-sm space-x-2">
                            <a href="{{ route('produtos.edit', $produto) }}" class="text-blue-600 hover:underline">Editar</a>
                            <form action="{{ route('produtos.destroy', $produto) }}" method="POST" class="inline" onsubmit="return confirm('Excluir produto?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Nenhum produto cadastrado.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $produtos->links() }}</div>
        </div>
    </div>
</x-app-layout>
