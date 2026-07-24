<x-app-layout>
    <div class="py-2 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">DRE gerencial</h1>
                <p class="mt-1 text-sm text-gray-500">Receitas e despesas a partir de lançamentos contábeis.</p>
            </div>
            <a href="{{ route('contabil.dashboard') }}" class="text-sm text-teal-700 hover:underline">← Área Contábil</a>
        </div>

        <x-dashboard.periodo-filter :action="route('contabil.dre')" />

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-dashboard.kpi-card label="Receitas" :value="'R$ '.number_format($dre['receitas'], 2, ',', '.')" border="green" />
            <x-dashboard.kpi-card label="Despesas" :value="'R$ '.number_format($dre['despesas'], 2, ',', '.')" border="rose" />
            <x-dashboard.kpi-card label="Resultado" :value="'R$ '.number_format($dre['resultado'], 2, ',', '.')" border="teal" />
        </div>

        <div class="bg-white shadow rounded-lg border overflow-hidden">
            <table class="min-w-full divide-y text-sm">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Conta</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Tipo</th>
                    <th class="px-4 py-3 text-right text-xs uppercase text-gray-500">Valor</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($dre['linhas'] as $linha)
                    <tr>
                        <td class="px-4 py-3 font-mono">{{ $linha['codigo'] }} — {{ $linha['nome'] }}</td>
                        <td class="px-4 py-3">{{ $linha['tipo'] }}</td>
                        <td class="px-4 py-3 text-right">R$ {{ number_format($linha['valor'], 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">Sem movimentos no período.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
