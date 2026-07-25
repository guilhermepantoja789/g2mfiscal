<?php

namespace App\Services\Erp;

use App\Models\Cobranca;
use App\Models\DocumentoComercial;
use App\Models\Empresa;
use App\Models\FormaPagamento;
use App\Models\LancamentoFinanceiro;
use App\Services\FinanceiroService;
use Carbon\Carbon;
use InvalidArgumentException;
use RuntimeException;

class LancamentoFinanceiroService
{
    public function __construct(
        private FormaPagamentoService $formas,
    ) {}

    /**
     * @return list<LancamentoFinanceiro>
     */
    public function gerarDoDocumento(DocumentoComercial $documento): array
    {
        $existentes = LancamentoFinanceiro::query()
            ->where('documento_comercial_id', $documento->id)
            ->whereIn('status', [LancamentoFinanceiro::STATUS_ABERTO, LancamentoFinanceiro::STATUS_PAGO])
            ->get();

        if ($existentes->isNotEmpty()) {
            return $existentes->all();
        }

        $documento->loadMissing(['pagamentos.formaPagamento', 'formaPagamentoRel']);

        $tipo = $documento->isVenda()
            ? LancamentoFinanceiro::TIPO_RECEBER
            : LancamentoFinanceiro::TIPO_PAGAR;

        $criados = [];

        $linhas = $documento->pagamentos;
        if ($linhas->isNotEmpty()) {
            foreach ($linhas as $linha) {
                $forma = $linha->formaPagamento;
                if ($forma && ! $forma->gera_lancamento) {
                    continue;
                }

                if (! $forma) {
                    throw new InvalidArgumentException('Forma de pagamento ausente no pagamento do documento.');
                }

                $parcelas = $this->formas->calcularParcelas($forma, (float) $linha->valor);

                foreach ($parcelas as $parcela) {
                    $criados[] = $this->criarLancamentoParcela(
                        $documento,
                        $tipo,
                        $forma->id,
                        $parcela
                    );
                }
            }

            return $criados;
        }

        $forma = $documento->formaPagamentoRel;
        if ($forma && ! $forma->gera_lancamento) {
            return [];
        }

        $parcelas = $this->resolverParcelas($documento, $forma);
        foreach ($parcelas as $parcela) {
            $criados[] = $this->criarLancamentoParcela(
                $documento,
                $tipo,
                $forma?->id,
                $parcela
            );
        }

        return $criados;
    }

    /**
     * @param  array{parcela: int, total_parcelas: int, valor: float, vencimento: string, pago_avista: bool}  $parcela
     */
    private function criarLancamentoParcela(
        DocumentoComercial $documento,
        string $tipo,
        ?int $formaPagamentoId,
        array $parcela,
    ): LancamentoFinanceiro {
        $pagoAvista = (bool) $parcela['pago_avista'];

        return LancamentoFinanceiro::create([
            'empresa_id' => $documento->empresa_id,
            'documento_comercial_id' => $documento->id,
            'cliente_id' => $documento->cliente_id,
            'fornecedor_id' => $documento->fornecedor_id,
            'forma_pagamento_id' => $formaPagamentoId,
            'parcela' => $parcela['parcela'],
            'total_parcelas' => $parcela['total_parcelas'],
            'tipo' => $tipo,
            'status' => $pagoAvista
                ? LancamentoFinanceiro::STATUS_PAGO
                : LancamentoFinanceiro::STATUS_ABERTO,
            'valor' => $parcela['valor'],
            'vencimento' => $parcela['vencimento'],
            'pago_em' => $pagoAvista ? now() : null,
            'descricao' => $this->descricaoPadrao($documento, $parcela),
        ]);
    }

