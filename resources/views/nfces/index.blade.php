<x-app-layout>
    <div class="space-y-8 animate-fade-in-up">
        <!-- HEADER -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-800 p-8 sm:p-10 shadow-lg text-white">
            <div class="absolute -top-24 -right-24 opacity-20 transform rotate-12 pointer-events-none">
                <svg width="300" height="300" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                    <path fill="#FFFFFF" d="M42.7,-73.4C55.6,-66.4,66.5,-54.6,74.9,-41.3C83.3,-28,89.2,-14,89.5,0.2C89.8,14.4,84.5,28.8,75.9,41.4C67.3,54,55.5,64.8,42.1,72.4C28.7,80,14.3,84.4,0,84.4C-14.3,84.4,-28.7,80,-41.9,72.3C-55.1,64.6,-67.2,53.6,-75.4,40.7C-83.6,27.8,-88,13.9,-87.5,0.3C-87,-13.3,-81.6,-26.6,-73.4,-38.7C-65.2,-50.8,-54.2,-61.7,-41.4,-68.8C-28.6,-75.9,-14.3,-79.2,0.4,-80C15.1,-80.8,30.2,-79.1,42.7,-73.4Z" transform="translate(100 100)" />
                </svg>
            </div>

            <div class="relative z-10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6">
                <div>
                    <h2 class="text-3xl font-extrabold tracking-tight">NFC-e (emissão manual)</h2>
                    <p class="mt-2 text-blue-100 text-sm sm:text-base max-w-xl">Emissão avulsa modelo 65 — sem estoque/financeiro. Para venda completa use Vendas → PDV.</p>
                    <a href="{{ route('nfces.dashboard') }}" class="inline-block mt-2 text-sm text-blue-100 hover:text-white underline underline-offset-2">← Painel NFC-e</a>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
                    <a href="{{ route('nfces.inutilizar.form') }}" class="inline-flex items-center justify-center px-5 py-3 rounded-xl font-bold text-sm text-white/90 border border-white/30 hover:bg-white/10 transition-all duration-200">
                        Inutilizar
                    </a>
                    <a href="{{ route('nfces.create') }}" class="inline-flex items-center justify-center px-6 py-3 bg-white text-blue-700 rounded-xl font-bold text-sm uppercase tracking-wider shadow-md hover:shadow-xl hover:bg-gray-50 transform hover:-translate-y-1 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 focus:ring-offset-blue-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Emitir Nova NFC-e
                    </a>
                </div>
            </div>
            <div class="relative z-10 mt-4">
                <a href="{{ route('nfces.laboratorio') }}" class="text-xs text-blue-200/80 hover:text-white underline underline-offset-2 transition-colors">Laboratório (homologação)</a>
            </div>
        </div>

        <!-- LISTAGEM -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead>
                        <tr class="bg-gray-50/80">
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Documento</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Emissão</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Destinatário</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-4 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        @forelse($nfces as $nfce)
                            <tr class="hover:bg-blue-50/50 transition-colors duration-200 group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-gray-900">{{ $nfce->numero }}/{{ $nfce->serie }}</div>
                                            <div class="text-[11px] font-medium text-gray-400 font-mono truncate max-w-[180px]" title="{{ $nfce->chave }}">
                                                {{ $nfce->chave ? Str::limit($nfce->chave, 20) : 'Sem chave' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-semibold">{{ $nfce->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $nfce->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-900 line-clamp-1">{{ $nfce->destinatario_nome ?: 'Consumidor final' }}</div>
                                    @if($nfce->destinatario_doc)
                                        <div class="text-[11px] text-gray-500 font-mono mt-0.5 tracking-tight">{{ $nfce->destinatario_doc }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-extrabold text-gray-900 tracking-tight">
                                        R$ {{ number_format($nfce->valor_total, 2, ',', '.') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold tracking-wide uppercase {{ $nfce->status_badge_class }}">
                                        @if($nfce->status === 'autorizada')
                                            <svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                        @endif
                                        @if(in_array($nfce->status, ['pendente_transmissao', 'processando'], true))
                                            <svg class="w-3.5 h-3.5 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        @endif
                                        @if(in_array($nfce->status, ['erro', 'rejeitada', 'cancelada'], true))
                                            <svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                        @endif
                                        {{ $nfce->status_label }}
                                        @if($nfce->c_stat)
                                            <span class="ml-1 opacity-70 font-mono normal-case">{{ $nfce->c_stat }}</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('nfces.show', $nfce->id) }}" class="inline-flex p-2 text-blue-600 hover:text-blue-800 bg-blue-50/50 hover:bg-blue-100 rounded-lg transition-all duration-200" title="Visualizar Detalhes">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="bg-gray-50 p-5 rounded-full mb-4 ring-1 ring-gray-100 shadow-inner">
                                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <p class="text-gray-900 font-bold text-lg">Nenhuma NFC-e encontrada.</p>
                                        <p class="text-gray-500 text-sm mt-1 max-w-sm">Emita um cupom avulso ou use o PDV para venda completa com estoque.</p>
                                        <a href="{{ route('nfces.create') }}" class="mt-6 inline-flex items-center px-5 py-2.5 bg-blue-50 text-blue-700 border border-blue-100 rounded-xl font-bold text-sm hover:bg-blue-100 hover:border-blue-200 transition-all duration-200">
                                            Emitir Nova NFC-e
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($nfces->hasPages())
                <div class="bg-gray-50/50 px-6 py-4 border-t border-gray-100">
                    {{ $nfces->links() }}
                </div>
            @endif
        </div>
    </div>

    <style>
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</x-app-layout>
