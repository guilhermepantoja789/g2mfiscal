@props([
    'action',
])

<div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-8">
    <form method="GET" action="{{ $action }}" id="filterForm">
        <div class="flex flex-wrap gap-2 mb-4 border-b border-gray-100 pb-3">
            <span class="text-xs font-bold text-gray-500 uppercase self-center mr-2">Período Rápido:</span>
            <button type="button" onclick="setDateRange('today')" class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 transition">Hoje</button>
            <button type="button" onclick="setDateRange('month')" class="px-3 py-1 text-xs font-medium rounded-full bg-blue-50 hover:bg-blue-100 text-blue-700 transition">Este Mês</button>
            <button type="button" onclick="setDateRange('last_month')" class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 transition">Mês Passado</button>
            <button type="button" onclick="setDateRange('year')" class="px-3 py-1 text-xs font-medium rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition">Este Ano</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">De</label>
                <input type="date" name="data_inicio" id="data_inicio"
                       value="{{ request('data_inicio', now()->startOfMonth()->format('Y-m-d')) }}"
                       class="block w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Até</label>
                <input type="date" name="data_fim" id="data_fim"
                       value="{{ request('data_fim', now()->endOfMonth()->format('Y-m-d')) }}"
                       class="block w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="md:col-span-2 flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-bold hover:bg-blue-700 transition shadow-sm">
                    Filtrar
                </button>
                @if(request()->anyFilled(['data_inicio', 'data_fim']))
                    <a href="{{ $action }}" class="flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 text-sm font-medium" title="Limpar">
                        Limpar
                    </a>
                @endif
                {{ $slot }}
            </div>
        </div>
    </form>
</div>

<script>
    function setDateRange(type) {
        const today = new Date();
        let start = new Date();
        let end = new Date();
        const formatDate = (date) => date.toISOString().split('T')[0];

        if (type === 'month') {
            start = new Date(today.getFullYear(), today.getMonth(), 1);
            end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        } else if (type === 'last_month') {
            start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            end = new Date(today.getFullYear(), today.getMonth(), 0);
        } else if (type === 'year') {
            start = new Date(today.getFullYear(), 0, 1);
            end = new Date(today.getFullYear(), 11, 31);
        }

        document.getElementById('data_inicio').value = formatDate(start);
        document.getElementById('data_fim').value = formatDate(end);
        document.getElementById('filterForm').submit();
    }
</script>
