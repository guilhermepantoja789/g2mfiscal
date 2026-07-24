<x-app-layout>
    <div class="max-w-2xl mx-auto space-y-8 animate-fade-in-up">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('nfces.index') }}" class="group flex items-center justify-center w-12 h-12 bg-white rounded-2xl hover:bg-gray-50 transition-colors border border-gray-200 shadow-sm">
                    <svg class="w-5 h-5 text-gray-500 group-hover:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Inutilizar numeração NFC-e</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ $empresa->razao_social }} — série atual {{ $empresa->nfce_serie ?? 1 }}</p>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-5 text-emerald-800 text-sm font-medium shadow-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-2xl bg-rose-50 border border-rose-200 p-5 text-rose-800 text-sm font-medium shadow-sm">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-2xl bg-rose-50 border border-rose-200 p-5 shadow-sm">
                <ul class="list-disc pl-5 text-sm text-rose-700 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
            <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                Use para faixas de números que não serão mais utilizados (quebra de sequência, erro de numeração).
                A operação é registrada na SEFAZ e auditada no sistema.
            </p>
            <form action="{{ route('nfces.inutilizar') }}" method="POST" class="space-y-5"
                  onsubmit="return confirm('Confirma inutilização desta faixa na SEFAZ?');">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Série</label>
                        <input type="number" name="serie" min="0" max="999" value="{{ old('serie', $empresa->nfce_serie ?? 1) }}"
                               class="block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Nº inicial</label>
                        <input type="number" name="numero_ini" min="1" value="{{ old('numero_ini') }}"
                               class="block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Nº final</label>
                        <input type="number" name="numero_fin" min="1" value="{{ old('numero_fin') }}"
                               class="block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Justificativa (mín. 15)</label>
                    <textarea name="x_just" rows="3" required minlength="15" maxlength="255"
                              class="block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                              placeholder="Ex.: Numeração inutilizada por erro operacional">{{ old('x_just') }}</textarea>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Perfil endpoint (opcional)</label>
                    <select name="profile" class="block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-900 focus:ring-blue-500 focus:border-blue-500 sm:text-sm cursor-pointer">
                        <option value="">Usar padrão da empresa/config</option>
                        <option value="homolog_nac" @selected(old('profile') === 'homolog_nac')>homolog_nac</option>
                        <option value="homolog" @selected(old('profile') === 'homolog')>homolog</option>
                        <option value="producao" @selected(old('profile') === 'producao')>produção</option>
                    </select>
                </div>
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-4 border-t border-gray-100">
                    <a href="{{ route('nfces.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 text-gray-600 font-bold text-sm hover:text-gray-900 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center px-6 py-2.5 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-bold text-sm shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                        Enviar inutilização
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .animate-fade-in-up {
            animation: fadeInUp 0.4s ease-out forwards;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</x-app-layout>
