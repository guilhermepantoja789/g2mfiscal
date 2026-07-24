<?php

namespace App\Services\Erp;

use App\Jobs\EmitirNfceJob;
use App\Jobs\EmitirNotaFiscalJob;
use App\Models\Cliente;
use App\Models\DocumentoComercial;
use App\Models\DocumentoItem;
use App\Models\Empresa;
use App\Models\FormaPagamento;
use App\Models\Nfce;
use App\Models\NotaFiscal;
use App\Models\Produto;
use App\Models\Servico;
use App\Services\Fiscal\RawNativeNfceIssuer;
use App\Services\NfseAmbiente;
use App\Services\NfseDpsNumero;
use App\Services\NfseIbscbsBuilder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class DocumentoOrchestrator
{
    /** @var array<int, array{dest_doc: ?string, dest_nome: ?string}> */
    private array $nfceDestByDoc = [];

    public function __construct(
        private EstoqueService $estoque,
        private LancamentoFinanceiroService $financeiro,
        private RawNativeNfceIssuer $nfceIssuer,
    ) {}

    /**
     * @param  array{
     *   tipo: string,
     *   canal_fiscal: string,
     *   cliente_id?: int|null,
     *   fornecedor_id?: int|null,
     *   forma_pagamento?: string|null,
     *   forma_pagamento_id?: int|null,
     *   vencimento?: string|null,
     *   pago_avista?: bool,
     *   observacoes?: string|null,
     *   chave_nfe?: string|null,
     *   xml_nfe?: string|null,
     *   numero_nfe?: string|null,
     *   serie_nfe?: string|null,
     *   data_competencia?: string|null,
     *   dest_doc?: string|null,
     *   dest_nome?: string|null,
     *   itens: list<array<string, mixed>>
     * }  $dados
     */
    public function criarRascunho(Empresa $empresa, array $dados): DocumentoComercial
    {
        return DB::transaction(function () use ($empresa, $dados) {
            $this->validarCanalItens($dados['canal_fiscal'], $dados['itens'] ?? []);
            $pagamento = $this->resolverPagamento($empresa, $dados);

            $doc = DocumentoComercial::create([
                'empresa_id' => $empresa->id,
                'tipo' => $dados['tipo'],
                'canal_fiscal' => $dados['canal_fiscal'],
                'status' => DocumentoComercial::STATUS_RASCUNHO,
                'cliente_id' => $dados['cliente_id'] ?? null,
                'fornecedor_id' => $dados['fornecedor_id'] ?? null,
                'forma_pagamento' => $pagamento['codigo'],
                'forma_pagamento_id' => $pagamento['id'],
                'vencimento' => $pagamento['vencimento'] ?? ($dados['vencimento'] ?? null),
                'pago_avista' => $pagamento['pago_avista'],
                'observacoes' => $dados['observacoes'] ?? null,
                'chave_nfe' => $dados['chave_nfe'] ?? null,
                'xml_nfe' => $dados['xml_nfe'] ?? null,
                'numero_nfe' => $dados['numero_nfe'] ?? null,
                'serie_nfe' => $dados['serie_nfe'] ?? null,
                'data_competencia' => $dados['data_competencia'] ?? null,
                'valor_total' => 0,
            ]);

            $total = $this->sincronizarItens($doc, $dados['itens']);
            $doc->update(['valor_total' => $total]);

            $this->rememberNfceDest($doc->id, $dados);

            return $doc->fresh(['itens', 'cliente', 'fornecedor', 'formaPagamentoRel']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $itens
     */
    public function atualizarRascunho(DocumentoComercial $documento, array $dados): DocumentoComercial
    {
        if (! $documento->isRascunho()) {
            throw new RuntimeException('Somente rascunhos podem ser editados.');
        }

        return DB::transaction(function () use ($documento, $dados) {
            if (isset($dados['itens'])) {
                $this->validarCanalItens($documento->canal_fiscal, $dados['itens']);
            }

            $pagamento = null;
            if (array_key_exists('forma_pagamento_id', $dados) || array_key_exists('forma_pagamento', $dados)) {
                $pagamento = $this->resolverPagamento($documento->empresa, $dados);
            }

            $documento->update([
                'cliente_id' => $dados['cliente_id'] ?? $documento->cliente_id,
                'fornecedor_id' => $dados['fornecedor_id'] ?? $documento->fornecedor_id,
                'forma_pagamento' => $pagamento !== null
                    ? $pagamento['codigo']
                    : ($dados['forma_pagamento'] ?? $documento->forma_pagamento),
                'forma_pagamento_id' => $pagamento !== null
                    ? $pagamento['id']
                    : (array_key_exists('forma_pagamento_id', $dados)
                        ? $dados['forma_pagamento_id']
                        : $documento->forma_pagamento_id),
                'vencimento' => $pagamento !== null
                    ? $pagamento['vencimento']
                    : ($dados['vencimento'] ?? $documento->vencimento),
                'pago_avista' => $pagamento !== null
                    ? $pagamento['pago_avista']
                    : (array_key_exists('pago_avista', $dados)
                        ? (bool) $dados['pago_avista']
                        : $documento->pago_avista),
                'observacoes' => $dados['observacoes'] ?? $documento->observacoes,
            ]);

            if (isset($dados['itens'])) {
                $documento->itens()->delete();
                $total = $this->sincronizarItens($documento, $dados['itens']);
                $documento->update(['valor_total' => $total]);
            }

            return $documento->fresh(['itens', 'cliente', 'fornecedor', 'formaPagamentoRel']);
        });
    }

    public function confirmar(DocumentoComercial $documento): DocumentoComercial
    {
        $documento = $documento->fresh(['itens.produto', 'itens.servico', 'cliente', 'fornecedor', 'empresa']);

        if (! $documento->isRascunho() && $documento->status !== DocumentoComercial::STATUS_ERRO) {
            throw new RuntimeException('Documento não pode ser confirmado no status atual.');
        }

        if ($documento->itens->isEmpty()) {
            throw new RuntimeException('Documento sem itens.');
        }

        return match ($documento->canal_fiscal) {
            DocumentoComercial::CANAL_NFE_ENTRADA => $this->confirmarCompraEntrada($documento),
            DocumentoComercial::CANAL_NFCE => $this->emitirVendaNfce($documento),
            DocumentoComercial::CANAL_NFSE => $this->emitirVendaNfse($documento),
            default => throw new InvalidArgumentException('Canal fiscal inválido.'),
        };
    }

    public function onFiscalAutorizado(DocumentoComercial $documento): DocumentoComercial
    {
        return DB::transaction(function () use ($documento) {
            $documento = DocumentoComercial::query()
                ->whereKey($documento->id)
                ->lockForUpdate()
                ->with(['itens.produto'])
                ->firstOrFail();

            if ($documento->status === DocumentoComercial::STATUS_AUTORIZADO) {
                return $documento;
            }

            if ($documento->canal_fiscal === DocumentoComercial::CANAL_NFCE) {
                foreach ($documento->itens as $item) {
                    if (! $item->produto_id) {
                        continue;
                    }
                    $produto = Produto::query()->find($item->produto_id);
                    if (! $produto) {
                        continue;
                    }
                    $this->estoque->saida(
                        $produto,
                        (float) $item->quantidade,
                        (float) $produto->custo_medio,
                        $documento->id,
                        'venda_nfce',
                    );
                }
            }

            $this->financeiro->gerarDoDocumento($documento);

            $this->sincronizarCompetencia($documento);

            app(\App\Services\Contabil\ContabilPostingService::class)->fromDocumento($documento->fresh());

            $documento->update([
                'status' => DocumentoComercial::STATUS_AUTORIZADO,
                'mensagem_erro' => null,
            ]);

            return $documento->fresh();
        });
    }

    public function onFiscalErro(DocumentoComercial $documento, string $mensagem): void
    {
        $documento->update([
            'status' => DocumentoComercial::STATUS_ERRO,
            'mensagem_erro' => $mensagem,
        ]);
    }

    /**
     * Após cancelamento fiscal homologado (NFC-e / NFS-e): estorna estoque,
     * cancela P/R (abertos e baixados) e marca o documento como cancelado.
     */
    public function onFiscalCancelado(DocumentoComercial $documento, ?string $motivo = null): DocumentoComercial
    {
        return DB::transaction(function () use ($documento, $motivo) {
            $documento = DocumentoComercial::query()
                ->whereKey($documento->id)
                ->lockForUpdate()
                ->with(['itens.produto', 'lancamentosFinanceiros'])
                ->firstOrFail();

            if ($documento->status === DocumentoComercial::STATUS_CANCELADO) {
                return $documento;
            }

            $this->estoque->estornarPorDocumento(
                $documento,
                observacao: $motivo
                    ? sprintf('Estorno cancelamento fiscal: %s', $motivo)
                    : null,
            );

            $this->financeiro->cancelarDoDocumento($documento);

            app(\App\Services\Contabil\ContabilPostingService::class)->reverterDocumento($documento);

            $documento->update([
                'status' => DocumentoComercial::STATUS_CANCELADO,
                'mensagem_erro' => $motivo,
            ]);

            return $documento->fresh(['itens', 'lancamentosFinanceiros', 'movimentacoesEstoque']);
        });
    }

    /**
     * Monta payload NFC-e a partir dos itens do documento (para testes e emissão).
     *
     * Dest explícito (opcoes ou stash de criarRascunho) tem prioridade; senão usa cliente.
     *
     * @param  array{dest_doc?: string|null, dest_nome?: string|null}  $opcoes
     * @return array{itens: list<array<string, mixed>>, pagamentos: list<array<string, mixed>>, dest_doc: ?string, dest_nome: ?string, natureza: string}
     */
    public function montarPayloadNfce(DocumentoComercial $documento, array $opcoes = []): array
    {
        $documento->loadMissing(['itens', 'cliente']);

        $itens = [];
        foreach ($documento->itens as $item) {
            $itens[] = [
                'descricao' => $item->descricao,
                'ncm' => preg_replace('/\D/', '', (string) ($item->ncm ?? '00000000')),
                'cfop' => $item->cfop ?: '5102',
                'csosn' => $item->csosn ?: '102',
                'unidade' => $item->unidade ?: 'UN',
                'quantidade' => (float) $item->quantidade,
                'valor_unitario' => (float) $item->valor_unitario,
                'pis_cst' => '49',
                'cofins_cst' => '49',
                'cean' => $item->ean,
                'cprod' => $item->produto_id ? (string) $item->produto_id : null,
            ];
        }

        $tPag = $documento->forma_pagamento ?: '01';
        $total = (float) $documento->valor_total;
        [$destDoc, $destNome] = $this->resolverDestNfce($documento, $opcoes);

        return [
            'itens' => $itens,
            'pagamentos' => [[
                't_pag' => $tPag,
                'v_pag' => $total,
                'v_troco' => null,
            ]],
            'dest_doc' => $destDoc,
            'dest_nome' => $destNome,
            'natureza' => 'VENDA',
        ];
    }

    /**
     * @param  array{dest_doc?: string|null, dest_nome?: string|null}  $dados
     */
    private function rememberNfceDest(int $documentoId, array $dados): void
    {
        if (! array_key_exists('dest_doc', $dados) && ! array_key_exists('dest_nome', $dados)) {
            return;
        }

        $doc = isset($dados['dest_doc']) ? preg_replace('/\D/', '', (string) $dados['dest_doc']) : null;
        $this->nfceDestByDoc[$documentoId] = [
            'dest_doc' => ($doc !== null && $doc !== '') ? $doc : null,
            'dest_nome' => isset($dados['dest_nome']) ? (trim((string) $dados['dest_nome']) ?: null) : null,
        ];
    }

    /**
     * @param  array{dest_doc?: string|null, dest_nome?: string|null}  $opcoes
     * @return array{0: ?string, 1: ?string}
     */
    private function resolverDestNfce(DocumentoComercial $documento, array $opcoes = []): array
    {
        $stashed = $this->nfceDestByDoc[$documento->id] ?? null;

        $explicitDoc = array_key_exists('dest_doc', $opcoes)
            ? $opcoes['dest_doc']
            : ($stashed['dest_doc'] ?? null);
        $explicitNome = array_key_exists('dest_nome', $opcoes)
            ? $opcoes['dest_nome']
            : ($stashed['dest_nome'] ?? null);

        if ($explicitDoc !== null && $explicitDoc !== '') {
            $doc = preg_replace('/\D/', '', (string) $explicitDoc) ?: null;
            $nome = $explicitNome !== null ? (trim((string) $explicitNome) ?: null) : null;

            return [$doc, $nome];
        }

        return [
            $documento->cliente?->cnpj,
            $documento->cliente?->razao_social,
        ];
    }

    private function confirmarCompraEntrada(DocumentoComercial $documento): DocumentoComercial
    {
        return DB::transaction(function () use ($documento) {
            foreach ($documento->itens as $item) {
                if (! $item->produto_id) {
                    continue;
                }
                $produto = Produto::query()->findOrFail($item->produto_id);
                $this->estoque->entrada(
                    $produto,
                    (float) $item->quantidade,
                    (float) $item->valor_unitario,
                    $documento->id,
                    'compra_nfe',
                );
            }

            $documento->update([
                'status' => DocumentoComercial::STATUS_AUTORIZADO,
                'mensagem_erro' => null,
            ]);

            $this->financeiro->gerarDoDocumento($documento->fresh());

            $this->sincronizarCompetencia($documento->fresh());

            app(\App\Services\Contabil\ContabilPostingService::class)->fromDocumento($documento->fresh());

            return $documento->fresh(['itens', 'lancamentosFinanceiros', 'movimentacoesEstoque']);
        });
    }

    private function emitirVendaNfce(DocumentoComercial $documento): DocumentoComercial
    {
        return DB::transaction(function () use ($documento) {
            $empresa = $documento->empresa;
            $payload = $this->montarPayloadNfce($documento);

            [$numero, $serie, $ambiente] = $this->nfceIssuer->reservarNumero($empresa);

            $nfce = Nfce::create([
                'empresa_id' => $empresa->id,
                'documento_comercial_id' => $documento->id,
                'numero' => $numero,
                'serie' => $serie,
                'ambiente' => $ambiente,
                'tp_emis' => 1,
                'status' => 'processando',
                'payload' => $payload,
                'valor_total' => $documento->valor_total,
                'destinatario_doc' => $payload['dest_doc'],
                'destinatario_nome' => $payload['dest_nome'],
            ]);

            $documento->update([
                'status' => DocumentoComercial::STATUS_PROCESSANDO_FISCAL,
                'mensagem_erro' => null,
            ]);

            // afterCommit: com QUEUE=sync, o job não pode rodar dentro desta TX —
            // senão autorização na SEFAZ + rollback local gera órfão e cStat 539.
            EmitirNfceJob::dispatch($nfce)->afterCommit();

            return $documento->fresh(['nfce', 'itens']);
        });
    }

    private function emitirVendaNfse(DocumentoComercial $documento): DocumentoComercial
    {
        return DB::transaction(function () use ($documento) {
            $item = $documento->itens->first();
            if (! $item || ! $item->servico_id) {
                throw new RuntimeException('Venda NFS-e exige um item de serviço.');
            }

            if (! $documento->cliente_id) {
                throw new RuntimeException('Venda NFS-e exige cliente (tomador).');
            }

            /** @var Cliente $cliente */
            $cliente = $documento->cliente;
            /** @var Servico $servico */
            $servico = $item->servico ?? Servico::findOrFail($item->servico_id);

            $numeroDps = NfseDpsNumero::reservar($documento->empresa);

            $nota = NotaFiscal::create([
                'empresa_id' => $documento->empresa_id,
                'cliente_id' => $cliente->id,
                'servico_id' => $servico->id,
                'documento_comercial_id' => $documento->id,
                'status' => 'processando',
                'ambiente' => NfseAmbiente::label(),
                'numero_dps' => $numeroDps,
                'tomador_cnpj' => $cliente->cnpj,
                'tomador_nome' => $cliente->razao_social,
                'tomador_email' => $cliente->email,
                'valor_servico' => $documento->valor_total,
                'descricao' => $item->descricao ?: ($servico->descricao ?: $servico->nome),
                'emissao' => now(),
                'trib_issqn' => '1',
                'tp_ret_issqn' => '1',
                'aliquota_iss' => 0,
                'fin_nfse' => $servico->fin_nfse ?: NfseIbscbsBuilder::DEFAULT_FIN_NFSE,
                'c_ind_op' => $servico->c_ind_op ?: NfseIbscbsBuilder::DEFAULT_C_IND_OP,
                'cst_ibscbs' => $servico->cst_ibscbs ?: NfseIbscbsBuilder::DEFAULT_CST,
                'c_class_trib' => $servico->c_class_trib ?: NfseIbscbsBuilder::DEFAULT_C_CLASS_TRIB,
                'ind_dest' => NfseIbscbsBuilder::DEFAULT_IND_DEST,
            ]);

            $documento->update([
                'status' => DocumentoComercial::STATUS_PROCESSANDO_FISCAL,
                'mensagem_erro' => null,
            ]);

            EmitirNotaFiscalJob::dispatch($nota)->afterCommit();

            return $documento->fresh(['notaFiscal', 'itens']);
        });
    }

    /**
     * Preenche data_competencia do documento e data_emissao da NFC-e vinculada.
     */
    private function sincronizarCompetencia(DocumentoComercial $documento): void
    {
        $documento->loadMissing(['nfce', 'notaFiscal']);
        $resolver = app(\App\Services\Contabil\CompetenciaResolver::class);

        $data = null;

        if ($documento->nfce) {
            $data = $documento->nfce->data_emissao
                ?? $resolver->dataDeXml($documento->nfce->xml_autorizado ?: $documento->nfce->xml_enviado);

            if (! $documento->nfce->data_emissao && $data) {
                $documento->nfce->update(['data_emissao' => $data->toDateString()]);
            } elseif (! $documento->nfce->data_emissao) {
                $documento->nfce->update(['data_emissao' => now()->toDateString()]);
                $data = now()->startOfDay();
            }
        }

        if (! $data && $documento->notaFiscal?->emissao) {
            $data = $documento->notaFiscal->emissao->copy()->startOfDay();
        }

        if (! $data && $documento->xml_nfe) {
            $data = $resolver->dataDeXml($documento->xml_nfe);
        }

        if (! $data && $documento->data_competencia) {
            return;
        }

        $documento->update([
            'data_competencia' => ($data ?? now())->toDateString(),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $itens
     */
    private function sincronizarItens(DocumentoComercial $documento, array $itens): float
    {
        $total = 0.0;
        foreach (array_values($itens) as $i => $raw) {
            $qtd = (float) ($raw['quantidade'] ?? 1);
            $vu = (float) ($raw['valor_unitario'] ?? 0);
            $linha = round($qtd * $vu, 2);
            $total += $linha;

            DocumentoItem::create([
                'documento_comercial_id' => $documento->id,
                'produto_id' => $raw['produto_id'] ?? null,
                'servico_id' => $raw['servico_id'] ?? null,
                'descricao' => $raw['descricao'] ?? 'Item',
                'ncm' => isset($raw['ncm']) ? preg_replace('/\D/', '', (string) $raw['ncm']) : null,
                'cfop' => $raw['cfop'] ?? null,
                'csosn' => $raw['csosn'] ?? null,
                'unidade' => $raw['unidade'] ?? 'UN',
                'codigo_fornecedor' => $raw['codigo_fornecedor'] ?? null,
                'ean' => $raw['ean'] ?? null,
                'quantidade' => $qtd,
                'valor_unitario' => $vu,
                'valor_total' => $linha,
                'ordem' => $i,
            ]);
        }

        return round($total, 2);
    }

    /**
     * @param  list<array<string, mixed>>  $itens
     */
    private function validarCanalItens(string $canal, array $itens): void
    {
        if ($itens === []) {
            throw new InvalidArgumentException('Informe ao menos um item.');
        }

        foreach ($itens as $item) {
            $temProduto = ! empty($item['produto_id']);
            $temServico = ! empty($item['servico_id']);

            if ($canal === DocumentoComercial::CANAL_NFSE) {
                if (! $temServico || $temProduto) {
                    throw new InvalidArgumentException('Canal NFS-e aceita somente itens de serviço.');
                }
            }

            if (in_array($canal, [DocumentoComercial::CANAL_NFCE, DocumentoComercial::CANAL_NFE_ENTRADA], true)) {
                if ($temServico) {
                    throw new InvalidArgumentException('Canal de produto não aceita itens de serviço.');
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array{id: ?int, codigo: ?string, vencimento: ?string, pago_avista: bool}
     */
    private function resolverPagamento(Empresa $empresa, array $dados): array
    {
        $forma = null;

        if (! empty($dados['forma_pagamento_id'])) {
            $forma = FormaPagamento::query()
                ->where('empresa_id', $empresa->id)
                ->whereKey($dados['forma_pagamento_id'])
                ->first();

            if (! $forma) {
                throw new InvalidArgumentException('Forma de pagamento inválida.');
            }
        }

        if ($forma) {
            $avista = $forma->isAvista();
            $dias = max(0, (int) $forma->dias_recebimento);
            $vencimento = $avista
                ? now()->toDateString()
                : now()->addDays($dias)->toDateString();

            return [
                'id' => $forma->id,
                'codigo' => $forma->codigo,
                'vencimento' => $dados['vencimento'] ?? $vencimento,
                'pago_avista' => $avista,
            ];
        }

        return [
            'id' => null,
            'codigo' => $dados['forma_pagamento'] ?? null,
            'vencimento' => $dados['vencimento'] ?? null,
            'pago_avista' => (bool) ($dados['pago_avista'] ?? false),
        ];
    }
}
