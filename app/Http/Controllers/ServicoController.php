<?php

namespace App\Http\Controllers;

use App\Models\Servico;
use App\Models\Empresa;
use App\Models\TributacaoNacional;
use App\Models\IndOp;
use App\Models\ClassTrib;
use App\Models\NbsCode;
use App\Models\NbsCorrelacao;
use App\Services\NfseEmitPayloadBuilder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

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

        $isManaus = $empresa->cod_ibge_mun == '1302603';
        $padraoMunicipal = $isManaus ? '100' : '';

        return view('servicos.create', array_merge(
            compact('isManaus', 'padraoMunicipal'),
            $this->catalogosParaWizard($isManaus)
        ));
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

        $input['codigo_nbs'] = NfseEmitPayloadBuilder::normalizeCnbs($input['codigo_nbs'] ?? null);

        $request->replace($input);

        $request->validate([
            'nome' => 'required|string|max:100',
            'codigo_tributacao_nacional' => 'required|string',
            'codigo_tributacao_municipal' => 'required|string',
            'codigo_nbs' => 'required|digits:9',
            'valor_unitario' => 'required|numeric|min:0',
            'fin_nfse' => 'required|in:0',
            'c_ind_op' => ['required', 'digits:6', Rule::exists('ind_ops', 'codigo')],
            'cst_ibscbs' => 'required|digits:3',
            'c_class_trib' => [
                'required',
                'digits:6',
                Rule::exists('class_tribs', 'c_class_trib')->where(fn ($q) => $q->where('cst', $request->input('cst_ibscbs'))),
            ],
            'codigo_interno' => [
                'nullable', 'string',
                Rule::unique('servicos')->where(fn ($q) => $q->where('empresa_id', $empresa->id))
            ],
        ], [
            'codigo_nbs.required' => 'Informe o código NBS (cNBS) com 9 dígitos. Obrigatório com IBS/CBS.',
            'codigo_nbs.digits' => 'O código NBS deve ter exatamente 9 dígitos (ex: 115011000 ou 1.1501.10.00).',
        ]);

        $empresa->servicos()->create($request->only([
            'nome',
            'codigo_interno',
            'codigo_tributacao_nacional',
            'codigo_tributacao_municipal',
            'codigo_nbs',
            'fin_nfse',
            'c_ind_op',
            'cst_ibscbs',
            'c_class_trib',
            'descricao',
            'valor_unitario',
        ]));

        return redirect()->route('servicos.index')->with('success', 'Serviço cadastrado!');
    }

    public function edit(Servico $servico)
    {
        if ($servico->empresa_id != session('empresa_ativa')) abort(403);

        $empresa = Empresa::find($servico->empresa_id);
        $isManaus = $empresa->cod_ibge_mun == '1302603';
        $padraoMunicipal = $isManaus ? '100' : '';

        return view('servicos.edit', array_merge(
            compact('servico', 'isManaus', 'padraoMunicipal'),
            $this->catalogosParaWizard($isManaus)
        ));
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

        $input['codigo_nbs'] = NfseEmitPayloadBuilder::normalizeCnbs($input['codigo_nbs'] ?? null);

        $request->replace($input);

        $request->validate([
            'nome' => 'required|string|max:100',
            'codigo_tributacao_nacional' => 'required|string',
            'codigo_tributacao_municipal' => 'required|string',
            'codigo_nbs' => 'required|digits:9',
            'valor_unitario' => 'required|numeric|min:0',
            'fin_nfse' => 'required|in:0',
            'c_ind_op' => ['required', 'digits:6', Rule::exists('ind_ops', 'codigo')],
            'cst_ibscbs' => 'required|digits:3',
            'c_class_trib' => [
                'required',
                'digits:6',
                Rule::exists('class_tribs', 'c_class_trib')->where(fn ($q) => $q->where('cst', $request->input('cst_ibscbs'))),
            ],
            'codigo_interno' => [
                'nullable', 'string',
                Rule::unique('servicos')->where(fn ($q) => $q->where('empresa_id', $servico->empresa_id))->ignore($servico->id)
            ],
        ], [
            'codigo_nbs.required' => 'Informe o código NBS (cNBS) com 9 dígitos. Obrigatório com IBS/CBS.',
            'codigo_nbs.digits' => 'O código NBS deve ter exatamente 9 dígitos (ex: 115011000 ou 1.1501.10.00).',
        ]);

        $servico->update($request->only([
            'nome',
            'codigo_interno',
            'codigo_tributacao_nacional',
            'codigo_tributacao_municipal',
            'codigo_nbs',
            'fin_nfse',
            'c_ind_op',
            'cst_ibscbs',
            'c_class_trib',
            'descricao',
            'valor_unitario',
        ]));

        return redirect()->route('servicos.index')->with('success', 'Serviço atualizado!');
    }

    public function destroy(Servico $servico)
    {
        if ($servico->empresa_id != Session::get('empresa_ativa')) abort(403);
        $servico->delete();
        return redirect()->route('servicos.index')->with('success', 'Serviço excluído.');
    }

    // Helpers
    private function catalogosParaWizard(bool $isManaus): array
    {
        $codigosNacionais = TributacaoNacional::select('codigo', 'item_lc116', 'descricao')
            ->orderBy('codigo')
            ->get();

        $indOps = IndOp::query()->where('ativo', true)->orderBy('codigo')->get();
        $classTribs = ClassTrib::query()
            ->where('ativo', true)
            ->orderByDesc('destaque')
            ->orderBy('cst')
            ->orderBy('c_class_trib')
            ->get();

        $nbsCodes = NbsCode::query()->where('ativo', true)->orderBy('codigo')->get(['codigo', 'descricao']);
        $nbsMap = $nbsCodes->mapWithKeys(fn ($n) => [$n->codigo => Str::limit($n->descricao, 80)])->all();

        $correlQuery = NbsCorrelacao::query();
        if ($isManaus) {
            $correlQuery->orderByRaw("CASE WHEN escopo = 'ti_manaus' THEN 0 ELSE 1 END");
        }
        $correlQuery->orderBy('c_trib_nac')->orderBy('codigo_nbs');
        $correlacoesJson = [];
        foreach ($correlQuery->get() as $row) {
            $correlacoesJson[$row->c_trib_nac][] = [
                'codigo' => $row->codigo_nbs,
                'c_ind_op' => $row->c_ind_op,
                'cst' => $row->cst,
                'c_class_trib' => $row->c_class_trib,
                'escopo' => $row->escopo,
            ];
        }

        return compact('codigosNacionais', 'indOps', 'classTribs', 'nbsCodes', 'nbsMap', 'correlacoesJson');
    }

    private function parseMoney($v) {
        return is_numeric($v) ? $v : (float) str_replace(['.', ','], ['', '.'], $v);
    }

    private function formatCodigoNacional($val) {
        // Ex: 10301 -> 1.03.01 (Apenas um exemplo simples, ajuste conforme sua regra de máscara)
        return $val;
    }
}
