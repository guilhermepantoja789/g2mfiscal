<x-app-layout>
    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <nav aria-label="Progress" class="mb-8">
                <ol role="list" class="flex items-center">
                    <li class="relative pr-8 sm:pr-20">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="h-0.5 w-full bg-blue-600"></div>
                        </div>
                        <a href="#" class="relative flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 hover:bg-blue-900">
                            <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </li>
                    <li class="relative pr-8 sm:pr-20">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="h-0.5 w-full bg-gray-200"></div>
                        </div>
                        <a href="#" class="relative flex h-8 w-8 items-center justify-center rounded-full bg-white border-2 border-blue-600" aria-current="step">
                            <span class="h-2.5 w-2.5 rounded-full bg-blue-600" aria-hidden="true"></span>
                        </a>
                    </li>
                    <li class="relative">
                        <a href="#" class="relative flex h-8 w-8 items-center justify-center rounded-full bg-white border-2 border-gray-300 hover:border-gray-400">
                            <span class="h-2.5 w-2.5 rounded-full bg-transparent group-hover:bg-gray-300" aria-hidden="true"></span>
                        </a>
                    </li>
                </ol>
            </nav>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:px-6 bg-slate-50 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Configurar Conta de Recebimento</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Para sua segurança, defina para onde o dinheiro será enviado quando você solicitar o saque.
                    </p>
                </div>

                <div class="px-4 py-5 sm:p-6">
                    <form action="{{ route('carteira.salvar_banco') }}" method="POST">
                        @csrf

                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        <strong>Atenção:</strong> A conta bancária ou Chave Pix informada DEVE pertencer ao mesmo titular do CNPJ: <strong>{{ $empresa->cnpj }}</strong>. Transferências para terceiros serão bloqueadas.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Chave Pix (Recomendado)</label>
                            <input type="text" name="chave_pix" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="CPF, CNPJ, Email ou Telefone">
                            <p class="mt-1 text-xs text-gray-500">O saque via Pix é processado 24h/7 dias e costuma ser gratuito.</p>
                        </div>

                        <div class="relative mb-6">
                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center">
                                <span class="px-2 bg-white text-sm text-gray-500">Ou Preencha os Dados Bancários</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-3">
                                <label class="block text-sm font-medium text-gray-700">Banco</label>
                                <input type="text" name="banco_nome" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Ex: Nubank, Itaú">
                            </div>

                            <div class="sm:col-span-3">
                                <label class="block text-sm font-medium text-gray-700">Tipo de Conta</label>
                                <select name="conta_tipo" class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="CC">Corrente</option>
                                    <option value="CP">Poupança</option>
                                </select>
                            </div>

                            <div class="sm:col-span-3">
                                <label class="block text-sm font-medium text-gray-700">Agência</label>
                                <input type="text" name="agencia" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>

                            <div class="sm:col-span-3">
                                <label class="block text-sm font-medium text-gray-700">Conta com Dígito</label>
                                <input type="text" name="conta" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                        </div>

                        <div class="mt-6 flex items-start">
                            <div class="flex items-center h-5">
                                <input id="saque_automatico" name="saque_automatico" type="checkbox" value="1" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="saque_automatico" class="font-medium text-gray-700">Transferência Automática Diária</label>
                                <p class="text-gray-500">Se marcado, todo saldo disponível será transferido automaticamente para esta conta às 10h da manhã.</p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900">
                                Salvar e Acessar Carteira
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
