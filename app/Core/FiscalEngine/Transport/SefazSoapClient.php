<?php

namespace App\Core\FiscalEngine\Transport;

use App\Core\FiscalEngine\Exceptions\SefazRejectionException;
use App\Core\FiscalEngine\Exceptions\SefazTransportException;

/**
 * Cliente SOAP + mTLS via cURL nativo para SEFAZ NFC-e.
 */
class SefazSoapClient
{
    public function __construct(
        private readonly SefazEndpoints $endpoints = new SefazEndpoints,
        private readonly int $timeout = 60,
        private readonly int $connectTimeout = 20,
    ) {}

    /**
     * @return array{
     *     cStat: string,
     *     xMotivo: string,
     *     protocolo: ?string,
     *     xml_retorno: string,
     *     nfeProc: ?string,
     *     autorizado: bool
     * }
     */
    public function autorizar(
        string $signedNFeXml,
        string $certPemPath,
        string $keyPemPath,
        int $tpAmb,
        string $cUF = '13',
        ?string $profile = null,
    ): array {
        $nfeXml = $this->extractNFe($signedNFeXml);
        $idLote = date('YmdHis').random_int(100, 999);

        $enviNFe = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<enviNFe xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
            .'<idLote>'.$idLote.'</idLote>'
            .'<indSinc>1</indSinc>'
            .$nfeXml
            .'</enviNFe>';

        $soap = $this->wrapSoap($enviNFe, 'NFeAutorizacao4');
        $url = $this->endpoints->autorizacao($profile);
        $response = $this->post(
            $url,
            $soap,
            $certPemPath,
            $keyPemPath,
            'http://www.portalfiscal.inf.br/nfe/wsdl/NFeAutorizacao4/nfeAutorizacaoLote',
        );

        return $this->parseAutorizacaoResponse($response, $nfeXml);
    }

    /**
     * @return array{cStat: string, xMotivo: string, xml_retorno: string}
     */
    public function statusServico(
        string $certPemPath,
        string $keyPemPath,
        int $tpAmb,
        string $cUF = '13',
        ?string $profile = null,
    ): array {
        $cons = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<consStatServ xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
            .'<tpAmb>'.$tpAmb.'</tpAmb>'
            .'<cUF>'.$cUF.'</cUF>'
            .'<xServ>STATUS</xServ>'
            .'</consStatServ>';

        $soap = $this->wrapSoap($cons, 'NFeStatusServico4');
        $url = $this->endpoints->status($profile);
        $response = $this->post(
            $url,
            $soap,
            $certPemPath,
            $keyPemPath,
            'http://www.portalfiscal.inf.br/nfe/wsdl/NFeStatusServico4/nfeStatusServicoNF',
        );

        return [
            'cStat' => $this->firstTag($response, 'cStat') ?? '',
            'xMotivo' => $this->firstTag($response, 'xMotivo') ?? '',
            'xml_retorno' => $response,
        ];
    }

