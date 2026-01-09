<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Empresa - {{ config('app.name', 'G2M Fiscal') }}</title>

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
            <h2 class="text-3xl font-bold text-gray-800">Editar Empresa</h2>
            <a href="{{ route('empresas.selecao') }}" class="text-gray-600 hover:text-gray-900 flex items-center font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Voltar
            </a>
        </div>

        <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-[#1e676d] to-[#2a8a91] px-8 py-6">
                <p class="text-white text-sm opacity-90">Atualize os dados cadastrais da empresa abaixo.</p>
            </div>

            <form action="{{ route('empresas.update', $empresa->id) }}" method="POST" class="p-8">
                @csrf
                @method('PUT') <div class="mb-10">
                    <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-6 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[#1e676d]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Identificação
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">CNPJ</label>
                            <input type="text" name="cnpj" value="{{ old('cnpj', $empresa->cnpj) }}" required
                                   class="w-full rounded-lg border-gray-300 bg-gray-100 cursor-not-allowed shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5"
                                   readonly>
                            <p class="text-xs text-gray-500 mt-1">O CNPJ não pode ser alterado.</p>
                        </div>

                        <div class="md:col-span-8">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Razão Social</label>
                            <input type="text" name="razao_social" value="{{ old('razao_social', $empresa->razao_social) }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5 bg-gray-50">
                        </div>

                        <div class="md:col-span-12">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome Fantasia</label>
                            <input type="text" name="nome_fantasia" value="{{ old('nome_fantasia', $empresa->nome_fantasia) }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5">
                        </div>

                        <div class="md:col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                            <input type="email" name="email" value="{{ old('email', $empresa->email) }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5">
                        </div>

                        <div class="md:col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                            <input type="text" name="telefone" value="{{ old('telefone', $empresa->telefone) }}" required
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
                            <input type="text" name="cep" id="cep" value="{{ old('cep', $empresa->cep) }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5"
                                   onblur="consultarCep(this.value)">
                        </div>

                        <div class="md:col-span-7">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Endereço (Rua/Av)</label>
                            <input type="text" name="logradouro" id="logradouro" value="{{ old('logradouro', $empresa->logradouro) }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5 bg-gray-50">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                            <input type="text" name="numero" id="numero" value="{{ old('numero', $empresa->numero) }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5">
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                            <input type="text" name="complemento" id="complemento" value="{{ old('complemento', $empresa->complemento) }}"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5">
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                            <input type="text" name="bairro" id="bairro" value="{{ old('bairro', $empresa->bairro) }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5 bg-gray-50">
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cód. IBGE Cidade</label>
                            <input type="text" name="cod_ibge_mun" id="cod_ibge_mun" value="{{ old('cod_ibge_mun', $empresa->cod_ibge_mun) }}" required
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
                            <input type="text" name="inscricao_municipal" value="{{ old('inscricao_municipal', $empresa->inscricao_municipal) }}" required
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Regime Tributário</label>
                            <select name="regime_tributario" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5">
                                <option value="1" {{ old('regime_tributario', $empresa->regime_tributario) == '1' ? 'selected' : '' }}>Simples Nacional</option>
                                <option value="2" {{ old('regime_tributario', $empresa->regime_tributario) == '2' ? 'selected' : '' }}>Simples Nacional - Excesso</option>
                                <option value="3" {{ old('regime_tributario', $empresa->regime_tributario) == '3' ? 'selected' : '' }}>Regime Normal (Lucro Presumido/Real)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Regime Especial</label>
                            <select name="regime_especial_tributacao" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#1e676d] focus:ring-[#1e676d] py-2.5">
                                <option value="0" {{ old('regime_especial_tributacao', $empresa->regime_especial_tributacao) == '0' ? 'selected' : '' }}>Nenhum</option>
                                <option value="1" {{ old('regime_especial_tributacao', $empresa->regime_especial_tributacao) == '1' ? 'selected' : '' }}>Microempresa Municipal</option>
                                <option value="2" {{ old('regime_especial_tributacao', $empresa->regime_especial_tributacao) == '2' ? 'selected' : '' }}>Estimativa</option>
                                <option value="3" {{ old('regime_especial_tributacao', $empresa->regime_especial_tributacao) == '3' ? 'selected' : '' }}>Sociedade de Profissionais</option>
                                <option value="4" {{ old('regime_especial_tributacao', $empresa->regime_especial_tributacao) == '4' ? 'selected' : '' }}>Cooperativa</option>
                                <option value="5" {{ old('regime_especial_tributacao', $empresa->regime_especial_tributacao) == '5' ? 'selected' : '' }}>MEI</option>
                                <option value="6" {{ old('regime_especial_tributacao', $empresa->regime_especial_tributacao) == '6' ? 'selected' : '' }}>Microempresa ou EPP</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-10 pt-6 border-t flex justify-end gap-4">
                    <a href="{{ route('empresas.selecao') }}" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="px-8 py-3 bg-[#1e676d] hover:bg-[#154d52] text-white rounded-lg font-bold shadow-lg transition transform hover:-translate-y-0.5">
                        Salvar Alterações
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    function consultarCep(cep) {
        cep = cep.replace(/\D/g, '');
        if (cep.length !== 8) return;
        fetch(`https://brasilapi.com.br/api/cep/v2/${cep}`)
            .then(res => res.json())
            .then(data => {
                if (data.errors) return;
                document.getElementById('logradouro').value = data.street;
                document.getElementById('bairro').value = data.neighborhood;
            });
    }
</script>
</body>
</html>
