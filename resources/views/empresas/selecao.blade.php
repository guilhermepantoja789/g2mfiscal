<x-guest-layout>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800">
            Selecione a Empresa
        </h2>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium flex items-center transition">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Sair
            </button>
        </form>
    </div>

    @if($empresas->isEmpty())
        <div class="text-center py-8">
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            Você ainda não tem nenhuma empresa cadastrada.
                        </p>
                    </div>
                </div>
            </div>

            <a href="{{ route('empresas.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                Cadastrar Minha Primeira Empresa
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4">
            @foreach($empresas as $empresa)
                <div class="flex items-center w-full bg-white border border-gray-200 rounded-lg hover:border-blue-300 transition duration-150 ease-in-out shadow-sm group">

                    <a href="{{ route('empresas.entrar', $empresa->id) }}" class="flex-grow p-4 flex justify-between items-center hover:bg-blue-50 rounded-l-lg">
                        <div>
                            <span class="block text-lg font-medium text-gray-900 group-hover:text-blue-700">
                                {{ $empresa->nome_fantasia ?: $empresa->razao_social }}
                            </span>
                            <span class="block text-sm text-gray-500">
                                CNPJ: {{ $empresa->cnpj }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-400 mr-2">
                            Clique para entrar &rarr;
                        </div>
                    </a>

                    <div class="border-l border-gray-200 p-2">
                        <a href="{{ route('empresas.edit', $empresa->id) }}" class="p-2 text-gray-400 hover:text-[#1e676d] hover:bg-gray-100 rounded-full transition" title="Editar Dados da Empresa">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 border-t pt-4">
            <a href="{{ route('empresas.create') }}" class="flex items-center justify-center w-full px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Nova Empresa
            </a>
        </div>
    @endif
</x-guest-layout>
