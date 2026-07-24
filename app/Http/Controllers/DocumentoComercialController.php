<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\DocumentoComercial;
use App\Models\Empresa;
use App\Models\FormaPagamento;
use App\Models\Fornecedor;
use App\Models\Produto;
use App\Models\Servico;
use App\Services\Erp\DocumentoOrchestrator;
use App\Services\Erp\FormaPagamentoService;
use App\Services\Erp\ModuloDashboardService;
use App\Services\Erp\NfeXmlImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Throwable;

class DocumentoComercialController extends Controller
{
    public function dashboard(Request $request, ModuloDashboardService $dashboards)
    {
        $empresaId = (int) session('empresa_ativa');
        [$inicio, $fim] = $dashboards->resolvePeriod($request);
        $stats = $dashboards->documentos($empresaId, $inicio, $fim);

        return view('documentos.dashboard', compact('stats', 'inicio', 'fim'));
    }

    public function index(Request $request)
    {
        $empresaId = session('empresa_ativa');
        $query = DocumentoComercial::query()
            ->where('empresa_id', $empresaId)
            ->with(['cliente', 'fornecedor', 'formaPagamentoRel']);

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('canal')) {
            $query->where('canal_fiscal', $request->canal);
        }
        if ($request->filled('de')) {
            $query->whereDate('created_at', '>=', $request->de);
        }
        if ($request->filled('ate')) {
            $query->whereDate('created_at', '<=', $request->ate);
        }

        $resumoQuery = clone $query;
        $porFormaRaw = (clone $resumoQuery)
            ->selectRaw('forma_pagamento_id, forma_pagamento, COUNT(*) as qtd, SUM(valor_total) as total')
            ->groupBy('forma_pagamento_id', 'forma_pagamento')
            ->get();

        $nomesForma = FormaPagamento::query()
            ->whereIn('id', $porFormaRaw->pluck('forma_pagamento_id')->filter()->unique())
            ->pluck('nome', 'id');

        $resumo = [
            'qtd' => (clone $resumoQuery)->count(),
            'total' => (float) (clone $resumoQuery)->sum('valor_total'),
            'por_forma' => $porFormaRaw->map(function ($linha) use ($nomesForma) {
                return (object) [
                    'nome' => $nomesForma[$linha->forma_pagamento_id] ?? ($linha->forma_pagamento ?: '—'),
                    'qtd' => (int) $linha->qtd,
                    'total' => (float) $linha->total,
                ];
            }),
        ];

        $documentos = $query->latest()->paginate(20)->withQueryString();

