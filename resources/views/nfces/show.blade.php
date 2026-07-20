<x-app-layout>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">NFC-e {{ $nfce->numero }}/{{ $nfce->serie }}</h2>
            <p class="text-gray-500 text-sm">Status: <strong>{{ $nfce->status }}</strong>
                @if($nfce->c_stat) — cStat {{ $nfce->c_stat }} @endif
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('nfces.index') }}" class="py-2 px-4 text-gray-600">Voltar</a>
            @if($nfce->status === 'autorizada')
                <a href="{{ route('nfces.imprimir', $nfce->id) }}" class="bg-gray-800 hover:bg-gray-900 text-white font-bold py-2 px-4 rounded">DANFE PDF</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
    @endif

    <div class="bg-white shadow rounded-lg p-6 space-y-3 text-sm max-w-3xl">
        <p><strong>Chave:</strong> <span class="font-mono">{{ $nfce->chave ?: '—' }}</span></p>
        <p><strong>Protocolo:</strong> {{ $nfce->protocolo ?: '—' }}</p>
        <p><strong>Motivo:</strong> {{ $nfce->x_motivo ?: '—' }}</p>
        <p><strong>Total:</strong> R$ {{ number_format($nfce->valor_total, 2, ',', '.') }}</p>
        @if($nfce->qr_code_url)
            <p class="break-all"><strong>QR:</strong> {{ $nfce->qr_code_url }}</p>
        @endif
        <div class="pt-2 flex gap-3">
            <a href="{{ route('nfces.laboratorio') }}" class="text-blue-600 hover:underline text-sm">← Laboratório</a>
            @if($nfce->status === 'autorizada')
                <a href="{{ route('nfces.imprimir', $nfce->id) }}" class="text-blue-600 hover:underline text-sm">Baixar DANFE</a>
            @endif
        </div>
    </div>
</x-app-layout>
