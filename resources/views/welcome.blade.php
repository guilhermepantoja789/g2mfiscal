<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'G2M Fiscal') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased font-sans text-gray-900 bg-white">

<div class="bg-white">
    <header class="absolute inset-x-0 top-0 z-50">
        <nav class="flex items-center justify-between p-6 lg:px-8" aria-label="Global">

            <div class="flex lg:flex-1">
                        <span class="-m-1.5 p-1.5">
                            <span class="sr-only">{{ config('app.name') }}</span>
                            <img class="h-16 w-auto" src="{{ asset('img/logo.png') }}" alt="Logo G2M">
                        </span>
            </div>

            <div class="flex flex-1 justify-end gap-4 items-center">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/app/selecao') }}" class="text-sm font-semibold leading-6 text-[#1e676d]">Ir para o Painel <span aria-hidden="true">&rarr;</span></a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold leading-6 text-[#1e676d] hover:text-[#154d52]">Entrar</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-md bg-[#1e676d] px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#154d52] transition">
                                Criar Conta
                            </a>
                        @endif
                    @endauth
                @endif
            </div>
        </nav>
    </header>

    <div class="relative isolate px-6 pt-14 lg:px-8 h-screen flex flex-col justify-center">
        <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
            <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#1e676d] to-[#9089fc] opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"></div>
        </div>

        <div class="mx-auto max-w-2xl text-center">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl">
                Emissão de notas simplificada!
            </h1>
            <p class="mt-6 text-lg leading-8 text-gray-600">
                Bem-vindo ao portal de emissão de notas fiscais da <strong>G2M Tecnologia</strong>.
                <br>Acesse sua conta para gerenciar seus documentos fiscais.
            </p>

            <div class="mt-10 flex items-center justify-center gap-x-6">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/app/selecao') }}" class="rounded-md bg-[#1e676d] px-6 py-3.5 text-base font-semibold text-white shadow-sm hover:bg-[#154d52] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1e676d] transition w-full sm:w-auto">
                            Acessar Painel
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-md bg-[#1e676d] px-6 py-3.5 text-base font-semibold text-white shadow-sm hover:bg-[#154d52] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1e676d] transition w-full sm:w-auto">
                            Fazer Login
                        </a>
                    @endauth
                @endif
            </div>
        </div>

        <div class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]" aria-hidden="true">
            <div class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-[#ff80b5] to-[#1e676d] opacity-20 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]"></div>
        </div>
    </div>
</div>
</body>
</html>
