<x-app-layout>
    <div class="max-w-4xl mx-auto space-y-6">
        <h2 class="text-2xl font-bold text-gray-800">{{ isset($produto) ? 'Editar Produto' : 'Novo Produto' }}</h2>
        <div class="bg-white shadow rounded-lg p-6">
            <form method="POST" action="{{ isset($produto) ? route('produtos.update', $produto) : route('produtos.store') }}" class="space-y-6">
                @csrf
                @if(isset($produto)) @method('PUT') @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Descrição</label>
                        <input type="text" name="descricao" value="{{ old('descricao', $produto->descricao ?? '') }}" required class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">SKU</label>
                        <input type="text" name="sku" value="{{ old('sku', $produto->sku ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">EAN</label>
                        <input type="text" name="ean" value="{{ old('ean', $produto->ean ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">NCM</label>
                        <input type="text" name="ncm" value="{{ old('ncm', $produto->ncm ?? '') }}" maxlength="8" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CFOP padrão</label>
                        <input type="text" name="cfop" value="{{ old('cfop', $produto->cfop ?? '5102') }}" required class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CSOSN</label>
                        <input type="text" name="csosn" value="{{ old('csosn', $produto->csosn ?? '102') }}" required class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Unidade</label>
                        <input type="text" name="unidade" value="{{ old('unidade', $produto->unidade ?? 'UN') }}" required class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Preço de venda</label>
                        <input type="number" step="0.01" name="preco_venda" value="{{ old('preco_venda', $produto->preco_venda ?? '0') }}" required class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Custo médio</label>
                        <input type="number" step="0.0001" name="custo_medio" value="{{ old('custo_medio', $produto->custo_medio ?? '0') }}" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    @unless(isset($produto))
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estoque inicial</label>
                        <input type="number" step="0.0001" name="estoque_atual" value="{{ old('estoque_atual', '0') }}" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    @endunless
                </div>

                <div class="flex gap-6">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="controla_estoque" value="1" @checked(old('controla_estoque', $produto->controla_estoque ?? true))>
                        Controla estoque
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="ativo" value="1" @checked(old('ativo', $produto->ativo ?? true))>
                        Ativo
                    </label>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('produtos.index') }}" class="px-4 py-2 text-gray-600">Cancelar</a>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-md font-bold">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
