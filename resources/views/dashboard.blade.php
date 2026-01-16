<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">

            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">Dashboard Geral</h1>
                <p class="mt-1 text-sm text-gray-500">Visão completa de emissões fiscais e recebimentos financeiros.</p>
            </div>

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

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
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

        document.addEventListener('DOMContentLoaded', function () {
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
