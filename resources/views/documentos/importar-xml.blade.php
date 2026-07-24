<x-app-layout>
    <div class="max-w-3xl mx-auto space-y-6">
        <h2 class="text-2xl font-bold text-gray-800">Importar XML NF-e (modelo 55)</h2>
        <p class="text-sm text-gray-600">Faça upload do XML da compra. O sistema criará fornecedor, produtos, documento, estoque e contas a pagar.</p>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-md p-4 text-sm">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('documentos.importar_xml.preview') }}" enctype="multipart/form-data" class="bg-white shadow rounded-lg p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Arquivo XML</label>
                <input type="file" name="xml_file" accept=".xml,text/xml" class="mt-1 block w-full text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Ou cole o XML</label>
                <textarea name="xml" rows="10" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs" placeholder="<nfeProc>...</nfeProc>">{{ old('xml') }}</textarea>
            </div>
            <div class="flex justify-end gap-3">
                <a href="{{ route('documentos.index') }}" class="px-4 py-2 text-gray-600">Cancelar</a>
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-md font-bold">Pré-visualizar</button>
            </div>
        </form>
    </div>
</x-app-layout>
