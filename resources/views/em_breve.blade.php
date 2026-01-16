<x-app-layout>
    <div class="min-h-[80vh] flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <div class="inline-flex items-center justify-center p-4 bg-indigo-50 rounded-full mb-6">
                <svg class="w-12 h-12 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                </svg>
            </div>

            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl mb-4">
                Estamos preparando algo incrível!
            </h2>

            <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto mb-8">
                O módulo financeiro (Cobranças e Carteira Digital) está em fase final de testes.
                Em breve você poderá emitir boletos e receber Pix diretamente por aqui.
            </p>

            <div class="inline-flex rounded-md shadow">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    Voltar ao Dashboard
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
