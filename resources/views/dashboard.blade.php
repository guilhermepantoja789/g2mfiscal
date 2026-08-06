<x-app-layout>
    <div class="space-y-6">
        <x-page-header
            title="Dashboard Geral"
            subtitle="Visão completa de emissões fiscais e recebimentos financeiros."
        >
            <x-slot name="help">
                <x-help-panel id="dashboard" :open="false">
                    <ul class="list-disc space-y-1 pl-4 text-sm text-slate-700">
                        <li>Use o filtro de período para consolidar NFS-e, NFC-e e cobranças.</li>
                        <li>Atalho <kbd class="rounded border border-slate-200 bg-white px-1 text-xs">⌘K</kbd> abre a busca rápida de telas.</li>
                        <li>Módulos habilitados da empresa aparecem na barra de ícones à esquerda (ou na barra inferior no celular).</li>
                    </ul>
                </x-help-panel>
            </x-slot>
        </x-page-header>

            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-8">
                <form method="GET" action="{{ route('dashboard') }}" id="filterForm">

                    <div class="flex flex-wrap gap-2 mb-4 border-b border-gray-100 pb-3">
                        <span class="text-xs font-bold text-gray-500 uppercase self-center mr-2">Período Rápido:</span>
                        <button type="button" onclick="setDateRange('today')" class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 transition">Hoje</button>
                        <button type="button" onclick="setDateRange('month')" class="px-3 py-1 text-xs font-medium rounded-full bg-blue-50 hover:bg-blue-100 text-blue-700 transition">Este Mês</button>
                        <button type="button" onclick="setDateRange('last_month')" class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 transition">Mês Passado</button>
                        <button type="button" onclick="setDateRange('year')" class="px-3 py-1 text-xs font-medium rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition">Este Ano</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">De</label>
                            <input type="date" name="data_inicio" id="data_inicio" value="{{ request('data_inicio', now()->startOfMonth()->format('Y-m-d')) }}"
                                   class="block w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Até</label>
                            <input type="date" name="data_fim" id="data_fim" value="{{ request('data_fim', now()->endOfMonth()->format('Y-m-d')) }}"
                                   class="block w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Cliente</label>
                            <select name="cliente_id" class="block w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todos os Clientes</option>
                                @foreach($filtroClientes as $c)
                                    <option value="{{ $c->id }}" {{ request('cliente_id') == $c->id ? 'selected' : '' }}>
                                        {{ Str::limit($c->razao_social, 20) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Serviço</label>
                            <select name="servico_id" class="block w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todos os Serviços</option>
                                @foreach($filtroServicos as $s)
                                    <option value="{{ $s->id }}" {{ request('servico_id') == $s->id ? 'selected' : '' }}>
                                        {{ Str::limit($s->nome, 20) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-bold hover:bg-blue-700 transition shadow-sm">
                                Filtrar
                            </button>
                            @if(request()->anyFilled(['cliente_id', 'servico_id', 'data_inicio']))
                                <a href="{{ route('dashboard') }}" class="flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 text-sm font-medium" title="Limpar">
                                    X
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-3">Fluxo de Caixa (Vencimento no Período)</h2>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 lg:grid-cols-3 mb-8">

                <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-green-600 relative group">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Recebido Real</dt>
                                    <dd class="mt-1 text-2xl font-bold text-gray-900">R$ {{ number_format($stats['fin_realizado'] ?? 0, 2, ',', '.') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-yellow-400">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">A Receber</dt>
                                    <dd class="mt-1 text-2xl font-bold text-gray-900">R$ {{ number_format($stats['fin_pendente'] ?? 0, 2, ',', '.') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-red-500">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Inadimplência</dt>
                                    <dd class="mt-1 text-2xl font-bold text-gray-900">R$ {{ number_format($stats['fin_vencido'] ?? 0, 2, ',', '.') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-3">Dados Fiscais (Competência/Emissão)</h2>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-blue-500">
                    <div class="p-5">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Faturamento Emitido</dt>
                            <dd class="mt-1 text-2xl font-bold text-gray-900">
                                R$ {{ number_format($stats['faturamento'], 2, ',', '.') }}
                            </dd>
                        </dl>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-green-500">
                    <div class="p-5">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Notas Autorizadas</dt>
                            <dd class="mt-1 text-2xl font-bold text-gray-900">
                                {{ $stats['notas_emitidas'] }}
                            </dd>
                        </dl>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-red-400">
                    <div class="p-5">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Provisão Impostos</dt>
                            <dd class="mt-1 text-2xl font-bold text-gray-900">
                                R$ {{ number_format($stats['impostos_total'], 2, ',', '.') }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            </div>

            <!-- INÍCIO: ACOMPANHAMENTO DO DAS -->
            @if(isset($dasAtual))
            <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-3 mt-4">Simples Nacional (DAS)</h2>
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 mb-8">
                <!-- Card Resumo do Mês Atual -->
                <div class="bg-white overflow-hidden shadow rounded-lg p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2">DAS - {{ Carbon\Carbon::createFromFormat('Y-m', $dasAtual->competencia)->translatedFormat('F/Y') }}</h3>
                        <p class="text-sm text-gray-500 mb-4">Estimativa para as notas autorizadas deste mês sem ISS retido.</p>
                        
                        <div class="mt-2 text-3xl font-bold {{ $dasAtual->status == 'pago' ? 'text-green-600' : 'text-gray-900' }}">
                            R$ {{ number_format($dasAtual->valor_pago ?? $dasAtual->valor_estimado, 2, ',', '.') }}
                        </div>
                        <div class="mt-1 flex items-center">
                            @if($dasAtual->status == 'pago')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Pago em {{ Carbon\Carbon::parse($dasAtual->data_pagamento)->format('d/m/Y') }}</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pendente</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Lista de Provisão Total e Histórico -->
                <div class="lg:col-span-2 bg-white overflow-hidden shadow rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Histórico de Pagamentos DAS</h3>
                        <div class="text-right">
                            <span class="text-sm text-gray-500">Provisão Acumulada:</span>
                            <span class="ml-2 text-lg font-bold {{ $provisaoDasTotal > 0 ? 'text-red-600' : 'text-green-600' }}">
                                R$ {{ number_format($provisaoDasTotal, 2, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider pb-2">Competência</th>
                                    <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider pb-2">Estimado</th>
                                    <th class="text-center text-xs font-medium text-gray-500 uppercase tracking-wider pb-2">Status</th>
                                    <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider pb-2">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($historicoDas as $das)
                                    <tr>
                                        <td class="py-2 text-sm text-gray-900 font-medium">{{ Carbon\Carbon::createFromFormat('Y-m', $das->competencia)->format('m/Y') }}</td>
                                        <td class="py-2 text-sm text-right">R$ {{ number_format($das->valor_estimado, 2, ',', '.') }}</td>
                                        <td class="py-2 text-center">
                                            @if($das->status == 'pago')
                                                <span class="text-xs font-bold text-green-600">PAGO</span>
                                            @else
                                                <span class="text-xs font-bold text-yellow-500">PENDENTE</span>
                                            @endif
                                        </td>
                                        <td class="py-2 text-right">
                                            @if($das->status == 'pendente')
                                                <button type="button" onclick="openDasModal('{{ $das->competencia }}', {{ $das->valor_estimado }})" class="text-blue-600 hover:text-blue-900 text-sm font-medium">Informar Pgto.</button>
                                            @else
                                                <span class="text-gray-400 text-sm">R$ {{ number_format($das->valor_pago, 2, ',', '.') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-2 text-sm text-center text-gray-500">Nenhum registro encontrado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
            <!-- FIM: ACOMPANHAMENTO DO DAS -->

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2 bg-white overflow-hidden shadow rounded-lg p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Evolução de Faturamento ({{ $stats['agrupamento'] }})</h3>
                    <div class="relative h-72 w-full">
                        <canvas id="faturamentoChart"></canvas>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Divisão de Impostos</h3>
                    <div class="relative h-64 w-full flex justify-center">
                        <canvas id="impostosChart"></canvas>
                    </div>
                </div>
            </div>

            @if($topClientes->count() > 0)
                <div class="mt-8 bg-white overflow-hidden shadow rounded-lg p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        @if(request('cliente_id'))
                            Cliente Selecionado
                        @else
                            Top Clientes no Período
                        @endif
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qtd. Notas</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total (R$)</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($topClientes as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item->cliente->razao_social }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">{{ $item->qtd_notas }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">R$ {{ number_format($item->total_gasto, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- INÍCIO: ACOMPANHAMENTO ASSÍNCRONO DE NOTAS -->
            @if(isset($ultimasNotas) && $ultimasNotas->count() > 0)
                <div class="mt-8 bg-white overflow-hidden shadow rounded-lg p-6 border-t-4 border-blue-500">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Monitor de Emissão de Notas</h3>
                        <a href="{{ route('notas.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">Ver Todas &rarr;</a>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">Acompanhe o status das últimas notas processadas em segundo plano.</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data/Hora</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor (R$)</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($ultimasNotas as $nota)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $nota->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ Str::limit($nota->cliente->razao_social ?? $nota->tomador_nome, 30) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                        {{ number_format($nota->valor_servico, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                        @if($nota->status == 'autorizada')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Autorizada
                                            </span>
                                        @elseif($nota->status == 'processando')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 inline-flex items-center">
                                                <svg class="animate-spin -ml-1 mr-2 h-3 w-3 text-blue-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                Processando...
                                            </span>
                                        @elseif($nota->status == 'erro')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800" title="{{ $nota->mensagem_erro }}">
                                                Falha
                                            </span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ ucfirst($nota->status) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
            <!-- FIM: ACOMPANHAMENTO ASSÍNCRONO DE NOTAS -->

    </div>

    <!-- Modal Informar Pagamento DAS -->
    <div id="dasModal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeDasModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('das.pagar') }}" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Registrar Pagamento DAS
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <input type="hidden" name="competencia" id="das_competencia">
                                    
                                    <div>
                                        <label for="valor_pago" class="block text-sm font-medium text-gray-700">Valor Pago (R$)</label>
                                        <input type="number" step="0.01" name="valor_pago" id="das_valor_pago" class="mt-1 flex-1 block w-full rounded-md sm:text-sm border-gray-300 focus:ring-blue-500 focus:border-blue-500" required>
                                    </div>
                                    
                                    <div>
                                        <label for="data_pagamento" class="block text-sm font-medium text-gray-700">Data do Pagamento</label>
                                        <input type="date" name="data_pagamento" id="das_data_pagamento" value="{{ date('Y-m-d') }}" class="mt-1 flex-1 block w-full rounded-md sm:text-sm border-gray-300 focus:ring-blue-500 focus:border-blue-500" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Confirmar Pagamento
                        </button>
                        <button type="button" onclick="closeDasModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function openDasModal(competencia, valorEstimado) {
            document.getElementById('das_competencia').value = competencia;
            document.getElementById('das_valor_pago').value = valorEstimado.toFixed(2);
            document.getElementById('dasModal').classList.remove('hidden');
        }

        function closeDasModal() {
            document.getElementById('dasModal').classList.add('hidden');
        }

        // Função para preencher datas automaticamente
        function setDateRange(type) {
            const today = new Date();
            let start = new Date();
            let end = new Date();

            const formatDate = (date) => date.toISOString().split('T')[0];

            if (type === 'today') {
                // Já está definido como today
            }
            else if (type === 'month') {
                start = new Date(today.getFullYear(), today.getMonth(), 1);
                end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            }
            else if (type === 'last_month') {
                start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                end = new Date(today.getFullYear(), today.getMonth(), 0);
            }
            else if (type === 'year') {
                start = new Date(today.getFullYear(), 0, 1);
                end = new Date(today.getFullYear(), 11, 31);
            }

            document.getElementById('data_inicio').value = formatDate(start);
            document.getElementById('data_fim').value = formatDate(end);

            // Submete o formulário automaticamente para UX fluida
            document.getElementById('filterForm').submit();
        }

        g2mPageInit('dashboard-home', function () {
                const __guard = document.getElementById('faturamentoChart');
                if (!__guard || typeof Chart === 'undefined') return;
                document.querySelectorAll('canvas').forEach(c => { try { Chart.getChart(c)?.destroy(); } catch (e) {} });
            // Configuração dos Gráficos
            const ctxFaturamento = document.getElementById('faturamentoChart').getContext('2d');
            new Chart(ctxFaturamento, {
                type: 'line',
                data: {
                    labels: @json($stats['grafico_labels']),
                    datasets: [{
                        label: 'Faturamento',
                        data: @json($stats['grafico_valores']),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            const ctxImpostos = document.getElementById('impostosChart').getContext('2d');
            const fed = {{ $stats['impostos_federais'] }};
            const mun = {{ $stats['impostos_municipais'] }};
            const hasData = (fed + mun) > 0;

            new Chart(ctxImpostos, {
                type: 'doughnut',
                data: {
                    labels: ['Federal', 'Municipal/ISS'],
                    datasets: [{
                        data: hasData ? [fed, mun] : [1, 0],
                        backgroundColor: hasData ? ['#EF4444', '#10B981'] : ['#E5E7EB', '#FFF'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        });
    </script>
</x-app-layout>
