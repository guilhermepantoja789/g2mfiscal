<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 md:px-8">

            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Minha Carteira</h1>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">
                    Conta Ativa e Verificada
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

                <div class="md:col-span-2 bg-gradient-to-r from-slate-800 to-slate-900 rounded-2xl shadow-xl text-white p-8 relative overflow-hidden">
                    <div class="relative z-10">
                        <p class="text-slate-400 text-sm font-medium uppercase tracking-wider mb-1">Saldo Disponível</p>
                        <h2 class="text-4xl font-bold mb-6">R$ {{ number_format($saldoDisponivel, 2, ',', '.') }}</h2>

                        <div x-data="{ modalOpen: false }">
                            <button @click="modalOpen = true"
                                    class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition transform hover:-translate-y-0.5"
                                {{ $saldoDisponivel <= 0 ? 'disabled class=opacity-50 cursor-not-allowed' : '' }}>
                                Solicitar Saque
                            </button>

                            <div x-show="modalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                    <div class="fixed inset-0 transition-opacity" @click="modalOpen = false">
                                        <div class="absolute inset-0 bg-gray-900 opacity-75"></div>
                                    </div>
                                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                        <form action="{{ route('carteira.sacar') }}" method="POST" class="p-6">
                                            @csrf
                                            <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4">Confirmar Saque</h3>

                                            <div class="bg-gray-50 p-4 rounded-md mb-4 border border-gray-200">
                                                <p class="text-xs text-gray-500 uppercase font-bold mb-1">Conta de Destino (Configurada)</p>
                                                @if($empresa->chave_pix)
                                                    <p class="text-sm font-mono text-gray-800">Pix: {{ $empresa->chave_pix }}</p>
                                                @else
                                                    <p class="text-sm font-mono text-gray-800">
                                                        {{ $empresa->banco_nome }} / Ag: {{ $empresa->agencia }} / CC: {{ $empresa->conta }}
                                                    </p>
                                                @endif
                                                <p class="text-xs text-gray-400 mt-2">
                                                    * Por segurança, o saque só é permitido para contas de mesma titularidade do CNPJ {{ $empresa->cnpj }}.
                                                </p>
                                            </div>

                                            <div class="mb-6">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Valor do Saque (R$)</label>
                                                <input type="text" name="valor" class="money w-full rounded-md border-gray-300 shadow-sm text-2xl font-bold text-gray-800" placeholder="0,00" required>
                                                <p class="text-xs text-gray-500 mt-1">Disponível: R$ {{ number_format($saldoDisponivel, 2, ',', '.') }}</p>
                                            </div>

                                            <div class="flex justify-end bg-gray-50 -mx-6 -mb-6 p-4">
                                                <button type="button" @click="modalOpen = false" class="mr-3 text-gray-700 font-bold py-2 px-4 rounded hover:bg-gray-200">Cancelar</button>
                                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded shadow">Confirmar Transferência</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-10 translate-y-10">
                        <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.15-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.6-2.6-1.6-1.54 0-2.33.85-2.33 1.63 0 .77.56 1.32 2.67 1.9 2.8.66 4.1 1.83 4.1 3.75 0 1.63-1.29 2.75-3.13 3.12z"/></svg>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-4">Conta de Destino Cadastrada</h3>

                        <div class="py-4">
                            @if($empresa->chave_pix)
                                <div class="mb-4">
                                    <span class="block text-xs text-gray-400">Chave Pix</span>
                                    <span class="text-lg font-mono font-medium text-gray-800">{{ $empresa->chave_pix }}</span>
                                </div>
                            @endif

                            @if($empresa->conta)
                                <div>
                                    <span class="block text-xs text-gray-400">Dados Bancários</span>
                                    <div class="text-sm font-medium text-gray-800 mt-1">
                                        {{ $empresa->banco_nome }} <br>
                                        Ag: {{ $empresa->agencia }} <br>
                                        CC: {{ $empresa->conta }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs text-gray-400 mb-3">Precisa alterar sua conta de recebimento?</p>
                        <a href="#" onclick="alert('Para alterar a conta de destino, entre em contato com o suporte ou refaça a validação de segurança.')" class="block w-full text-center bg-white border border-gray-300 text-gray-700 font-semibold py-2 px-4 rounded shadow-sm text-sm hover:bg-gray-50">
                            Alterar Dados Bancários
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
