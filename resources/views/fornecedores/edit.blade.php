<x-app-layout>
    <div class="max-w-4xl mx-auto space-y-6">
        <h2 class="text-2xl font-bold text-gray-800">{{ isset($fornecedor) ? 'Editar Fornecedor' : 'Novo Fornecedor' }}</h2>
        <div class="bg-white shadow rounded-lg p-6">
            <form method="POST" action="{{ isset($fornecedor) ? route('fornecedores.update', $fornecedor) : route('fornecedores.store') }}" class="space-y-4">
                @csrf
                @if(isset($fornecedor)) @method('PUT') @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CNPJ/CPF</label>
                        <input type="text" name="cnpj" value="{{ old('cnpj', $fornecedor->cnpj ?? '') }}" required class="mt-1 block w-full rounded-md border-gray-300 font-mono">
                        @error('cnpj') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Razão Social</label>
                        <input type="text" name="razao_social" value="{{ old('razao_social', $fornecedor->razao_social ?? '') }}" required class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">IE</label>
                        <input type="text" name="inscricao_estadual" value="{{ old('inscricao_estadual', $fornecedor->inscricao_estadual ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">E-mail</label>
                        <input type="email" name="email" value="{{ old('email', $fornecedor->email ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Telefone</label>
                        <input type="text" name="telefone" value="{{ old('telefone', $fornecedor->telefone ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CEP</label>
                        <input type="text" name="cep" value="{{ old('cep', $fornecedor->cep ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Logradouro</label>
                        <input type="text" name="logradouro" value="{{ old('logradouro', $fornecedor->logradouro ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Número</label>
                        <input type="text" name="numero" value="{{ old('numero', $fornecedor->numero ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bairro</label>
                        <input type="text" name="bairro" value="{{ old('bairro', $fornecedor->bairro ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">UF</label>
                        <input type="text" name="uf" maxlength="2" value="{{ old('uf', $fornecedor->uf ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 uppercase">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cód. IBGE município</label>
                        <input type="text" name="cidade_codigo" value="{{ old('cidade_codigo', $fornecedor->cidade_codigo ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300">
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <a href="{{ route('fornecedores.index') }}" class="px-4 py-2 text-gray-600">Cancelar</a>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-md font-bold">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