    /**
     * Lançamento manual (sem documento comercial).
     *
     * @param  array{
     *   tipo: string,
     *   valor: float|string,
     *   vencimento: string,
     *   descricao?: string|null,
     *   cliente_id?: int|null,
     *   fornecedor_id?: int|null,
     *   forma_pagamento_id?: int|null,
     *   status?: string,
     *   pago_em?: string|null
     * }  $dados
     */
    public function criarManual(Empresa $empresa, array $dados): LancamentoFinanceiro
    {
        $tipo = $dados['tipo'] ?? '';
        if (! in_array($tipo, [LancamentoFinanceiro::TIPO_RECEBER, LancamentoFinanceiro::TIPO_PAGAR], true)) {
            throw new InvalidArgumentException('Tipo de lançamento inválido.');
        }

        $valor = round((float) ($dados['valor'] ?? 0), 2);
        if ($valor <= 0) {
            throw new InvalidArgumentException('Valor deve ser maior que zero.');
        }

        $status = $dados['status'] ?? LancamentoFinanceiro::STATUS_ABERTO;
        if (! in_array($status, [LancamentoFinanceiro::STATUS_ABERTO, LancamentoFinanceiro::STATUS_PAGO], true)) {
            throw new InvalidArgumentException('Status inicial inválido.');
        }

        return LancamentoFinanceiro::create([
            'empresa_id' => $empresa->id,
            'documento_comercial_id' => null,
            'cliente_id' => $dados['cliente_id'] ?? null,
            'fornecedor_id' => $dados['fornecedor_id'] ?? null,
            'forma_pagamento_id' => $dados['forma_pagamento_id'] ?? null,
            'parcela' => 1,
            'total_parcelas' => 1,
            'tipo' => $tipo,
            'status' => $status,
            'valor' => $valor,
            'vencimento' => $dados['vencimento'],
            'pago_em' => $status === LancamentoFinanceiro::STATUS_PAGO
                ? ($dados['pago_em'] ?? now())
                : null,
            'descricao' => $dados['descricao'] ?? 'Lançamento manual',
        ]);
    }

    /**
     * Edita lançamento aberto (manual ou vinculado). Cancelados/pagos não editam valor.
     *
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(LancamentoFinanceiro $lancamento, array $dados): LancamentoFinanceiro
    {
        if ($lancamento->status === LancamentoFinanceiro::STATUS_CANCELADO) {
            throw new RuntimeException('Lançamento cancelado não pode ser editado.');
        }

        if ($lancamento->status === LancamentoFinanceiro::STATUS_PAGO) {
            throw new RuntimeException('Baixe o estorno antes de editar um lançamento pago.');
        }

        $payload = [];

        if (array_key_exists('tipo', $dados)) {
            $tipo = $dados['tipo'];
            if (! in_array($tipo, [LancamentoFinanceiro::TIPO_RECEBER, LancamentoFinanceiro::TIPO_PAGAR], true)) {
                throw new InvalidArgumentException('Tipo de lançamento inválido.');
            }
            $payload['tipo'] = $tipo;
        }

        if (array_key_exists('valor', $dados)) {
            $valor = round((float) $dados['valor'], 2);
            if ($valor <= 0) {
                throw new InvalidArgumentException('Valor deve ser maior que zero.');
            }
            $payload['valor'] = $valor;
        }

        if (array_key_exists('vencimento', $dados)) {
            $payload['vencimento'] = $dados['vencimento'];
        }

        if (array_key_exists('descricao', $dados)) {
            $payload['descricao'] = $dados['descricao'];
        }

        if (array_key_exists('cliente_id', $dados)) {
            $payload['cliente_id'] = $dados['cliente_id'];
        }

        if (array_key_exists('fornecedor_id', $dados)) {
            $payload['fornecedor_id'] = $dados['fornecedor_id'];
        }

        if (array_key_exists('forma_pagamento_id', $dados)) {
            $payload['forma_pagamento_id'] = $dados['forma_pagamento_id'];
        }

        $lancamento->update($payload);

        return $lancamento->fresh();
    }

    public function baixar(LancamentoFinanceiro $lancamento, ?Carbon $pagoEm = null): LancamentoFinanceiro
    {
        if ($lancamento->status === LancamentoFinanceiro::STATUS_CANCELADO) {
            throw new RuntimeException('Lançamento cancelado não pode ser baixado.');
        }

        if ($lancamento->status === LancamentoFinanceiro::STATUS_PAGO) {
            return $lancamento;
        }

        $lancamento->update([
            'status' => LancamentoFinanceiro::STATUS_PAGO,
            'pago_em' => $pagoEm ?? now(),
        ]);

        return $lancamento->fresh();
    }

    /**
     * Estorna baixa gerencial (pago → aberto). Não reabre cancelados.
     */
    public function estornar(LancamentoFinanceiro $lancamento): LancamentoFinanceiro
    {
        if ($lancamento->status === LancamentoFinanceiro::STATUS_CANCELADO) {
            throw new RuntimeException('Lançamento cancelado não pode ser estornado.');
        }

        if ($lancamento->status === LancamentoFinanceiro::STATUS_ABERTO) {
            return $lancamento;
        }

        $lancamento->update([
            'status' => LancamentoFinanceiro::STATUS_ABERTO,
            'pago_em' => null,
        ]);

        return $lancamento->fresh();
    }

