<x-app-layout>
    <div class="space-y-6">
        <x-page-header
            title="Documentos"
            subtitle="Faturamento, funil fiscal e mix de canal/forma no período."
        >
            <x-slot name="actions">
                <a href="{{ route('documentos.create') }}" class="inline-flex items-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-hover">Novo documento</a>
                <a href="{{ route('documentos.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Ver lista</a>
            </x-slot>
            <x-slot name="help">
                <x-help-panel id="documentos">
                    <ul class="list-disc space-y-1 pl-4 text-sm text-slate-700">
                        <li>Documentos comerciais unificam venda de serviço, produto e compra via XML.</li>
                        <li>Após autorização fiscal, estoque, financeiro e contábil são atualizados automaticamente.</li>
                        <li>Use “Importar XML” na lista para entradas de NF-e 55.</li>
                    </ul>
                </x-help-panel>
            </x-slot>
        </x-page-header>

        <x-dashboard.periodo-filter :action="route('documentos.dashboard')" />

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <x-dashboard.kpi-card label="Faturamento (vendas)" :value="'R$ '.number_format($stats['faturamento'], 2, ',', '.')" border="green" />
            <x-dashboard.kpi-card label="Compras" :value="'R$ '.number_format($stats['compras'], 2, ',', '.')" border="indigo" />
            <x-dashboard.kpi-card label="Docs no período" :value="(string) $stats['qtd_periodo']" border="blue" />
            <x-dashboard.kpi-card label="Com erro" :value="(string) ($stats['funil']['erro'] ?? 0)" border="red" />
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 mb-8">
            <div class="lg:col-span-2">
                <x-dashboard.chart-canvas id="vendasChart" title="Vendas autorizadas por dia" />
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Funil por status</h3>
                <ul class="space-y-2 text-sm">
                    @php
                        $labels = [
                            'rascunho' => 'Rascunho',
                            'confirmado' => 'Confirmado',
                            'processando_fiscal' => 'Processando',
                            'autorizado' => 'Autorizado',
                            'erro' => 'Erro',
                            'cancelado' => 'Cancelado',
                        ];
                    @endphp
                    @foreach($labels as $key => $label)
                        <li class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-600">{{ $label }}</span>
                            <span class="font-semibold text-gray-900">{{ $stats['funil'][$key] ?? 0 }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Mix por canal</h3>
                <table class="min-w-full divide-y">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs uppercase text-gray-500">Canal</th>
                        <th class="px-3 py-2 text-right text-xs uppercase text-gray-500">Qtd</th>
                        <th class="px-3 py-2 text-right text-xs uppercase text-gray-500">Total</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @forelse($stats['por_canal'] as $row)
                        <tr>
                            <td class="px-3 py-2 text-sm">{{ $row['canal'] }}</td>
                            <td class="px-3 py-2 text-sm text-right">{{ $row['qtd'] }}</td>
                            <td class="px-3 py-2 text-sm text-right">R$ {{ number_format($row['total'], 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-6 text-center text-sm text-gray-500">Sem autorizados no período.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Mix por forma</h3>
                <table class="min-w-full divide-y">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs uppercase text-gray-500">Forma</th>
                        <th class="px-3 py-2 text-right text-xs uppercase text-gray-500">Qtd</th>
                        <th class="px-3 py-2 text-right text-xs uppercase text-gray-500">Total</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @forelse($stats['por_forma'] as $row)
                        <tr>
                            <td class="px-3 py-2 text-sm">{{ $row['nome'] }}</td>
                            <td class="px-3 py-2 text-sm text-right">{{ $row['qtd'] }}</td>
                            <td class="px-3 py-2 text-sm text-right">R$ {{ number_format($row['total'], 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-6 text-center text-sm text-gray-500">Sem dados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Últimos documentos</h3>
                <a href="{{ route('documentos.index') }}" class="text-sm text-blue-600 hover:text-blue-500">Ver lista →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">#</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Parceiro</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs uppercase text-gray-500">Valor</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @foreach($stats['recentes'] as $doc)
                        <tr>
                            <td class="px-4 py-3 text-sm"><a href="{{ route('documentos.show', $doc->id) }}" class="text-blue-600 hover:underline">{{ $doc->id }}</a></td>
                            <td class="px-4 py-3 text-sm uppercase">{{ $doc->tipo }} / {{ $doc->canal_fiscal }}</td>
                            <td class="px-4 py-3 text-sm">{{ $doc->cliente?->razao_social ?? $doc->fornecedor?->razao_social ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $doc->status_label }}</td>
                            <td class="px-4 py-3 text-sm text-right">R$ {{ number_format($doc->valor_total, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new Chart(document.getElementById('vendasChart'), {
                    type: 'line',
                    data: {
                        labels: @json($stats['grafico_labels']),
                        datasets: [{
                            label: 'Vendas',
                            data: @json($stats['grafico_valores']),
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
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
            });
        </script>
    @endpush
</x-app-layout>
