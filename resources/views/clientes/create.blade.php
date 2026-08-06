<x-app-layout>
    <div class="max-w-4xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Novo Cliente</h2>

        <div class="bg-white shadow rounded-lg p-6">

            <form action="{{ route('clientes.store') }}" method="POST" class="space-y-6"
                  x-data="{
                      docType: 'cnpj', // Controla a máscara
                      cep: '',
                      rua: '',
                      bairro: '',
                      uf: '',
                      cidadeCodigo: '',
                      loadingCep: false,

                      fetchCep() {
                          let cepLimpo = this.cep.replace(/\D/g, '');
                          if (cepLimpo.length === 8) {
                              this.loadingCep = true;
                              fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`)
                                  .then(response => response.json())
                                  .then(data => {
                                      if (!data.erro) {
                                          this.rua = data.logradouro;
                                          this.bairro = data.bairro;
                                          this.uf = data.uf;
                                          this.cidadeCodigo = data.ibge || '';
                                          document.getElementById('numero').focus();
                                      } else {
                                          alert('CEP não encontrado!');
                                      }
                                  })
                                  .catch(() => alert('Erro ao buscar CEP'))
                                  .finally(() => this.loadingCep = false);
                          }
                      }
                  }">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 flex justify-between">
                            <span>Documento (CPF ou CNPJ)</span>
                            <span class="text-xs text-blue-600 cursor-pointer" @click="docType = docType === 'cnpj' ? 'cpf' : 'cnpj'">
                                Alternar máscara
                            </span>
                        </label>
                        <input type="text" name="cnpj" required
                               x-mask:dynamic="docType === 'cnpj' ? '99.999.999/9999-99' : '999.999.999-99'"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-lg"
                               placeholder="Digite apenas números">
                        @error('cnpj') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Razão Social / Nome Completo</label>
                        <input type="text" name="razao_social" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase">
                        @error('razao_social') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">E-mail para NFE</label>
                    <input type="email" name="email"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="border-t pt-6 mt-4">
                    <h3 class="text-sm font-bold text-gray-900 uppercase mb-4 flex items-center">
                        Endereço
                        <span x-show="loadingCep" class="ml-2 text-blue-600 text-xs animate-pulse">Buscando CEP...</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <div class="md:col-span-3">
                            <label class="text-xs font-bold text-gray-500">CEP</label>
                            <input type="text" name="cep" x-model="cep" @blur="fetchCep()" x-mask="99999-999"
                                   class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="md:col-span-7">
                            <label class="text-xs font-bold text-gray-500">Logradouro</label>
                            <input type="text" name="logradouro" x-model="rua"
                                   class="w-full rounded-md border-gray-300 text-sm bg-gray-50">
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-xs font-bold text-gray-500">Número</label>
                            <input type="text" name="numero" id="numero"
                                   class="w-full rounded-md border-gray-300 text-sm">
                        </div>

                        <div class="md:col-span-4">
                            <label class="text-xs font-bold text-gray-500">Bairro</label>
                            <input type="text" name="bairro" x-model="bairro"
                                   class="w-full rounded-md border-gray-300 text-sm bg-gray-50">
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-xs font-bold text-gray-500">UF</label>
                            <input type="text" name="uf" x-model="uf" maxlength="2"
                                   class="w-full rounded-md border-gray-300 text-sm bg-gray-50 text-center uppercase">
                        </div>

                        <div class="md:col-span-3">
                            <label class="text-xs font-bold text-gray-500">Cód. IBGE município</label>
                            <input type="text" name="cidade_codigo" x-model="cidadeCodigo" maxlength="7"
                                   inputmode="numeric" pattern="[0-9]{7}"
                                   placeholder="Preenchido pelo CEP"
                                   class="w-full rounded-md border-gray-300 text-sm bg-gray-50 font-mono"
                                   title="Código IBGE do município (7 dígitos). Necessário para emitir NFS-e.">
                            <p class="mt-1 text-[11px] text-gray-400">Obrigatório para NFS-e. Preenchido ao buscar o CEP.</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100 mt-6">
                    <a href="{{ route('clientes.index') }}" class="mr-4 px-4 py-2 text-gray-700 hover:text-gray-900">Cancelar</a>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 font-bold shadow-sm transition">
                        Salvar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
