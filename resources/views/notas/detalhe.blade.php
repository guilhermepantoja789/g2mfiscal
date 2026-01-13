<x-app-layout>
    <div class="max-w-5xl mx-auto space-y-6 py-6 px-4">

        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-4 w-full md:w-auto">
                <a href="{{ route('notas.index') }}" class="text-gray-500 hover:text-gray-700">
                    &larr; Voltar
                </a>
                <h1 class="text-2xl font-bold text-gray-900">
                    Nota {{ $nota->numero_nfse ? '#' . $nota->numero_nfse : '(Rascunho #' . $nota->id . ')' }}
                </h1>

                @php
                    $statusClasses = [
                        'criada' => 'bg-gray-100 text-gray-800',
                        'processando' => 'bg-yellow-100 text-yellow-800',
                        'autorizada' => 'bg-green-100 text-green-800',
                        'erro' => 'bg-red-100 text-red-800',
                        'cancelada' => 'bg-red-100 text-red-800',
                    ];
                    $statusLabels = [
                        'criada' => 'Rascunho',
                        'processando' => 'Processando',
                        'autorizada' => 'Emitida',
                        'erro' => 'Falha',
                        'cancelada' => 'Cancelada',
                    ];
                @endphp
                <span class="px-3 py-1 rounded-full text-sm font-bold {{ $statusClasses[$nota->status] ?? 'bg-gray-100' }}">
                    {{ $statusLabels[$nota->status] ?? ucfirst($nota->status) }}
                </span>
            </div>

            <div class="flex flex-wrap gap-2 justify-end w-full md:w-auto">

                {{-- AÇÕES PARA RASCUNHO OU ERRO --}}
                @if(in_array($nota->status, ['criada', 'erro']))

                    <a href="{{ route('notas.imprimir', $nota->id) }}" target="_blank" class="flex items-center bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 shadow font-medium transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        Ver Rascunho
                    </a>

                    <form action="{{ route('notas.emitir', $nota->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="flex items-center bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 shadow-md font-bold transition transform hover:scale-105">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            EMITIR AGORA
                        </button>
                    </form>
                @endif

                {{-- AÇÕES PARA NOTA AUTORIZADA --}}
                @if($nota->status == 'autorizada')

                    <a href="{{ route('notas.imprimir', $nota->id) }}" target="_blank" class="flex items-center bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 shadow transition" title="Gerado pelo sistema">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Espelho PDF
                    </a>

                    <a href="{{ route('notas.danfse_oficial', $nota->id) }}" target="_blank" class="flex items-center bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 shadow transition" title="Baixar do Portal Nacional">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        DANFSe Oficial
                    </a>
                @endif
            </div>
        </div>

        @if($nota->status == 'erro' || $errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm">
                <p class="font-bold">Problema na Emissão:</p>
                <p>{{ $nota->mensagem_erro ?? $errors->first('erro') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div class="md:col-span-2 space-y-6">
                <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
                    <h3 class="text-xs font-bold text-gray-500 uppercase mb-4 tracking-wider">Dados do Serviço</h3>
                    <p class="text-gray-900 text-lg whitespace-pre-wrap">{{ $nota->descricao }}</p>
                </div>

                <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
                    <h3 class="text-xs font-bold text-gray-500 uppercase mb-4 tracking-wider">Tomador do Serviço</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500">Razão Social</p>
                            <p class="font-medium text-gray-900">{{ $nota->tomador_nome }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Documento (CNPJ/CPF)</p>
                            <p class="font-medium text-gray-900">{{ $nota->tomador_cnpj }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-500">E-mail</p>
                            <p class="font-medium text-gray-900">{{ $nota->tomador_email ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
                    <h3 class="text-xs font-bold text-gray-500 uppercase mb-4 tracking-wider">Valores</h3>

                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-600">Valor Serviço</span>
                        <span class="font-bold text-xl text-gray-900">R$ {{ number_format($nota->valor_servico, 2, ',', '.') }}</span>
                    </div>

                    @if($nota->tp_ret_issqn == 2)
                        <div class="bg-yellow-50 p-3 rounded border border-yellow-200 mb-2">
                            <p class="text-xs text-yellow-800 font-bold uppercase">ISS Retido pelo Tomador</p>
                            <p class="text-xs text-yellow-600 mt-1">O tomador deve recolher o ISS.</p>
                        </div>
                    @elseif($nota->tp_ret_issqn == 3)
                        <div class="bg-blue-50 p-3 rounded border border-blue-200 mb-2">
                            <p class="text-xs text-blue-800 font-bold uppercase">ISS Retido pelo Intermediário</p>
                        </div>
                    @endif

                    @if($nota->v_tot_trib_fed > 0 || $nota->v_tot_trib_mun > 0)
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <p class="text-xs text-gray-400 font-bold uppercase mb-2">Tributos Aproximados (Lei 12.741)</p>
                            <div class="text-xs text-gray-600 space-y-1">
                                @if($nota->v_tot_trib_fed > 0)
                                    <div class="flex justify-between">
                                        <span>Federal</span>
                                        <span>R$ {{ number_format($nota->v_tot_trib_fed, 2, ',', '.') }}</span>
                                    </div>
                                @endif
                                @if($nota->v_tot_trib_est > 0)
                                    <div class="flex justify-between">
                                        <span>Estadual</span>
                                        <span>R$ {{ number_format($nota->v_tot_trib_est, 2, ',', '.') }}</span>
                                    </div>
                                @endif
                                @if($nota->v_tot_trib_mun > 0)
                                    <div class="flex justify-between">
                                        <span>Municipal</span>
                                        <span>R$ {{ number_format($nota->v_tot_trib_mun, 2, ',', '.') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
