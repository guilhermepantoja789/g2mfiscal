<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotaFiscalRequest;
use App\Models\ClassTrib;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\IndOp;
use App\Models\NotaFiscal;
use App\Models\Servico;
use App\Services\FinanceiroService;
use App\Services\Fiscal\NfseDanfseService;
use App\Services\NfseNacionalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            $query->where(function ($q) use ($search) {
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

        if (! $empresa->certificado || ! $empresa->certificado->ativo) {
            return redirect()->route('empresas.configuracao')
                ->withErrors(['erro' => 'Configure seu certificado antes de emitir.']);
        }

        $servicos = Servico::where('empresa_id', $empresaId)->orderBy('nome')->get();
        $clientes = Cliente::where('empresa_id', $empresaId)->orderBy('razao_social')->get();
        $indOps = IndOp::query()->where('ativo', true)->orderBy('codigo')->get();
        $classTribs = ClassTrib::query()
            ->where('ativo', true)
            ->orderByDesc('destaque')
            ->orderBy('cst')
            ->orderBy('c_class_trib')
            ->get();

        return view('notas.criar', compact('servicos', 'clientes', 'indOps', 'classTribs'));
    }

    /**
     * Salvar Nota (Com lógica de impostos e atualização de cliente)
     */
    public function store(NotaFiscalRequest $request, FinanceiroService $financeiroService)
    {
        $empresaId = session('empresa_ativa');
        $data = $request->all();

        DB::beginTransaction();

        try {
            $clienteId = $this->syncClienteFromTomador($empresaId, $request->cliente_id, $data);

            // 4. Criação da Nota (Rascunho)
            $nota = NotaFiscal::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $clienteId,
                'servico_id' => $request->servico_id,
                'status' => 'criada', // Rascunho inicial
                'ambiente' => \App\Services\NfseAmbiente::label(),

                'tomador_cnpj' => $data['tomador_cnpj'],
                'tomador_nome' => $data['tomador_nome'],
                'tomador_email' => $data['tomador_email'] ?? null,

                'valor_servico' => $data['valor_servico'],
                'descricao' => $request->descricao,
                'emissao' => $request->emissao,

                'trib_issqn' => $data['trib_issqn'],
                'tp_ret_issqn' => $data['tp_ret_issqn'],
                // Garante que a alíquota venha do form ou seja 0
                'aliquota_iss' => $data['aliquota_iss'] ?? 0,
                ...\App\Models\NotaFiscal::calcularValores(
                    (float) $data['valor_servico'],
                    $data['aliquota_iss'] ?? 0,
                    $data['p_tot_trib_mun'] ?? 0,
                    $data['tp_ret_issqn'],
                ),

                'fin_nfse' => $data['fin_nfse'] ?? null,
                'ind_final' => $data['ind_final'] ?? null,
                'ind_dest' => $data['ind_dest'] ?? '0',
                'c_ind_op' => $data['c_ind_op'] ?? null,
                'cst_ibscbs' => $data['cst_ibscbs'] ?? null,
                'c_class_trib' => $data['c_class_trib'] ?? null,

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

            return back()->withInput()->withErrors(['erro' => 'Erro ao salvar: '.$e->getMessage()]);
        }
    }

    /**
     * Exibe detalhes da nota
     */
    public function show($id)
    {
        $nota = NotaFiscal::where('empresa_id', session('empresa_ativa'))
            ->with(['cliente', 'documentoComercial'])
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
        if (! in_array($nota->status, ['criada', 'erro', 'rascunho'])) {
            return redirect()->route('notas.show', $id)
                ->withErrors(['erro' => 'Esta nota não pode ser editada pois já foi processada ou autorizada.']);
        }

        $servicos = Servico::where('empresa_id', $empresaId)->orderBy('nome')->get();
        $clientes = Cliente::where('empresa_id', $empresaId)->orderBy('razao_social')->get();
        $indOps = IndOp::query()->where('ativo', true)->orderBy('codigo')->get();
        $classTribs = ClassTrib::query()
            ->where('ativo', true)
            ->orderByDesc('destaque')
            ->orderBy('cst')
            ->orderBy('c_class_trib')
            ->get();

        return view('notas.editar', compact('nota', 'servicos', 'clientes', 'indOps', 'classTribs'));
    }

    /**
     * Atualiza a Nota no Banco
     */
    public function update(NotaFiscalRequest $request, $id, FinanceiroService $financeiroService)
    {
        $empresaId = session('empresa_ativa');
        $nota = NotaFiscal::where('empresa_id', $empresaId)->with('cobranca')->findOrFail($id);

        // 1. Bloqueio
        if (! in_array($nota->status, ['criada', 'erro', 'rascunho'])) {
            return back()->withErrors(['erro' => 'Nota bloqueada para edição.']);
        }

        $data = $request->all();

        DB::beginTransaction();

        try {
            $clienteId = $this->syncClienteFromTomador($empresaId, $request->cliente_id, $data);

            // 5. Atualiza a Nota
            $nota->update([
                'cliente_id' => $clienteId,
                'servico_id' => $request->servico_id,

                // RESET DE STATUS: Se estava com erro, volta para rascunho para tentar de novo
                'status' => ($nota->status == 'erro') ? 'criada' : $nota->status,

                'tomador_cnpj' => $data['tomador_cnpj'],
                'tomador_nome' => $data['tomador_nome'],
                'tomador_email' => $data['tomador_email'] ?? null,

                'valor_servico' => $data['valor_servico'],
                'descricao' => $request->descricao,
                'emissao' => $request->emissao,

                'trib_issqn' => $data['trib_issqn'],
                'tp_ret_issqn' => $data['tp_ret_issqn'],
                'aliquota_iss' => $data['aliquota_iss'],
                ...\App\Models\NotaFiscal::calcularValores(
                    (float) $data['valor_servico'],
                    $data['aliquota_iss'] ?? 0,
                    $data['p_tot_trib_mun'] ?? 0,
                    $data['tp_ret_issqn'],
                ),

                'fin_nfse' => $data['fin_nfse'] ?? null,
                'ind_final' => $data['ind_final'] ?? null,
                'ind_dest' => $data['ind_dest'] ?? '0',
                'c_ind_op' => $data['c_ind_op'] ?? null,
                'cst_ibscbs' => $data['cst_ibscbs'] ?? null,
                'c_class_trib' => $data['c_class_trib'] ?? null,

                'v_tot_trib_fed' => $data['v_tot_trib_fed'] ?? 0,
                'v_tot_trib_est' => $data['v_tot_trib_est'] ?? 0,
                'v_tot_trib_mun' => $data['v_tot_trib_mun'] ?? 0,

                'p_tot_trib_fed' => $data['p_tot_trib_fed'] ?? 0,
                'p_tot_trib_est' => $data['p_tot_trib_est'] ?? 0,
                'p_tot_trib_mun' => $data['p_tot_trib_mun'] ?? 0,

                'mensagem_erro' => null, // Limpa erro antigo
            ]);

            // 6. Lógica Financeira (Criar, Atualizar ou Remover)
            if ($request->boolean('gerar_cobranca')) {
                // Usuário quer cobrança
                if ($nota->cobranca) {
                    // Já existe: Atualiza valor e vencimento
                    $nota->cobranca->update([
                        'valor' => $nota->calcularValorLiquido(),
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

            return back()->withInput()->withErrors(['erro' => 'Erro ao atualizar: '.$e->getMessage()]);
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
        if (! in_array($nota->status, ['criada', 'erro'])) {
            return back()->withErrors(['erro' => 'Status inválido para emissão.']);
        }

        if (! $nota->servico) {
            return back()->withErrors(['erro' => 'Nenhum serviço vinculado. Impossível obter códigos tributários.']);
        }

        // Pega códigos do cadastro do serviço
        $codMun = $nota->servico->codigo_tributacao_municipal; // Ex: 100
        $codTribNac = $nota->servico->codigo_tributacao_nacional; // Ex: 010601 (cTribNac)
        $codCnbs = \App\Services\NfseEmitPayloadBuilder::normalizeCnbs($nota->servico->codigo_nbs);

        if (empty($codMun) || empty($codTribNac)) {
            return back()->withErrors(['erro' => 'O serviço selecionado não possui código de tributação nacional ou municipal configurado.']);
        }

        if ($codCnbs === null) {
            return back()->withErrors([
                'erro' => 'O serviço selecionado não possui código NBS (cNBS) válido. Com IBS/CBS é obrigatório informar o item da NBS (9 dígitos). Atualize o cadastro do serviço.',
            ]);
        }

        try {
            \App\Services\NfseEmitPayloadBuilder::assertClienteCompleto($nota->cliente);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['erro' => $e->getMessage()]);
        }

        try {
            // Atualiza para evitar duplo clique e indica que está na fila.
            // Em reemissão após erro, descarta DPS anterior e reserva novo nDPS
            // (a SEFIN rejeita reenvio do mesmo série+número após aceite).
            $payload = [
                'status' => 'processando',
                'ambiente' => \App\Services\NfseAmbiente::label(),
                'mensagem_erro' => null,
            ];

            if ($nota->status === 'erro') {
                $payload['xml_enviado'] = null;
                $payload['numero_dps'] = \App\Services\NfseDpsNumero::reservar($nota->empresa);
            } elseif (blank($nota->numero_dps)) {
                $payload['numero_dps'] = \App\Services\NfseDpsNumero::reservar($nota->empresa);
            }

            $nota->update($payload);

            \App\Jobs\EmitirNotaFiscalJob::dispatch($nota);

            return redirect()->route('notas.show', $nota->id)
                ->with('success', 'Nota enviada para processamento. Você será notificado assim que for concluída.');

        } catch (\Exception $e) {
            // Erro Fatal (Exception) ao enfileirar
            $nota->update([
                'status' => 'erro',
                'mensagem_erro' => 'Erro interno ao enfileirar: '.$e->getMessage(),
            ]);

            return back()->withErrors(['erro' => $e->getMessage()]);
        }
    }

    /**
     * Gera PDF local (DANFSe a partir do XML autorizado, ou espelho se rascunho).
     */
    public function imprimir($id, NfseDanfseService $danfse)
    {
        $nota = NotaFiscal::where('empresa_id', session('empresa_ativa'))
            ->with(['empresa', 'cliente', 'servico'])
            ->findOrFail($id);

        return $danfse->stream($nota);
    }

    /**
     * Tenta o PDF da ADN. Em falha, volta à tela da nota com aviso e ações — nunca entrega o espelho como oficial.
     */
    public function baixarDanfseOficial($id, NfseDanfseService $danfse)
    {
        $nota = NotaFiscal::where('empresa_id', session('empresa_ativa'))
            ->with('empresa')
            ->findOrFail($id);

        if ($nota->status !== 'autorizada' || empty($nota->xml_autorizado)) {
            return redirect()->route('notas.show', $id)
                ->withErrors(['download' => 'Esta nota não possui XML autorizado para baixar a DANFSe da ADN.']);
        }

        $chaveAcesso = $danfse->resolverChaveAcesso($nota);

        if (empty($chaveAcesso)) {
            return redirect()->route('notas.show', $id)
                ->withErrors(['download' => 'Não foi possível identificar a chave de acesso desta nota.']);
        }

        try {
            $service = $this->nfseNacionalService($nota->empresa);
            $pdfContent = $service->downloadDanfse($chaveAcesso);

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="DANFSe_ADN_'.$nota->numero_nfse.'.pdf"');
        } catch (\Throwable $e) {
            $http = $this->httpStatusFromDanfseException($e);
            $codigo = $http ? 'HTTP '.$http : 'erro de conexão';

            Log::warning('DANFSe ADN indisponível', [
                'nota_id' => $nota->id,
                'chave' => $chaveAcesso,
                'http' => $http,
                'erro' => $e->getMessage(),
            ]);

            return redirect()->route('notas.show', $id)->withErrors([
                'download' => 'Serviço da ADN indisponível ('.$codigo.'): '.$e->getMessage(),
            ]);
        }
    }

    private function nfseNacionalService(Empresa $empresa): NfseNacionalService
    {
        if (app()->bound(NfseNacionalService::class)) {
            return app(NfseNacionalService::class);
        }

        return new NfseNacionalService($empresa);
    }

    private function httpStatusFromDanfseException(\Throwable $e): ?int
    {
        if (preg_match('/Falha ao baixar DANFSe:\s*(\d{3})/', $e->getMessage(), $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function destroy($id)
    {
        $nota = NotaFiscal::where('empresa_id', session('empresa_ativa'))->findOrFail($id);

        if (! in_array($nota->status, ['criada', 'rascunho', 'erro'])) {
            return back()->withErrors(['erro' => 'Apenas rascunhos ou notas com erro podem ser excluídas.']);
        }

        // Se houver cobrança em rascunho atrelada
        if ($nota->cobranca && $nota->cobranca->status === 'RASCUNHO') {
            $nota->cobranca->forceDelete();
        }

        $nota->delete();

        return redirect()->route('notas.index')->with('success', 'Nota apagada com sucesso!');
    }

    /**
     * Cria/atualiza o cliente com os dados do tomador do formulário (endereço incluso).
     * A emissão NFS-e lê endereço de Cliente, não dos campos avulsos da nota.
     */
    private function syncClienteFromTomador(int|string $empresaId, mixed $clienteId, array $data): ?int
    {
        $cnpj = preg_replace('/\D/', '', (string) ($data['tomador_cnpj'] ?? ''));
        if ($cnpj === '') {
            return $clienteId ? (int) $clienteId : null;
        }

        $payload = [
            'razao_social' => $data['tomador_nome'] ?? null,
            'email' => $data['tomador_email'] ?? null,
            'inscricao_municipal' => $data['tomador_im'] ?? null,
            'telefone' => $data['tomador_telefone'] ?? null,
            'cep' => $data['tomador_cep'] ?? null,
            'logradouro' => $data['tomador_endereco'] ?? null,
            'numero' => $data['tomador_numero'] ?? null,
            'complemento' => $data['tomador_complemento'] ?? null,
            'bairro' => $data['tomador_bairro'] ?? null,
            'cidade_codigo' => $data['tomador_cidade'] ?? null,
            'uf' => $data['tomador_uf'] ?? null,
        ];

        if ($clienteId) {
            $cliente = Cliente::where('empresa_id', $empresaId)->find($clienteId);
            if ($cliente) {
                $cliente->fill($payload);
                if (empty($cliente->cnpj)) {
                    $cliente->cnpj = $cnpj;
                }
                $cliente->save();

                return (int) $cliente->id;
            }
        }

        $cliente = Cliente::updateOrCreate(
            ['empresa_id' => $empresaId, 'cnpj' => $cnpj],
            $payload
        );

        return (int) $cliente->id;
    }
}
