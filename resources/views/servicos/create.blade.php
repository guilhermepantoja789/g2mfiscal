<x-app-layout>
    <div class="max-w-4xl mx-auto py-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Novo Serviço</h2>
            <a href="{{ route('servicos.index') }}" class="text-gray-600 hover:text-gray-800 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Voltar
            </a>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <form action="{{ route('servicos.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome do Serviço (Apelido)</label>
                        <input type="text" name="nome" value="{{ old('nome') }}" required placeholder="Ex: Consultoria de TI"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Nome para identificação interna.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Código Interno (Opcional)</label>
                        <input type="text" name="codigo_interno" value="{{ old('codigo_interno') }}" placeholder="Ex: SERV-001"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div class="border-t pt-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Dados Fiscais</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Código Tributação Nacional (LC 116)</label>

                            <input list="lista-tributacao"
                                   name="codigo_tributacao_nacional"
                                   value="{{ old('codigo_tributacao_nacional') }}"
                                   required
                                   autocomplete="off"
                                   placeholder="Digite o código (ex: 010101) ou nome do serviço..."
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-blue-50"
                                   onchange="atualizarDescricao(this)">

                            <datalist id="lista-tributacao">
                                @foreach(\App\Models\TributacaoNacional::all() as $trib)
                                    <option value="{{ $trib->codigo }}" data-descricao="{{ $trib->descricao }}">
                                        {{ $trib->codigo }} - {{ Illuminate\Support\Str::limit($trib->descricao, 100) }}
                                    </option>
                                @endforeach
                            </datalist>

                            <p class="mt-1 text-xs text-gray-500">
                                Selecione na lista para preencher a descrição oficial automaticamente.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cód. Trib. Municipal</label>

                            <div class="relative">
                                <input type="text"
                                       id="codigo_tributacao_municipal"
                                       name="codigo_tributacao_municipal"
                                       value="{{ old('codigo_tributacao_municipal', $padraoMunicipal) }}"
                                       required
                                       placeholder="Ex: 123"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 {{ $isManaus ? 'bg-yellow-50 border-yellow-400' : '' }}">

                                @if($isManaus)
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            @if($isManaus)
                                <p class="text-xs text-yellow-700 mt-1 font-semibold">
                                    * Padrão sugerido para Manaus: 100
                                </p>
                            @else
                                <p class="text-xs text-gray-500 mt-1">Código específico da prefeitura.</p>
                            @endif
                        </div>

                        @if($isManaus)
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const inputMun = document.getElementById('codigo_tributacao_municipal');

                                    // 1. Alerta ao carregar a página (Apenas se for novo cadastro)
                                    // Usamos setTimeout para garantir que o navegador renderizou
                                    setTimeout(() => {
                                        alert("⚠️ AVISO IMPORTANTE PARA MANAUS\n\nO Código de Tributação Municipal foi preenchido automaticamente com '100'.\n\nIdentificamos em testes que este é o padrão aceito pela Prefeitura de Manaus. Alterar este valor pode causar erros na emissão da nota.");
                                    }, 500);

                                    // 2. Alerta extra caso ele tente mudar
                                    inputMun.addEventListener('change', function(e) {
                                        if (this.value !== '100') {
                                            const confirma = confirm("⚠️ ATENÇÃO!\n\nVocê está alterando o código padrão '100' de Manaus.\nIsso costuma gerar rejeição na nota fiscal.\n\nTem certeza que deseja usar o código '" + this.value + "'?");

                                            if (!confirma) {
                                                this.value = '100'; // Volta para 100 se ele cancelar
                                            }
                                        }
                                    });
                                });
                            </script>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código NBS (Opcional)</label>
                            <input type="text" name="codigo_nbs" value="{{ old('codigo_nbs') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Descrição do Serviço (Na Nota)</label>
                    <textarea name="descricao" rows="3" required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('descricao') }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Texto que sairá impresso na nota fiscal.</p>
                </div>

                <div class="border-t pt-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Valores e Impostos</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Valor Unitário (R$)</label>
                            <p class="text-xs text-gray-500 mt-1">Esse é o valor padrão que virá na nota ao selecionar esse serviço, mas pode ser alterado.</p>
                            <input type="text" name="valor_unitario" value="{{ old('valor_unitario') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 money">
                        </div>

                        <div class="flex items-center pt-6">
                            <input type="checkbox" name="iss_retido" id="iss_retido" value="1" {{ old('iss_retido') ? 'checked' : '' }}
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="iss_retido" class="ml-2 block text-sm text-gray-900">
                                ISS Retido na Fonte?
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Aliq. ISS (%)</label>
                            <input type="text" name="aliquota_iss" value="{{ old('aliquota_iss', '0,00') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm money">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Aliq. PIS (%)</label>
                            <input type="text" name="aliquota_pis" value="{{ old('aliquota_pis', '0,00') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm money">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Aliq. COFINS (%)</label>
                            <input type="text" name="aliquota_cofins" value="{{ old('aliquota_cofins', '0,00') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm money">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Aliq. INSS (%)</label>
                            <input type="text" name="aliquota_inss" value="{{ old('aliquota_inss', '0,00') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm money">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-6">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">
                        Salvar Serviço
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function atualizarDescricao(input) {
            const list = document.getElementById('lista-tributacao');
            const options = list.options;
            let descricaoEncontrada = '';

            for(let i = 0; i < options.length; i++) {
                if(options[i].value === input.value) {
                    descricaoEncontrada = options[i].getAttribute('data-descricao');
                    break;
                }
            }

            const campoDescricao = document.querySelector('textarea[name="descricao"]');

            // Só preenche se achou a descrição e o campo estiver vazio ou muito curto
            if (descricaoEncontrada && campoDescricao) {
                if (campoDescricao.value.length < 5) {
                    campoDescricao.value = descricaoEncontrada;
                }
            }
        }
    </script>
</x-app-layout>
