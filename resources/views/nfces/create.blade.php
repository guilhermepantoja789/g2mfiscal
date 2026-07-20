<x-app-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Venda avulsa → NFC-e</h2>
        <p class="text-gray-500 text-sm">Simples Nacional / consumidor final (AM)</p>
    </div>

    <form action="{{ route('nfces.store') }}" method="POST" class="bg-white shadow rounded-lg p-6 grid grid-cols-1 md:grid-cols-2 gap-4 max-w-3xl">
        @csrf
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Descrição</label>
            <input type="text" name="descricao" value="{{ old('descricao', 'PRODUTO TESTE') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">NCM</label>
            <input type="text" name="ncm" value="{{ old('ncm', '22021000') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">CFOP</label>
            <select name="cfop" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="5102">5102 — Venda</option>
                <option value="5405">5405 — Venda c/ ST</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">CSOSN</label>
            <select name="csosn" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="102">102 — Sem crédito</option>
                <option value="500">500 — ST cobrado anteriormente</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Unidade</label>
            <input type="text" name="unidade" value="{{ old('unidade', 'UN') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Quantidade</label>
            <input type="number" step="0.001" name="quantidade" value="{{ old('quantidade', '1') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Valor unitário</label>
            <input type="number" step="0.01" name="valor_unitario" value="{{ old('valor_unitario', '1.00') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Forma de pagamento</label>
            <select name="t_pag" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="01">01 — Dinheiro</option>
                <option value="03">03 — Crédito</option>
                <option value="04">04 — Débito</option>
                <option value="17">17 — PIX</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Troco (dinheiro)</label>
            <input type="number" step="0.01" name="v_troco" value="{{ old('v_troco') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">CPF/CNPJ destinatário (opcional)</label>
            <input type="text" name="dest_doc" value="{{ old('dest_doc') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Nome destinatário</label>
            <input type="text" name="dest_nome" value="{{ old('dest_nome') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <div class="md:col-span-2 flex gap-3 mt-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Emitir NFC-e</button>
            <a href="{{ route('nfces.index') }}" class="py-2 px-4 text-gray-600">Cancelar</a>
        </div>
    </form>
</x-app-layout>
