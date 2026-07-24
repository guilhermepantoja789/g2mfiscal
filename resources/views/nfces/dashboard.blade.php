<x-app-layout>
    <div class="space-y-6">
        <x-page-header
            title="NFC-e"
            subtitle="Volume autorizado, rejeições, contingência e últimos cupons."
        >
            <x-slot name="actions">
                <a href="{{ route('nfces.create') }}" class="inline-flex items-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-hover">Emitir NFC-e</a>
                <a href="{{ route('nfces.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Ver lista</a>
            </x-slot>
            <x-slot name="help">
                <x-help-panel id="nfces">
                    <ul class="list-disc space-y-1 pl-4 text-sm text-slate-700">
                        <li>Painel de emissão avulsa (sem estoque/financeiro). Para venda completa use o PDV.</li>
                        <li>Acompanhe contingência e pendências de transmissão nos KPIs.</li>
                        <li>Inutilização de numeração fica em Fiscal → NFC-e → Inutilizar.</li>
                    </ul>
                </x-help-panel>
            </x-slot>
        </x-page-header>

        <x-dashboard.periodo-filter :action="route('nfces.dashboard')" />

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-8">
            <x-dashboard.kpi-card label="Autorizadas" :value="(string) $stats['autorizadas_qtd']" border="green" :hint="'R$ '.number_format($stats['autorizadas_valor'], 2, ',', '.')" />
            <x-dashboard.kpi-card label="Canceladas" :value="(string) $stats['canceladas']" border="rose" />
            <x-dashboard.kpi-card label="Pendentes transmissão" :value="(string) $stats['pendentes']" border="amber" />
            <x-dashboard.kpi-card label="Erros / rejeitadas" :value="(string) $stats['erros']" border="red" />
            <x-dashboard.kpi-card label="Contingência" :value="(string) $stats['contingencia']" border="indigo" hint="tpEmis ≠ 1" />
        </div>

        <div class="mb-8">
            <x-dashboard.chart-canvas id="nfceChart" title="Valor autorizado por dia" />
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Últimos cupons</h3>
                <a href="{{ route('nfces.index') }}" class="text-sm text-blue-600 hover:text-blue-500">Ver lista →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Número</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Emissão</th>
                        <th class="px-4 py-3 text-right text-xs uppercase text-gray-500">Valor</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @forelse($stats['recentes'] as $nfce)
                        <tr>
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('nfces.show', $nfce->id) }}" class="text-blue-600 hover:underline">
                                    {{ $nfce->serie }}/{{ $nfce->numero ?: '—' }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $nfce->status_badge_class }}">{{ $nfce->status_label }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $nfce->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm text-right">R$ {{ number_format($nfce->valor_total ?? 0, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Nenhuma NFC-e.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new Chart(document.getElementById('nfceChart'), {
                    type: 'line',
                    data: {
                        labels: @json($stats['grafico_labels']),
                        datasets: [{
                            label: 'Valor',
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
            });
        </script>
    @endpush
</x-app-layout>
