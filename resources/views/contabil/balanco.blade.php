<x-app-layout>
    <div class="py-2 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Balanço gerencial</h1>
                <p class="mt-1 text-sm text-gray-500">Saldos acumulados até {{ $ate->format('d/m/Y') }}.</p>
            </div>
            <a href="{{ route('contabil.dashboard') }}" class="text-sm text-teal-700 hover:underline">← Área Contábil</a>
        </div>

        <form method="GET" action="{{ route('contabil.balanco') }}" class="bg-white p-4 rounded-lg shadow-sm flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Até</label>
                <input type="date" name="data_fim" value="{{ $ate->format('Y-m-d') }}" class="rounded-md border-gray-300 text-sm">
            </div>
            <button class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm">Filtrar</button>
        </form>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-dashboard.kpi-card label="Ativo" :value="'R$ '.number_format($balanco['ativo'], 2, ',', '.')" border="blue" />
            <x-dashboard.kpi-card label="Passivo" :value="'R$ '.number_format($balanco['passivo'], 2, ',', '.')" border="amber" />
            <x-dashboard.kpi-card label="Patrimônio" :value="'R$ '.number_format($balanco['patrimonio'], 2, ',', '.')" border="indigo" />
        </div>

        <div class="bg-white shadow rounded-lg border overflow-hidden">
            <table class="min-w-full divide-y text-sm">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Conta</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Tipo</th>
                    <th class="px-4 py-3 text-right text-xs uppercase text-gray-500">Saldo</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($balanco['linhas'] as $linha)
                    <tr>
                        <td class="px-4 py-3 font-mono">{{ $linha['codigo'] }} — {{ $linha['nome'] }}</td>
                        <td class="px-4 py-3">{{ $linha['tipo'] }}</td>
                        <td class="px-4 py-3 text-right">R$ {{ number_format($linha['saldo'], 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">Sem saldos até a data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
