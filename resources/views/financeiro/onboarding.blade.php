<x-app-layout>
    <div class="min-h-[80vh] flex flex-col justify-center items-center py-12 sm:px-6 lg:px-8 bg-gray-50">

        <div class="sm:mx-auto sm:w-full sm:max-w-3xl text-center mb-10">
            <div class="inline-flex items-center justify-center p-4 bg-blue-100 rounded-full mb-6">
                <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                Ative seus Recebimentos Automáticos
            </h2>
            <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto">
                Transforme o G2M Fiscal em uma máquina de cobrar. Emita boletos e Pix junto com suas notas fiscais e receba direto no sistema.
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 border border-gray-200">
                <div class="space-y-6">

                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <p class="ml-3 text-base text-gray-700">Emissão de Boleto + Pix no mesmo PDF da Nota.</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <p class="ml-3 text-base text-gray-700">Baixa automática (Conciliação bancária).</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <p class="ml-3 text-base text-gray-700">Transferência gratuita via Pix para sua conta.</p>
                        </li>
                    </ul>

                    <div class="border-t border-gray-200 pt-6">
                        <div class="rounded-md bg-blue-50 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1 md:flex md:justify-between">
                                    <p class="text-sm text-blue-700">
                                        Sua conta será criada usando o CNPJ: <strong>{{ $empresa->cnpj }}</strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('carteira.ativar') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                Ativar Conta Digital Grátis
                            </button>
                        </form>
                        <p class="mt-3 text-center text-xs text-gray-500">
                            Ao ativar, você concorda com os termos de uso da plataforma financeira.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
