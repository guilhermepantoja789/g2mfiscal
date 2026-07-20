<x-app-layout>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">NFC-e</h2>
            <p class="text-gray-500 text-sm">Emissões modelo 65 — {{ $empresa->razao_social }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('nfces.laboratorio') }}" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-4 rounded shadow text-sm">
                Laboratório
            </a>
            <a href="{{ route('nfces.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow text-sm">
                Nova venda avulsa
            </a>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">Número</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Chave</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($nfces as $nfce)
                    <tr>
                        <td class="px-4 py-3">{{ $nfce->numero }}/{{ $nfce->serie }}</td>
                        <td class="px-4 py-3">{{ $nfce->status }} @if($nfce->c_stat) ({{ $nfce->c_stat }}) @endif</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $nfce->chave ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">R$ {{ number_format($nfce->valor_total, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('nfces.show', $nfce->id) }}" class="text-blue-600 hover:underline">Detalhe</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">Nenhuma NFC-e emitida.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $nfces->links() }}</div>
</x-app-layout>
