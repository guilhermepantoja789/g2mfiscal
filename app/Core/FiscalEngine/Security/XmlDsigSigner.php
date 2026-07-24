<?php

namespace App\Core\FiscalEngine\Security;

use App\Core\FiscalEngine\Exceptions\FiscalEngineException;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Assinatura XMLDSIG (RSA-SHA1 + C14N) — PHP nativo.
 * Assina infNFe, infEvento ou infInut pelo atributo Id.
 */
class XmlDsigSigner
{
    public function sign(DOMDocument $dom, string $privateKeyPem, string $x509CertificateBase64): DOMDocument
    {
        return $this->signByLocalName($dom, 'infNFe', $privateKeyPem, $x509CertificateBase64);
    }

    public function signByLocalName(
        DOMDocument $dom,
        string $localName,
        string $privateKeyPem,
        string $x509CertificateBase64,
    ): DOMDocument {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');

        /** @var DOMElement|null $target */
        $target = $xpath->query('//*[local-name()="'.$localName.'"]')->item(0);
        if (! $target instanceof DOMElement) {
            throw new FiscalEngineException("Nó {$localName} não encontrado para assinatura.");
        }

        $id = $target->getAttribute('Id');
        if ($id === '') {
            throw new FiscalEngineException("Atributo Id de {$localName} ausente.");
        }

        $c14n = $target->C14N(false, false);
        if ($c14n === false || $c14n === '') {
            throw new FiscalEngineException("Falha na canonicalização C14N de {$localName}.");
        }

        $digestValue = base64_encode(sha1($c14n, true));

        /** @var DOMElement $parent */
        $parent = $target->parentNode;

        $signature = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'Signature');
        $parent->appendChild($signature);

        $signedInfo = $this->appendSignedInfo($dom, $signature, $id, $digestValue);

        $signedInfoC14n = $signedInfo->C14N(false, false);
        if ($signedInfoC14n === false || $signedInfoC14n === '') {
            throw new FiscalEngineException('Falha na canonicalização C14N de SignedInfo.');
        }

        $pkey = openssl_pkey_get_private($privateKeyPem);
        if ($pkey === false) {
            throw new FiscalEngineException('Chave privada inválida para openssl_sign.');
        }

        $signatureBinary = '';
        $ok = openssl_sign($signedInfoC14n, $signatureBinary, $pkey, OPENSSL_ALGO_SHA1);
        if (! $ok) {
            throw new FiscalEngineException('Falha em openssl_sign (RSA-SHA1).');
        }

        $sigValueEl = $dom->createElement('SignatureValue', base64_encode($signatureBinary));
        $signature->appendChild($sigValueEl);

        $keyInfo = $dom->createElement('KeyInfo');
        $signature->appendChild($keyInfo);
        $x509Data = $dom->createElement('X509Data');
        $keyInfo->appendChild($x509Data);
        $x509Cert = $dom->createElement('X509Certificate', $x509CertificateBase64);
        $x509Data->appendChild($x509Cert);

        return $dom;
    }

    public function digestValueOfInfNFe(DOMDocument $dom): string
    {
        return $this->digestValueOf($dom, 'infNFe');
    }

    public function digestValueOf(DOMDocument $dom, string $localName = 'infNFe'): string
    {
        $xpath = new DOMXPath($dom);
        $node = $xpath->query('//*[local-name()="'.$localName.'"]')->item(0);
        if (! $node instanceof DOMElement) {
            throw new FiscalEngineException("Nó {$localName} não encontrado.");
        }
        $c14n = $node->C14N(false, false);

        return base64_encode(sha1($c14n, true));
    }

    /**
     * Extrai DigestValue já presente na Signature do XML assinado.
     */
    public function extractDigestValue(string $signedXml): string
    {
        if (preg_match('/<DigestValue>([^<]+)<\/DigestValue>/', $signedXml, $m)) {
            return $m[1];
        }

        throw new FiscalEngineException('DigestValue ausente no XML assinado.');
    }

    private function appendSignedInfo(
        DOMDocument $dom,
        DOMElement $signature,
        string $referenceUri,
        string $digestValue,
    ): DOMElement {
        $signedInfo = $dom->createElement('SignedInfo');
        $signature->appendChild($signedInfo);

        $c14nMethod = $dom->createElement('CanonicalizationMethod');
        $c14nMethod->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');
        $signedInfo->appendChild($c14nMethod);

        $sigMethod = $dom->createElement('SignatureMethod');
        $sigMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-sha1');
        $signedInfo->appendChild($sigMethod);

        $reference = $dom->createElement('Reference');
        $reference->setAttribute('URI', '#'.$referenceUri);
        $signedInfo->appendChild($reference);

        $transforms = $dom->createElement('Transforms');
        $reference->appendChild($transforms);

        $t1 = $dom->createElement('Transform');
        $t1->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');
        $transforms->appendChild($t1);

        $t2 = $dom->createElement('Transform');
        $t2->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');
        $transforms->appendChild($t2);

        $digestMethod = $dom->createElement('DigestMethod');
        $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');
        $reference->appendChild($digestMethod);

        $digestValueEl = $dom->createElement('DigestValue', $digestValue);
        $reference->appendChild($digestValueEl);

        return $signedInfo;
    }
}
