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

        // 1. LIMPEZA DE MÁSCARAS (Moeda e Porcentagem)
        $camposMonetarios = [
            'valor_servico',
            'v_tot_trib_fed', 'v_tot_trib_est', 'v_tot_trib_mun',
            'p_tot_trib_fed', 'p_tot_trib_est', 'p_tot_trib_mun',
            'aliquota_iss' // Caso venha do form
        ];

        foreach ($camposMonetarios as $campo) {
            if (!empty($data[$campo])) {
                // Remove ponto de milhar e troca vírgula decimal por ponto
                $data[$campo] = str_replace('.', '', $data[$campo]);
                $data[$campo] = str_replace(',', '.', $data[$campo]);
            } else {
                $data[$campo] = 0;
            }
        }

        // Limpeza de CNPJ
        if(!empty($data['tomador_cnpj'])) {
            $data['tomador_cnpj'] = preg_replace('/\D/', '', $data['tomador_cnpj']);
        }

        // Atualiza o request com os dados limpos para validação
        $request->merge($data);

        // 2. VALIDAÇÃO
        $request->validate([
            'tomador_cnpj'   => 'required|digits_between:11,14',
            'tomador_nome'   => 'required|string|max:255',
            'valor_servico'  => 'required|numeric|min:0.01',
            'emissao'        => 'required|date',
            'descricao'      => 'required|string|min:5',
            'trib_issqn'     => 'required|integer',
            'tp_ret_issqn'   => 'required|integer',
            // Validação condicional do vencimento
            'vencimento'     => 'required_if:gerar_cobranca,1|date|nullable|after_or_equal:today',
        ]);

        DB::beginTransaction();

        try {
            // 3. Lógica de Cliente (Busca ou Cria)
            $clienteId = $request->cliente_id;
            if (!$clienteId) {
                $cliente = Cliente::updateOrCreate(
                    ['empresa_id' => $empresaId, 'cnpj' => $data['tomador_cnpj']],
                    [
                        'razao_social' => $data['tomador_nome'],
                        'email' => $data['tomador_email'] ?? null,
                        // Se tiver endereço no form, adicione aqui
                    ]
                );
                $clienteId = $cliente->id;
            }

            // 4. Criação da Nota (Rascunho)
            $nota = NotaFiscal::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $clienteId,
                'servico_id' => $request->servico_id,
                'status'     => 'criada', // Rascunho inicial
                'ambiente'   => config('app.env') === 'production' ? 'producao' : 'homologacao',

                'tomador_cnpj'   => $data['tomador_cnpj'],
                'tomador_nome'   => $data['tomador_nome'],
                'tomador_email'  => $data['tomador_email'] ?? null,

                'valor_servico'  => $data['valor_servico'],
                'descricao'      => $request->descricao,
                'emissao'        => $request->emissao,

                'trib_issqn'     => $data['trib_issqn'],
                'tp_ret_issqn'   => $data['tp_ret_issqn'],
                // Garante que a alíquota venha do form ou seja 0
                'aliquota_iss'   => $data['aliquota_iss'] ?? 0,

                // Valores de Tributos Aproximados
                'v_tot_trib_fed' => $data['v_tot_trib_fed'] ?? 0,
                'v_tot_trib_est' => $data['v_tot_trib_est'] ?? 0,
                'v_tot_trib_mun' => $data['v_tot_trib_mun'] ?? 0,

                // Porcentagens
                'p_tot_trib_fed' => $data['p_tot_trib_fed'] ?? 0,
                'p_tot_trib_est' => $data['p_tot_trib_est'] ?? 0,
                'p_tot_trib_mun' => $data['p_tot_trib_mun'] ?? 0,
            ]);

            // 5. Integração Financeira (Se marcado)
            if ($request->boolean('gerar_cobranca')) {
                // Chama o Service que deve criar a cobrança com status 'RASCUNHO'
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

        // 1. Carrega Nota com Relacionamentos (Incluindo Cobrança)
        $nota = NotaFiscal::where('empresa_id', $empresaId)
            ->with(['servico', 'cliente', 'cobranca']) // <--- Importante para preencher o financeiro
            ->findOrFail($id);

        // 2. Bloqueio de Segurança
        if (!in_array($nota->status, ['criada', 'erro', 'rascunho'])) {
            return redirect()->route('notas.show', $id)
                ->withErrors(['erro' => 'Esta nota não pode ser editada pois já foi processada ou autorizada.']);
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
        $nota = NotaFiscal::where('empresa_id', $empresaId)->with('cobranca')->findOrFail($id);

        // 1. Bloqueio
        if (!in_array($nota->status, ['criada', 'erro', 'rascunho'])) {
            return back()->withErrors(['erro' => 'Nota bloqueada para edição.']);
        }

        $data = $request->all();

        // 2. Limpeza de Máscaras (Dinheiro e %)
        $camposMonetarios = [
            'valor_servico',
            'aliquota_iss',
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

        // Atualiza request para validação funcionar
        $request->merge($data);

        // 3. Validação
        $request->validate([
            'tomador_cnpj'   => 'required|digits_between:11,14',
            'tomador_nome'   => 'required|string|max:255',
            'valor_servico'  => 'required|numeric|min:0.01',
            'emissao'        => 'required|date',
            'descricao'      => 'required|string|min:5',
            'trib_issqn'     => 'required|integer',
            'tp_ret_issqn'   => 'required|integer',
            // Validação Financeira Condicional
            'vencimento'     => 'required_if:gerar_cobranca,1|date|nullable|after_or_equal:today',
        ]);

        DB::beginTransaction();

        try {
            // 4. Lógica de Cliente (Atualiza ou Cria se mudou o CNPJ)
            $clienteId = $request->cliente_id;

            // Se usuário limpou o select e digitou CNPJ, busca ou cria
            if (!$clienteId && $data['tomador_cnpj']) {
                $cliente = Cliente::updateOrCreate(
                    ['empresa_id' => $empresaId, 'cnpj' => $data['tomador_cnpj']],
                    [
                        'razao_social' => $data['tomador_nome'],
                        'email' => $data['tomador_email'] ?? null,
                    ]
                );
                $clienteId = $cliente->id;
            }

            // 5. Atualiza a Nota
            $nota->update([
                'cliente_id' => $clienteId,
                'servico_id' => $request->servico_id,

                // RESET DE STATUS: Se estava com erro, volta para rascunho para tentar de novo
                'status'     => ($nota->status == 'erro') ? 'criada' : $nota->status,

                'tomador_cnpj'   => $data['tomador_cnpj'],
                'tomador_nome'   => $data['tomador_nome'],
                'tomador_email'  => $data['tomador_email'] ?? null,

                'valor_servico'  => $data['valor_servico'],
                'descricao'      => $request->descricao,
                'emissao'        => $request->emissao,

                'trib_issqn'     => $data['trib_issqn'],
                'tp_ret_issqn'   => $data['tp_ret_issqn'],
                'aliquota_iss'   => $data['aliquota_iss'],

                'v_tot_trib_fed' => $data['v_tot_trib_fed'] ?? 0,
                'v_tot_trib_est' => $data['v_tot_trib_est'] ?? 0,
                'v_tot_trib_mun' => $data['v_tot_trib_mun'] ?? 0,

                'p_tot_trib_fed' => $data['p_tot_trib_fed'] ?? 0,
                'p_tot_trib_est' => $data['p_tot_trib_est'] ?? 0,
                'p_tot_trib_mun' => $data['p_tot_trib_mun'] ?? 0,

                'mensagem_erro' => null // Limpa erro antigo
            ]);

            // 6. Lógica Financeira (Criar, Atualizar ou Remover)
            if ($request->boolean('gerar_cobranca')) {
                // Usuário quer cobrança
                if ($nota->cobranca) {
                    // Já existe: Atualiza valor e vencimento
                    $nota->cobranca->update([
                        'valor' => $nota->valor_servico,
                        'valor_liquido' => $nota->valor_servico,
                        'vencimento' => $request->vencimento,
                        // Se estava cancelada ou erro, volta pra rascunho? Geralmente mantém o status atual ou reseta.
                        // Aqui mantemos a lógica simples: atualiza os dados.
                    ]);
                } else {
                    // Não existe: Cria nova
                    $financeiroService->gerarCobrancaDeNota($nota, $request->vencimento);
                }
            } else {
                // Usuário DESMARCOU a cobrança
                if ($nota->cobranca && $nota->cobranca->status === 'RASCUNHO') {
                    // Só excluímos se ainda for Rascunho (segurança)
                    $nota->cobranca->forceDelete();
                }
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
        // 1. Busca Nota com Relacionamentos Obrigatórios
        $nota = NotaFiscal::where('empresa_id', session('empresa_ativa'))
            ->with(['cliente', 'servico', 'cobranca']) // Traz cobrança também
            ->findOrFail($id);

        // 2. Validações de Status e Serviço
        if (!in_array($nota->status, ['criada', 'erro'])) {
            return back()->withErrors(['erro' => 'Status inválido para emissão.']);
        }

        if (!$nota->servico) {
            return back()->withErrors(['erro' => 'Nenhum serviço vinculado. Impossível obter códigos tributários.']);
        }

        // Pega códigos do cadastro do serviço
        $codMun = $nota->servico->codigo_tributacao_municipal; // Ex: 100
        $codNbs = $nota->servico->codigo_tributacao_nacional;  // Ex: 010601

        if (empty($codMun) || empty($codNbs)) {
            return back()->withErrors(['erro' => 'O serviço selecionado não possui código NBS ou Municipal configurado.']);
        }

        try {
            // Atualiza para evitar duplo clique
            $nota->update(['status' => 'processando']);

            $service = new NfseNacionalService($nota->empresa);

            // 3. Montagem do Payload
            // Define a alíquota: usa a do banco (p_tot_trib_mun) ou padrão 2.00
            $aliqVal = ($nota->p_tot_trib_mun > 0) ? $nota->p_tot_trib_mun : 2.00;

            $dados = [
                'numero' => $nota->id,
                // Lógica de Série: Produção = 1, Teste = 99
                'serie' => config('app.env') === 'production' ? '1' : '99',
                'competencia' => $nota->emissao->format('Y-m-d'),

                'tomador_doc' => $nota->tomador_cnpj,
                'tomador_nome' => $nota->tomador_nome,
                'tomador_email' => $nota->tomador_email,

                // Endereço do Cliente (Fallbacks seguros)
                'tomador_endereco' => $nota->cliente->logradouro ?? 'Endereço não inf.',
                'tomador_numero' => $nota->cliente->numero ?? 'S/N',
                'tomador_bairro' => $nota->cliente->bairro ?? 'Centro',
                'tomador_cep' => $nota->cliente->cep ?? '69000000',
                'tomador_cidade_codigo' => $nota->cliente->cidade_codigo ?? '1302603', // Padrão Manaus se vazio
                'tomador_uf' => $nota->cliente->uf ?? 'AM',
                'tomador_complemento' => $nota->cliente->complemento ?? '',

                'valor' => $nota->valor_servico,
                'discriminacao' => $nota->descricao,
                'tributacao_iss' => $nota->trib_issqn,
                'retencao_iss' => $nota->tp_ret_issqn,

                'servico_nbs' => $codNbs,
                'servico_municipal' => $codMun,

                'aliquota' => $aliqVal,

                'v_tot_trib_fed' => $nota->v_tot_trib_fed,
                'v_tot_trib_est' => $nota->v_tot_trib_est,
                'v_tot_trib_mun' => $nota->v_tot_trib_mun,
            ];

            // 4. Chamada ao Serviço Nacional
            $retorno = $service->emitirNota($dados);

            // Salva XML enviado se disponível (mesmo se der erro depois)
            if (isset($retorno['xml_dps_enviado'])) {
                $nota->xml_enviado = $retorno['xml_dps_enviado'];
                $nota->save();
            }

            // 5. Processamento do Retorno
            if ($retorno['sucesso']) {
                $nota->update([
                    'status' => 'autorizada',
                    'numero_nfse' => $retorno['numero_nota'],
                    'codigo_verificacao' => $retorno['codigo_verificacao'] ?? null,
                    'chave_acesso' => $retorno['chave_acesso'] ?? null,
                    'xml_autorizado' => $retorno['xml_autorizado'],
                    'mensagem_erro' => null // Limpa erro anterior
                ]);

                // === ATIVAÇÃO FINANCEIRA ===
                // Se houver cobrança em RASCUNHO, ativa para PENDING (A Receber)
                if ($nota->cobranca && $nota->cobranca->status === 'RASCUNHO') {
                    $nota->cobranca->update([
                        'status' => 'PENDING',
                        'descricao' => 'Ref. NFS-e Nº ' . $retorno['numero_nota']
                    ]);
                }

                return redirect()->route('notas.show', $nota->id)
                    ->with('success', 'Nota emitida com sucesso!');
            } else {
                // Tratamento de Erros da API
                $msg = $retorno['mensagem'];
                if(isset($retorno['erros']) && is_array($retorno['erros'])) {
                    $msgs = [];
                    foreach($retorno['erros'] as $e) {
                        // Trata se o erro vier como array ou string
                        $detalhe = is_array($e) ? ($e['Descricao'] ?? json_encode($e)) : $e;
                        $msgs[] = $detalhe;
                    }
                    $msg = implode(' | ', $msgs);
                }

                $nota->update([
                    'status' => 'erro',
                    'mensagem_erro' => $msg
                ]);

                return back()->withErrors(['erro' => $msg]);
            }

        } catch (\Exception $e) {
            // Erro Fatal (Exception)
            $nota->update([
                'status' => 'erro',
                'mensagem_erro' => 'Erro interno: ' . $e->getMessage()
            ]);
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
            'serie' => config('app.env') == 'production' ? '1' : '99',
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
