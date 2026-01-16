<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">

            <div class="md:flex md:items-center md:justify-between mb-6">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                        Agendamentos e Recorrências
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">Gerencie seus contratos mensais e veja a previsão de emissão.</p>
                </div>
                <div class="mt-4 flex md:ml-4 md:mt-0">
                    <a href="{{ route('recorrencias.create') }}" class="ml-3 inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                        + Nova Regra
                    </a>
                </div>
            </div>

            <div class="flex items-center justify-between bg-white p-4 rounded-t-lg border-b border-gray-200 shadow-sm">
                <a href="{{ route('recorrencias.index', ['mes_ano' => $dataBase->copy()->subMonth()->format('Y-m')]) }}" class="text-gray-500 hover:text-gray-900 font-bold">
                    &larr; Anterior
                </a>
                <h3 class="text-lg font-bold text-gray-800 capitalize">
                    {{ $dataBase->translatedFormat('F Y') }}
                </h3>
                <a href="{{ route('recorrencias.index', ['mes_ano' => $dataBase->copy()->addMonth()->format('Y-m')]) }}" class="text-gray-500 hover:text-gray-900 font-bold">
                    Próximo &rarr;
                </a>
            </div>

            <div class="bg-white shadow rounded-b-lg mb-8 overflow-hidden">
                <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    <div class="py-2">Dom</div><div class="py-2">Seg</div><div class="py-2">Ter</div><div class="py-2">Qua</div>
                    <div class="py-2">Qui</div><div class="py-2">Sex</div><div class="py-2">Sáb</div>
                </div>

                <div class="grid grid-cols-7 bg-gray-200 gap-px">
                    @php
                        // Cálculos de Calendário
                        $inicioMes = $dataBase->copy()->startOfMonth();
                        $fimMes = $dataBase->copy()->endOfMonth();
                        $diaSemanaInicio = $inicioMes->dayOfWeek; // 0 (Dom) a 6 (Sab)

                        // Data de hoje real (sem hora) para comparação
                        $dataAtualReal = now()->startOfDay();

                        // Preenchimento dias vazios antes do dia 1
                        for ($i = 0; $i < $diaSemanaInicio; $i++) {
                            echo '<div class="bg-white min-h-[100px] p-2 bg-gray-50"></div>';
                        }
                    @endphp

                    @for($dia = 1; $dia <= $fimMes->day; $dia++)
                        @php
                            $hoje = \Carbon\Carbon::create($dataBase->year, $dataBase->month, $dia)->startOfDay();
                            $hojeFormatado = $hoje->format('Y-m-d');
                            $ehHoje = $hoje->eq($dataAtualReal);

                            // Verifica se é passado (bloqueio)
                            $isPast = $hoje->lt($dataAtualReal);

                            // Filtra eventos deste dia específico
                            $eventosDoDia = collect($eventos)->where('data', $hojeFormatado);
                        @endphp

                        <div class="relative min-h-[100px] p-2 transition group {{ $isPast ? 'bg-gray-100 cursor-not-allowed opacity-75' : 'bg-white hover:bg-gray-50 cursor-pointer' }}"
                             @if(!$isPast) onclick="window.location.href='{{ route('recorrencias.create', ['data' => $hojeFormatado]) }}'" @endif>

                            <span class="text-sm font-bold {{ $ehHoje ? 'bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center' : ($isPast ? 'text-gray-400' : 'text-gray-700') }}">
                                {{ $dia }}
                            </span>

                            @if(!$isPast)
                                <button class="absolute top-2 right-2 hidden group-hover:block text-blue-600 hover:text-blue-800 text-xs font-bold" title="Agendar neste dia">
                                    +
                                </button>
                            @endif

                            <div class="mt-1 space-y-1">
                                @foreach($eventosDoDia as $evt)
                                    @if($evt['tipo'] == 'nota')
                                        <a href="{{ route('notas.show', $evt['id']) }}" class="block px-1 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-800 truncate border border-green-200 z-10 relative" onclick="event.stopPropagation();" title="R$ {{ number_format($evt['valor'], 2, ',', '.') }} - Emitida">
                                            ✓ {{ Str::limit($evt['titulo'], 15) }}
                                        </a>
                                    @else
                                        <a href="{{ route('recorrencias.edit', $evt['id']) }}" class="block px-1 py-0.5 rounded text-[10px] font-medium bg-yellow-100 text-yellow-800 truncate border border-yellow-200 border-dashed z-10 relative" onclick="event.stopPropagation();" title="Previsto: R$ {{ number_format($evt['valor'], 2, ',', '.') }}">
                                            ⏲ {{ Str::limit($evt['titulo'], 15) }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Seus Contratos Ativos</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Próxima Execução</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modo</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($recorrencias as $rec)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $rec->descricao_recorrencia }}
                                    @if(!$rec->ativo) <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Pausada</span> @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $rec->tomador_nome }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">R$ {{ number_format($rec->valor_servico, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $rec->proxima_execucao->format('d/m/Y') }} <br>
                                    <span class="text-xs text-gray-400">({{ ucfirst($rec->frequencia) }})</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($rec->emitir_automaticamente)
                                        <span class="text-green-600 font-bold flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                            Auto
                                        </span>
                                    @else
                                        <span class="text-gray-500 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            Rascunho
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('recorrencias.edit', $rec->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</a>

                                    <form action="{{ route('recorrencias.destroy', $rec->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Tem certeza?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                    Nenhuma recorrência ativa. Clique no calendário ou em "Nova Regra" para criar.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
