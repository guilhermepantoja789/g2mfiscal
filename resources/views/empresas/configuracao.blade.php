<x-app-layout>

    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Configurações da Empresa</h2>
            <p class="text-gray-500 text-sm">Gerencie dados cadastrais, fiscais e equipe.</p>
        </div>
        <button type="submit" form="formConfig" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            Salvar Alterações
        </button>
    </div>

    @if(empty($empresa->inscricao_municipal))
        <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 shadow-sm rounded-r-lg animate-pulse" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-yellow-800">Atenção: Inscrição Municipal não configurada</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>Para emitir notas, é obrigatório informar a Inscrição Municipal (IM).</p>
                        <p class="mt-1">
                            1. Faça o upload do seu <strong>Certificado Digital</strong> abaixo.<br>
                            2. Clique no botão <strong>"Buscar Automático"</strong> ao lado do campo Inscrição Municipal.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            <strong class=\"font-bold\">Verifique os erros:</strong>
            <ul class="mt-2 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative text-center">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Dados Cadastrais</h3>
                <form id="formConfig" action="{{ route('empresas.update', $empresa->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">CNPJ</label>
                        <input type="text" value="{{ $empresa->cnpj }}" disabled class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Razão Social</label>
                        <input type="text" name="razao_social" value="{{ old('razao_social', $empresa->razao_social) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Inscrição Municipal</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" name="inscricao_municipal" id="inscricao_municipal"
                                   value="{{ old('inscricao_municipal', $empresa->inscricao_municipal) }}"
                                   placeholder="Ex: 123456"
                                   class="flex-1 min-w-0 block w-full rounded-none rounded-l-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">

                            <button type="button" onclick="buscarImApi()" id="btnBuscarIm"
                                    class="inline-flex items-center px-4 py-2 border border-l-0 border-gray-300 rounded-r-md bg-blue-50 text-blue-700 hover:bg-blue-100 hover:text-blue-800 font-medium text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg id="iconLupa" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <svg id="iconLoading" class="animate-spin h-4 w-4 mr-2 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Buscar Automático
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Requer que o Certificado Digital esteja configurado abaixo.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">CEP</label>
                        <input type="text" name="cep" value="{{ old('cep', $empresa->cep) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Logradouro</label>
                        <input type="text" name="logradouro" value="{{ old('logradouro', $empresa->logradouro) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Número</label>
                        <input type="text" name="numero" value="{{ old('numero', $empresa->numero) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bairro</label>
                        <input type="text" name="bairro" value="{{ old('bairro', $empresa->bairro) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">UF</label>
                        <input type="text" name="uf" value="{{ old('uf', $empresa->uf) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cód. IBGE</label>
                        <input type="text" name="cod_ibge_mun" value="{{ old('cod_ibge_mun', $empresa->cod_ibge_mun) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-2 border-t pt-4 mt-2">
                        <h4 class="text-md font-semibold text-gray-900 mb-3">NFC-e Amazonas</h4>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Inscrição Estadual</label>
                        <input type="text" name="inscricao_estadual" value="{{ old('inscricao_estadual', $empresa->inscricao_estadual) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CRT</label>
                        <select name="crt" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="1" @selected(old('crt', $empresa->crt) == 1)>1 — Simples Nacional</option>
                            <option value="2" @selected(old('crt', $empresa->crt) == 2)>2 — Simples excesso sublimite</option>
                            <option value="3" @selected(old('crt', $empresa->crt) == 3)>3 — Regime Normal</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Série NFC-e</label>
                        <input type="number" name="nfce_serie" min="1" max="999" value="{{ old('nfce_serie', $empresa->nfce_serie ?? 1) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Último número usado</label>
                        <input type="number" name="nfce_ultimo_numero" min="0" value="{{ old('nfce_ultimo_numero', $empresa->nfce_ultimo_numero ?? 0) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CSC ID (idToken)</label>
                        <input type="text" name="nfce_csc_id" value="{{ old('nfce_csc_id', $empresa->nfce_csc_id) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CSC Token</label>
                        <input type="password" name="nfce_csc_token" value="" placeholder="{{ $empresa->nfce_csc_token ? '•••••••• (deixe em branco para manter)' : 'Token CSC' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" autocomplete="new-password">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Ambiente NFC-e</label>
                        <select name="nfce_ambiente" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="2" @selected(old('nfce_ambiente', $empresa->nfce_ambiente ?? 2) == 2)>2 — Homologação</option>
                            <option value="1" @selected(old('nfce_ambiente', $empresa->nfce_ambiente ?? 2) == 1)>1 — Produção</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <div class="space-y-6">

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Certificado Digital</h3>

                @if($certificado && $certificado->ativo)
                    <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">Certificado Configurado</h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p>Válido até: <strong>{{ $certificado->valido_ate ? $certificado->valido_ate->format('d/m/Y') : 'Data desc.' }}</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mb-4">Para atualizar, faça upload de um novo arquivo abaixo.</p>
                @else
                    <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Nenhum certificado ativo</h3>
                                <p class="text-sm text-red-700 mt-1">Necessário para emitir notas.</p>
                            </div>
                        </div>
                    </div>
                @endif

                <form action="{{ route('certificados.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Arquivo .PFX ou .P12</label>
                        <input type="file" name="arquivo" required accept=".pfx,.p12"
                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 @error('arquivo') border-red-500 @enderror">
                        @error('arquivo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">
                            Aceita certificado A1 (.pfx/.p12). Em OpenSSL 3, arquivos com criptografia antiga (RC2/3DES) são convertidos automaticamente.
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Senha do Certificado</label>
                        <input type="password" name="senha" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('senha') border-red-500 @enderror">
                        @error('senha')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">
                            Use a senha definida na exportação do certificado (não a senha da conta gov.br).
                        </p>
                    </div>
                    <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 text-white py-2 px-4 rounded shadow text-sm font-medium">
                        Salvar Certificado
                    </button>
                </form>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Equipe</h3>

                <div class="bg-gray-50 p-4 rounded-md mb-6 border border-gray-200">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Adicionar Membro Existente</label>
                    <form action="{{ route('equipe.store') }}" method="POST" class="flex gap-2">
                        @csrf
                        <input type="email" name="email" required
                               placeholder="Digite o e-mail do usuário cadastrado..."
                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">

                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Adicionar
                        </button>
                    </form>
                    <p class="text-xs text-gray-500 mt-2">
                        * O usuário precisa criar uma conta no sistema antes de ser adicionado aqui.
                    </p>
                </div>

                <ul class="space-y-4">
                    @forelse($empresa->users as $user)
                        <li class="flex justify-between items-center text-sm border-b border-gray-100 pb-2 last:border-0">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold mr-3 border border-blue-200">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                    <p class="text-gray-500 text-xs">{{ $user->email }}</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3">

                                @if(auth()->id() === $user->id)
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-bold uppercase">
                                        {{ $user->pivot->perfil }} (Você)
                                    </span>
                                @else
                                    <form action="{{ route('equipe.updateRole', $user->id) }}" method="POST" class="flex items-center">
                                        @csrf
                                        @method('PUT')
                                        <select name="perfil" onchange="this.form.submit()"
                                                class="text-xs border-gray-300 rounded-l-md focus:ring-blue-500 focus:border-blue-500 py-1 pl-2 pr-6 bg-gray-50">
                                            <option value="operador" {{ $user->pivot->perfil == 'operador' ? 'selected' : '' }}>Operador</option>
                                            <option value="admin" {{ $user->pivot->perfil == 'admin' ? 'selected' : '' }}>Admin</option>
                                        </select>
                                        <div class="bg-gray-200 border border-l-0 border-gray-300 rounded-r-md px-2 py-1">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        </div>
                                    </form>
                                @endif

                                @if(auth()->id() !== $user->id)
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="text-gray-500 text-center py-4">Nenhum membro na equipe além de você.</li>
                    @endforelse
                </ul>
            </div>

        </div>
    </div>

    <script>
        function buscarImApi() {
            const btn = document.getElementById('btnBuscarIm');
            const iconLupa = document.getElementById('iconLupa');
            const iconLoading = document.getElementById('iconLoading');
            const inputIM = document.getElementById('inscricao_municipal');

            // 1. UI Loading
            btn.disabled = true;
            iconLupa.classList.add('hidden');
            iconLoading.classList.remove('hidden');

            // 2. Chamada AJAX
            fetch('{{ route("empresas.buscar_im_certificado") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso) {
                        inputIM.value = data.im;
                        // Pisca em verde para indicar sucesso
                        inputIM.classList.add('bg-green-100');
                        setTimeout(() => inputIM.classList.remove('bg-green-100'), 1000);
                        alert(data.mensagem);
                    } else {
                        alert('Erro: ' + data.erro);
                    }
                })
                .catch(error => {
                    console.error(error);
                    alert('Ocorreu um erro ao comunicar com o servidor.');
                })
                .finally(() => {
                    // 3. UI Reset
                    btn.disabled = false;
                    iconLupa.classList.remove('hidden');
                    iconLoading.classList.add('hidden');
                });
        }
    </script>
</x-app-layout>
