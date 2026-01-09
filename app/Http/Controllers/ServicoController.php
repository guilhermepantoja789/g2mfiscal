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

        // Verifica se é Manaus (IBGE 1302603)
        $isManaus = $empresa->cod_ibge_mun == '1302603';

        // Define o padrão se for Manaus
        $padraoMunicipal = $isManaus ? '100' : '';

        return view('servicos.create', compact('isManaus', 'padraoMunicipal'));
    }

    public function store(Request $request)
    {
        $empresa = $this->getEmpresaAtiva();
        $input = $request->all();

        // 1. Tratamento de Moeda
        $parseMoney = fn($v) => is_numeric($v) ? $v : (float) str_replace(['.', ','], ['', '.'], $v ?? 0);
        $keysMoney = ['valor_unitario', 'aliquota_iss', 'aliquota_pis', 'aliquota_cofins', 'aliquota_inss', 'aliquota_ir', 'aliquota_csll'];

        foreach($keysMoney as $key) {
            if (isset($input[$key])) $input[$key] = $parseMoney($input[$key]);
        }

        // 2. CORREÇÃO DE FORMATAÇÃO (CRÍTICO PARA EMISSÃO)
        // Garante 6 dígitos no Nacional e 3 no Municipal
        if (!empty($input['codigo_tributacao_nacional'])) {
            $limpo = preg_replace('/\D/', '', $input['codigo_tributacao_nacional']);
            $input['codigo_tributacao_nacional'] = str_pad($limpo, 6, '0', STR_PAD_LEFT);
        }

        if (!empty($input['codigo_tributacao_municipal'])) {
            $limpo = preg_replace('/\D/', '', $input['codigo_tributacao_municipal']);
            $input['codigo_tributacao_municipal'] = str_pad($limpo, 3, '0', STR_PAD_LEFT);
        }

        $request->replace($input);

        $request->validate([
            'nome' => 'required|string|max:100',
            'codigo_tributacao_nacional' => 'required|string|size:6|regex:/^[0-9]+$/',
            'codigo_tributacao_municipal' => 'required|string|size:3|regex:/^[0-9]+$/',
            'descricao' => 'required|string',
            'valor_unitario' => 'required|numeric|min:0',
            'codigo_interno' => [
                'nullable', 'string',
                Rule::unique('servicos')->where(fn ($q) => $q->where('empresa_id', $empresa->id))
            ],
        ]);

        $data = $request->all();
        // Checkbox HTML não envia nada se desmarcado, garantimos o boolean
        $data['iss_retido'] = $request->has('iss_retido');

        $empresa->servicos()->create($data);

        return redirect()->route('servicos.index')->with('success', 'Serviço cadastrado com sucesso!');
    }

    public function edit(Servico $servico)
    {
        $empresa = $this->getEmpresaAtiva();
        if ($servico->empresa_id !== $empresa->id) abort(403);

        return view('servicos.edit', compact('servico'));
    }

    public function update(Request $request, Servico $servico)
    {
        $empresa = $this->getEmpresaAtiva();
        if ($servico->empresa_id !== $empresa->id) abort(403);

        $input = $request->all();

        // Repete tratamentos do Store
        $parseMoney = fn($v) => is_numeric($v) ? $v : (float) str_replace(['.', ','], ['', '.'], $v ?? 0);
        $keysMoney = ['valor_unitario', 'aliquota_iss', 'aliquota_pis', 'aliquota_cofins', 'aliquota_inss', 'aliquota_ir', 'aliquota_csll'];
        foreach($keysMoney as $key) {
            if (isset($input[$key])) $input[$key] = $parseMoney($input[$key]);
        }

        if (!empty($input['codigo_tributacao_nacional'])) {
            $limpo = preg_replace('/\D/', '', $input['codigo_tributacao_nacional']);
            $input['codigo_tributacao_nacional'] = str_pad($limpo, 6, '0', STR_PAD_LEFT);
        }

        if (!empty($input['codigo_tributacao_municipal'])) {
            $limpo = preg_replace('/\D/', '', $input['codigo_tributacao_municipal']);
            $input['codigo_tributacao_municipal'] = str_pad($limpo, 3, '0', STR_PAD_LEFT);
        }

        $request->replace($input);

        $request->validate([
            'nome' => 'required|string|max:100',
            'codigo_tributacao_nacional' => 'required|string|size:6|regex:/^[0-9]+$/',
            'codigo_tributacao_municipal' => 'required|string|size:3|regex:/^[0-9]+$/',
            'valor_unitario' => 'required|numeric|min:0',
            'codigo_interno' => [
                'nullable', 'string',
                Rule::unique('servicos')->where(fn ($q) => $q->where('empresa_id', $empresa->id))->ignore($servico->id)
            ],
        ]);

        $data = $request->all();
        $data['iss_retido'] = $request->has('iss_retido');

        $servico->update($data);

        return redirect()->route('servicos.index')->with('success', 'Serviço atualizado!');
    }

    public function destroy(Servico $servico)
    {
        $empresa = $this->getEmpresaAtiva();
        if ($servico->empresa_id !== $empresa->id) abort(403);

        $servico->delete();
        return redirect()->route('servicos.index')->with('success', 'Serviço removido.');
    }
}
