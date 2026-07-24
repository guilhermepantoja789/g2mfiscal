<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Vínculos usuário / empresa"
            subtitle="Admin de plataforma — atribua apenas operador ou contador. Admins de empresa são imutáveis (Artisan: user:attach-empresa)."
        />
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
                            @foreach ($empresas as $e)
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
                <div class="bg-white shadow-sm rounded-lg border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="font-semibold text-gray-900">{{ $empresa->nome_fantasia ?: $empresa->razao_social }}</h3>
                        <p class="text-xs text-gray-500">CNPJ {{ $empresa->cnpj }} · #{{ $empresa->id }}</p>
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
            @empty
                <div class="bg-white shadow-sm rounded-lg p-8 text-center text-gray-500 text-sm">
                    Nenhuma empresa cadastrada.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
