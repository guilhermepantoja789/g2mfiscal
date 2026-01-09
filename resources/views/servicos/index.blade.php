<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Meus Serviços
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Gerencie o catálogo de serviços para agilizar a emissão de notas.
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="{{ route('servicos.create') }}" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Novo Serviço
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-md bg-green-50 p-4 mb-6 border-l-4 border-green-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white shadow overflow-hidden sm:rounded-lg border border-gray-200">
            @if($servicos->count() > 0)
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Serviço / Descrição
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cód. Tributação (LC 116)
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Valor Unit.
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Ações</span>
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($servicos as $servico)
                        <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-bold text-gray-900">{{ $servico->nome }}</div>
                                        <div class="text-xs text-gray-500 mt-1 max-w-md truncate">{{ $servico->descricao }}</div>
                                        @if($servico->codigo_interno)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 mt-1">
                                                    Ref: {{ $servico->codigo_interno }}
                                                </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ $servico->codigo_tributacao_nacional }}
                                    </span>
                                @if($servico->iss_retido)
                                    <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800" title="ISS Retido na Fonte">
                                            Retido
                                        </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">
                                R$ {{ number_format($servico->valor_unitario, 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('servicos.edit', $servico->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-4 font-bold">Editar</a>

                                <form action="{{ route('servicos.destroy', $servico->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Tem certeza que deseja excluir este serviço?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 font-bold">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <div class="px-4 py-3 border-t border-gray-200 sm:px-6">
                    {{ $servicos->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum serviço cadastrado</h3>
                    <p class="mt-1 text-sm text-gray-500">Comece cadastrando os serviços que você presta frequentemente.</p>
                    <div class="mt-6">
                        <a href="{{ route('servicos.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Cadastrar Primeiro Serviço
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
