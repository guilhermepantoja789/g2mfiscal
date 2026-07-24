<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-surface"
      @if(request()->routeIs('pdv.*', 'documentos.importar*')) data-turbo="false" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'G2M Fiscal') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-800">

@php
    $initialGroup = null;
    foreach (($nav['groups'] ?? []) as $g) {
        if (($g['active'] ?? false) && (empty($g['href']) || count($g['items'] ?? []) > 1)) {
            $initialGroup = $g['id'];
            break;
        }
    }
@endphp

<div
    class="min-h-full"
    x-data="appShell({ initialGroup: @js($initialGroup) })"
    @keydown.window="onGlobalKey($event)"
>
    @include('layouts.partials.nav-rail')
    @include('layouts.partials.nav-panel')
    @include('layouts.partials.nav-mobile')
    @include('layouts.partials.command-palette')
    @include('layouts.partials.spotlight')

    <div
        class="flex min-h-full flex-col pb-16 md:pb-0"
        :class="activeGroup ? 'md:pl-[19rem]' : 'md:pl-rail'"
    >
        <header class="sticky top-0 z-10 flex h-14 flex-shrink-0 items-center border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="flex flex-1 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-base font-semibold leading-tight text-slate-900 max-w-[200px] sm:max-w-md">
                        {{ $nomeEmpresaExibicao ?? 'Painel' }}
                    </h1>
                    @if(!empty($cnpjEmpresaExibicao))
                        <span class="block truncate text-xs text-slate-500">{{ $cnpjEmpresaExibicao }}</span>
                    @endif
                </div>

                <div class="flex items-center gap-1 sm:gap-2">
                    <button
                        type="button"
                        data-spotlight="cmdk-btn"
                        @click="openCmd()"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm text-slate-500 hover:border-slate-300 hover:text-slate-700"
                        title="Busca rápida"
                    >
                        <x-icon name="search" class="h-4 w-4" />
                        <span class="hidden sm:inline">Buscar</span>
                        <kbd class="hidden md:inline rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-medium text-slate-400">⌘K</kbd>
                    </button>

                    <button
                        type="button"
                        data-spotlight="help-btn"
                        @click="startSpotlight(true)"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                        title="Ajuda do shell"
                    >
                        <x-icon name="question-mark-circle" class="h-5 w-5" />
                    </button>

                    <div class="relative" x-data="{ openNotif: false }">
                        <button
                            @click="openNotif = !openNotif"
                            type="button"
                            class="relative rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-brand"
                        >
                            <span class="sr-only">Ver notificações</span>
                            <x-icon name="bell" class="h-5 w-5" />
                            @if(Auth::user()->unreadNotifications->count() > 0)
                                <span class="absolute top-1.5 right-1.5 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                            @endif
                        </button>
                        <div
                            x-show="openNotif"
                            x-cloak
                            x-transition
                            @click.away="openNotif = false"
                            class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-lg bg-white py-1 shadow-lg ring-1 ring-black/5"
                        >
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
                                <h3 class="text-sm font-semibold text-slate-900">Notificações</h3>
                                @if(Auth::user()->unreadNotifications->count() > 0)
                                    <form method="POST" action="{{ route('notificacoes.ler-todas') }}">
                                        @csrf
                                        <button type="submit" class="text-xs text-brand hover:text-brand-hover">Marcar lidas</button>
                                    </form>
                                @endif
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                @forelse(Auth::user()->notifications()->take(5)->get() as $notification)
                                    <div class="border-b border-slate-50 px-4 py-3 {{ $notification->read_at ? 'bg-white' : 'bg-brand-soft/50' }}">
                                        <p class="text-sm text-slate-800">
                                            @if(isset($notification->data['mensagem']))
                                                {{ $notification->data['mensagem'] }}
                                            @else
                                                Nova atualização no sistema.
                                            @endif
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $notification->created_at->diffForHumans() }}</p>
                                    </div>
                                @empty
                                    <div class="px-4 py-3">
                                        <p class="text-center text-sm text-slate-500">Nenhuma notificação no momento.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="relative" x-data="{ open: false }">
                        <button
                            @click="open = !open"
                            type="button"
                            class="flex items-center rounded-full focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
                        >
                            <span class="sr-only">Menu do usuário</span>
                            <div class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-slate-100 text-sm font-bold text-slate-600">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                        </button>
                        <div
                            x-show="open"
                            x-cloak
                            x-transition
                            @click.away="open = false"
                            class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-lg bg-white py-1 shadow-lg ring-1 ring-black/5"
                        >
                            <div class="border-b border-slate-100 px-4 py-3">
                                <p class="truncate text-sm font-medium text-slate-900">{{ Auth::user()->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Meu Perfil</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">Sair</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 bg-surface">
            <div class="py-6">
                <div class="mx-auto max-w-content px-4 sm:px-6 lg:px-8 page-shell">
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
@stack('scripts')
</body>
</html>
