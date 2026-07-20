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

                    <a href="{{ route('nfces.index') }}" class="{{ request()->routeIs('nfces.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="text-slate-400 group-hover:text-slate-300 mr-3 flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                        NFC-e
                    </a>

                    @php
                        $financeiroAtivo = env('FEATURE_FINANCEIRO', false);
                    @endphp

                    <a href="{{ route('cobrancas.index') }}" class="{{ request()->routeIs('cobrancas.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} group flex items-center justify-between px-2 py-2 text-sm font-medium rounded-md">
                        <div class="flex items-center">
                            <svg class="text-slate-400 group-hover:text-slate-300 mr-3 flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Cobranças
                        </div>
                        @if(!$financeiroAtivo)
                            <span class="bg-slate-700 text-slate-300 py-0.5 px-2 rounded-full text-[10px] uppercase font-bold tracking-wide">BREVE</span>
                        @endif
                    </a>

                    <a href="{{ route('carteira.index') }}" class="{{ request()->routeIs('carteira.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} group flex items-center justify-between px-2 py-2 text-sm font-medium rounded-md">
                        <div class="flex items-center">
                            <svg class="text-slate-400 group-hover:text-slate-300 mr-3 flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            Carteira / Saque
                        </div>
                        @if(!$financeiroAtivo)
                            <span class="bg-slate-700 text-slate-300 py-0.5 px-2 rounded-full text-[10px] uppercase font-bold tracking-wide">BREVE</span>
                        @endif
                    </a>

                    <a href="{{ route('recorrencias.index') }}" class="{{ request()->routeIs('recorrencias.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <svg class="text-slate-400 group-hover:text-slate-300 mr-3 flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Recorrências
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
                            if (is_numeric($sessao)) {
                                $empresaAtivaId = $sessao;
                            }
                            elseif ($sessao instanceof \App\Models\Empresa) {
                                $empresaAtivaId = $sessao->id;
                            }
                            elseif (is_array($sessao) && isset($sessao['id'])) {
                                $empresaAtivaId = $sessao['id'];
                            }
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
                <div class="ml-4 flex items-center md:ml-6 space-x-3">
                    
                    <!-- Notification Bell -->
                    <div class="relative" x-data="{ openNotif: false }">
                        <button @click="openNotif = !openNotif" type="button" class="relative rounded-full bg-white p-1 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <span class="sr-only">Ver notificações</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            @if(Auth::user()->unreadNotifications->count() > 0)
                                <span class="absolute top-0 right-0 block h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white"></span>
                            @endif
                        </button>
                        <div x-show="openNotif" @click.away="openNotif = false" class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" style="display: none;">
                            <div class="px-4 py-2 border-b border-gray-100 flex justify-between items-center">
                                <h3 class="text-sm font-semibold text-gray-900">Notificações</h3>
                                @if(Auth::user()->unreadNotifications->count() > 0)
                                    <form method="POST" action="{{ route('notificacoes.ler-todas') }}">
                                        @csrf
                                        <button type="submit" class="text-xs text-blue-600 hover:text-blue-800">Marcar lidas</button>
                                    </form>
                                @endif
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                @forelse(Auth::user()->notifications()->take(5)->get() as $notification)
                                    <div class="px-4 py-3 border-b border-gray-50 {{ $notification->read_at ? 'bg-white' : 'bg-blue-50' }}">
                                        <p class="text-sm text-gray-800">
                                            @if(isset($notification->data['mensagem']))
                                                {{ $notification->data['mensagem'] }}
                                            @else
                                                Nova atualização no sistema.
                                            @endif
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                    </div>
                                @empty
                                    <div class="px-4 py-3">
                                        <p class="text-sm text-gray-500 text-center">Nenhuma notificação no momento.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
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
                    @if(app()->environment('production') && request()->routeIs('nfces.*'))
                        <div class="relative">
                            <div class="pointer-events-none select-none blur-sm opacity-60" aria-hidden="true">
                                {{ $slot }}
                            </div>
                            <div class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 backdrop-blur-[1px]">
                                <div class="mx-4 max-w-md rounded-lg border border-slate-200 bg-white px-6 py-5 text-center shadow-lg">
                                    <p class="text-base font-semibold text-slate-900">Cupom fiscal (NFC-e) em preparação</p>
                                    <p class="mt-2 text-sm text-slate-600">A emissão de notas de produto ainda não está liberada em produção.</p>
                                </div>
                            </div>
                        </div>
                    @else
                        {{ $slot }}
                    @endif
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
