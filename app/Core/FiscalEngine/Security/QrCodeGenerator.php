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
     * QR online (tpEmis=1): chave|versao|tpAmb|idCSC|cHash
     * QR contingência offline (tpEmis=9): chave|versao|tpAmb|dia|vNF|digValHex|idCSC|cHash
     *
     * Manual NT QR Code v2 (offline): dia = dd de dhEmi; digVal = DigestValue em hex ASCII.
     *
     * @return array{url: string, params: string, cHash: string}
     */
    public function build(
        string $chave,
        int $tpAmb,
        string $cscId,
        string $cscToken,
        string $baseUrl,
        int $tpEmis = 1,
        ?string $dhEmi = null,
        ?string $vNF = null,
        ?string $digVal = null,
    ): array {
        $chave = preg_replace('/\D/', '', $chave) ?? '';
        // QR Code v2 (XSD): idCSC sem zeros à esquerda — "000001" falha no pattern e causa cStat 215.
        $cscId = ltrim(preg_replace('/\D/', '', $cscId) ?? '', '0') ?: '0';

        if ($tpEmis === 9) {
            if ($dhEmi === null || $vNF === null || $digVal === null || $digVal === '') {
                throw new \InvalidArgumentException('QR contingência exige dhEmi, vNF e digVal.');
            }
            // NT Manual QR Code v2 offline: dia (dd) + DigestValue em hex ASCII (não base64 cru).
            $dia = (new \DateTimeImmutable($dhEmi))->format('d');
            $digHex = $this->str2Hex($digVal);
            $params = implode('|', [
                $chave,
                $this->qrcodeVersion,
                (string) $tpAmb,
                $dia,
                $vNF,
                $digHex,
                $cscId,
            ]);
        } else {
            $params = implode('|', [
                $chave,
                $this->qrcodeVersion,
                (string) $tpAmb,
                $cscId,
            ]);
        }

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
     * Converte DigestValue (base64) para hex ASCII — exigência NT QR Code v2 offline.
     */
    public function str2Hex(string $str): string
    {
        $hex = '';
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $hex .= sprintf('%02x', ord($str[$i]));
        }

        return $hex;
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
        $qrCode = $dom->createElementNS('http://www.portalfiscal.inf.br/nfe', 'qrCode', $qrCodeUrl);
        $supl->appendChild($qrCode);
        $urlChaveEl = $dom->createElementNS('http://www.portalfiscal.inf.br/nfe', 'urlChave', $urlChave);
        $supl->appendChild($urlChaveEl);

        // Schema TNFe: infNFe → infNFeSupl → Signature
        $signature = $xpath->query('//*[local-name()="Signature"]')->item(0);
        if ($signature) {
            $nfe->insertBefore($supl, $signature);
        } else {
            $nfe->appendChild($supl);
        }

        return $dom->saveXML() ?: '';
    }
}
