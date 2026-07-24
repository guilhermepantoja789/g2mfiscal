<x-app-layout>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Fornecedores</h2>
                <a href="{{ route('fornecedores.dashboard') }}" class="text-sm text-blue-600 hover:underline">← Painel de fornecedores</a>
            </div>
            <a href="{{ route('fornecedores.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold text-sm">+ Novo Fornecedor</a>
        </div>

        <div class="bg-white p-4 rounded-lg shadow-sm">
            <form action="{{ route('fornecedores.index') }}" method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar nome ou CNPJ..." class="w-full border-gray-300 rounded-md">
                <button class="bg-gray-800 text-white px-4 rounded-md">Buscar</button>
            </form>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Razão Social</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">UF</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @forelse($fornecedores as $fornecedor)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ $fornecedor->razao_social }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $fornecedor->cnpj }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $fornecedor->uf ?? '-' }}</td>
                        <td class="px-6 py-4 text-right text-sm space-x-2">
                            <a href="{{ route('fornecedores.edit', $fornecedor) }}" class="text-blue-600 hover:underline">Editar</a>
                            <form action="{{ route('fornecedores.destroy', $fornecedor) }}" method="POST" class="inline" onsubmit="return confirm('Excluir?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">Nenhum fornecedor.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $fornecedores->links() }}</div>
        </div>
    </div>
</x-app-layout>
