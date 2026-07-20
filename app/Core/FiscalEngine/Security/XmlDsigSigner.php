<?php

namespace App\Core\FiscalEngine\Security;

use App\Core\FiscalEngine\Exceptions\FiscalEngineException;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Assinatura XMLDSIG (RSA-SHA1 + C14N) da tag infNFe — PHP nativo.
 */
class XmlDsigSigner
{
    public function sign(DOMDocument $dom, string $privateKeyPem, string $x509CertificateBase64): DOMDocument
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');

        /** @var DOMElement|null $infNFe */
        $infNFe = $xpath->query('//*[local-name()="infNFe"]')->item(0);
        if (! $infNFe instanceof DOMElement) {
            throw new FiscalEngineException('Nó infNFe não encontrado para assinatura.');
        }

        $id = $infNFe->getAttribute('Id');
        if ($id === '') {
            throw new FiscalEngineException('Atributo Id de infNFe ausente.');
        }

        $c14n = $infNFe->C14N(false, false);
        if ($c14n === false || $c14n === '') {
            throw new FiscalEngineException('Falha na canonicalização C14N de infNFe.');
        }

        $digestValue = base64_encode(sha1($c14n, true));

        /** @var DOMElement $nfe */
        $nfe = $infNFe->parentNode;

        $signature = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'Signature');
        $nfe->appendChild($signature);

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
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
        $infNFe = $xpath->query('//*[local-name()="infNFe"]')->item(0);
        if (! $infNFe instanceof DOMElement) {
            throw new FiscalEngineException('Nó infNFe não encontrado.');
        }
        $c14n = $infNFe->C14N(false, false);

        return base64_encode(sha1($c14n, true));
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
