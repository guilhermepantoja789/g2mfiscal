<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">

            <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Visão geral da competência atual</p>

            <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-md bg-blue-50 p-3">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Faturamento (Mês)</dt>
                                    <dd class="text-lg font-bold text-gray-900">
                                        R$ {{ number_format($stats['faturamento_mes'], 2, ',', '.') }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-md bg-green-50 p-3">
                                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Notas Emitidas</dt>
                                    <dd class="text-lg font-bold text-gray-900">
                                        {{ $stats['notas_emitidas'] }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-md bg-red-50 p-3">
                                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m8-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m0 6V4" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Impostos Estimados (ISS)</dt>
                                    <dd class="text-lg font-bold text-gray-900">
                                        R$ {{ number_format($stats['impostos_mes'], 2, ',', '.') }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-2">

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Evolução do Faturamento</h3>
                        <div class="relative h-64 w-full">
                            <canvas id="faturamentoChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Top Clientes</h3>
                        <div class="flow-root">
                            <ul role="list" class="-my-5 divide-y divide-gray-200">
                                @forelse($topClientes as $item)
                                    <li class="py-4">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex-shrink-0">
                                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-100">
                                                    <span class="text-xs font-medium leading-none text-blue-700">
                                                        {{ substr($item->cliente->razao_social, 0, 2) }}
                                                    </span>
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    {{ $item->cliente->razao_social }}
                                                </p>
                                                <p class="text-xs text-gray-500 truncate">
                                                    {{ $item->qtd_notas }} notas emitidas
                                                </p>
                                            </div>
                                            <div>
                                                <span class="inline-flex items-center shadow-sm px-2.5 py-0.5 border border-gray-300 text-sm leading-5 font-medium rounded-full text-gray-700 bg-white hover:bg-gray-50">
                                                    R$ {{ number_format($item->total_gasto, 2, ',', '.') }}
                                                </span>
                                            </div>
                                        </div>
                                    </li>
                                @empty
                                    <li class="py-4 text-center text-sm text-gray-500">
                                        Nenhuma nota emitida ainda.
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('faturamentoChart').getContext('2d');

            // Dados vindos do Controller (Convertidos para JS)
            const labels = @json($stats['grafico_labels']);
            const data = @json($stats['grafico_valores']);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Faturamento (R$)',
                        data: data,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)', // Blue-500
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                borderDash: [2, 4],
                                color: '#e5e7eb'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
    </script>
</x-app-layout>
