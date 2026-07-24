<x-app-layout>
    <div class="py-2 space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Plano de contas e mapeamentos</h1>
                <p class="mt-1 text-sm text-gray-500">Contas gerenciais e vínculos fiscais → débito/crédito (fase 2B).</p>
            </div>
            <a href="{{ route('contabil.dashboard') }}" class="text-sm text-teal-700 hover:underline">← Área Contábil</a>
        </div>

        @if(session('success'))
            <div class="bg-green-50 text-green-800 border border-green-200 rounded-md px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 text-red-800 border border-red-200 rounded-md px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <section class="bg-white shadow rounded-lg border p-5 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Contas</h2>
                <form method="POST" action="{{ route('contabil.plano.contas.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3 border-b pb-4">
                    @csrf
                    <input name="codigo" required placeholder="Código" class="rounded-md border-gray-300 text-sm" value="{{ old('codigo') }}">
                    <input name="nome" required placeholder="Nome" class="rounded-md border-gray-300 text-sm" value="{{ old('nome') }}">
                    <select name="tipo" required class="rounded-md border-gray-300 text-sm">
                        @foreach(['ativo','passivo','receita','despesa','patrimonio'] as $t)
                            <option value="{{ $t }}" @selected(old('tipo')===$t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                    <select name="natureza" required class="rounded-md border-gray-300 text-sm">
                        <option value="D" @selected(old('natureza','D')==='D')>Débito (D)</option>
                        <option value="C" @selected(old('natureza')==='C')>Crédito (C)</option>
                    </select>
                    <div class="sm:col-span-2">
                        <button class="px-3 py-2 bg-teal-700 text-white rounded-md text-sm font-semibold">Adicionar conta</button>
                    </div>
                </form>

                <div class="overflow-x-auto max-h-96 overflow-y-auto">
                    <table class="min-w-full text-sm divide-y">
                        <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-2 py-2 text-left text-xs uppercase text-gray-500">Código</th>
                            <th class="px-2 py-2 text-left text-xs uppercase text-gray-500">Nome</th>
                            <th class="px-2 py-2 text-left text-xs uppercase text-gray-500">Tipo</th>
                            <th class="px-2 py-2 text-left text-xs uppercase text-gray-500">Nat.</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y">
                        @foreach($contas as $c)
                            <tr class="{{ $c->ativo ? '' : 'opacity-50' }}">
                                <td class="px-2 py-2 font-mono">{{ $c->codigo }}</td>
                                <td class="px-2 py-2">{{ $c->nome }}</td>
                                <td class="px-2 py-2">{{ $c->tipo }}</td>
                                <td class="px-2 py-2">{{ $c->natureza }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bg-white shadow rounded-lg border p-5 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Mapeamentos fiscais → conta</h2>
                <p class="text-xs text-gray-500">Cada origem precisa de um débito (D) e um crédito (C) para a postagem automática.</p>

                <form method="POST" action="{{ route('contabil.plano.mapeamentos.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3 border-b pb-4">
                    @csrf
                    <select name="origem" required class="rounded-md border-gray-300 text-sm sm:col-span-2">
                        @foreach($origens as $cod => $label)
                            <option value="{{ $cod }}">{{ $label }} ({{ $cod }})</option>
                        @endforeach
                    </select>
                    <select name="papel" required class="rounded-md border-gray-300 text-sm">
                        <option value="D">Débito (D)</option>
                        <option value="C">Crédito (C)</option>
                    </select>
                    <select name="conta_id" required class="rounded-md border-gray-300 text-sm">
                        @foreach($contas->where('ativo', true) as $c)
                            <option value="{{ $c->id }}">{{ $c->codigo }} — {{ $c->nome }}</option>
                        @endforeach
                    </select>
                    <div class="sm:col-span-2">
                        <button class="px-3 py-2 bg-teal-700 text-white rounded-md text-sm font-semibold">Salvar mapeamento</button>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm divide-y">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-left text-xs uppercase text-gray-500">Origem</th>
                            <th class="px-2 py-2 text-left text-xs uppercase text-gray-500">Papel</th>
                            <th class="px-2 py-2 text-left text-xs uppercase text-gray-500">Conta</th>
                            <th class="px-2 py-2 text-right text-xs uppercase text-gray-500"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y">
                        @forelse($mapeamentos as $m)
                            <tr>
                                <td class="px-2 py-2">{{ $origens[$m->origem] ?? $m->origem }}</td>
                                <td class="px-2 py-2 font-mono">{{ $m->metadados['papel'] ?? '—' }}</td>
                                <td class="px-2 py-2">{{ $m->conta?->codigo }} — {{ $m->conta?->nome }}</td>
                                <td class="px-2 py-2 text-right">
                                    <form method="POST" action="{{ route('contabil.plano.mapeamentos.destroy', $m) }}" onsubmit="return confirm('Remover?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-rose-600 text-xs hover:underline">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-2 py-6 text-center text-gray-500">Nenhum mapeamento.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
