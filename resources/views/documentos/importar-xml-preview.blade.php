<x-app-layout>
    <div class="max-w-4xl mx-auto space-y-6">
        <h2 class="text-2xl font-bold text-gray-800">Preview da NF-e</h2>

        <div class="bg-white border rounded-lg p-4 shadow-sm space-y-2 text-sm">
            <p>Chave: <span class="font-mono break-all">{{ $preview['chave'] }}</span></p>
            <p>Número/Série: {{ $preview['numero'] }} / {{ $preview['serie'] }}</p>
            <p>Fornecedor: <strong>{{ $preview['fornecedor']['razao_social'] }}</strong> ({{ $preview['fornecedor']['cnpj'] }})</p>
            <p>Total: <strong>R$ {{ number_format($preview['valor_total'], 2, ',', '.') }}</strong></p>
        </div>

        <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">Produto</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">Qtd</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">Unit.</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">Total</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @foreach($preview['itens'] as $item)
                    <tr>
                        <td class="px-4 py-2 text-sm">{{ $item['descricao'] }}</td>
                        <td class="px-4 py-2 text-sm">{{ number_format($item['quantidade'], 3, ',', '.') }}</td>
                        <td class="px-4 py-2 text-sm">R$ {{ number_format($item['valor_unitario'], 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-sm">R$ {{ number_format($item['valor_total'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('documentos.importar_xml.confirmar') }}" class="flex justify-end gap-3">
            @csrf
            <input type="hidden" name="confirmar" value="1">
            <a href="{{ route('documentos.importar_xml') }}" class="px-4 py-2 text-gray-600">Voltar</a>
            <button class="px-4 py-2 bg-emerald-600 text-white rounded-md font-bold">Confirmar importação</button>
        </form>
    </div>
</x-app-layout>
