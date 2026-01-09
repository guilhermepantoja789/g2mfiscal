<x-app-layout>
    <div class="max-w-5xl mx-auto space-y-6">

        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="{{ route('notas.index') }}" class="text-gray-500 hover:text-gray-700">
                    &larr; Voltar
                </a>
                <h1 class="text-2xl font-bold text-gray-900">
                    Nota {{ $nota->numero_nfse ? '#' . $nota->numero_nfse : '(Rascunho)' }}
                </h1>
                <span class="px-3 py-1 rounded-full text-sm font-bold {{ $nota->status_color }}">
                    {{ $nota->status_label }}
                </span>
            </div>

            @if($nota->status == 'erro')
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p class="font-bold">Atenção: Erro na Emissão</p>
                    <p>{{ $nota->mensagem_erro }}</p>
                </div>
            @endif

            <div class="flex space-x-3">
                @if($nota->status == 'autorizada')
                    <a href="{{ route('notas.imprimir', $nota->id) }}" target="_blank"
                       class="flex items-center bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 border border-gray-300 transition"
                       title="Gerado pelo sistema (Visualização simples)">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        PDF Simples
                    </a>

                    <a href="{{ route('notas.danfse_oficial', $nota->id) }}" target="_blank"
                       class="flex items-center bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 shadow-sm transition"
                       title="Documento Oficial da Receita Federal">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        DANFSe Oficial (Gov)
                    </a>
                @else
                    <a href="{{ route('notas.imprimir', $nota->id) }}" target="_blank"
                       class="flex items-center bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 shadow-sm transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        Baixar Rascunho
                    </a>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-400 p-4">
                <p class="text-green-700">{{ session('success') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div class="md:col-span-2 space-y-6">

                <div class="bg-white shadow rounded-lg overflow-hidden border border-gray-200">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between">
                        <span class="text-xs font-bold text-gray-500 uppercase">Prestador</span>
                        <span class="text-xs font-bold text-gray-500 uppercase">Tomador</span>
                    </div>
                    <div class="p-6 grid grid-cols-2 gap-8">
                        <div>
                            <p class="font-bold text-gray-900">Sua Empresa</p>
                            <p class="text-sm text-gray-500">
                                CNPJ: {{ $nota->empresa ? preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $nota->empresa->cnpj) : 'N/A' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-900">{{ $nota->cliente->razao_social ?? $nota->tomador_nome }}</p>
                            <p class="text-sm text-gray-500">CNPJ: {{ preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $nota->cliente->cnpj) ?? $preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $nota->tomador_cnpj) }}</p>
                            @if($nota->cliente)
                                <a href="#" class="text-xs text-blue-600 hover:underline">Ver histórico deste cliente</a>
                            @endif
                        </div>
                    </div>

                    <div class="border-t border-gray-100 px-6 py-4">
                        <p class="text-xs font-bold text-gray-500 uppercase mb-2">Descrição dos Serviços</p>
                        <p class="text-gray-700 whitespace-pre-line">{{ $nota->descricao }}</p>
                        <p class="mt-4 text-xs text-gray-400">Código do Serviço: {{ $nota->codigo_servico }}</p>
                    </div>
                </div>

            </div>

            <div class="space-y-6">

                <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
                    <h3 class="text-gray-500 text-xs font-bold uppercase mb-4">Valores</h3>

                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Valor Serviço</span>
                        <span class="font-medium">R$ {{ number_format($nota->valor_servico, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between mb-4">
                        <span class="text-gray-600">ISS ({{ $nota->aliquota_iss }}%)</span>
                        <span class="text-red-600 font-medium">- R$ {{ number_format($nota->valor_iss, 2, ',', '.') }}</span>
                    </div>
                    <div class="border-t pt-4 flex justify-between items-center">
                        <span class="text-gray-900 font-bold">Líquido</span>
                        <span class="text-2xl font-bold text-green-600">R$ {{ number_format($nota->valor_liquido, 2, ',', '.') }}</span>
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
                    <h3 class="text-gray-500 text-xs font-bold uppercase mb-4">Histórico</h3>
                    <ul class="space-y-4">
                        <li class="flex items-center">
                            <div class="h-2 w-2 bg-green-500 rounded-full mr-3"></div>
                            <div class="text-sm">
                                <p class="text-gray-900">Nota Criada</p>
                                <p class="text-xs text-gray-500">{{ $nota->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </li>
                        @if($nota->status == 'processando')
                            <li class="flex items-center">
                                <div class="h-2 w-2 bg-yellow-400 rounded-full mr-3 animate-pulse"></div>
                                <div class="text-sm">
                                    <p class="text-gray-900">Aguardando Prefeitura</p>
                                    <p class="text-xs text-gray-500">Em processamento...</p>
                                </div>
                            </li>
                        @endif
                    </ul>
                </div>

            </div>
        </div>

    </div>
</x-app-layout>
