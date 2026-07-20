<?php

namespace App\Core\FiscalEngine\Xml;

use InvalidArgumentException;

class AccessKeyGenerator
{
    /**
     * Monta chave de acesso de 44 dígitos (UF + AAMM + CNPJ + mod + série + nNF + tpEmis + cNF + DV).
     */
    public function generate(
        string $cUF,
        string $aamm,
        string $cnpj,
        string $mod,
        int $serie,
        int $numero,
        int $tpEmis,
        ?string $cNF = null,
    ): string {
        $cUF = $this->onlyDigits($cUF, 2);
        $aamm = $this->onlyDigits($aamm, 4);
        $cnpj = $this->onlyDigits($cnpj, 14);
        $mod = $this->onlyDigits($mod, 2);
        $serie = str_pad((string) $serie, 3, '0', STR_PAD_LEFT);
        $nNF = str_pad((string) $numero, 9, '0', STR_PAD_LEFT);
        $tpEmis = (string) $tpEmis;
        $cNF = $cNF !== null
            ? $this->onlyDigits($cNF, 8)
            : str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

        if (strlen($serie) > 3 || strlen($nNF) > 9 || strlen($tpEmis) !== 1) {
            throw new InvalidArgumentException('Parâmetros inválidos para chave de acesso.');
        }

        $base = $cUF.$aamm.$cnpj.$mod.$serie.$nNF.$tpEmis.$cNF;
        $dv = $this->modulo11($base);

        return $base.$dv;
    }

    public function modulo11(string $base43): string
    {
        $base43 = preg_replace('/\D/', '', $base43) ?? '';
        if (strlen($base43) !== 43) {
            throw new InvalidArgumentException('Base da chave deve ter 43 dígitos.');
        }

        $soma = 0;
        $peso = 2;
        for ($i = 42; $i >= 0; $i--) {
            $soma += (int) $base43[$i] * $peso;
            $peso = $peso === 9 ? 2 : $peso + 1;
        }

        $resto = $soma % 11;
        $dv = ($resto === 0 || $resto === 1) ? 0 : 11 - $resto;

        return (string) $dv;
    }

    private function onlyDigits(string $value, int $length): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';
        $digits = str_pad($digits, $length, '0', STR_PAD_LEFT);

        if (strlen($digits) !== $length) {
            throw new InvalidArgumentException("Campo numérico deve ter {$length} dígitos.");
        }

        return $digits;
    }
}
