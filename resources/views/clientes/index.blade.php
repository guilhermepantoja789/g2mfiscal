<x-app-layout>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-800">Meus Clientes</h2>
            <a href="{{ route('clientes.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold text-sm">
                + Novo Cliente
            </a>
        </div>

        <div class="bg-white p-4 rounded-lg shadow-sm">
            <form action="{{ route('clientes.index') }}" method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por Nome ou CNPJ..." class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <button type="submit" class="bg-gray-800 text-white px-4 rounded-md">Buscar</button>
            </form>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Razão Social / Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @foreach($clientes as $cliente)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ $cliente->razao_social }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $cliente->cnpj }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $cliente->email ?? '-' }}</td>
                        <td class="px-6 py-4 text-right text-sm space-x-2">
                            <a href="{{ route('clientes.edit', $cliente->id) }}" class="text-blue-600 hover:underline">Editar</a>
                            <form action="{{ route('clientes.destroy', $cliente->id) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $clientes->links() }}</div>
        </div>
    </div>
</x-app-layout>
