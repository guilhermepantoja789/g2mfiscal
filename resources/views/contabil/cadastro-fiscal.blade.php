<x-app-layout>
    <div class="py-2">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Cadastro fiscal</h1>
                <p class="mt-1 text-sm text-gray-500">Dados fiscais da empresa (somente leitura).</p>
            </div>
            <a href="{{ route('contabil.dashboard') }}" class="text-sm text-teal-700 hover:underline">← Área Contábil</a>
        </div>

        <div class="bg-white shadow rounded-lg p-6 max-w-3xl">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">Razão social</dt>
                    <dd class="font-medium text-gray-900">{{ $empresa->razao_social }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Nome fantasia</dt>
                    <dd class="font-medium text-gray-900">{{ $empresa->nome_fantasia ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">CNPJ</dt>
                    <dd class="font-medium text-gray-900">{{ $empresa->cnpj }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Inscrição estadual</dt>
                    <dd class="font-medium text-gray-900">{{ $empresa->inscricao_estadual ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Inscrição municipal</dt>
                    <dd class="font-medium text-gray-900">{{ $empresa->inscricao_municipal ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">CRT</dt>
                    <dd class="font-medium text-gray-900">{{ $empresa->crt ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Regime tributário</dt>
                    <dd class="font-medium text-gray-900">{{ $empresa->regime_tributario ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">UF / Município IBGE</dt>
                    <dd class="font-medium text-gray-900">{{ $empresa->uf }} / {{ $empresa->cod_ibge_mun }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Série NFC-e</dt>
                    <dd class="font-medium text-gray-900">{{ $empresa->nfce_serie ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Ambiente NFC-e</dt>
                    <dd class="font-medium text-gray-900">{{ (int) $empresa->nfce_ambiente === 1 ? 'Produção' : 'Homologação' }}</dd>
                </div>
            </dl>
            <p class="mt-6 text-xs text-gray-500">Certificado digital e alterações cadastrais ficam restritos ao administrador da empresa.</p>
        </div>
    </div>
</x-app-layout>
