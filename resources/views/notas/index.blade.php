<x-app-layout>
    <div class="space-y-8 animate-fade-in-up">
        <!-- HEADER -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-800 p-8 sm:p-10 shadow-lg text-white">
            <!-- Decorative SVG background -->
            <div class="absolute -top-24 -right-24 opacity-20 transform rotate-12 pointer-events-none">
                <svg width="300" height="300" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                    <path fill="#FFFFFF" d="M42.7,-73.4C55.6,-66.4,66.5,-54.6,74.9,-41.3C83.3,-28,89.2,-14,89.5,0.2C89.8,14.4,84.5,28.8,75.9,41.4C67.3,54,55.5,64.8,42.1,72.4C28.7,80,14.3,84.4,0,84.4C-14.3,84.4,-28.7,80,-41.9,72.3C-55.1,64.6,-67.2,53.6,-75.4,40.7C-83.6,27.8,-88,13.9,-87.5,0.3C-87,-13.3,-81.6,-26.6,-73.4,-38.7C-65.2,-50.8,-54.2,-61.7,-41.4,-68.8C-28.6,-75.9,-14.3,-79.2,0.4,-80C15.1,-80.8,30.2,-79.1,42.7,-73.4Z" transform="translate(100 100)" />
                </svg>
            </div>
            
            <div class="relative z-10 flex flex-col sm:flex-row justify-between items-start sm:items-center">
                <div>
                    <h2 class="text-3xl font-extrabold tracking-tight">NFS-e (emissão manual)</h2>
                    <p class="mt-2 text-blue-100 text-sm sm:text-base max-w-xl">Emissão avulsa de notas de serviço — sem estoque/venda. Para o fluxo completo use Vendas → Documentos.</p>
                </div>
                <a href="{{ route('notas.create') }}" class="mt-6 sm:mt-0 inline-flex items-center px-6 py-3 bg-white text-blue-700 rounded-xl font-bold text-sm uppercase tracking-wider shadow-md hover:shadow-xl hover:bg-gray-50 transform hover:-translate-y-1 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 focus:ring-offset-blue-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Emitir Nova Nota
                </a>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="bg-white/90 backdrop-blur-md p-6 rounded-2xl shadow-sm border border-gray-100 transition-all hover:shadow-md">
            <form method="GET" action="{{ route('notas.index') }}" class="flex flex-col lg:flex-row gap-5 items-end">
                <div class="flex-1 w-full">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Busca Rápida</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome, CNPJ ou Número da Nota"
                               class="pl-10 block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors duration-200">
                    </div>
                </div>

                <div class="w-full lg:w-48">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Status da Nota</label>
                    <select name="status" class="block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors duration-200 cursor-pointer">
                        <option value="">Todos os status</option>
                        <option value="autorizada" {{ request('status') == 'autorizada' ? 'selected' : '' }}>✓ Emitida / Autorizada</option>
                        <option value="processando" {{ request('status') == 'processando' ? 'selected' : '' }}>⟳ Em Processamento</option>
                        <option value="erro" {{ request('status') == 'erro' ? 'selected' : '' }}>⚠ Com Erro</option>
                        <option value="rascunho" {{ request('status') == 'rascunho' ? 'selected' : '' }}>📝 Rascunho</option>
                    </select>
                </div>

                <div class="w-full lg:w-40">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Data Inicial</label>
                    <input type="date" name="data_inicio" value="{{ request('data_inicio') }}"
                           class="block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors duration-200 cursor-pointer">
                </div>
                
                <div class="w-full lg:w-40">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Data Final</label>
                    <input type="date" name="data_fim" value="{{ request('data_fim') }}"
                           class="block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors duration-200 cursor-pointer">
                </div>

                <div class="w-full lg:w-auto mt-4 lg:mt-0">
                    <button type="submit" class="w-full lg:w-auto flex items-center justify-center bg-gray-900 text-white px-6 py-[11px] rounded-xl font-semibold hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-all duration-200 shadow-sm hover:shadow active:scale-95">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- LISTAGEM -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead>
                        <tr class="bg-gray-50/80">
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Documento</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Emissão</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Tomador / Cliente</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Valor Líquido</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-4 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        @forelse($notas as $nota)
                            <tr class="hover:bg-blue-50/50 transition-colors duration-200 group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-gray-900">{{ $nota->numero_nfse ? str_pad($nota->numero_nfse, 8, '0', STR_PAD_LEFT) : 'Rascunho' }}</div>
                                            <div class="text-[11px] font-medium text-gray-400">ID #{{ $nota->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-semibold">{{ $nota->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $nota->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-900 line-clamp-1">{{ $nota->tomador_nome }}</div>
                                    <div class="text-[11px] text-gray-500 font-mono mt-0.5 tracking-tight">
                                        @if(strlen($nota->tomador_cnpj) == 14)
                                            {{ preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $nota->tomador_cnpj) }}
                                        @elseif(strlen($nota->tomador_cnpj) == 11)
                                            {{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $nota->tomador_cnpj) }}
                                        @else
                                            {{ $nota->tomador_cnpj }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-extrabold text-gray-900 tracking-tight">
                                        R$ {{ number_format($nota->valor_servico, 2, ',', '.') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
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
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold tracking-wide {{ $style }}">
                                        @if($nota->status == 'autorizada') <svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg> @endif
                                        @if($nota->status == 'processando') <svg class="w-3.5 h-3.5 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> @endif
                                        @if($nota->status == 'erro') <svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg> @endif
                                        {{ $nota->status_label ?? ucfirst($nota->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('notas.show', $nota->id) }}" class="p-2 text-blue-600 hover:text-blue-800 bg-blue-50/50 hover:bg-blue-100 rounded-lg transition-all duration-200" title="Visualizar Detalhes">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </a>
                                        
                                        @if(in_array($nota->status, ['criada', 'rascunho', 'erro']))
                                            <form action="{{ route('notas.destroy', $nota->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Tem certeza que deseja excluir esta nota permanentemente?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 text-red-600 hover:text-red-800 bg-red-50/50 hover:bg-red-100 rounded-lg transition-all duration-200" title="Excluir Rascunho">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="bg-gray-50 p-5 rounded-full mb-4 ring-1 ring-gray-100 shadow-inner">
                                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <p class="text-gray-900 font-bold text-lg">Nenhuma nota fiscal encontrada.</p>
                                        <p class="text-gray-500 text-sm mt-1 max-w-sm">Ajuste os filtros de busca acima ou clique em "Emitir Nova Nota" para começar a faturar.</p>
                                        <a href="{{ route('notas.create') }}" class="mt-6 inline-flex items-center px-5 py-2.5 bg-blue-50 text-blue-700 border border-blue-100 rounded-xl font-bold text-sm hover:bg-blue-100 hover:border-blue-200 transition-all duration-200">
                                            Emitir Nova Nota
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINAÇÃO -->
            @if($notas->hasPages())
                <div class="bg-gray-50/50 px-6 py-4 border-t border-gray-100">
                    {{ $notas->links() }}
                </div>
            @endif
        </div>
    </div>
    
    <!-- Animação Customizada CSS -->
    <style>
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
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
