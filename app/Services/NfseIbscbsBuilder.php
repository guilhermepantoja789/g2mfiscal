<?php

namespace App\Services;

/**
 * Resolve e monta o grupo IBSCBS da DPS (NFS-e Nacional v1.01).
 */
class NfseIbscbsBuilder
{
    public const DEFAULT_FIN_NFSE = '0';

    public const DEFAULT_IND_DEST = '0';

    public const DEFAULT_C_IND_OP = '100301';

    public const DEFAULT_CST = '000';

    public const DEFAULT_C_CLASS_TRIB = '000001';

    /**
     * @param  array<string, mixed>  $dados  payload de emissão
     * @return array{fin_nfse: string, ind_final: string, ind_dest: string, c_ind_op: string, cst: string, c_class_trib: string}
     */
    public static function resolve(array $dados): array
    {
        $doc = preg_replace('/\D/', '', (string) ($dados['tomador_doc'] ?? ''));
        $indFinal = $dados['ind_final'] ?? null;
        if ($indFinal === null || $indFinal === '') {
            $indFinal = strlen($doc) <= 11 ? '1' : '0';
        }

        $cIndOp = preg_replace('/\D/', '', (string) ($dados['c_ind_op'] ?? ''));
        $cst = preg_replace('/\D/', '', (string) ($dados['cst_ibscbs'] ?? ''));
        $cClassTrib = preg_replace('/\D/', '', (string) ($dados['c_class_trib'] ?? ''));

        return [
            'fin_nfse' => (string) ($dados['fin_nfse'] ?? self::DEFAULT_FIN_NFSE),
            'ind_final' => (string) $indFinal,
            'ind_dest' => (string) ($dados['ind_dest'] ?? self::DEFAULT_IND_DEST),
            'c_ind_op' => str_pad($cIndOp !== '' ? $cIndOp : self::DEFAULT_C_IND_OP, 6, '0', STR_PAD_LEFT),
            'cst' => str_pad($cst !== '' ? $cst : self::DEFAULT_CST, 3, '0', STR_PAD_LEFT),
            'c_class_trib' => str_pad($cClassTrib !== '' ? $cClassTrib : self::DEFAULT_C_CLASS_TRIB, 6, '0', STR_PAD_LEFT),
        ];
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public static function toXml(array $dados): string
    {
        $r = self::resolve($dados);

        return <<<XML
        <IBSCBS>
            <finNFSe>{$r['fin_nfse']}</finNFSe>
            <indFinal>{$r['ind_final']}</indFinal>
            <cIndOp>{$r['c_ind_op']}</cIndOp>
            <indDest>{$r['ind_dest']}</indDest>
            <valores>
                <trib>
                    <gIBSCBS>
                        <CST>{$r['cst']}</CST>
                        <cClassTrib>{$r['c_class_trib']}</cClassTrib>
                    </gIBSCBS>
                </trib>
            </valores>
        </IBSCBS>
XML;
    }
}
