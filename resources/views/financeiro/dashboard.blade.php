<x-app-layout>
    <div class="space-y-6">
        <x-page-header
            title="Financeiro"
            subtitle="Contas a pagar/receber, vencimentos e baixas do período."
        >
            <x-slot name="actions">
                <a href="{{ route('lancamentos.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Ver lançamentos
                </a>
            </x-slot>
            <x-slot name="help">
                <x-help-panel id="financeiro">
                    <ul class="list-disc space-y-1 pl-4 text-sm text-slate-700">
                        <li>Lançamentos abertos vêm de documentos autorizados ou cadastro manual.</li>
                        <li>Baixa, estorno e cancelamento ficam na lista de lançamentos.</li>
                        <li>Aging destaca títulos vencidos há mais de 30 dias.</li>
                    </ul>
                </x-help-panel>
            </x-slot>
        </x-page-header>

        <x-dashboard.periodo-filter :action="route('financeiro.dashboard')" />

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-8">
            <x-dashboard.kpi-card label="A receber (aberto)" :value="'R$ '.number_format($stats['a_receber_aberto'], 2, ',', '.')" border="green" />
            <x-dashboard.kpi-card label="A pagar (aberto)" :value="'R$ '.number_format($stats['a_pagar_aberto'], 2, ',', '.')" border="amber" />
            <x-dashboard.kpi-card label="Vencidos" :value="'R$ '.number_format($stats['vencidos_total'], 2, ',', '.')" border="red" :hint="'Receber R$ '.number_format($stats['vencidos_receber'], 2, ',', '.').' · Pagar R$ '.number_format($stats['vencidos_pagar'], 2, ',', '.')" />
            <x-dashboard.kpi-card label="Recebido no período" :value="'R$ '.number_format($stats['recebido_periodo'], 2, ',', '.')" border="emerald" />
            <x-dashboard.kpi-card label="Pago no período" :value="'R$ '.number_format($stats['pago_periodo'], 2, ',', '.')" border="indigo" />
            <x-dashboard.kpi-card label="Aging 30+ dias" :value="'R$ '.number_format($stats['aging']['30_mais'], 2, ',', '.')" border="rose" :hint="'0–7: R$ '.number_format($stats['aging']['0_7'], 2, ',', '.').' · 8–30: R$ '.number_format($stats['aging']['8_30'], 2, ',', '.')" />
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 mb-8">
            <div class="lg:col-span-2">
                <x-dashboard.chart-canvas id="baixasChart" title="Baixas por dia (pagamento)" />
            </div>
            <x-dashboard.chart-canvas id="formasChart" title="Mix por forma de pagamento" height="h-64" />
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Próximos vencimentos</h3>
                <a href="{{ route('lancamentos.index', ['status' => 'aberto']) }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">Ver abertos →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parceiro</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimento</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    @forelse($stats['proximos_vencimentos'] as $l)
                        <tr>
                            <td class="px-4 py-3 text-sm uppercase">{{ $l->tipo }}</td>
                            <td class="px-4 py-3 text-sm">{{ $l->cliente?->razao_social ?? $l->fornecedor?->razao_social ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm {{ $l->vencimento && $l->vencimento->isPast() ? 'text-red-600 font-medium' : '' }}">
                                {{ $l->vencimento?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium">R$ {{ number_format($l->valor, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 text-sm">Nenhum lançamento aberto.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            g2mPageInit('financeiro-dashboard', function () {
                const __guard = document.getElementById('baixasChart');
                if (!__guard || typeof Chart === 'undefined') return;
                document.querySelectorAll('canvas').forEach(c => { try { Chart.getChart(c)?.destroy(); } catch (e) {} });
                const labels = @json($stats['grafico_labels']);
                const valores = @json($stats['grafico_valores']);
                const formas = @json($stats['por_forma']);

                new Chart(document.getElementById('baixasChart'), {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Baixas',
                            data: valores,
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

                const formaLabels = formas.map(f => f.nome);
                const formaValores = formas.map(f => f.total);
                const hasData = formaValores.some(v => v > 0);

                new Chart(document.getElementById('formasChart'), {
                    type: 'doughnut',
                    data: {
                        labels: hasData ? formaLabels : ['Sem dados'],
                        datasets: [{
                            data: hasData ? formaValores : [1],
                            backgroundColor: hasData
                                ? ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#64748B']
                                : ['#E5E7EB'],
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
    @endpush
</x-app-layout>
