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
        $codNbs = $nota->servico->codigo_tributacao_nacional;

        if (empty($codMun) || empty($codNbs)) {
            throw new InvalidArgumentException('Serviço sem código NBS ou municipal configurado.');
        }

        $cliente = $nota->cliente;
        self::assertClienteCompleto($cliente);

        $aliqVal = ($nota->p_tot_trib_mun > 0) ? $nota->p_tot_trib_mun : 2.00;

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

            'servico_nbs' => $codNbs,
            'servico_municipal' => $codMun,

            'aliquota' => $aliqVal,

            'v_tot_trib_fed' => $nota->v_tot_trib_fed,
            'v_tot_trib_est' => $nota->v_tot_trib_est,
            'v_tot_trib_mun' => $nota->v_tot_trib_mun,
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
