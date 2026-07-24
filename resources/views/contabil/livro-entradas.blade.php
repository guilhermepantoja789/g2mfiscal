<x-app-layout>
    <div class="py-2">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Livro de entradas</h1>
                <p class="mt-1 text-sm text-gray-500">Documentos de compra / NF-e importada.</p>
            </div>
            <a href="{{ route('contabil.dashboard') }}" class="text-sm text-teal-700 hover:underline">← Área Contábil</a>
        </div>

        <x-dashboard.periodo-filter :action="route('contabil.livro_entradas')" />

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Número</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Chave</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Fornecedor</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Data</th>
                        <th class="px-4 py-3 text-right text-xs uppercase text-gray-500">Valor</th>
                        <th class="px-4 py-3 text-right text-xs uppercase text-gray-500">Ações</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @forelse($documentos as $doc)
                        <tr>
                            <td class="px-4 py-3 text-sm">{{ $doc->serie_nfe }}/{{ $doc->numero_nfe ?: '—' }}</td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-600">{{ $doc->chave_nfe ? \Illuminate\Support\Str::limit($doc->chave_nfe, 20) : '—' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $doc->fornecedor?->razao_social ?: '—' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $doc->status_label ?? $doc->status }}</td>
                            <td class="px-4 py-3 text-sm">{{ $doc->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm text-right">R$ {{ number_format($doc->valor_total ?? 0, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right">
                                <a href="{{ route('documentos.show', $doc->id) }}" class="text-teal-700 hover:underline">Detalhe</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">Nenhuma compra no período.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $documentos->links() }}</div>
        </div>
    </div>
</x-app-layout>
