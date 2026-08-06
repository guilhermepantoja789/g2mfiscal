<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\NotaFiscal;
use InvalidArgumentException;

/**
 * Monta o payload de emissão a partir da NotaFiscal (job + recorrência).
 */
class NfseEmitPayloadBuilder
{
    /**
     * @return array<string, mixed>
     */
    public static function fromNota(NotaFiscal $nota): array
    {
        $nota->loadMissing(['cliente', 'servico']);

        if (! $nota->servico) {
            throw new InvalidArgumentException('Nenhum serviço vinculado à nota.');
        }

        $codMun = $nota->servico->codigo_tributacao_municipal;
        $codTribNac = $nota->servico->codigo_tributacao_nacional;
        $codCnbs = self::normalizeCnbs($nota->servico->codigo_nbs ?? null);

        if (empty($codMun) || empty($codTribNac)) {
            throw new InvalidArgumentException('Serviço sem código de tributação nacional (cTribNac) ou municipal configurado.');
        }

        if ($codCnbs === null) {
            throw new InvalidArgumentException(
                'Serviço sem código NBS (cNBS) configurado. Com IBS/CBS na DPS é obrigatório informar o item da NBS (9 dígitos).'
            );
        }

        $cliente = $nota->cliente;
        self::assertClienteCompleto($cliente);

        $aliqVal = ($nota->p_tot_trib_mun > 0) ? $nota->p_tot_trib_mun : 2.00;

        $servico = $nota->servico;
        $ibscbs = self::resolveIbscbs($nota, $servico);

        return [
            'numero' => (int) ($nota->numero_dps ?: $nota->id),
            'serie' => NfseAmbiente::serie(),
            'competencia' => $nota->emissao->format('Y-m-d'),

            'tomador_doc' => $nota->tomador_cnpj,
            'tomador_nome' => $nota->tomador_nome,
            'tomador_email' => $nota->tomador_email,

            'tomador_endereco' => $cliente->logradouro,
            'tomador_numero' => $cliente->numero ?: 'S/N',
            'tomador_bairro' => $cliente->bairro,
            'tomador_cep' => $cliente->cep,
            'tomador_cidade_codigo' => $cliente->cidade_codigo,
            'tomador_uf' => $cliente->uf,
            'tomador_complemento' => $cliente->complemento ?? '',

            'valor' => $nota->valor_servico,
            'discriminacao' => $nota->descricao,
            'tributacao_iss' => $nota->trib_issqn,
            'retencao_iss' => $nota->tp_ret_issqn,

            // Histórico: servico_nbs = cTribNac (LC 116, 6 dígitos). cNBS é servico_cnbs.
            'servico_nbs' => $codTribNac,
            'servico_cnbs' => $codCnbs,
            'servico_municipal' => $codMun,

            'aliquota' => $aliqVal,

            'v_tot_trib_fed' => $nota->v_tot_trib_fed,
            'v_tot_trib_est' => $nota->v_tot_trib_est,
            'v_tot_trib_mun' => $nota->v_tot_trib_mun,

            'fin_nfse' => $ibscbs['fin_nfse'],
            'ind_final' => $ibscbs['ind_final'],
            'ind_dest' => $ibscbs['ind_dest'],
            'c_ind_op' => $ibscbs['c_ind_op'],
            'cst_ibscbs' => $ibscbs['cst_ibscbs'],
            'c_class_trib' => $ibscbs['c_class_trib'],
        ];
    }

    /**
     * Normaliza NBS para 9 dígitos (TSCodNBS). Aceita máscara 1.1501.10.00.
     */
    public static function normalizeCnbs(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);
        if ($digits === '' || $digits === null) {
            return null;
        }

        if (strlen($digits) !== 9) {
            return null;
        }

        return $digits;
    }

    /**
     * @return array{fin_nfse: string, ind_final: ?string, ind_dest: string, c_ind_op: string, cst_ibscbs: string, c_class_trib: string}
     */
    public static function resolveIbscbs(NotaFiscal $nota, $servico): array
    {
        $pick = static function (?string $notaVal, ?string $servicoVal, string $default): string {
            $notaVal = $notaVal !== null ? trim($notaVal) : '';
            if ($notaVal !== '') {
                return $notaVal;
            }
            $servicoVal = $servicoVal !== null ? trim($servicoVal) : '';
            if ($servicoVal !== '') {
                return $servicoVal;
            }

            return $default;
        };

        $indFinal = $nota->ind_final;
        if ($indFinal === null || trim((string) $indFinal) === '') {
            $indFinal = null; // NfseIbscbsBuilder deriva do documento
        }

        return [
            'fin_nfse' => $pick($nota->fin_nfse, $servico->fin_nfse ?? null, NfseIbscbsBuilder::DEFAULT_FIN_NFSE),
            'ind_final' => $indFinal,
            'ind_dest' => $pick($nota->ind_dest, null, NfseIbscbsBuilder::DEFAULT_IND_DEST),
            'c_ind_op' => $pick($nota->c_ind_op, $servico->c_ind_op ?? null, NfseIbscbsBuilder::DEFAULT_C_IND_OP),
            'cst_ibscbs' => $pick($nota->cst_ibscbs, $servico->cst_ibscbs ?? null, NfseIbscbsBuilder::DEFAULT_CST),
            'c_class_trib' => $pick($nota->c_class_trib, $servico->c_class_trib ?? null, NfseIbscbsBuilder::DEFAULT_C_CLASS_TRIB),
        ];
    }

    public static function assertClienteCompleto(?Cliente $cliente): void
    {
        if (! $cliente) {
            throw new InvalidArgumentException('Cliente (tomador) não vinculado à nota.');
        }

        $obrigatorios = [
            'logradouro' => $cliente->logradouro,
            'bairro' => $cliente->bairro,
            'cep' => $cliente->cep,
            'cidade_codigo' => $cliente->cidade_codigo,
            'uf' => $cliente->uf,
        ];

        $faltando = [];
        foreach ($obrigatorios as $campo => $valor) {
            if ($valor === null || trim((string) $valor) === '') {
                $faltando[] = $campo;
            }
        }

        if ($faltando !== []) {
            throw new InvalidArgumentException(
                'Endereço do tomador incompleto ('.implode(', ', $faltando).'). Atualize o cliente antes de emitir.'
            );
        }
    }
}
