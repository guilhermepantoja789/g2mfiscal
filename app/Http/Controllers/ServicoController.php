<?php

namespace App\Http\Controllers;

use App\Models\Servico;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;

class ServicoController extends Controller
{
    private function getEmpresaAtiva()
    {
        $id = Session::get('empresa_ativa');
        if (!$id) abort(403, 'Nenhuma empresa selecionada.');
        return Empresa::findOrFail($id);
    }

    public function index()
    {
        $empresa = $this->getEmpresaAtiva();
        $servicos = $empresa->servicos()->orderBy('nome')->paginate(10);
        return view('servicos.index', compact('servicos'));
    }

    public function create()
    {
        $empresa = $this->getEmpresaAtiva();

        // Mantemos apenas a lógica de códigos padrão se necessário
        $isManaus = $empresa->cod_ibge_mun == '1302603';
        $padraoMunicipal = $isManaus ? '100' : '';

        return view('servicos.create', compact('isManaus', 'padraoMunicipal'));
    }

    public function store(Request $request)
    {
        $empresa = $this->getEmpresaAtiva();
        $input = $request->all();

        // Tratamento de Moeda (apenas valor unitário agora)
        $input['valor_unitario'] = $this->parseMoney($input['valor_unitario'] ?? 0);

        // Limpeza de códigos
        if (!empty($input['codigo_tributacao_nacional'])) {
            $limpo = preg_replace('/\D/', '', $input['codigo_tributacao_nacional']);
            $input['codigo_tributacao_nacional'] = $this->formatCodigoNacional($limpo);
        }

        $request->replace($input);

        $request->validate([
            'nome' => 'required|string|max:100',
            'codigo_tributacao_nacional' => 'required|string',
            'codigo_tributacao_municipal' => 'required|string',
            'valor_unitario' => 'required|numeric|min:0',
            'codigo_interno' => [
                'nullable', 'string',
                Rule::unique('servicos')->where(fn ($q) => $q->where('empresa_id', $empresa->id))
            ],
        ]);

        $empresa->servicos()->create($request->all());

        return redirect()->route('servicos.index')->with('success', 'Serviço cadastrado!');
    }

    public function edit(Servico $servico)
    {
        if ($servico->empresa_id != Session::get('empresa_ativa')) abort(403);
        return view('servicos.edit', compact('servico'));
    }

    public function update(Request $request, Servico $servico)
    {
        if ($servico->empresa_id != Session::get('empresa_ativa')) abort(403);

        $input = $request->all();
        $input['valor_unitario'] = $this->parseMoney($input['valor_unitario'] ?? 0);

        if (!empty($input['codigo_tributacao_nacional'])) {
            $limpo = preg_replace('/\D/', '', $input['codigo_tributacao_nacional']);
            $input['codigo_tributacao_nacional'] = $this->formatCodigoNacional($limpo);
        }

        $request->replace($input);

        $request->validate([
            'nome' => 'required|string|max:100',
            'codigo_tributacao_nacional' => 'required|string',
            'codigo_tributacao_municipal' => 'required|string',
            'valor_unitario' => 'required|numeric|min:0',
            'codigo_interno' => [
                'nullable', 'string',
                Rule::unique('servicos')->where(fn ($q) => $q->where('empresa_id', $servico->empresa_id))->ignore($servico->id)
            ],
        ]);

        $servico->update($request->all());

        return redirect()->route('servicos.index')->with('success', 'Serviço atualizado!');
    }

    public function destroy(Servico $servico)
    {
        if ($servico->empresa_id != Session::get('empresa_ativa')) abort(403);
        $servico->delete();
        return redirect()->route('servicos.index')->with('success', 'Serviço excluído.');
    }

    // Helpers
    private function parseMoney($v) {
        return is_numeric($v) ? $v : (float) str_replace(['.', ','], ['', '.'], $v);
    }

    private function formatCodigoNacional($val) {
        // Ex: 10301 -> 1.03.01 (Apenas um exemplo simples, ajuste conforme sua regra de máscara)
        return $val;
    }
}
