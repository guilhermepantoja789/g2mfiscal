<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Gestão de Cobranças</h1>
                    <p class="mt-1 text-sm text-gray-500">Controle seus recebimentos de Pix e Boletos.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="{{ route('notas.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                        + Nova Cobrança (Via Nota)
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
                <div class="bg-white overflow-hidden shadow-sm rounded-lg border-l-4 border-yellow-400">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">A Receber (Em dia)</dt>
                                    <dd class="text-2xl font-bold text-gray-900">R$ {{ number_format($resumo->a_receber, 2, ',', '.') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm rounded-lg border-l-4 border-red-500">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-red-100 rounded-md p-3">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Vencidos / Atrasados</dt>
                                    <dd class="text-2xl font-bold text-red-600">R$ {{ number_format($resumo->atrasado, 2, ',', '.') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm rounded-lg border-l-4 border-green-500">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Pago (Geral)</dt>
                                    <dd class="text-2xl font-bold text-gray-900">R$ {{ number_format($resumo->recebido_mes, 2, ',', '.') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                <form action="{{ route('cobrancas.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Buscar Cliente</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome ou CNPJ..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                            <option value="">Todos</option>
                            <option value="PENDING" {{ request('status') == 'PENDING' ? 'selected' : '' }}>Pendente</option>
                            <option value="RECEIVED" {{ request('status') == 'RECEIVED' ? 'selected' : '' }}>Pago</option>
                            <option value="OVERDUE" {{ request('status') == 'OVERDUE' ? 'selected' : '' }}>Vencido</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Vencimento (Início)</label>
                        <input type="date" name="vencimento_inicio" value="{{ request('vencimento_inicio') }}" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline text-sm transition">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente / Nota</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($cobrancas as $cobranca)
                        @php
                            // Lógica visual simples para Atraso
                            $isAtrasado = $cobranca->status == 'PENDING' && $cobranca->vencimento < now()->startOfDay();
                            $statusLabel = $cobranca->status_label;
                            $statusColor = $cobranca->status_color;

                            if($isAtrasado) {
                                $statusLabel = 'Vencido (' . $cobranca->vencimento->diffForHumans() . ')';
                                $statusColor = 'bg-red-100 text-red-800 border border-red-200';
                            }
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                        {{ $statusLabel }}
                                    </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div class="font-bold {{ $isAtrasado ? 'text-red-600' : '' }}">
                                    {{ $cobranca->vencimento->format('d/m/Y') }}
                                </div>
                                <div class="text-xs text-gray-400">{{ $cobranca->vencimento->isToday() ? 'Hoje' : $cobranca->vencimento->dayName }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ Str::limit($cobranca->cliente->razao_social, 30) }}</div>
                                <div class="text-xs text-gray-500 flex items-center">
                                    @if($cobranca->notaFiscal)
                                        <a href="{{ route('notas.show', $cobranca->notaFiscal->id) }}" class="hover:text-indigo-600 hover:underline">
                                            Ref: NFS-e #{{ $cobranca->notaFiscal->numero_nfse ?? $cobranca->notaFiscal->id }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">Cobrança Avulsa</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                R$ {{ number_format($cobranca->valor, 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                @if($cobranca->status == 'PENDING')
                                    <form action="{{ route('cobrancas.baixar_manual', $cobranca->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Confirmar o recebimento deste valor?');">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-900 bg-green-50 px-2 py-1 rounded text-xs font-bold border border-green-200 hover:bg-green-100">
                                            $ Baixar
                                        </button>
                                    </form>

                                    <button type="button" onclick="alert('Link do Boleto (Simulação):\n\nhttps://asaas.com/pagar/{{ $cobranca->id }}')" class="ml-2 text-indigo-600 hover:text-indigo-900 text-xs">
                                        Link
                                    </button>
                                @else
                                    <span class="text-gray-400 text-xs italic">Concluído</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <p>Nenhuma cobrança encontrada com estes filtros.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $cobrancas->links() }}
            </div>

        </div>
    </div>
</x-app-layout>
