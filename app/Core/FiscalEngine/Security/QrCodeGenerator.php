<?php

namespace App\Core\FiscalEngine\Security;

/**
 * Monta URL do QR Code NFC-e e cHash (SHA-1 hex) — lógica pura, sem lib de imagem.
 */
class QrCodeGenerator
{
    public function __construct(
        private readonly string $qrcodeVersion = '2',
    ) {}

    /**
     * @return array{url: string, params: string, cHash: string}
     */
    public function build(
        string $chave,
        int $tpAmb,
        string $cscId,
        string $cscToken,
        string $baseUrl,
    ): array {
        $chave = preg_replace('/\D/', '', $chave) ?? '';
        $cscId = ltrim($cscId, '0') ?: '0';
        $cscIdPadded = str_pad($cscId, 6, '0', STR_PAD_LEFT);

        $params = implode('|', [
            $chave,
            $this->qrcodeVersion,
            (string) $tpAmb,
            $cscIdPadded,
        ]);

        $cHash = $this->cHash($params, $cscToken);
        $query = $params.'|'.$cHash;

        $baseUrl = rtrim($baseUrl, '?&');
        $separator = str_contains($baseUrl, '?') ? '&' : '?';
        $url = $baseUrl.$separator.'p='.$query;

        return [
            'url' => $url,
            'params' => $params,
            'cHash' => $cHash,
        ];
    }

    public function cHash(string $paramsWithoutHash, string $cscToken): string
    {
        return strtoupper(sha1($paramsWithoutHash.$cscToken));
    }

    /**
     * Anexa infNFeSupl ao XML assinado (DOM ou string).
     */
    public function attachInfNFeSupl(string $signedXml, string $qrCodeUrl, string $urlChave): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        if (! @$dom->loadXML($signedXml)) {
            throw new \RuntimeException('XML assinado inválido ao anexar infNFeSupl.');
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');

        /** @var \DOMElement|null $infNFe */
        $infNFe = $xpath->query('//*[local-name()="infNFe"]')->item(0);
        if (! $infNFe instanceof \DOMElement) {
            throw new \RuntimeException('infNFe ausente ao anexar infNFeSupl.');
        }

        /** @var \DOMElement $nfe */
        $nfe = $infNFe->parentNode;

        $existing = $xpath->query('//*[local-name()="infNFeSupl"]')->item(0);
        if ($existing) {
            $existing->parentNode?->removeChild($existing);
        }

        $supl = $dom->createElementNS('http://www.portalfiscal.inf.br/nfe', 'infNFeSupl');
        $qrCode = $dom->createElement('qrCode', $qrCodeUrl);
        $supl->appendChild($qrCode);
        $urlChaveEl = $dom->createElement('urlChave', $urlChave);
        $supl->appendChild($urlChaveEl);

        // infNFeSupl deve vir imediatamente após infNFe (antes da Signature)
        $signature = $xpath->query('//*[local-name()="Signature"]')->item(0);
        if ($signature) {
            $nfe->insertBefore($supl, $signature);
        } else {
            $nfe->appendChild($supl);
        }

        return $dom->saveXML() ?: '';
    }
}
