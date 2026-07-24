<?php

namespace App\Core\FiscalEngine\Transport;

class SefazEndpoints
{
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('nfce', []);
    }

    public function profile(?string $profile = null): string
    {
        return $profile ?: ($this->config['endpoint_profile'] ?? 'homolog_nac');
    }

    /**
     * Alinha perfil de URL ao tpAmb da empresa.
     * tpAmb=1 → producao; tpAmb=2 → homolog_nac|homolog (nunca producao).
     */
    public function profileForAmbiente(int $tpAmb, ?string $requested = null): string
    {
        if ($tpAmb === 1) {
            return 'producao';
        }

        $requested = $requested ?: $this->profile();
        if (in_array($requested, ['homolog_nac', 'homolog'], true)) {
            return $requested;
        }

        $fallback = $this->config['endpoint_profile'] ?? 'homolog_nac';

        return in_array($fallback, ['homolog_nac', 'homolog'], true) ? $fallback : 'homolog_nac';
    }

    public function url(string $service, ?string $profile = null): string
    {
        $profile = $this->profile($profile);
        $urls = $this->config['urls'][$profile] ?? null;
        if (! is_array($urls) || empty($urls[$service])) {
            throw new \InvalidArgumentException("Endpoint NFC-e '{$service}' não configurado para perfil '{$profile}'.");
        }

        return (string) $urls[$service];
    }

    public function autorizacao(?string $profile = null): string
    {
        return $this->url('autorizacao', $profile);
    }

    public function status(?string $profile = null): string
    {
        return $this->url('status', $profile);
    }

    public function consulta(?string $profile = null): string
    {
        return $this->url('consulta', $profile);
    }

    public function qrcode(?string $profile = null): string
    {
        return $this->url('qrcode', $profile);
    }

    public function consultaChave(?string $profile = null): string
    {
        return $this->url('consulta_chave', $profile);
    }

    public function evento(?string $profile = null): string
    {
        return $this->url('evento', $profile);
    }

    public function inutilizacao(?string $profile = null): string
    {
        return $this->url('inutilizacao', $profile);
    }
}
