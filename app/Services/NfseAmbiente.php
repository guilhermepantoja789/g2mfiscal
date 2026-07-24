<?php

namespace App\Services;

/**
 * Resolve tpAmb, série e URLs SEFIN/ADN de forma sincronizada.
 *
 * Evita E0006 (tpAmb ≠ endpoint). Override opcional via NFSE_NACIONAL_TP_AMB
 * e URLs explícitas no .env (validadas contra o ambiente).
 */
class NfseAmbiente
{
    public const PRODUCAO = 1;

    public const HOMOLOGACAO = 2;

    public static function tpAmb(): int
    {
        $override = config('services.nfse_nacional.tp_amb');
        if ($override !== null && $override !== '') {
            return (int) $override === self::PRODUCAO ? self::PRODUCAO : self::HOMOLOGACAO;
        }

        return app()->environment('production') ? self::PRODUCAO : self::HOMOLOGACAO;
    }

    public static function isProducao(): bool
    {
        return self::tpAmb() === self::PRODUCAO;
    }

    public static function label(): string
    {
        return self::isProducao() ? 'producao' : 'homologacao';
    }

    /** Série DPS: produção = 1; homologação = 99. */
    public static function serie(): string
    {
        return self::isProducao() ? '1' : '99';
    }

    public static function urlSefin(): string
    {
        $override = config('services.nfse_nacional.url_sefin');
        $url = filled($override)
            ? (string) $override
            : (string) config('services.nfse_nacional.urls.'.self::label().'.sefin');

        self::assertUrlMatchesAmbiente($url, 'SEFIN');

        return rtrim($url, '/');
    }

    public static function urlAdn(): string
    {
        $override = config('services.nfse_nacional.url_adn');
        $url = filled($override)
            ? (string) $override
            : (string) config('services.nfse_nacional.urls.'.self::label().'.adn');

        self::assertUrlMatchesAmbiente($url, 'ADN');

        return rtrim($url, '/');
    }

    public static function urlConsulta(string $chaveAcesso): string
    {
        $chave = preg_replace('/\D/', '', $chaveAcesso) ?? '';
        $override = config('services.nfse_nacional.url_consulta_api');
        $base = filled($override)
            ? (string) $override
            : (string) config('services.nfse_nacional.urls.'.self::label().'.consulta');

        self::assertUrlMatchesAmbiente($base, 'consulta');

        return rtrim($base, '/').'/'.$chave;
    }

    private static function assertUrlMatchesAmbiente(string $url, string $contexto): void
    {
        $isRestrita = str_contains($url, 'producaorestrita');
        $isProducaoHost = str_contains($url, 'sefin.nfse.gov.br')
            || str_contains($url, 'adn.nfse.gov.br')
            || str_contains($url, 'api.nfse.gov.br');

        if (self::isProducao() && $isRestrita) {
            throw new \InvalidArgumentException(
                "Mismatch NFS-e: tpAmb=produção mas URL {$contexto} aponta para produção restrita ({$url})."
            );
        }

        if (! self::isProducao() && $isProducaoHost && ! $isRestrita) {
            throw new \InvalidArgumentException(
                "Mismatch NFS-e: tpAmb=homologação mas URL {$contexto} aponta para produção ({$url})."
            );
        }
    }
}
