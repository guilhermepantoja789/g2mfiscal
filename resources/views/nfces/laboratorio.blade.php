<x-app-layout>
    <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Laboratório NFC-e</h2>
            <p class="text-gray-500 text-sm">Homologação SEFAZ-AM — {{ $empresa->razao_social }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('nfces.index') }}" class="py-2 px-4 text-sm text-gray-600 hover:text-gray-900">Lista</a>
            <a href="{{ route('empresas.configuracao', $empresa) }}" class="py-2 px-4 text-sm bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-50">Configurar empresa</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded text-sm">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if (session('lab_status'))
        @php $st = session('lab_status'); @endphp
        <div class="mb-4 px-4 py-3 rounded border text-sm {{ $st['ok'] ? 'bg-green-50 border-green-300 text-green-800' : 'bg-red-50 border-red-300 text-red-800' }}">
            <p class="font-semibold">Status serviço ({{ $st['profile'] }})</p>
            <p>cStat: {{ $st['cStat'] ?: '—' }} — {{ $st['xMotivo'] }}</p>
            @if($st['ok'])
                <p class="mt-1 text-xs">107 = Serviço em Operação</p>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Checklist --}}
        <div class="bg-white shadow rounded-lg p-5">
            <h3 class="font-semibold text-gray-900 border-b pb-2 mb-3">Pré-requisitos</h3>
            <ul class="space-y-2 text-sm">
                @foreach($checks as $check)
                    <li class="flex items-start gap-2">
                        <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full text-xs font-bold {{ $check['ok'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $check['ok'] ? '✓' : '!' }}
                        </span>
                        <div>
                            <p class="font-medium text-gray-800">{{ $check['label'] }}</p>
                            <p class="text-xs text-gray-500">{{ $check['detail'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
            @unless($pronto)
                <p class="mt-4 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded p-2">
                    Complete IE, CSC e certificado na configuração da empresa antes do smoke test.
                </p>
            @endunless
            <p class="mt-3 text-xs text-gray-400">
                Série {{ $empresa->nfce_serie ?? 1 }} · último nº {{ $empresa->nfce_ultimo_numero ?? 0 }} · driver {{ config('nfce.driver') }}
            </p>
        </div>

        {{-- Status SEFAZ --}}
        <div class="bg-white shadow rounded-lg p-5">
            <h3 class="font-semibold text-gray-900 border-b pb-2 mb-3">1. Status do serviço</h3>
            <p class="text-sm text-gray-500 mb-4">Consulta <code class="text-xs bg-gray-100 px-1 rounded">NfeStatusServico</code> com mTLS do A1.</p>
            <form action="{{ route('nfces.laboratorio.status') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Perfil endpoint</label>
                    <select name="profile" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="homolog_nac" @selected($perfilDefault === 'homolog_nac')>homolog_nac (software house)</option>
                        <option value="homolog" @selected($perfilDefault === 'homolog')>homolog (contribuinte)</option>
                        <option value="producao" @selected($perfilDefault === 'producao')>produção</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2 px-4 rounded text-sm">
                    Consultar SEFAZ
                </button>
            </form>
        </div>

        {{-- Emitir teste --}}
        <div class="bg-white shadow rounded-lg p-5">
            <h3 class="font-semibold text-gray-900 border-b pb-2 mb-3">2. Emissão smoke</h3>
            <p class="text-sm text-gray-500 mb-4">Item simbólico CSOSN 102 / CFOP 5102, pagamento dinheiro.</p>
            <form action="{{ route('nfces.laboratorio.emitir') }}" method="POST" class="space-y-3"
                  onsubmit="return confirm('Emitir NFC-e de teste? Isso consome numeração.');">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Perfil endpoint</label>
                    <select name="profile" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" {{ $pronto ? '' : 'disabled' }}>
                        <option value="homolog_nac" selected>homolog_nac (comece por aqui)</option>
                        <option value="homolog">homolog (contribuinte)</option>
                        <option value="producao">produção</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Valor (R$)</label>
                    <input type="number" name="valor" step="0.01" min="0.01" max="100" value="1.00"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" {{ $pronto ? '' : 'disabled' }}>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Modo</label>
                    <select name="modo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" {{ $pronto ? '' : 'disabled' }}>
                        <option value="sync" selected>Síncrono (resultado na hora)</option>
                        <option value="fila">Fila (EmitirNfceJob)</option>
                    </select>
                </div>
                <button type="submit" @disabled(! $pronto)
                        class="w-full font-semibold py-2 px-4 rounded text-sm text-white {{ $pronto ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-300 cursor-not-allowed' }}">
                    Emitir NFC-e de teste
                </button>
            </form>
        </div>
    </div>

    {{-- Recentes --}}
    <div class="mt-6 bg-white shadow rounded-lg overflow-hidden">
        <div class="px-5 py-3 border-b flex justify-between items-center">
            <h3 class="font-semibold text-gray-900">Emissões recentes</h3>
            <a href="{{ route('nfces.create') }}" class="text-sm text-blue-600 hover:underline">Venda avulsa →</a>
        </div>
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left">#</th>
                    <th class="px-4 py-2 text-left">Núm/Série</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-left">cStat / motivo</th>
                    <th class="px-4 py-2 text-right">Total</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($recentes as $nfce)
                    <tr>
                        <td class="px-4 py-2 text-gray-500">{{ $nfce->id }}</td>
                        <td class="px-4 py-2">{{ $nfce->numero }}/{{ $nfce->serie }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium
                                @if($nfce->status === 'autorizada') bg-green-100 text-green-800
                                @elseif($nfce->status === 'rejeitado') bg-red-100 text-red-800
                                @elseif($nfce->status === 'processando') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-700 @endif">
                                {{ $nfce->status }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-600 max-w-xs truncate" title="{{ $nfce->x_motivo }}">
                            {{ $nfce->c_stat ?: '—' }}
                            @if($nfce->x_motivo) — {{ \Illuminate\Support\Str::limit($nfce->x_motivo, 60) }} @endif
                        </td>
                        <td class="px-4 py-2 text-right">R$ {{ number_format($nfce->valor_total, 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('nfces.show', $nfce->id) }}" class="text-blue-600 hover:underline">Abrir</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-400">Nenhum teste ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