    /**
     * Consulta protocolo pela chave (idempotência pós-timeout).
     *
     * @return array{
     *     cStat: string,
     *     xMotivo: string,
     *     protocolo: ?string,
     *     xml_retorno: string,
     *     nfeProc: ?string,
     *     autorizado: bool
     * }
     */
    public function consultar(
        string $chave,
        string $certPemPath,
        string $keyPemPath,
        int $tpAmb,
        string $signedNFeXml = '',
        ?string $profile = null,
    ): array {
        $chave = preg_replace('/\D/', '', $chave) ?? '';
        $cons = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<consSitNFe xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
            .'<tpAmb>'.$tpAmb.'</tpAmb>'
            .'<xServ>CONSULTAR</xServ>'
            .'<chNFe>'.$chave.'</chNFe>'
            .'</consSitNFe>';

        $soap = $this->wrapSoap($cons, 'NFeConsultaProtocolo4');
        $url = $this->endpoints->consulta($profile);
        $response = $this->post(
            $url,
            $soap,
            $certPemPath,
            $keyPemPath,
            'http://www.portalfiscal.inf.br/nfe/wsdl/NFeConsultaProtocolo4/nfeConsultaNF',
        );

        $cStat = '';
        if (preg_match('/<infProt[\s\S]*?<cStat>(\d+)<\/cStat>/', $response, $m)) {
            $cStat = $m[1];
        } else {
            $cStat = $this->firstTag($response, 'cStat') ?? '';
        }

        $xMotivo = 'Consulta sem xMotivo';
        if (preg_match('/<infProt[\s\S]*?<xMotivo>([^<]+)<\/xMotivo>/', $response, $m)) {
            $xMotivo = html_entity_decode($m[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
        } else {
            $xMotivo = $this->firstTag($response, 'xMotivo') ?? $xMotivo;
        }

        $protocolo = null;
        if (preg_match('/<nProt>([^<]+)<\/nProt>/', $response, $m)) {
            $protocolo = $m[1];
        }

        $autorizado = $cStat === '100';
        $nfeProc = null;
        if ($autorizado && $signedNFeXml !== '') {
            $nfeXml = $this->extractNFe($signedNFeXml);
            $protNFe = $this->extractTagOuter($response, 'protNFe');
            if ($protNFe !== null) {
                $nfeProc = '<?xml version="1.0" encoding="UTF-8"?>'
                    .'<nfeProc xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
                    .$nfeXml
                    .$protNFe
                    .'</nfeProc>';
            }
        }

        return [
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'protocolo' => $protocolo,
            'xml_retorno' => $response,
            'nfeProc' => $nfeProc,
            'autorizado' => $autorizado,
        ];
    }

    private function wrapSoap(string $dadosMsg, string $service): string
    {
        $ns = 'http://www.portalfiscal.inf.br/nfe/wsdl/'.$service;

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            .' xmlns:xsd="http://www.w3.org/2001/XMLSchema"'
            .' xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'
            .'<soap12:Body>'
            .'<nfeDadosMsg xmlns="'.$ns.'">'
            .$dadosMsg
            .'</nfeDadosMsg>'
            .'</soap12:Body>'
            .'</soap12:Envelope>';
    }

    private function post(
        string $url,
        string $body,
        string $certPemPath,
        string $keyPemPath,
        string $soapAction,
    ): string {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new SefazTransportException('Falha ao iniciar cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/soap+xml; charset=utf-8; action="'.$soapAction.'"',
                'Content-Length: '.strlen($body),
            ],
            CURLOPT_SSLCERT => $certPemPath,
            CURLOPT_SSLKEY => $keyPemPath,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
        ]);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $response === false) {
            throw new SefazTransportException('Falha de transporte cURL/mTLS: '.$error.' (errno '.$errno.')');
        }

        if ($httpCode >= 500 || $httpCode === 0) {
            throw new SefazTransportException('SEFAZ HTTP '.$httpCode.' no webservice.');
        }

        return html_entity_decode((string) $response, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * @return array{
     *     cStat: string,
     *     xMotivo: string,
     *     protocolo: ?string,
     *     xml_retorno: string,
     *     nfeProc: ?string,
     *     autorizado: bool
     * }
     */
    private function parseAutorizacaoResponse(string $response, string $nfeXml): array
    {
        $cStat = '';
        $xMotivo = 'Retorno SEFAZ sem xMotivo';

        if (preg_match('/<infProt[\s\S]*?<cStat>(\d+)<\/cStat>/', $response, $m)) {
            $cStat = $m[1];
        } else {
            $cStat = $this->firstTag($response, 'cStat') ?? '';
        }

        if (preg_match('/<infProt[\s\S]*?<xMotivo>([^<]+)<\/xMotivo>/', $response, $m)) {
            $xMotivo = html_entity_decode($m[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
        } else {
            $xMotivo = $this->firstTag($response, 'xMotivo') ?? $xMotivo;
        }

        $protocolo = null;
        if (preg_match('/<nProt>([^<]+)<\/nProt>/', $response, $m)) {
            $protocolo = $m[1];
        }

        if ($cStat === '') {
            throw new SefazTransportException('Retorno SEFAZ sem cStat interpretável.');
        }

        if ($cStat !== '100') {
            throw new SefazRejectionException($cStat, $xMotivo);
        }

        $protNFe = $this->extractTagOuter($response, 'protNFe');
        if ($protNFe === null) {
            throw new SefazTransportException('Autorizado sem protNFe no retorno.');
        }

        $nfeProc = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<nfeProc xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
            .$nfeXml
            .$protNFe
            .'</nfeProc>';

        return [
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'protocolo' => $protocolo,
            'xml_retorno' => $response,
            'nfeProc' => $nfeProc,
            'autorizado' => true,
        ];
    }

    private function extractNFe(string $xml): string
    {
        $outer = $this->extractTagOuter($xml, 'NFe');
        if ($outer === null) {
            throw new SefazTransportException('XML assinado sem tag NFe.');
        }

        return $outer;
    }

    private function extractTagOuter(string $xml, string $tag): ?string
    {
        if (preg_match('/<'.$tag.'\b[\s\S]*?<\/'.$tag.'>/', $xml, $m)) {
            return $m[0];
        }

        return null;
    }

    private function firstTag(string $xml, string $tag): ?string
    {
        if (preg_match('/<'.$tag.'(?:\s[^>]*)?>([^<]*)<\/'.$tag.'>/', $xml, $m)) {
            return trim($m[1]);
        }

        return null;
    }
}
