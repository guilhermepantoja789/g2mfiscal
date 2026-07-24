<x-app-layout>
    <div class="py-2">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Fornecedores</h1>
                <p class="mt-1 text-sm text-gray-500">Cadastro e compras no período.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('fornecedores.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-bold hover:bg-blue-700">+ Novo fornecedor</a>
                <a href="{{ route('fornecedores.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Lista completa</a>
            </div>
        </div>

        <x-dashboard.periodo-filter :action="route('fornecedores.dashboard')" />

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-8">
            <x-dashboard.kpi-card label="Cadastrados" :value="(string) $stats['total']" border="blue" />
            <x-dashboard.kpi-card label="Compras no período" :value="'R$ '.number_format($stats['compras_total'], 2, ',', '.')" border="indigo" />
            @if($incluirFinanceiro && $stats['a_pagar_aberto'] !== null)
                <x-dashboard.kpi-card label="A pagar (fornecedores)" :value="'R$ '.number_format($stats['a_pagar_aberto'], 2, ',', '.')" border="amber" />
            @endif
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Compras por fornecedor</h3>
                <table class="min-w-full divide-y">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs uppercase text-gray-500">Fornecedor</th>
                        <th class="px-3 py-2 text-right text-xs uppercase text-gray-500">Docs</th>
                        <th class="px-3 py-2 text-right text-xs uppercase text-gray-500">Total</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @forelse($stats['compras_por_fornecedor'] as $row)
                        <tr>
                            <td class="px-3 py-2 text-sm">{{ $row->fornecedor?->razao_social ?? ('#'.$row->fornecedor_id) }}</td>
                            <td class="px-3 py-2 text-sm text-right">{{ $row->qtd }}</td>
                            <td class="px-3 py-2 text-sm text-right">R$ {{ number_format($row->total, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-6 text-center text-sm text-gray-500">Sem compras autorizadas no período.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Preview</h3>
                    <a href="{{ route('fornecedores.index') }}" class="text-sm text-blue-600">Ver todos →</a>
                </div>
                <table class="min-w-full divide-y">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs uppercase text-gray-500">Razão social</th>
                        <th class="px-3 py-2 text-left text-xs uppercase text-gray-500">CNPJ</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @forelse($stats['preview'] as $f)
                        <tr>
                            <td class="px-3 py-2 text-sm">
                                <a href="{{ route('fornecedores.edit', $f) }}" class="text-blue-600 hover:underline">{{ $f->razao_social }}</a>
                            </td>
                            <td class="px-3 py-2 text-sm font-mono">{{ $f->cnpj }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-3 py-6 text-center text-sm text-gray-500">Nenhum fornecedor.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