    public function cancelar(LancamentoFinanceiro $lancamento): LancamentoFinanceiro
    {
        if ($lancamento->status === LancamentoFinanceiro::STATUS_CANCELADO) {
            return $lancamento;
        }

        $lancamento->update([
            'status' => LancamentoFinanceiro::STATUS_CANCELADO,
            // Política: cancelamento fiscal cancela P/R mesmo se já baixado;
            // reembolso/estorno bancário fica fora do livro gerencial.
            'pago_em' => null,
        ]);

        return $lancamento->fresh();
    }

    /**
     * Cancela lançamentos abertos e pagos do documento (idempotente).
     *
     * @return list<LancamentoFinanceiro>
     */
    public function cancelarDoDocumento(DocumentoComercial $documento): array
    {
        $lancamentos = LancamentoFinanceiro::query()
            ->where('documento_comercial_id', $documento->id)
            ->whereIn('status', [
                LancamentoFinanceiro::STATUS_ABERTO,
                LancamentoFinanceiro::STATUS_PAGO,
                LancamentoFinanceiro::STATUS_CANCELADO,
            ])
            ->orderBy('id')
            ->get();

        $resultado = [];
        foreach ($lancamentos as $lancamento) {
            $resultado[] = $this->cancelar($lancamento);
        }

        return $resultado;
    }

    /**
     * Ponte Asaas: gera Cobranca rascunho a partir de P/R a receber.
     * Só atua quando FEATURE_FINANCEIRO=true.
     */
    public function gerarCobrancaAsaas(LancamentoFinanceiro $lancamento, ?FinanceiroService $financeiro = null): Cobranca
    {
        if (! (bool) config('services.financeiro.enabled')) {
            throw new RuntimeException('FEATURE_FINANCEIRO desabilitado.');
        }

        if ($lancamento->tipo !== LancamentoFinanceiro::TIPO_RECEBER) {
            throw new RuntimeException('Somente contas a receber geram cobrança Asaas.');
        }

        if ($lancamento->status === LancamentoFinanceiro::STATUS_CANCELADO) {
            throw new RuntimeException('Lançamento cancelado não gera cobrança.');
        }

        if (! $lancamento->cliente_id) {
            throw new RuntimeException('Lançamento sem cliente não gera cobrança.');
        }

        if ($lancamento->cobranca_id) {
            $existente = Cobranca::query()->find($lancamento->cobranca_id);
            if ($existente) {
                return $existente;
            }
        }

        $duplicada = Cobranca::query()
            ->where('lancamento_financeiro_id', $lancamento->id)
            ->whereNotIn('status', ['CANCELLED'])
            ->first();

        if ($duplicada) {
            if (! $lancamento->cobranca_id) {
                $lancamento->update(['cobranca_id' => $duplicada->id]);
            }

            return $duplicada;
        }

        $financeiro ??= app(FinanceiroService::class);
        $cobranca = $financeiro->gerarCobrancaDeLancamento($lancamento);

        $lancamento->update(['cobranca_id' => $cobranca->id]);

        return $cobranca;
    }

    /**
     * @return list<array{parcela: int, total_parcelas: int, valor: float, vencimento: string, pago_avista: bool}>
     */
    private function resolverParcelas(DocumentoComercial $documento, ?FormaPagamento $forma): array
    {
        $base = (float) $documento->valor_total;

        if ($forma) {
            return $this->formas->calcularParcelas($forma, $base);
        }

        $pagoAvista = (bool) $documento->pago_avista;
        $vencimento = $documento->vencimento
            ? $documento->vencimento->toDateString()
            : now()->toDateString();

        return [[
            'parcela' => 1,
            'total_parcelas' => 1,
            'valor' => round($base, 2),
            'vencimento' => $vencimento,
            'pago_avista' => $pagoAvista,
        ]];
    }

    /**
     * @param  array{parcela: int, total_parcelas: int}  $parcela
     */
    private function descricaoPadrao(DocumentoComercial $documento, array $parcela): string
    {
        $prefix = $documento->isVenda() ? 'Receber' : 'Pagar';
        $base = sprintf('%s ref. Documento #%d (%s)', $prefix, $documento->id, $documento->canal_fiscal);

        if ($parcela['total_parcelas'] > 1) {
            $base .= sprintf(' — parcela %d/%d', $parcela['parcela'], $parcela['total_parcelas']);
        }

        return $base;
    }
}
