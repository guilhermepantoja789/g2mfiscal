<x-app-layout>
    <div class="max-w-6xl mx-auto space-y-8 py-8 px-4 sm:px-6 lg:px-8 animate-fade-in-up">
        
        <!-- HEADER COM EFEITO GLASS -->
        <div class="relative overflow-hidden rounded-3xl bg-white shadow-sm border border-gray-100 p-6 sm:p-8">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                
                <!-- Info da Nota -->
                <div class="flex items-center gap-6">
                    <a href="{{ route('notas.index') }}" class="group flex items-center justify-center w-12 h-12 bg-gray-50 rounded-2xl hover:bg-gray-100 transition-colors border border-gray-200 shadow-sm">
                        <svg class="w-5 h-5 text-gray-500 group-hover:text-gray-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                                {{ $nota->numero_nfse ? 'NFS-e #' . str_pad($nota->numero_nfse, 8, '0', STR_PAD_LEFT) : 'Rascunho' }}
                            </h1>
                            
                            @php
                                $badgeStyles = [
                                    'autorizada' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 shadow-sm',
                                    'processando' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 shadow-sm animate-pulse',
                                    'erro' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 shadow-sm',
                                    'criada' => 'bg-slate-50 text-slate-600 ring-1 ring-slate-500/20',
                                    'rascunho' => 'bg-slate-50 text-slate-600 ring-1 ring-slate-500/20',
                                ];
                                $style = $badgeStyles[$nota->status] ?? $badgeStyles['criada'];
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold tracking-wide uppercase {{ $style }}">
                                @if($nota->status == 'autorizada') <svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg> @endif
                                @if($nota->status == 'processando') <svg class="w-3.5 h-3.5 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> @endif
                                @if($nota->status == 'erro') <svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg> @endif
                                {{ $nota->status_label ?? ucfirst($nota->status) }}
                            </span>
                        </div>
                        <p class="text-sm font-medium text-gray-500">
                            ID do Sistema: #{{ $nota->id }} &bull; Criada em {{ $nota->created_at->format('d/m/Y \à\s H:i') }}
                            @if($nota->documento_comercial_id)
                                &bull; <a href="{{ route('documentos.show', $nota->documento_comercial_id) }}" class="text-indigo-600 hover:underline">Documento #{{ $nota->documento_comercial_id }}</a>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="flex flex-wrap gap-3 w-full md:w-auto justify-start md:justify-end">
                    
                    @if(in_array($nota->status, ['criada', 'erro', 'rascunho']))
                        <form action="{{ route('notas.destroy', $nota->id) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir esta nota permanentemente?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="flex items-center justify-center px-4 py-2.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 hover:text-red-700 transition-colors border border-red-100 shadow-sm font-bold text-sm" title="Excluir">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Excluir
                            </button>
                        </form>

                        <a href="{{ route('notas.edit', $nota->id) }}" class="flex items-center px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 shadow-sm font-bold text-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Editar
                        </a>

                        <a href="{{ route('notas.imprimir', $nota->id) }}" target="_blank" class="flex items-center px-5 py-2.5 bg-gray-800 text-white rounded-xl hover:bg-gray-900 shadow-sm font-bold text-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                            <svg class="w-4 h-4 mr-2 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            Ver Espelho
                        </a>

                        <form action="{{ route('notas.emitir', $nota->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="flex items-center px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl hover:from-emerald-600 hover:to-emerald-700 shadow-md hover:shadow-lg font-bold text-sm uppercase tracking-wide transition-all transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                Emitir na Sefin
                            </button>
                        </form>
                    @endif

                    @if($nota->status == 'autorizada')
                        @php
                            $danfseService = app(\App\Services\Fiscal\NfseDanfseService::class);
                            $chavePortal = $danfseService->resolverChaveAcesso($nota);
                            $urlPortalNfse = $danfseService->urlConsultaPublica($chavePortal);
                        @endphp

                        <a href="{{ route('notas.imprimir', $nota->id) }}" target="_blank" class="flex items-center px-6 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 shadow-md hover:shadow-lg font-bold text-sm transition-all transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Baixar DANFSe
                        </a>

                        <a href="{{ route('notas.danfse_oficial', $nota->id) }}" class="flex items-center px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 shadow-sm font-bold text-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Tentar PDF da ADN
                        </a>

                        @if($urlPortalNfse)
                            <a href="{{ $urlPortalNfse }}" target="_blank" rel="noopener noreferrer" class="flex items-center px-5 py-2.5 bg-white border border-indigo-200 text-indigo-700 rounded-xl hover:bg-indigo-50 shadow-sm font-bold text-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                Consultar no Portal Nacional
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <!-- MENSAGEM DE ERRO DE EMISSÃO -->
        @if($nota->status == 'erro' || $errors->has('erro'))
            <div class="rounded-2xl bg-rose-50 border border-rose-200 p-6 shadow-sm relative overflow-hidden">
                <div class="absolute -right-4 -top-4 text-rose-100 opacity-50 transform rotate-12">
                    <svg width="120" height="120" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                </div>
                <div class="relative z-10 flex items-start">
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="h-6 w-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-bold text-rose-800 uppercase tracking-wide">Problema na Emissão</h3>
                        <div class="mt-2 text-rose-700 font-medium">
                            {{ $nota->mensagem_erro ?? $errors->first('erro') }}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($errors->has('download'))
            @php
                $danfseService = $danfseService ?? app(\App\Services\Fiscal\NfseDanfseService::class);
                $chavePortal = $chavePortal ?? $danfseService->resolverChaveAcesso($nota);
                $urlPortalNfse = $urlPortalNfse ?? $danfseService->urlConsultaPublica($chavePortal);
            @endphp
            <div class="rounded-2xl bg-amber-50 border border-amber-200 p-6 shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-bold text-amber-900 uppercase tracking-wide">ADN indisponível</h3>
                        <p class="mt-2 text-amber-800 font-medium">{{ $errors->first('download') }}</p>
                        <p class="mt-1 text-sm text-amber-700">O PDF da ADN não foi entregue. Use o DANFSe local (gerado do XML autorizado) ou consulte a nota no Portal Nacional.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('notas.imprimir', $nota->id) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700">
                                Baixar DANFSe local
                            </a>
                            <a href="{{ route('notas.danfse_oficial', $nota->id) }}" class="inline-flex items-center px-4 py-2 bg-white border border-amber-300 text-amber-900 rounded-lg text-sm font-bold hover:bg-amber-100">
                                Tentar de novo
                            </a>
                            @if($urlPortalNfse)
                                <a href="{{ $urlPortalNfse }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-4 py-2 bg-white border border-indigo-200 text-indigo-700 rounded-lg text-sm font-bold hover:bg-indigo-50">
                                    Abrir Portal Nacional
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- COLUNA ESQUERDA (Dados do Serviço e Tomador) -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Card Serviço -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mr-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Descrição do Serviço</h3>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100">
                        <p class="text-gray-700 text-base leading-relaxed whitespace-pre-wrap font-medium">{{ $nota->descricao }}</p>
                    </div>
                </div>

                <!-- Card Tomador -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mr-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Tomador do Serviço (Cliente)</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="col-span-1 sm:col-span-2">
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Razão Social / Nome</p>
                            <p class="font-bold text-gray-900 text-lg">{{ $nota->tomador_nome }}</p>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Documento (CNPJ/CPF)</p>
                            <p class="font-bold text-gray-900 font-mono">
                                @if(strlen($nota->tomador_cnpj) == 14)
                                    {{ preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $nota->tomador_cnpj) }}
                                @elseif(strlen($nota->tomador_cnpj) == 11)
                                    {{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $nota->tomador_cnpj) }}
                                @else
                                    {{ $nota->tomador_cnpj }}
                                @endif
                            </p>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">E-mail de Contato</p>
                            <p class="font-bold text-gray-900">{{ $nota->tomador_email ?? 'Não informado' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUNA DIREITA (Valores) -->
            <div class="space-y-8">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 sticky top-8">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-6">Resumo Financeiro</h3>

                    <!-- Valor Principal -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-100 mb-6">
                        <span class="block text-blue-600 text-sm font-bold mb-1">Valor Líquido do Serviço</span>
                        <span class="block text-4xl font-extrabold text-blue-900 tracking-tight">
                            R$ {{ number_format($nota->valor_servico, 2, ',', '.') }}
                        </span>
                    </div>

                    <!-- Avisos de Retenção -->
                    @if($nota->tp_ret_issqn == 2)
                        <div class="flex items-start bg-amber-50 p-4 rounded-2xl border border-amber-200 mb-6">
                            <svg class="w-5 h-5 text-amber-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <div>
                                <p class="text-xs text-amber-800 font-bold uppercase tracking-wider">ISS Retido pelo Tomador</p>
                                <p class="text-xs text-amber-700 mt-1 font-medium">O tomador é responsável pelo recolhimento do ISS.</p>
                            </div>
                        </div>
                    @elseif($nota->tp_ret_issqn == 3)
                        <div class="flex items-start bg-indigo-50 p-4 rounded-2xl border border-indigo-200 mb-6">
                            <svg class="w-5 h-5 text-indigo-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div>
                                <p class="text-xs text-indigo-800 font-bold uppercase tracking-wider">ISS Retido pelo Intermediário</p>
                            </div>
                        </div>
                    @endif

                    <!-- Impostos Aproximados -->
                    @if($nota->v_tot_trib_fed > 0 || $nota->v_tot_trib_mun > 0 || $nota->v_tot_trib_est > 0)
                        <div>
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-3">Tributos Aproximados (Lei 12.741)</p>
                            <div class="space-y-2">
                                @if($nota->v_tot_trib_fed > 0)
                                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                                        <span class="text-sm font-medium text-gray-500">Federal</span>
                                        <span class="text-sm font-bold text-gray-900">R$ {{ number_format($nota->v_tot_trib_fed, 2, ',', '.') }}</span>
                                    </div>
                                @endif
                                @if($nota->v_tot_trib_est > 0)
                                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                                        <span class="text-sm font-medium text-gray-500">Estadual</span>
                                        <span class="text-sm font-bold text-gray-900">R$ {{ number_format($nota->v_tot_trib_est, 2, ',', '.') }}</span>
                                    </div>
                                @endif
                                @if($nota->v_tot_trib_mun > 0)
                                    <div class="flex justify-between items-center py-2">
                                        <span class="text-sm font-medium text-gray-500">Municipal</span>
                                        <span class="text-sm font-bold text-gray-900">R$ {{ number_format($nota->v_tot_trib_mun, 2, ',', '.') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
    
    <!-- Animação Customizada CSS -->
    <style>
        .animate-fade-in-up {
            animation: fadeInUp 0.4s ease-out forwards;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</x-app-layout>
