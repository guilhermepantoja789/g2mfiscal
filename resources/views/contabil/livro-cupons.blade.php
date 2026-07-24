<x-app-layout>
    <div class="py-2">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Livro de saídas — Cupons</h1>
                <p class="mt-1 text-sm text-gray-500">NFC-e no período selecionado.</p>
            </div>
            <a href="{{ route('contabil.dashboard') }}" class="text-sm text-teal-700 hover:underline">← Área Contábil</a>
        </div>

        <x-dashboard.periodo-filter :action="route('contabil.livro_cupons')">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Status</label>
                <select name="status" class="block w-full text-sm border-gray-300 rounded-md" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <option value="autorizada" @selected(request('status')==='autorizada')>Autorizada</option>
                    <option value="cancelada" @selected(request('status')==='cancelada')>Cancelada</option>
                    <option value="pendente_transmissao" @selected(request('status')==='pendente_transmissao')>Pendente transmissão</option>
                    <option value="erro" @selected(request('status')==='erro')>Erro</option>
                </select>
            </div>
        </x-dashboard.periodo-filter>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Número</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Chave</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Emissão</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs uppercase text-gray-500">Valor</th>
                        <th class="px-4 py-3 text-right text-xs uppercase text-gray-500">Ações</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @forelse($cupons as $nfce)
                        <tr>
                            <td class="px-4 py-3 text-sm">{{ $nfce->serie }}/{{ $nfce->numero ?: '—' }}</td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-600">{{ $nfce->chave ? \Illuminate\Support\Str::limit($nfce->chave, 20) : '—' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $nfce->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $nfce->status_badge_class }}">{{ $nfce->status_label }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right">R$ {{ number_format($nfce->valor_total ?? 0, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right space-x-2">
                                <a href="{{ route('nfces.show', $nfce->id) }}" class="text-teal-700 hover:underline">Detalhe</a>
                                @if($nfce->podeImprimirDanfe())
                                    <a href="{{ route('nfces.imprimir', $nfce->id) }}" class="text-gray-600 hover:underline">DANFE</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Nenhuma NFC-e no período.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $cupons->links() }}</div>
        </div>
    </div>
</x-app-layout>
