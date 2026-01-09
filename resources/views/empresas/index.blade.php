<x-app-layout>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-800">Minhas Empresas</h2>
            <a href="{{ route('empresas.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold text-sm">
                + Nova Empresa
            </a>
        </div>

        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empresa</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CNPJ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cidade/UF</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Regime</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @foreach($empresas as $empresa)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-gray-900">{{ $empresa->nome_fantasia }}</div>
                            <div class="text-xs text-gray-500">{{ $empresa->razao_social }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ $empresa->cnpj }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $empresa->bairro }} - {{ $empresa->uf }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if($empresa->regime_tributario == 1)
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Simples Nacional</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Outro</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-2">
                            @if(session('empresa_ativa') == $empresa->id)
                                <span class="text-xs font-bold text-green-600 border border-green-200 px-2 py-1 rounded">Ativa Agora</span>
                            @else
                                <a href="{{ route('empresas.entrar', $empresa->id) }}" class="text-xs font-bold text-blue-600 border border-blue-200 px-2 py-1 rounded hover:bg-blue-50">Acessar Painel</a>
                            @endif

                            <a href="{{ route('empresas.edit', $empresa->id) }}" class="text-gray-500 hover:text-blue-600">Editar</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
