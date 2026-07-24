<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nova Empresa - {{ config('app.name', 'G2M Fiscal') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 text-gray-900">

<div class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="{{ route('empresas.selecao') }}">
                    <img src="{{ asset('img/logo.png') }}" alt="Logo" class="h-10 w-auto">
                </a>
            </div>

            <div class="flex items-center">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium transition flex items-center">
                        Sair
                        <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="min-h-screen py-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-8">
            <h2 class="text-3xl font-bold text-gray-800">Nova Empresa</h2>
            <a href="{{ route('empresas.selecao') }}" class="text-gray-600 hover:text-gray-900 flex items-center font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Voltar
            </a>
        </div>

        <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-[#1e676d] to-[#2a8a91] px-8 py-6">
                <p class="text-white text-sm opacity-90">Preencha os dados abaixo. Use a busca automática pelo CNPJ para agilizar.</p>
            </div>

            <form action="{{ route('empresas.store') }}" method="POST" class="p-8">
                @csrf

                <div class="mb-10">
                    <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-6 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[#1e676d]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Identificação
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">CNPJ</label>
                            <div class="relative">
                                <input type="text" name="cnpj" id="cnpj" value="{{ old('cnpj') }}" required
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5"
                                       placeholder="00.000.000/0000-00" onblur="consultarCnpj(this.value)">
                                <div id="loading-cnpj" class="absolute right-3 top-3 hidden">
                                    <svg class="animate-spin h-5 w-5 text-[#1e676d]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Digite para buscar.</p>
                            @error('cnpj') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-8">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Razão Social</label>
                            <input type="text" name="razao_social" id="razao_social" value="{{ old('razao_social') }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5 bg-gray-50">
                        </div>

                        <div class="md:col-span-12">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome Fantasia</label>
                            <input type="text" name="nome_fantasia" id="nome_fantasia" value="{{ old('nome_fantasia') }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5">
                        </div>

                        <div class="md:col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5">
                        </div>

                        <div class="md:col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                            <input type="text" name="telefone" id="telefone" value="{{ old('telefone') }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5 phone-mask">
                        </div>
                    </div>
                </div>

                <div class="mb-10">
                    <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-6 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[#1e676d]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Endereço
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                            <input type="text" name="cep" id="cep" value="{{ old('cep') }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5"
                                   onblur="consultarCep(this.value)">
                            @error('cep') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-7">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Endereço (Rua/Av)</label>
                            <input type="text" name="logradouro" id="logradouro" value="{{ old('logradouro') }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5 bg-gray-50">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                            <input type="text" name="numero" id="numero" value="{{ old('numero') }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5">
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                            <input type="text" name="complemento" id="complemento" value="{{ old('complemento') }}"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5">
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                            <input type="text" name="bairro" id="bairro" value="{{ old('bairro') }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5 bg-gray-50">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">UF</label>
                            <input type="text" name="uf" id="uf" value="{{ old('uf') }}" required maxlength="2"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5 bg-gray-50 text-center uppercase">
                            @error('uf') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cód. IBGE Cidade</label>
                            <input type="text" name="cod_ibge_mun" id="cod_ibge_mun" value="{{ old('cod_ibge_mun') }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5 bg-gray-50" readonly>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-6 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[#1e676d]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Dados Fiscais
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Inscrição Municipal (IM)</label>
                            <input type="text" name="inscricao_municipal" value="{{ old('inscricao_municipal') }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Regime Tributário</label>
                            <select name="regime_tributario" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5">
                                <option value="1" {{ old('regime_tributario') == '1' ? 'selected' : '' }}>1 — Não optante do Simples</option>
                                <option value="2" {{ old('regime_tributario') == '2' ? 'selected' : '' }}>2 — MEI</option>
                                <option value="3" {{ old('regime_tributario', '3') == '3' ? 'selected' : '' }}>3 — ME/EPP optante do Simples</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Regime Especial</label>
                            <select name="regime_especial_tributacao" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5">
                                <option value="0">Nenhum</option>
                                <option value="1">Microempresa Municipal</option>
                                <option value="2">Estimativa</option>
                                <option value="3">Sociedade de Profissionais</option>
                                <option value="4">Cooperativa</option>
                                <option value="5">MEI</option>
                                <option value="6">Microempresa ou EPP</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-10 pt-6 border-t flex justify-end gap-4">
                    <a href="{{ route('empresas.selecao') }}" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="px-8 py-3 bg-[#1e676d] hover:bg-[#154d52] text-white rounded-lg font-bold shadow-lg transition transform hover:-translate-y-0.5">
                        Cadastrar Empresa
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    function consultarCnpj(cnpj) {
        cnpj = cnpj.replace(/\D/g, '');
        if (cnpj.length !== 14) return;
        document.getElementById('loading-cnpj').classList.remove('hidden');
        fetch(`https://brasilapi.com.br/api/cnpj/v1/${cnpj}`)
            .then(res => res.json())
            .then(data => {
                if (data.message) { alert('CNPJ não encontrado!'); return; }
                document.getElementById('razao_social').value = data.razao_social;
                document.getElementById('nome_fantasia').value = data.nome_fantasia || data.razao_social;
                document.getElementById('email').value = data.email || '';
                document.getElementById('telefone').value = data.ddd_telefone_1 || '';

                document.getElementById('cep').value = data.cep;
                document.getElementById('logradouro').value = data.logradouro;
                document.getElementById('numero').value = data.numero;
                document.getElementById('complemento').value = data.complemento;
                document.getElementById('bairro').value = data.bairro;

                // Preenche o novo campo UF
                document.getElementById('uf').value = data.uf;

                document.getElementById('cod_ibge_mun').value = data.codigo_municipio_ibge;
            })
            .catch(err => console.error(err))
            .finally(() => document.getElementById('loading-cnpj').classList.add('hidden'));
    }

    function consultarCep(cep) {
        cep = cep.replace(/\D/g, '');
        if (cep.length !== 8) return;
        fetch(`https://brasilapi.com.br/api/cep/v2/${cep}`)
            .then(res => res.json())
            .then(data => {
                if (data.errors) return;
                document.getElementById('logradouro').value = data.street;
                document.getElementById('bairro').value = data.neighborhood;
                // Preenche o novo campo UF via CEP também
                document.getElementById('uf').value = data.state;
            });
    }
</script>
</body>
</html>
