<x-app-layout>
    <div class="py-2">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Produtos</h1>
                <p class="mt-1 text-sm text-gray-500">Cadastro, preços e valor potencial de estoque.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('produtos.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-bold hover:bg-blue-700">+ Novo produto</a>
                <a href="{{ route('produtos.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Lista completa</a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <x-dashboard.kpi-card label="Total" :value="(string) $stats['total']" border="blue" />
            <x-dashboard.kpi-card label="Ativos / inativos" :value="$stats['ativos'].' / '.$stats['inativos']" border="green" />
            <x-dashboard.kpi-card label="Com controle estoque" :value="(string) $stats['com_estoque']" border="indigo" />
            <x-dashboard.kpi-card label="Valor potencial venda" :value="'R$ '.number_format($stats['valor_potencial'], 2, ',', '.')" border="emerald" hint="estoque × preço" />
            <x-dashboard.kpi-card label="Margem média (R$)" :value="'R$ '.number_format($stats['margem_media'], 2, ',', '.')" border="amber" hint="preço − custo" />
            <x-dashboard.kpi-card label="Sem preço / sem NCM" :value="$stats['sem_preco'].' / '.$stats['sem_ncm']" border="red" />
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Top 5 por preço</h3>
                <table class="min-w-full divide-y">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs uppercase text-gray-500">Produto</th>
                        <th class="px-3 py-2 text-right text-xs uppercase text-gray-500">Preço</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @forelse($stats['top_preco'] as $p)
                        <tr>
                            <td class="px-3 py-2 text-sm">{{ $p->descricao }}</td>
                            <td class="px-3 py-2 text-sm text-right">R$ {{ number_format($p->preco_venda, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-3 py-6 text-center text-sm text-gray-500">Nenhum produto.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Preview</h3>
                    <a href="{{ route('produtos.index') }}" class="text-sm text-blue-600">Ver todos →</a>
                </div>
                <table class="min-w-full divide-y">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs uppercase text-gray-500">Descrição</th>
                        <th class="px-3 py-2 text-right text-xs uppercase text-gray-500">Preço</th>
                        <th class="px-3 py-2 text-right text-xs uppercase text-gray-500">Estoque</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @foreach($stats['preview'] as $p)
                        <tr>
                            <td class="px-3 py-2 text-sm">
                                <a href="{{ route('produtos.edit', $p) }}" class="text-blue-600 hover:underline">{{ $p->descricao }}</a>
                            </td>
                            <td class="px-3 py-2 text-sm text-right">R$ {{ number_format($p->preco_venda, 2, ',', '.') }}</td>
                            <td class="px-3 py-2 text-sm text-right">{{ number_format($p->estoque_atual, 3, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
