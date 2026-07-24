<x-app-layout>
    <div class="space-y-6">
        <x-page-header
            title="Estoque"
            subtitle="Valor em estoque, saldos críticos e movimentações do período."
        >
            <x-slot name="actions">
                <a href="{{ route('estoque.saldos') }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Ver saldos
                </a>
            </x-slot>
            <x-slot name="help">
                <x-help-panel id="estoque">
                    <ul class="list-disc space-y-1 pl-4 text-sm text-slate-700">
                        <li>Saídas ocorrem na autorização de vendas; entradas na importação de XML ou ajuste.</li>
                        <li>Cancelamento fiscal estorna o kardex do documento.</li>
                        <li>Valor em estoque = saldo × custo médio.</li>
                    </ul>
                </x-help-panel>
            </x-slot>
        </x-page-header>

        <x-dashboard.periodo-filter :action="route('estoque.dashboard')" />

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-8">
            <x-dashboard.kpi-card label="Valor em estoque" :value="'R$ '.number_format($stats['valor_estoque'], 2, ',', '.')" border="blue" hint="Saldo × custo médio" />
            <x-dashboard.kpi-card label="SKUs com saldo" :value="(string) $stats['com_saldo']" border="green" />
            <x-dashboard.kpi-card label="Zerados / negativos" :value="$stats['zerados'].' / '.$stats['negativos']" border="red" />
            <x-dashboard.kpi-card label="Entradas (período)" :value="number_format($stats['entradas'], 3, ',', '.')" border="emerald" />
            <x-dashboard.kpi-card label="Saídas (período)" :value="number_format($stats['saidas'], 3, ',', '.')" border="amber" />
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Top saídas no período</h3>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">Produto</th>
                        <th class="px-4 py-2 text-right text-xs uppercase text-gray-500">Qtd</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @forelse($stats['top_saidas'] as $row)
                        <tr>
                            <td class="px-4 py-2 text-sm">{{ $row->produto?->descricao ?? ('#'.$row->produto_id) }}</td>
                            <td class="px-4 py-2 text-sm text-right">{{ number_format($row->total_qtd, 3, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-center text-sm text-gray-500">Sem saídas no período.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Saldos mais críticos</h3>
                    <a href="{{ route('estoque.saldos') }}" class="text-sm text-blue-600 hover:text-blue-500">Ver todos →</a>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">Produto</th>
                        <th class="px-4 py-2 text-right text-xs uppercase text-gray-500">Saldo</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @forelse($stats['criticos'] as $p)
                        <tr>
                            <td class="px-4 py-2 text-sm">
                                <a href="{{ route('estoque.show', $p->id) }}" class="text-blue-600 hover:underline">{{ $p->descricao }}</a>
                            </td>
                            <td class="px-4 py-2 text-sm text-right {{ $p->estoque_atual <= 0 ? 'text-red-600 font-medium' : '' }}">
                                {{ number_format($p->estoque_atual, 3, ',', '.') }} {{ $p->unidade }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-center text-sm text-gray-500">Nenhum produto com controle de estoque.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