        return view('documentos.index', compact('documentos', 'resumo'));
    }

    public function create(Request $request, FormaPagamentoService $formasService)
    {
        $tipo = $request->get('tipo', DocumentoComercial::TIPO_VENDA);
        $canal = $request->get('canal', DocumentoComercial::CANAL_NFCE);

        $empresa = $this->empresaAtiva();
        $formasService->garantirDefaults($empresa);

        $clientes = Cliente::where('empresa_id', $empresa->id)->orderBy('razao_social')->get();
        $fornecedores = Fornecedor::where('empresa_id', $empresa->id)->orderBy('razao_social')->get();
        $produtos = Produto::where('empresa_id', $empresa->id)->where('ativo', true)->orderBy('descricao')->get();
        $servicos = Servico::where('empresa_id', $empresa->id)->orderBy('nome')->get();
        $formas = FormaPagamento::where('empresa_id', $empresa->id)->where('ativo', true)->orderBy('nome')->get();

        return view('documentos.create', compact('tipo', 'canal', 'clientes', 'fornecedores', 'produtos', 'servicos', 'formas'));
    }

    public function store(Request $request, DocumentoOrchestrator $orchestrator)
    {
        $empresa = $this->empresaAtiva();

        $validated = $request->validate([
            'tipo' => 'required|in:venda,compra',
            'canal_fiscal' => 'required|in:nfse,nfce,nfe_entrada',
            'cliente_id' => 'nullable|integer',
            'fornecedor_id' => 'nullable|integer',
            'forma_pagamento_id' => 'nullable|integer',
            'forma_pagamento' => 'nullable|string|max:10',
            'vencimento' => 'nullable|date',
            'pago_avista' => 'nullable|boolean',
            'observacoes' => 'nullable|string',
            'itens' => 'required|array|min:1',
            'itens.*.produto_id' => 'nullable|integer',
            'itens.*.servico_id' => 'nullable|integer',
            'itens.*.descricao' => 'required|string|max:255',
            'itens.*.quantidade' => 'required|numeric|min:0.001',
            'itens.*.valor_unitario' => 'required|numeric|min:0',
            'itens.*.ncm' => 'nullable|string|max:8',
            'itens.*.cfop' => 'nullable|string|max:4',
            'itens.*.csosn' => 'nullable|string|max:3',
            'itens.*.unidade' => 'nullable|string|max:6',
        ]);

        try {
            $doc = $orchestrator->criarRascunho($empresa, [
                ...$validated,
                'pago_avista' => $request->boolean('pago_avista'),
            ]);
        } catch (Throwable $e) {
            return back()->withErrors(['erro' => $e->getMessage()])->withInput();
        }

        return redirect()->route('documentos.show', $doc->id)
            ->with('success', 'Documento criado como rascunho.');
    }

    public function show(int $id)
    {
        $doc = $this->findDoc($id);
        $doc->load([
            'itens.produto',
            'itens.servico',
            'cliente',
            'fornecedor',
            'notaFiscal',
            'nfce',
            'lancamentosFinanceiros',
            'movimentacoesEstoque.produto',
        ]);

        return view('documentos.show', ['documento' => $doc]);
    }

    public function confirmar(int $id, DocumentoOrchestrator $orchestrator)
    {
        $doc = $this->findDoc($id);

        try {
            $doc = $orchestrator->confirmar($doc);
        } catch (Throwable $e) {
            return back()->withErrors(['erro' => $e->getMessage()]);
        }

        return redirect()->route('documentos.show', $doc->id)
            ->with('success', 'Documento confirmado.');
    }

    public function importarXmlForm()
    {
        return view('documentos.importar-xml');
    }

    public function importarXmlPreview(Request $request, NfeXmlImporter $importer)
    {
        $request->validate([
            'xml' => 'required_without:xml_file|nullable|string',
            'xml_file' => 'required_without:xml|nullable|file|mimes:xml,txt|max:5120',
        ]);

        $xml = $request->input('xml');
        if ($request->hasFile('xml_file')) {
            $xml = file_get_contents($request->file('xml_file')->getRealPath());
        }

        try {
            $preview = $importer->preview((string) $xml);
        } catch (Throwable $e) {
            return back()->withErrors(['xml' => $e->getMessage()])->withInput();
        }

        Session::put('nfe_xml_preview', $preview);

        return view('documentos.importar-xml-preview', compact('preview'));
    }

    public function importarXmlConfirmar(Request $request, NfeXmlImporter $importer)
    {
        $preview = Session::get('nfe_xml_preview');
        if (! $preview || empty($preview['xml'])) {
            return redirect()->route('documentos.importar_xml')
                ->withErrors(['xml' => 'Preview expirado. Envie o XML novamente.']);
        }

        $confirmar = $request->boolean('confirmar', true);

        try {
            $doc = $importer->importar($this->empresaAtiva(), $preview['xml'], $confirmar);
            Session::forget('nfe_xml_preview');
        } catch (Throwable $e) {
            return redirect()->route('documentos.importar_xml')
                ->withErrors(['xml' => $e->getMessage()]);
        }

        return redirect()->route('documentos.show', $doc->id)
            ->with('success', $confirmar ? 'NF-e importada e confirmada.' : 'NF-e importada como rascunho.');
    }

    private function findDoc(int $id): DocumentoComercial
    {
        return DocumentoComercial::query()
            ->where('empresa_id', session('empresa_ativa'))
            ->findOrFail($id);
    }

    private function empresaAtiva(): Empresa
    {
        $empresa = Empresa::find(session('empresa_ativa'));
        if (! $empresa) {
            abort(403, 'Selecione uma empresa.');
        }

        return $empresa;
    }
}
