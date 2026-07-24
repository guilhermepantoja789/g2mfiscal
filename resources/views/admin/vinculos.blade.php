<x-app-layout>
    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-page-header
                title="Empresas (plataforma)"
                subtitle="Gestão multi-empresa — módulos e vínculos. Admins de empresa são imutáveis (Artisan: user:attach-empresa)."
            />

            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-4 border border-gray-100">
                <form method="GET" action="{{ route('admin.vinculos.index') }}" class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <label for="q" class="sr-only">Buscar empresa</label>
                        <input
                            id="q"
                            type="search"
                            name="q"
                            value="{{ $q }}"
                            placeholder="Buscar por nome, razão social ou CNPJ…"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex justify-center rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                            Buscar
                        </button>
                        @if ($q !== '')
                            <a href="{{ route('admin.vinculos.index') }}" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Limpar
                            </a>
                        @endif
                    </div>
                </form>
                <p class="mt-2 text-xs text-gray-500">
                    {{ $empresas->count() }} {{ $empresas->count() === 1 ? 'empresa' : 'empresas' }}
                    @if ($q !== '')
                        para “{{ $q }}”
                    @endif
                </p>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6 border border-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Novo vínculo</h3>
                <form method="POST" action="{{ route('admin.vinculos.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Usuário</label>
                        <select name="user_id" required class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Selecione…</option>
                            @foreach ($usuarios as $u)
                                @continue($u->is_platform_admin)
                                <option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>
                                    {{ $u->name }} ({{ $u->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Empresa</label>
                        <select name="empresa_id" required class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Selecione…</option>
                            @foreach ($empresasOpcoes as $e)
                                <option value="{{ $e->id }}" @selected(old('empresa_id') == $e->id)>
                                    {{ $e->nome_fantasia ?: $e->razao_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Papel</label>
                        <select name="perfil" required class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="operador" @selected(old('perfil', 'operador') === 'operador')>Operador</option>
                            <option value="contador" @selected(old('perfil') === 'contador')>Contador</option>
                        </select>
                    </div>
                    <button type="submit" class="inline-flex justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Vincular
                    </button>
                </form>
                <p class="mt-3 text-xs text-gray-500">
                    Para promover admin de empresa: <code class="bg-gray-100 px-1 rounded">php artisan user:attach-empresa email@x.com ID --perfil=admin</code>
                </p>
            </div>

            @forelse ($empresas as $empresa)
                @php
                    $modulosAtivos = collect([
                        \App\Models\EmpresaModulo::MODULO_ERP => 'ERP',
                        \App\Models\EmpresaModulo::MODULO_PDV => 'PDV',
                        \App\Models\EmpresaModulo::MODULO_FINANCEIRO_GERENCIAL => 'Financeiro',
                        \App\Models\EmpresaModulo::MODULO_CONTABIL => 'Contábil',
                    ])->filter(fn ($label, $key) => $empresa->temModulo($key));
                @endphp
                <div class="bg-white shadow-sm rounded-lg border border-gray-100 overflow-hidden" x-data="{ open: false }">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="font-semibold text-gray-900">{{ $empresa->nome_fantasia ?: $empresa->razao_social }}</h3>
                            <p class="text-xs text-gray-500">CNPJ {{ $empresa->cnpj }} · #{{ $empresa->id }} · {{ $empresa->users->count() }} {{ $empresa->users->count() === 1 ? 'membro' : 'membros' }}</p>
                            <div class="mt-2 flex flex-wrap gap-1">
                                @forelse ($modulosAtivos as $label)
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-emerald-100 text-emerald-800">
                                        {{ $label }}
                                    </span>
                                @empty
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-gray-100 text-gray-600">
                                        Só fiscal avulso
                                    </span>
                                @endforelse
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="open = !open"
                            class="text-xs font-medium text-blue-700 hover:text-blue-900 border border-blue-200 rounded-md px-3 py-1.5 bg-white"
                            x-text="open ? 'Recolher' : 'Gerenciar'"
                        ></button>
                    </div>

                    <div x-show="open" x-cloak class="border-t border-gray-100">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h4 class="text-sm font-semibold text-gray-900 mb-1">Módulos</h4>
                            <p class="text-xs text-gray-500 mb-3">ERP/PDV/Financeiro: opt-out. Contábil: opt-in.</p>
                            <form method="POST" action="{{ route('admin.vinculos.modulos', $empresa) }}" class="space-y-2">
                                @csrf
                                @method('PUT')
                                <label class="flex items-start gap-3">
                                    <input type="hidden" name="modulo_erp" value="0">
                                    <input type="checkbox" name="modulo_erp" value="1"
                                           @checked($empresa->temModulo(\App\Models\EmpresaModulo::MODULO_ERP))
                                           class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-800">ERP / Vendas</span>
                                </label>
                                <label class="flex items-start gap-3">
                                    <input type="hidden" name="modulo_pdv" value="0">
                                    <input type="checkbox" name="modulo_pdv" value="1"
                                           @checked($empresa->temModulo(\App\Models\EmpresaModulo::MODULO_PDV))
                                           class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-800">PDV</span>
                                </label>
                                <label class="flex items-start gap-3">
                                    <input type="hidden" name="modulo_financeiro_gerencial" value="0">
                                    <input type="checkbox" name="modulo_financeiro_gerencial" value="1"
                                           @checked($empresa->temModulo(\App\Models\EmpresaModulo::MODULO_FINANCEIRO_GERENCIAL))
                                           class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-800">Financeiro gerencial</span>
                                </label>
                                <label class="flex items-start gap-3">
                                    <input type="hidden" name="modulo_contabil" value="0">
                                    <input type="checkbox" name="modulo_contabil" value="1"
                                           @checked($empresa->temModulo(\App\Models\EmpresaModulo::MODULO_CONTABIL))
                                           class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-800">Área Contábil</span>
                                </label>
                                <button type="submit" class="mt-2 inline-flex rounded-md bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-700">
                                    Salvar módulos
                                </button>
                            </form>
                        </div>

                        <div class="px-6 py-3 bg-gray-50 border-b border-gray-100">
                            <h4 class="text-sm font-semibold text-gray-900">Vínculos</h4>
                        </div>
                        <ul class="divide-y divide-gray-100">
                            @forelse ($empresa->users as $member)
                                @php
                                    $perfil = \App\Enums\EmpresaPerfil::tryFrom((string) $member->pivot->perfil);
                                    $ehAdmin = $perfil === \App\Enums\EmpresaPerfil::Admin;
                                @endphp
                                <li class="px-6 py-3 flex flex-wrap items-center justify-between gap-3 text-sm">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $member->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $member->email }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if ($ehAdmin)
                                            <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide bg-blue-100 text-blue-800">
                                                Admin — imutável
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('admin.vinculos.update', [$empresa, $member]) }}" class="flex items-center gap-2">
                                                @csrf
                                                @method('PUT')
                                                <select name="perfil" onchange="this.form.submit()"
                                                        class="text-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-1">
                                                    <option value="operador" @selected($member->pivot->perfil === 'operador')>Operador</option>
                                                    <option value="contador" @selected($member->pivot->perfil === 'contador')>Contador</option>
                                                </select>
                                            </form>
                                            <form method="POST" action="{{ route('admin.vinculos.destroy', [$empresa, $member]) }}"
                                                  onsubmit="return confirm('Remover vínculo?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium">
                                                    Remover
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li class="px-6 py-4 text-sm text-gray-500">Nenhum membro vinculado.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            @empty
                <div class="bg-white shadow-sm rounded-lg p-8 text-center text-gray-500 text-sm">
                    @if ($q !== '')
                        Nenhuma empresa encontrada para “{{ $q }}”.
                    @else
                        Nenhuma empresa cadastrada.
                    @endif
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
