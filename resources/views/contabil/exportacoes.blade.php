<x-app-layout>
    <div class="py-2">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Exportações contábeis</h1>
                <p class="mt-1 text-sm text-gray-500">CSV resumido ou ZIP com XMLs do período.</p>
            </div>
            <a href="{{ route('contabil.dashboard') }}" class="text-sm text-teal-700 hover:underline">← Área Contábil</a>
        </div>

        <x-dashboard.periodo-filter :action="route('contabil.exportacoes')" />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach([
                'nfse' => 'NFS-e (serviços)',
                'nfce' => 'NFC-e (cupons)',
                'compras' => 'Compras (NF-e entrada)',
            ] as $tipo => $label)
                <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ $label }}</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('contabil.export.csv', array_merge(request()->only(['data_inicio','data_fim']), ['tipo' => $tipo])) }}"
                           class="inline-flex justify-center px-3 py-2 bg-teal-600 text-white text-sm font-medium rounded-md hover:bg-teal-700">
                            Baixar CSV
                        </a>
                        <a href="{{ route('contabil.export.zip', array_merge(request()->only(['data_inicio','data_fim']), ['tipo' => $tipo])) }}"
                           class="inline-flex justify-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                            Baixar ZIP (XMLs)
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
