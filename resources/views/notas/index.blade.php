<x-app-layout>
    <div class="space-y-6">

        <div class="flex flex-col sm:flex-row justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-800">Gerenciamento de Notas</h2>
            <a href="{{ route('notas.create') }}" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                + Nova Nota
            </a>
        </div>

        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <form method="GET" action="{{ route('notas.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700">Buscar</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome, CNPJ ou Número"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">Todos</option>
                        <option value="autorizada" {{ request('status') == 'autorizada' ? 'selected' : '' }}>Autorizada</option>
                        <option value="processando" {{ request('status') == 'processando' ? 'selected' : '' }}>Processando</option>
                        <option value="erro" {{ request('status') == 'erro' ? 'selected' : '' }}>Com Erro</option>
                        <option value="rascunho" {{ request('status') == 'rascunho' ? 'selected' : '' }}>Rascunho</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">De</label>
                    <input type="date" name="data_inicio" value="{{ request('data_inicio') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Até</label>
                    <div class="flex space-x-2">
                        <input type="date" name="data_fim" value="{{ request('data_fim') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">

                        <button type="submit" class="mt-1 bg-gray-800 text-white px-3 py-2 rounded-md hover:bg-gray-700">
                            Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Número</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Emissão</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tomador</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($notas as $nota)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                {{ $nota->numero_nfse ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $nota->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ Str::limit($nota->tomador_nome, 20) }}</div>
                                <div class="text-xs text-gray-500">{{ $nota->tomador_cnpj }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">
                                R$ {{ number_format($nota->valor_servico, 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $nota->status_color }}">
                                        {{ $nota->status_label }}
                                    </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('notas.show', $nota->id) }}" class="text-blue-600 hover:text-blue-900 font-bold">
                                    Detalhes &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                Nenhuma nota encontrada com estes filtros.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $notas->links() }}
            </div>
        </div>

    </div>
</x-app-layout>
