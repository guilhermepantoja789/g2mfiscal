<x-app-layout>
    <div class="max-w-6xl mx-auto space-y-8 py-2 animate-fade-in-up">

        <!-- HEADER -->
        <div class="relative overflow-hidden rounded-3xl bg-white shadow-sm border border-gray-100 p-6 sm:p-8">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="flex items-center gap-6">
                    <a href="{{ route('nfces.index') }}" class="group flex items-center justify-center w-12 h-12 bg-gray-50 rounded-2xl hover:bg-gray-100 transition-colors border border-gray-200 shadow-sm">
                        <svg class="w-5 h-5 text-gray-500 group-hover:text-gray-700 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>

                    <div>
                        <div class="flex flex-wrap items-center gap-3 mb-1">
                            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                                NFC-e #{{ $nfce->numero }}/{{ $nfce->serie }}
                            </h1>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold tracking-wide uppercase {{ $nfce->status_badge_class }}">
                                @if($nfce->status === 'autorizada')
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                @endif
                                @if(in_array($nfce->status, ['pendente_transmissao', 'processando'], true))
                                    <svg class="w-3.5 h-3.5 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                @endif
                                {{ $nfce->status_label }}
                            </span>
                            @if($nfce->tp_emis == 9)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold tracking-wide uppercase bg-amber-50 text-amber-700 ring-1 ring-amber-600/20">Contingência</span>
                            @endif
                        </div>
                        <p class="text-sm font-medium text-gray-500">
                            ID do Sistema: #{{ $nfce->id }} &bull; Criada em {{ $nfce->created_at->format('d/m/Y \à\s H:i') }}
                            @if($nfce->c_stat)
                                &bull; cStat {{ $nfce->c_stat }}
                            @endif
                            @if($nfce->documento_comercial_id)
                                &bull; <a href="{{ route('documentos.show', $nfce->documento_comercial_id) }}" class="text-indigo-600 hover:underline">Documento #{{ $nfce->documento_comercial_id }}</a>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 w-full md:w-auto justify-start md:justify-end">
                    @if($nfce->podeImprimirDanfe())
                        <a href="{{ route('nfces.imprimir', $nfce->id) }}" target="_blank" class="flex items-center px-6 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 shadow-md hover:shadow-lg font-bold text-sm transition-all transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Baixar DANFE
                        </a>
                    @endif

                    @if($nfce->isPendenteTransmissao())
                        <form action="{{ route('nfces.transmitir', $nfce->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="flex items-center px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-xl hover:from-amber-600 hover:to-amber-700 shadow-md font-bold text-sm uppercase tracking-wide transition-all transform hover:-translate-y-0.5">
                                Transmitir SEFAZ
                            </button>
                        </form>
                    @endif

                    @if($nfce->c_stat === '539' || str_contains((string) $nfce->x_motivo, 'Duplicidade'))
                        <form action="{{ route('nfces.recuperar-duplicidade', $nfce->id) }}" method="POST" class="inline"
                              onsubmit="return confirm('Consultar na SEFAZ a chave da duplicidade e adotar o protocolo?');">
                            @csrf
                            <button type="submit" class="flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-sm font-bold text-sm transition-all">
                                Recuperar duplicidade
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-5 text-emerald-800 text-sm font-medium shadow-sm">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="rounded-2xl bg-rose-50 border border-rose-200 p-5 text-rose-800 text-sm font-medium shadow-sm">{{ session('error') }}</div>
        @endif

        @if($nfce->isPendenteTransmissao())
            <div class="rounded-2xl bg-amber-50 border border-amber-200 p-6 shadow-sm relative overflow-hidden">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-amber-800 uppercase tracking-wide">Contingência offline</h3>
                        <p class="mt-1 text-amber-800 font-medium text-sm">NFC-e emitida em contingência. Imprima o DANFE para o cliente e transmita quando a SEFAZ voltar.</p>
                    </div>
                </div>
            </div>
        @endif

        @if(in_array($nfce->status, ['erro', 'rejeitada'], true) && $nfce->x_motivo)
            <div class="rounded-2xl bg-rose-50 border border-rose-200 p-6 shadow-sm">
                <h3 class="text-sm font-bold text-rose-800 uppercase tracking-wide">Problema na emissão</h3>
                <p class="mt-2 text-rose-700 font-medium">{{ $nfce->x_motivo }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                @php $itensPayload = $nfce->payload['itens'] ?? []; @endphp
                @if(count($itensPayload))
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mr-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Itens ({{ count($itensPayload) }})</h3>
                    </div>
                    <div class="overflow-x-auto rounded-2xl border border-gray-100">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50/80">
                                    <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Descrição</th>
                                    <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider">Qtd</th>
                                    <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider">Unit.</th>
                                    <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($itensPayload as $item)
                                    @php
                                        $q = (float) ($item['quantidade'] ?? 0);
                                        $vu = (float) ($item['valor_unitario'] ?? 0);
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-900">{{ $item['descricao'] ?? '—' }}</div>
                                            <div class="text-[11px] text-gray-400 font-mono mt-0.5">
                                                NCM {{ $item['ncm'] ?? '—' }} · CFOP {{ $item['cfop'] ?? '—' }} · CSOSN {{ $item['csosn'] ?? '—' }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono">{{ number_format($q, 3, ',', '.') }} {{ $item['unidade'] ?? 'UN' }}</td>
                                        <td class="px-4 py-3 text-right font-mono">R$ {{ number_format($vu, 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right font-extrabold font-mono">R$ {{ number_format($q * $vu, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Identificação -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mr-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Identificação</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 sm:col-span-2">
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Chave de acesso</p>
                            <p class="font-bold text-gray-900 font-mono text-sm break-all">{{ $nfce->chave ?: '—' }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Protocolo</p>
                            <p class="font-bold text-gray-900 font-mono text-sm">{{ $nfce->protocolo ?: '—' }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Tipo de emissão</p>
                            <p class="font-bold text-gray-900 text-sm">{{ $nfce->tp_emis }} {{ $nfce->tp_emis == 9 ? '(offline)' : '(normal)' }}</p>
                        </div>
                        @if($nfce->x_motivo && !in_array($nfce->status, ['erro', 'rejeitada'], true))
                            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 sm:col-span-2">
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Motivo / retorno</p>
                                <p class="font-medium text-gray-800 text-sm">{{ $nfce->x_motivo }}</p>
                            </div>
                        @endif
                        @if($nfce->qr_code_url)
                            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 sm:col-span-2">
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">QR Code</p>
                                <p class="font-mono text-xs text-gray-700 break-all">{{ $nfce->qr_code_url }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Destinatário -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mr-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Destinatário</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="col-span-1 sm:col-span-2">
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Nome</p>
                            <p class="font-bold text-gray-900 text-lg">{{ $nfce->destinatario_nome ?: 'Consumidor final' }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">CPF/CNPJ</p>
                            <p class="font-bold text-gray-900 font-mono">{{ $nfce->destinatario_doc ?: 'Não informado' }}</p>
                        </div>
                    </div>
                </div>

                @if($nfce->isCancelada())
                    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-8">
                        <h3 class="text-lg font-bold text-rose-800 mb-4">Cancelamento</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Cancelada em</p>
                                <p class="font-semibold text-gray-900">{{ $nfce->cancelado_em?->format('d/m/Y H:i') ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Protocolo</p>
                                <p class="font-semibold text-gray-900 font-mono">{{ $nfce->protocolo_cancelamento ?: '—' }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Motivo</p>
                                <p class="font-medium text-gray-800">{{ $nfce->motivo_cancelamento ?: '—' }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($nfce->podeCancelar())
                    <div class="bg-white rounded-3xl shadow-sm border border-rose-100 p-8">
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Cancelar NFC-e</h3>
                        <p class="text-xs text-gray-500 mb-4">Prazo: {{ config('nfce.cancelamento_prazo_minutos', 30) }} minutos após autorização. Motivo mín. 15 caracteres. Evento SEFAZ 110111.</p>
                        <form action="{{ route('nfces.cancelar', $nfce->id) }}" method="POST" class="space-y-4"
                              onsubmit="return confirm('Confirma o cancelamento desta NFC-e na SEFAZ?');">
                            @csrf
                            <textarea name="motivo" rows="3" required minlength="15" maxlength="255"
                                      placeholder="Justificativa do cancelamento..."
                                      class="block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-900 focus:ring-rose-500 focus:border-rose-500 sm:text-sm"></textarea>
                            <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold text-sm shadow-sm transition-all">
                                Cancelar NFC-e
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <!-- Resumo sticky -->
            <div class="space-y-8">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 sticky top-8">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-6">Resumo</h3>

                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-100 mb-6">
                        <span class="block text-blue-600 text-sm font-bold mb-1">Valor total</span>
                        <span class="block text-4xl font-extrabold text-blue-900 tracking-tight">
                            R$ {{ number_format($nfce->valor_total, 2, ',', '.') }}
                        </span>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center py-2 border-b border-gray-50">
                            <span class="font-medium text-gray-500">Número / Série</span>
                            <span class="font-bold text-gray-900">{{ $nfce->numero }}/{{ $nfce->serie }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-50">
                            <span class="font-medium text-gray-500">Ambiente</span>
                            <span class="font-bold text-gray-900">{{ $nfce->ambiente == 1 ? 'Produção' : 'Homologação' }}</span>
                        </div>
                        <div class="flex justify-between items-start py-2 gap-2">
                            <span class="font-medium text-gray-500 shrink-0">Protocolo</span>
                            <span class="font-bold text-gray-900 font-mono text-xs text-right break-all">{{ $nfce->protocolo ?: '—' }}</span>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100 flex flex-col gap-2">
                        <a href="{{ route('nfces.inutilizar.form') }}" class="text-sm font-semibold text-indigo-600 hover:underline">Inutilizar numeração</a>
                        <a href="{{ route('nfces.laboratorio') }}" class="text-sm font-medium text-gray-500 hover:text-gray-800 hover:underline">Laboratório</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .animate-fade-in-up {
            animation: fadeInUp 0.4s ease-out forwards;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</x-app-layout>
