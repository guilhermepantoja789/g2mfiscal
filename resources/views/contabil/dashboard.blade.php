<x-app-layout>
    <div class="space-y-6">
        <x-page-header
            title="Área Contábil"
            subtitle="Visão fiscal consolidada da empresa {{ $empresa->nome_fantasia ?: $empresa->razao_social }}."
        >
            <x-slot name="help">
                <x-help-panel id="contabil">
                    <ul class="list-disc space-y-1 pl-4 text-sm text-slate-700">
                        <li>Livros usam competência (emissão NFC-e / competência do documento).</li>
                        <li>DRE e balanço leem as partidas dobradas geradas na autorização fiscal.</li>
                        <li>Exportações geram CSV/ZIP para o escritório.</li>
                    </ul>
                </x-help-panel>
            </x-slot>
        </x-page-header>

        <x-dashboard.periodo-filter :action="route('contabil.dashboard')" />

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-8">
            <x-dashboard.kpi-card label="NFS-e autorizadas" :value="(string) $stats['nfse_qtd']" border="green" :hint="'R$ '.number_format($stats['nfse_valor'], 2, ',', '.')" />
            <x-dashboard.kpi-card label="ISS aproximado" :value="'R$ '.number_format($stats['nfse_iss'], 2, ',', '.')" border="teal" :hint="$stats['nfse_canceladas'].' canceladas'" />
            <x-dashboard.kpi-card label="NFC-e autorizadas" :value="(string) $stats['nfce_qtd']" border="blue" :hint="'R$ '.number_format($stats['nfce_valor'], 2, ',', '.')" />
            <x-dashboard.kpi-card label="NFC-e canceladas" :value="(string) $stats['nfce_canceladas']" border="rose" />
            <x-dashboard.kpi-card label="Compras (entradas)" :value="(string) $stats['compras_qtd']" border="amber" :hint="'R$ '.number_format($stats['compras_valor'], 2, ',', '.')" />
            <x-dashboard.kpi-card label="Faturamento período" :value="'R$ '.number_format($stats['faturamento'], 2, ',', '.')" border="indigo" hint="NFS-e + NFC-e" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <a href="{{ route('contabil.livro_servicos', request()->only(['data_inicio','data_fim'])) }}" class="bg-white border border-gray-200 rounded-lg p-4 hover:border-teal-400 transition">
                <p class="text-sm font-semibold text-gray-900">Livro de saídas — Serviços</p>
                <p class="text-xs text-gray-500 mt-1">NFS-e por competência</p>
            </a>
            <a href="{{ route('contabil.livro_cupons', request()->only(['data_inicio','data_fim'])) }}" class="bg-white border border-gray-200 rounded-lg p-4 hover:border-teal-400 transition">
                <p class="text-sm font-semibold text-gray-900">Livro de saídas — Cupons</p>
                <p class="text-xs text-gray-500 mt-1">NFC-e por data de emissão</p>
            </a>
            <a href="{{ route('contabil.livro_entradas', request()->only(['data_inicio','data_fim'])) }}" class="bg-white border border-gray-200 rounded-lg p-4 hover:border-teal-400 transition">
                <p class="text-sm font-semibold text-gray-900">Livro de entradas</p>
                <p class="text-xs text-gray-500 mt-1">NF-e de compra / XML</p>
            </a>
            <a href="{{ route('contabil.plano.index') }}" class="bg-white border border-gray-200 rounded-lg p-4 hover:border-teal-400 transition">
                <p class="text-sm font-semibold text-gray-900">Plano de contas</p>
                <p class="text-xs text-gray-500 mt-1">Contas e mapeamentos fiscais</p>
            </a>
            <a href="{{ route('contabil.dre', request()->only(['data_inicio','data_fim'])) }}" class="bg-white border border-gray-200 rounded-lg p-4 hover:border-teal-400 transition">
                <p class="text-sm font-semibold text-gray-900">DRE gerencial</p>
                <p class="text-xs text-gray-500 mt-1">Receitas × despesas</p>
            </a>
            <a href="{{ route('contabil.balanco') }}" class="bg-white border border-gray-200 rounded-lg p-4 hover:border-teal-400 transition">
                <p class="text-sm font-semibold text-gray-900">Balanço gerencial</p>
                <p class="text-xs text-gray-500 mt-1">Ativo / passivo / patrimônio</p>
            </a>
            <a href="{{ route('contabil.exportacoes', request()->only(['data_inicio','data_fim'])) }}" class="bg-white border border-gray-200 rounded-lg p-4 hover:border-teal-400 transition">
                <p class="text-sm font-semibold text-gray-900">Exportações</p>
                <p class="text-xs text-gray-500 mt-1">CSV e ZIP de XMLs</p>
            </a>
        </div>
    </div>
</x-app-layout>
