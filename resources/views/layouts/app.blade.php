<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'G2M Fiscal') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full">

<div class="min-h-full">

    <div class="hidden md:fixed md:inset-y-0 md:flex md:w-64 md:flex-col">
        <div class="flex min-h-0 flex-1 flex-col bg-slate-900">
            <div class="flex h-16 flex-shrink-0 items-center bg-slate-900 px-4 font-bold text-white text-xl">
                G2M Fiscal
            </div>

            <div class="flex flex-1 flex-col overflow-y-auto">
                <nav class="mt-5 flex-1 px-2 space-y-1">

                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="text-slate-400 group-hover:text-slate-300 mr-3 flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                        Dashboard
                    </a>

                    <a href="{{ route('notas.index') }}" class="{{ request()->routeIs('notas.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="text-slate-400 group-hover:text-slate-300 mr-3 flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Notas Fiscais
                    </a>

                    <a href="{{ route('clientes.index') }}" class="{{ request()->routeIs('clientes.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="text-slate-400 group-hover:text-slate-300 mr-3 flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        Clientes
                    </a>

                    <a href="{{ route('servicos.index') }}" class="{{ request()->routeIs('servicos.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="text-slate-400 group-hover:text-slate-300 mr-3 flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        Serviços
                    </a>

                    @php
                        $empresaAtivaId = null;
                        $sessao = \Illuminate\Support\Facades\Session::get('empresa_ativa');

                        if ($sessao) {
                            // 1. Se for numérico (ID puro), usa direto
                            if (is_numeric($sessao)) {
                                $empresaAtivaId = $sessao;
                            }
                            // 2. Se for instância da Model Empresa (verifica tipo estrito)
                            elseif ($sessao instanceof \App\Models\Empresa) {
                                $empresaAtivaId = $sessao->id;
                            }
                            // 3. Se for array
                            elseif (is_array($sessao) && isset($sessao['id'])) {
                                $empresaAtivaId = $sessao['id'];
                            }
                            // NOTA: Removemos o check genérico is_object($sessao) para evitar o erro do Store
                        }
                    @endphp

                    @if($empresaAtivaId)
                        <a href="{{ route('empresas.configuracao', $empresaAtivaId) }}" class="{{ request()->routeIs('empresas.configuracao') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                            <svg class="text-slate-400 group-hover:text-slate-300 mr-3 flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Configurações
                        </a>
                    @endif

                    <a href="{{ route('empresas.selecao') }}" class="mt-8 text-slate-400 hover:bg-slate-800 hover:text-white group flex items-center px-2 py-2 text-sm font-medium rounded-md border-t border-slate-700">
                        <svg class="text-slate-400 group-hover:text-slate-300 mr-3 flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                        Trocar Empresa
                    </a>

                </nav>
            </div>
        </div>
    </div>

    <div class="flex flex-1 flex-col md:pl-64 transition-all duration-300">

        <div class="sticky top-0 z-10 flex h-16 flex-shrink-0 bg-white shadow items-center">

            <button type="button" class="border-r border-gray-200 px-4 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 md:hidden" @click="sidebarOpen = true">
                <span class="sr-only">Open sidebar</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" /></svg>
            </button>

            <div class="flex flex-1 justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex flex-1 items-center">
                    @php
                        $nomeEmpresaExibicao = 'Painel';
                        $cnpjEmpresaExibicao = '';

                        // Reutiliza a lógica segura do ID calculada acima
                        if($empresaAtivaId) {
                            $empresaObj = \App\Models\Empresa::find($empresaAtivaId);
                            if($empresaObj) {
                                $nomeEmpresaExibicao = $empresaObj->nome_fantasia ?? $empresaObj->razao_social;
                                $cnpjEmpresaExibicao = $empresaObj->cnpj;
                            }
                        }
                    @endphp
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 leading-tight truncate max-w-[200px] sm:max-w-md">
                            {{ $nomeEmpresaExibicao }}
                        </h1>
                        @if($cnpjEmpresaExibicao)
                            <span class="text-xs text-gray-500 block">{{ $cnpjEmpresaExibicao }}</span>
                        @endif
                    </div>
                </div>
                <div class="ml-4 flex items-center md:ml-6">
                    <div class="relative ml-3" x-data="{ open: false }">
                        <div>
                            <button @click="open = !open" type="button" class="flex max-w-xs items-center rounded-full bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <span class="sr-only">Open user menu</span>
                                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold border border-gray-300">
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                </div>
                            </button>
                        </div>

                        <div x-show="open" @click.away="open = false" class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" style="display: none;">
                            <div class="px-4 py-3 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Meu Perfil</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Sair</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <main class="flex-1 bg-gray-100">
            <div class="py-8">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
