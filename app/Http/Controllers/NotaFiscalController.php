<?php

namespace App\Http\Controllers;
use App\Models\Cobranca;
use App\Services\FinanceiroService;
use App\Models\NotaFiscal;
use App\Models\Servico;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Services\NfseNacionalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class NotaFiscalController extends Controller
{
    /**
     * Listagem de Notas
     */
    public function index(Request $request)
    {
        $query = NotaFiscal::where('empresa_id', session('empresa_ativa'));

        // 1. Filtro de Busca (Texto)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tomador_nome', 'like', "%{$search}%")
                    ->orWhere('tomador_cnpj', 'like', "%{$search}%")
                    ->orWhere('numero_nfse', 'like', "%{$search}%");
            });
        }

        // 2. Filtro de Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. Filtro de Data Início
        if ($request->filled('data_inicio')) {
            $query->whereDate('created_at', '>=', $request->data_inicio);
        }

        // 4. Filtro de Data Fim
        if ($request->filled('data_fim')) {
            $query->whereDate('created_at', '<=', $request->data_fim);
        }

        // Paginação mantendo os filtros na URL
        $notas = $query->latest()->paginate(10)->withQueryString();

        return view('notas.index', compact('notas'));
    }

    /**
     * Formulário de Criação
     */
    public function create()
    {
        $empresaId = session('empresa_ativa');
        $empresa = Empresa::find($empresaId);

        if (!$empresa->certificado || !$empresa->certificado->ativo) {
            return redirect()->route('empresas.configuracao')
                ->withErrors(['erro' => 'Configure seu certificado antes de emitir.']);
        }

        $servicos = Servico::where('empresa_id', $empresaId)->orderBy('nome')->get();
        $clientes = Cliente::where('empresa_id', $empresaId)->orderBy('razao_social')->get();

        return view('notas.criar', compact('servicos', 'clientes'));
    }

    /**
     * Salvar Nota (Com lógica de impostos e atualização de cliente)
     */
    public function store(Request $request, FinanceiroService $financeiroService)
    {
        $empresaId = session('empresa_ativa');
        $data = $request->all();

        // 1. LIMPEZA DE MÁSCARAS DE DINHEIRO E PORCENTAGEM
        $camposMonetarios = [
            'valor_servico',
            'v_tot_trib_fed', 'v_tot_trib_est', 'v_tot_trib_mun',
            'p_tot_trib_fed', 'p_tot_trib_est', 'p_tot_trib_mun' // <-- ADICIONADO PORCENTAGENS
        ];

        foreach ($camposMonetarios as $campo) {
            if (!empty($data[$campo])) {
                $data[$campo] = str_replace('.', '', $data[$campo]);
                $data[$campo] = str_replace(',', '.', $data[$campo]);
            } else {
                $data[$campo] = 0;
            }
        }

        if(!empty($data['tomador_cnpj'])) {
            $data['tomador_cnpj'] = preg_replace('/\D/', '', $data['tomador_cnpj']);
        }

        $request->replace($data);

        // 2. VALIDAÇÃO
        $request->validate([
            'tomador_cnpj'   => 'required|digits_between:11,14',
            'tomador_nome'   => 'required|string|max:255',
            'valor_servico'  => 'required|numeric|min:0.01',
            'emissao'        => 'required|date',
            'descricao'      => 'required|string|min:5',
            'trib_issqn'     => 'required|integer',
            'tp_ret_issqn'   => 'required|integer',
        ]);

        if ($request->has('gerar_cobranca')) {
            $request->validate([
                'vencimento' => 'required|date|after_or_equal:today'
            ]);
        }

        DB::beginTransaction();

        try {
            // 3. Lógica de Cliente
            $clienteId = $request->cliente_id;
            if (!$clienteId) {
                $cliente = Cliente::updateOrCreate(
                    ['empresa_id' => $empresaId, 'documento' => $data['tomador_cnpj']],
                    [
                        'razao_social' => $data['tomador_nome'],
                        'email' => $data['tomador_email'] ?? null,
                        'endereco' => $data['tomador_endereco'] ?? null
                    ]
                );
                $clienteId = $cliente->id;
            }

            // 4. Criação da Nota
            $nota = NotaFiscal::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $clienteId,
                'servico_id' => $request->servico_id,
                'status'     => 'criada',
                'ambiente'   => config('app.env') === 'production' ? 'producao' : 'homologacao',

                'tomador_cnpj'   => $data['tomador_cnpj'],
                'tomador_nome'   => $data['tomador_nome'],
                'tomador_email'  => $data['tomador_email'] ?? null,

                'valor_servico'  => $data['valor_servico'],
                'descricao'      => $request->descricao,
                'emissao'        => $request->emissao,

                'trib_issqn'     => $data['trib_issqn'],
                'tp_ret_issqn'   => $data['tp_ret_issqn'],

                // Valores
                'v_tot_trib_fed' => $data['v_tot_trib_fed'] ?? 0,
                'v_tot_trib_est' => $data['v_tot_trib_est'] ?? 0,
                'v_tot_trib_mun' => $data['v_tot_trib_mun'] ?? 0,

                // Porcentagens (Se seu banco tiver colunas p_tot_..., descomente abaixo)
                'p_tot_trib_fed' => $data['p_tot_trib_fed'] ?? 0,
                'p_tot_trib_est' => $data['p_tot_trib_est'] ?? 0,
                'p_tot_trib_mun' => $data['p_tot_trib_mun'] ?? 0,
            ]);

            if ($request->has('gerar_cobranca')) {
                $financeiroService->gerarCobrancaDeNota(
                    $nota,
                    $request->input('vencimento')
                );
            }

            DB::commit();

            return redirect()->route('notas.show', $nota->id)
                ->with('success', 'Rascunho criado com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['erro' => 'Erro ao salvar: ' . $e->getMessage()]);
        }
    }

    /**
     * Exibe detalhes da nota
     */
    public function show($id)
    {
        $nota = NotaFiscal::where('empresa_id', session('empresa_ativa'))
            ->with('cliente')
            ->findOrFail($id);

        return view('notas.detalhe', compact('nota'));
    }
    /*
    * Tela de Edição (Apenas Rascunho/Erro)
    */
    public function edit($id)
    {
        $empresaId = session('empresa_ativa');
        $nota = NotaFiscal::where('empresa_id', $empresaId)
            ->with(['servico', 'cliente'])
            ->findOrFail($id);

        // Bloqueia edição se já foi emitida
        if (!in_array($nota->status, ['criada', 'erro', 'rascunho'])) {
            return redirect()->route('notas.show', $id)
                ->withErrors(['erro' => 'Esta nota não pode ser editada pois já foi processada.']);
        }

        $servicos = Servico::where('empresa_id', $empresaId)->orderBy('nome')->get();
        $clientes = Cliente::where('empresa_id', $empresaId)->orderBy('razao_social')->get();

        return view('notas.editar', compact('nota', 'servicos', 'clientes'));
    }

    /**
     * Atualiza a Nota no Banco
     */
    public function update(Request $request, $id, FinanceiroService $financeiroService)
    {
        $empresaId = session('empresa_ativa');
        $nota = NotaFiscal::where('empresa_id', $empresaId)->findOrFail($id);

        if (!in_array($nota->status, ['criada', 'erro', 'rascunho'])) {
            return back()->withErrors(['erro' => 'Nota bloqueada para edição.']);
        }

        $data = $request->all();

        // 1. Limpeza de Máscaras
        $camposMonetarios = [
            'valor_servico',
            'v_tot_trib_fed', 'v_tot_trib_est', 'v_tot_trib_mun',
            'p_tot_trib_fed', 'p_tot_trib_est', 'p_tot_trib_mun'
        ];

        foreach ($camposMonetarios as $campo) {
            if (!empty($data[$campo])) {
                $data[$campo] = str_replace('.', '', $data[$campo]);
                $data[$campo] = str_replace(',', '.', $data[$campo]);
            } else {
                $data[$campo] = 0;
            }
        }

        if(!empty($data['tomador_cnpj'])) {
            $data['tomador_cnpj'] = preg_replace('/\D/', '', $data['tomador_cnpj']);
        }

        $request->replace($data);

        // 2. Validação
        $request->validate([
            'tomador_cnpj'   => 'required|digits_between:11,14',
            'tomador_nome'   => 'required|string|max:255',
            'valor_servico'  => 'required|numeric|min:0.01',
            'emissao'        => 'required|date',
            'descricao'      => 'required|string|min:5',
            'trib_issqn'     => 'required|integer',
            'tp_ret_issqn'   => 'required|integer',
        ]);

        DB::beginTransaction();

        try {
            // 3. Atualiza ou Cria Cliente (caso tenha mudado os dados)
            // Se o usuário mudou o CNPJ, pode ser um novo cliente ou atualização do atual.
            // Para simplificar, buscamos pelo CNPJ.
            $clienteId = $request->cliente_id;

            // Se não veio ID mas tem CNPJ, tenta achar ou criar
            if (!$clienteId && $data['tomador_cnpj']) {
                $cliente = Cliente::updateOrCreate(
                    ['empresa_id' => $empresaId, 'documento' => $data['tomador_cnpj']],
                    [
                        'razao_social' => $data['tomador_nome'],
                        'email' => $data['tomador_email'] ?? null,
                        'endereco' => $data['tomador_endereco'] ?? null
                    ]
                );
                $clienteId = $cliente->id;
            }

            // 4. Atualiza a Nota
            $nota->update([
                'cliente_id' => $clienteId,
                'servico_id' => $request->servico_id,
                // Mantém status como rascunho (criada) ao editar, ou erro se estava em erro
                // Se estava em erro e o usuário editou, voltamos para 'criada' para permitir nova tentativa limpa?
                // Geralmente sim:
                'status'     => ($nota->status == 'erro') ? 'criada' : $nota->status,

                'tomador_cnpj'   => $data['tomador_cnpj'],
                'tomador_nome'   => $data['tomador_nome'],
                'tomador_email'  => $data['tomador_email'] ?? null,

                'valor_servico'  => $data['valor_servico'],
                'descricao'      => $request->descricao,
                'emissao'        => $request->emissao,

                'trib_issqn'     => $data['trib_issqn'],
                'tp_ret_issqn'   => $data['tp_ret_issqn'],

                'v_tot_trib_fed' => $data['v_tot_trib_fed'] ?? 0,
                'v_tot_trib_est' => $data['v_tot_trib_est'] ?? 0,
                'v_tot_trib_mun' => $data['v_tot_trib_mun'] ?? 0,

                'p_tot_trib_fed' => $data['p_tot_trib_fed'] ?? 0,
                'p_tot_trib_est' => $data['p_tot_trib_est'] ?? 0,
                'p_tot_trib_mun' => $data['p_tot_trib_mun'] ?? 0,

                // Limpa mensagem de erro antiga ao editar
                'mensagem_erro' => null
            ]);

            if ($request->has('gerar_cobranca')) {
                // Se não existia cobrança, cria. Se existia, atualiza.
                // Para simplificar, vamos assumir atualizar:
                $financeiroService->atualizarCobrancaDaNota($nota, $request->input('vencimento'));
            }

            DB::commit();

            return redirect()->route('notas.show', $nota->id)
                ->with('success', 'Nota atualizada com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['erro' => 'Erro ao atualizar: ' . $e->getMessage()]);
        }
    }

    /**
     * Processa a emissão da nota (Envia para a API Nacional)
     */
    public function emitir($id)
    {
        $nota = NotaFiscal::where('empresa_id', session('empresa_ativa'))
            // AQUI ESTAVA O PROBLEMA: Carregar o relacionamento 'servico' é obrigatório
            ->with(['cliente', 'servico'])
            ->findOrFail($id);

        if (!in_array($nota->status, ['criada', 'erro'])) {
            return back()->withErrors(['erro' => 'Status inválido.']);
        }

        // VALIDAÇÃO PRÉVIA DOS CÓDIGOS DO SERVIÇO
        if (!$nota->servico) {
            return back()->withErrors(['erro' => 'Nenhum serviço vinculado a esta nota. Não é possível obter os códigos de tributação.']);
        }

        // Pega os códigos EXATOS do banco, sem inventar fallback
        $codMun = $nota->servico->codigo_tributacao_municipal; // Ex: 100
        $codNbs = $nota->servico->codigo_tributacao_nacional;  // Ex: 10101

        if (empty($codMun)) {
            return back()->withErrors(['erro' => 'O cadastro do serviço selecionado não possui o Código de Tributação Municipal (Ex: 100).']);
        }

        try {
            $nota->update(['status' => 'processando']);
            $service = new NfseNacionalService($nota->empresa);

            $dados = [
                'numero' => $nota->id,
                'serie' => '1',
                'competencia' => $nota->emissao->format('Y-m-d'),
                'tomador_doc' => $nota->tomador_cnpj,
                'tomador_nome' => $nota->tomador_nome,
                'tomador_email' => $nota->tomador_email,

                'tomador_endereco' => $nota->cliente->logradouro ?? '',
                'tomador_numero' => $nota->cliente->numero ?? 'S/N',
                'tomador_bairro' => $nota->cliente->bairro ?? 'Centro',
                'tomador_cep' => $nota->cliente->cep ?? '',
                'tomador_cidade_codigo' => $nota->cliente->cidade_codigo ?? '1302603',
                'tomador_uf' => $nota->cliente->uf ?? 'AM',

                'valor' => $nota->valor_servico,
                'discriminacao' => $nota->descricao,
                'tributacao_iss' => $nota->trib_issqn,
                'retencao_iss' => $nota->tp_ret_issqn,

                // USA OS CÓDIGOS REAIS CARREGADOS ACIMA
                'servico_nbs' => $codNbs,
                'servico_municipal' => $codMun,

                // Mapeia p_tot_trib_mun (Alíquota ISS) para 'aliquota' que o service espera
                'aliquota' => ($nota->p_tot_trib_mun > 0) ? $nota->p_tot_trib_mun : 2.00,

                'v_tot_trib_fed' => $nota->v_tot_trib_fed,
                'v_tot_trib_est' => $nota->v_tot_trib_est,
                'v_tot_trib_mun' => $nota->v_tot_trib_mun,
            ];

            $retorno = $service->emitirNota($dados);

            if (isset($retorno['xml_dps_enviado'])) {
                $nota->xml_enviado = $retorno['xml_dps_enviado'];
                // Salva apenas o campo xml_enviado por enquanto, sem mudar status
                $nota->save();
            }

            if ($retorno['sucesso']) {
                $nota->update([
                    'status' => 'autorizada',
                    'numero_nfse' => $retorno['numero_nota'],
                    'codigo_verificacao' => $retorno['codigo_verificacao'] ?? null,
                    'chave_acesso' => $retorno['chave_acesso'] ?? null,
                    'xml_autorizado' => $retorno['xml_autorizado']
                ]);
                return redirect()->route('notas.show', $nota->id)->with('success', 'Emitida com sucesso!');
            } else {
                $msg = $retorno['mensagem'];
                if(isset($retorno['erros']) && is_array($retorno['erros'])) {
                    $msgs = [];
                    foreach($retorno['erros'] as $e) {
                        $msgs[] = is_array($e) ? ($e['Descricao'] ?? json_encode($e)) : $e;
                    }
                    $msg = implode(' | ', $msgs);
                }

                $nota->update([
                    'status' => 'erro',
                    'mensagem_erro' => $msg,
                    'xml_enviado' => $retorno['xml_dps_enviado'] ?? $nota->xml_enviado
                ]);
                return back()->withErrors(['erro' => $msg]);
            }
        } catch (\Exception $e) {
            $nota->update(['status' => 'erro', 'mensagem_erro' => $e->getMessage()]);
            return back()->withErrors(['erro' => $e->getMessage()]);
        }
    }

    /**
     * Gera PDF local (Espelho)
     */
    public function imprimir($id)
    {
        $nota = NotaFiscal::where('empresa_id', session('empresa_ativa'))
            ->findOrFail($id);

        $empresaIdSessao = session('empresa_ativa');

        if (!$empresaIdSessao) {
            return redirect()->route('dashboard')->withErrors(['erro' => 'Sessão expirada.']);
        }

        $empresa = $nota->empresa ?? Empresa::find($empresaIdSessao);
        $cliente = $nota->cliente;

        $xmlObject = null;
        $chaveAcesso = null;
        $dataEmissao = $nota->created_at;

        if ($nota->status === 'autorizada' && !empty($nota->xml_autorizado)) {
            try {
                $content = $nota->xml_autorizado;
                if (str_starts_with($content, "\x1f\x8b")) {
                    $content = gzdecode($content);
                } elseif (!str_starts_with(trim($content), '<')) {
                    $decoded = base64_decode($content, true);
                    if ($decoded && str_starts_with($decoded, "\x1f\x8b")) $content = gzdecode($decoded);
                    elseif ($decoded) $content = $decoded;
                }

                $xmlObject = simplexml_load_string(str_replace(['ns1:', 'nfse:'], '', $content));

                if (preg_match('/<chvAcesso>(.*?)<\/chvAcesso>/', $content, $matches)) {
                    $chaveAcesso = $matches[1];
                } elseif (preg_match('/Id="NFS([0-9]{50})"/', $content, $matches)) {
                    $chaveAcesso = $matches[1];
                }

                if (preg_match('/<dhEmi>(.*?)<\/dhEmi>/', $content, $matches)) {
                    $dataEmissao = new \DateTime($matches[1]);
                }
            } catch (\Exception $e) { }
        }

        $qrBase64 = null;
        $fallbackImage = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        if (!empty($chaveAcesso)) {
            try {
                $urlConsulta = "https://www.nfse.gov.br/ConsultaPublica/";
                $qrLink = "{$urlConsulta}?tpc=1&chave={$chaveAcesso}";
                $apiUrl = "https://quickchart.io/qr?text=" . urlencode($qrLink) . "&size=300&ecLevel=M&margin=1";
                $response = Http::withOptions(['verify' => false])->timeout(5)->get($apiUrl);
                if ($response->successful()) {
                    $qrBase64 = 'data:image/png;base64,' . base64_encode($response->body());
                } else {
                    $qrBase64 = $fallbackImage;
                }
            } catch (\Exception $e) {
                $qrBase64 = $fallbackImage;
            }
        } else {
            $chaveAcesso = 'PENDENTE';
            $qrBase64 = $fallbackImage;
        }

        $emitente = (object) [
            'razao_social' => $empresa->razao_social,
            'cnpj' => $empresa->cnpj,
            'inscricao_municipal' => $empresa->inscricao_municipal,
            'endereco' => $empresa->logradouro,
            'numero' => $empresa->numero,
            'complemento' => $empresa->complemento,
            'bairro' => $empresa->bairro,
            'cidade' => 'Manaus',
            'uf' => $empresa->uf,
            'cep' => $empresa->cep,
            'telefone' => $empresa->telefone,
            'email' => $empresa->email,
            'regime_tributario' => 'Simples Nacional'
        ];

        $tomador = (object) [
            'razao_social'        => $cliente?->razao_social ?? $nota->tomador_nome,
            'documento'           => $cliente?->documento ?? $nota->tomador_cnpj,
            'inscricao_municipal' => $cliente?->inscricao_municipal ?? '',
            'endereco'            => $cliente?->logradouro ?? '',
            'numero'              => $cliente?->numero ?? '',
            'complemento'         => $cliente?->complemento ?? '',
            'bairro'              => $cliente?->bairro ?? '',
            'cidade'              => $cliente?->cidade_codigo ?? '',
            'uf'                  => $cliente?->uf ?? '',
            'cep'                 => $cliente?->cep ?? '',
            'email'               => $cliente?->email ?? ($nota->tomador_email ?? ''),
            'telefone'            => $cliente?->telefone ?? ''
        ];

        $dadosNota = (object) [
            'id' => $nota->id,
            'numero' => $nota->numero_nfse,
            'serie' => '1',
            'chave' => $chaveAcesso,
            'data_emissao' => $dataEmissao,
            'codigo_verificacao' => $nota->codigo_verificacao,
            'competencia' => $dataEmissao,
            'local_prestacao' => 'Manaus/AM',
            'status' => $nota->status,
        ];

        $dadosServico = (object) [
            'discriminacao' => $nota->descricao,
            'codigo_nbs' => '01.05.01',
            'codigo_cnae' => '',
            'item_lista_servico' => $nota->servico?->codigo_tributacao_municipal ?? '',
            'valor_servico' => (float)$nota->valor_servico,
            'valor_deducoes' => 0.00,
            'iss_retido' => $nota->tp_ret_issqn == 2 ? 1 : 2,
            'valor_iss' => 0.00,
            'valor_liquido' => (float)$nota->valor_servico,
            'aliquota_iss' => 0.00
        ];

        $outras_informacoes = "Documento emitido por ME ou EPP optante pelo Simples Nacional.";

        $pdf = Pdf::loadView('pdf.danfse', [
            'emitente' => $emitente,
            'tomador'  => $tomador,
            'nota'     => $dadosNota,
            'servico'  => $dadosServico,
            'outras_informacoes' => $outras_informacoes,
            'xml'      => $xmlObject,
            'chaveAcesso' => $chaveAcesso,
            'qrCodeBase64' => $qrBase64
        ]);

        return $pdf->stream("NFSe-{$nota->numero_nfse}.pdf");
    }

    /**
     * Download do PDF Oficial da API Nacional
     */
    public function baixarDanfseOficial($id)
    {
        $nota = NotaFiscal::where('empresa_id', session('empresa_ativa'))
            ->findOrFail($id);

        if ($nota->status !== 'autorizada' || empty($nota->xml_autorizado)) {
            return back()->withErrors(['erro' => 'Esta nota não possui XML autorizado para gerar o DANFSe.']);
        }

        $chaveAcesso = $nota->chave_acesso ?? null;

        if (empty($chaveAcesso)) {
            $content = $nota->xml_autorizado;
            if (str_starts_with($content, "\x1f\x8b")) {
                $content = gzdecode($content);
            } elseif (!str_starts_with(trim($content), '<')) {
                $decoded = base64_decode($content, true);
                if ($decoded && str_starts_with($decoded, "\x1f\x8b")) $content = gzdecode($decoded);
                elseif ($decoded) $content = $decoded;
            }

            if (preg_match('/<chvAcesso>(.*?)<\/chvAcesso>/', $content, $matches)) {
                $chaveAcesso = $matches[1];
            } elseif (preg_match('/Id="NFS([0-9]{50})"/', $content, $matches)) {
                $chaveAcesso = $matches[1];
            }
        }

        if (empty($chaveAcesso)) {
            return back()->withErrors(['erro' => 'Não foi possível identificar a Chave de Acesso desta nota.']);
        }

        try {
            $service = new NfseNacionalService($nota->empresa);
            $pdfContent = $service->downloadDanfse($chaveAcesso);

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="DANFSe_Oficial_' . $nota->numero_nfse . '.pdf"');

        } catch (\Exception $e) {
            return back()->withErrors(['erro' => 'Erro ao baixar do governo: ' . $e->getMessage()]);
        }
    }
}
