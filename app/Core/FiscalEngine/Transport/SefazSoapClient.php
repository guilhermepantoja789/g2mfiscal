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
        private readonly ?bool $sslVerify = null,
        private readonly ?string $caFile = null,
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
        // TIdLote: 1–15 dígitos. YmdHis(14)+3 estoura o schema → cStat 215.
        $idLote = date('YmdHis').(string) random_int(0, 9);

        $enviNFe = '<enviNFe xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
            .'<idLote>'.$idLote.'</idLote>'
            .'<indSinc>1</indSinc>'
            .$nfeXml
            .'</enviNFe>';

        $soap = $this->wrapSoap(
            $enviNFe,
            'http://www.portalfiscal.inf.br/nfe/wsdl/NfeAutorizacao',
            $cUF,
        );
        $url = $this->endpoints->autorizacao($profile);
        $response = $this->post(
            $url,
            $soap,
            $certPemPath,
            $keyPemPath,
            'http://www.portalfiscal.inf.br/nfe/wsdl/NfeAutorizacao/nfeAutorizacaoLote',
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
        $cons = '<consStatServ xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
            .'<tpAmb>'.$tpAmb.'</tpAmb>'
            .'<cUF>'.$cUF.'</cUF>'
            .'<xServ>STATUS</xServ>'
            .'</consStatServ>';

        $soap = $this->wrapSoap(
            $cons,
            'http://www.portalfiscal.inf.br/nfe/wsdl/NfeStatusServico2',
            $cUF,
        );
        $url = $this->endpoints->status($profile);
        $response = $this->post(
            $url,
            $soap,
            $certPemPath,
            $keyPemPath,
            'http://www.portalfiscal.inf.br/nfe/wsdl/NfeStatusServico2/nfeStatusServicoNF2',
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
        $cUF = strlen($chave) >= 2 ? substr($chave, 0, 2) : '13';
        $cons = '<consSitNFe xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">'
            .'<tpAmb>'.$tpAmb.'</tpAmb>'
            .'<xServ>CONSULTAR</xServ>'
            .'<chNFe>'.$chave.'</chNFe>'
            .'</consSitNFe>';

        $soap = $this->wrapSoap(
            $cons,
            'http://www.portalfiscal.inf.br/nfe/wsdl/NfeConsulta2',
            $cUF,
        );
        $url = $this->endpoints->consulta($profile);
        $response = $this->post(
            $url,
            $soap,
            $certPemPath,
            $keyPemPath,
            'http://www.portalfiscal.inf.br/nfe/wsdl/NfeConsulta2/nfeConsultaNF2',
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

    /**
     * Envia evento (cancelamento 110111) via RecepcaoEvento.
     *
     * @return array{cStat: string, xMotivo: string, protocolo: ?string, xml_retorno: string}
     */
    public function enviarEvento(
        string $signedEventoXml,
        string $certPemPath,
        string $keyPemPath,
        string $cUF = '13',
        ?string $profile = null,
    ): array {
        $eventoXml = $this->extractTagOuter($signedEventoXml, 'evento');
        if ($eventoXml === null) {
            throw new SefazTransportException('XML de evento assinado sem tag evento.');
        }

        $idLote = substr((string) time(), -15);
        $envEvento = '<envEvento xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.00">'
            .'<idLote>'.$idLote.'</idLote>'
            .$eventoXml
            .'</envEvento>';

        $soap = $this->wrapSoap(
            $envEvento,
            'http://www.portalfiscal.inf.br/nfe/wsdl/RecepcaoEvento',
            $cUF,
            '1.00',
        );
        $url = $this->endpoints->evento($profile);
        $response = $this->post(
            $url,
            $soap,
            $certPemPath,
            $keyPemPath,
            'http://www.portalfiscal.inf.br/nfe/wsdl/RecepcaoEvento/nfeRecepcaoEvento',
        );

        return $this->parseEventoResponse($response);
    }

    /**
     * Inutilização de faixa de numeração.
     *
     * @return array{cStat: string, xMotivo: string, protocolo: ?string, xml_retorno: string}
     */
    public function inutilizar(
        string $signedInutXml,
        string $certPemPath,
        string $keyPemPath,
        string $cUF = '13',
        ?string $profile = null,
    ): array {
        $inutXml = $this->extractTagOuter($signedInutXml, 'inutNFe');
        if ($inutXml === null) {
            throw new SefazTransportException('XML de inutilização assinado sem tag inutNFe.');
        }

        $soap = $this->wrapSoap(
            $inutXml,
            'http://www.portalfiscal.inf.br/nfe/wsdl/NfeInutilizacao2',
            $cUF,
        );
        $url = $this->endpoints->inutilizacao($profile);
        $response = $this->post(
            $url,
            $soap,
            $certPemPath,
            $keyPemPath,
            'http://www.portalfiscal.inf.br/nfe/wsdl/NfeInutilizacao2/nfeInutilizacaoNF2',
        );

        return $this->parseInutilizacaoResponse($response);
    }

    /**
     * @return array{cStat: string, xMotivo: string, protocolo: ?string, xml_retorno: string}
     */
    private function parseEventoResponse(string $response): array
    {
        $cStat = '';
        if (preg_match('/<infEvento[\s\S]*?<cStat>(\d+)<\/cStat>/', $response, $m)) {
            $cStat = $m[1];
        } else {
            $cStat = $this->firstTag($response, 'cStat') ?? '';
        }

        $xMotivo = 'Retorno SEFAZ sem xMotivo';
        if (preg_match('/<infEvento[\s\S]*?<xMotivo>([^<]+)<\/xMotivo>/', $response, $m)) {
            $xMotivo = html_entity_decode($m[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
        } else {
            $xMotivo = $this->firstTag($response, 'xMotivo') ?? $xMotivo;
        }

        $protocolo = null;
        if (preg_match('/<nProt>([^<]+)<\/nProt>/', $response, $m)) {
            $protocolo = $m[1];
        }

        if ($cStat === '') {
            throw new SefazTransportException('Retorno de evento sem cStat interpretável.');
        }

        // 135 = Evento registrado e vinculado a NF-e
        if (! in_array($cStat, ['135', '155'], true)) {
            throw new SefazRejectionException($cStat, $xMotivo);
        }

        return [
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'protocolo' => $protocolo,
            'xml_retorno' => $response,
        ];
    }

    /**
     * @return array{cStat: string, xMotivo: string, protocolo: ?string, xml_retorno: string}
     */
    private function parseInutilizacaoResponse(string $response): array
    {
        $cStat = '';
        if (preg_match('/<infInut[\s\S]*?<cStat>(\d+)<\/cStat>/', $response, $m)) {
            $cStat = $m[1];
        } else {
            $cStat = $this->firstTag($response, 'cStat') ?? '';
        }

        $xMotivo = 'Retorno SEFAZ sem xMotivo';
        if (preg_match('/<infInut[\s\S]*?<xMotivo>([^<]+)<\/xMotivo>/', $response, $m)) {
            $xMotivo = html_entity_decode($m[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
        } else {
            $xMotivo = $this->firstTag($response, 'xMotivo') ?? $xMotivo;
        }

        $protocolo = null;
        if (preg_match('/<nProt>([^<]+)<\/nProt>/', $response, $m)) {
            $protocolo = $m[1];
        }

        if ($cStat === '') {
            throw new SefazTransportException('Retorno de inutilização sem cStat interpretável.');
        }

        // 102 = Inutilização de número homologado
        if ($cStat !== '102') {
            throw new SefazRejectionException($cStat, $xMotivo);
        }

        return [
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'protocolo' => $protocolo,
            'xml_retorno' => $response,
        ];
    }

    /**
     * Envelope SOAP 1.2 no padrão SEFAZ-AM (namespace *2 / NfeAutorizacao + nfeCabecMsg).
     */
    private function wrapSoap(
        string $dadosMsg,
        string $wsdlNamespace,
        string $cUF = '13',
        string $versaoDados = '4.00',
    ): string {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            .' xmlns:xsd="http://www.w3.org/2001/XMLSchema"'
            .' xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'
            .'<soap12:Header>'
            .'<nfeCabecMsg xmlns="'.$wsdlNamespace.'">'
            .'<cUF>'.$cUF.'</cUF>'
            .'<versaoDados>'.$versaoDados.'</versaoDados>'
            .'</nfeCabecMsg>'
            .'</soap12:Header>'
            .'<soap12:Body>'
            .'<nfeDadosMsg xmlns="'.$wsdlNamespace.'">'
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

        $verifyPeer = $this->sslVerify ?? (bool) config('nfce.ssl_verify', false);
        $caFile = $this->caFile ?? config('nfce.ssl_cafile');

        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/soap+xml; charset=utf-8; action="'.$soapAction.'"',
                'Content-Length: '.strlen($body),
            ],
            CURLOPT_SSLCERT => $certPemPath,
            CURLOPT_SSLKEY => $keyPemPath,
            // SEFAZ-AM/ICP-Brasil frequentemente falha no CA store do macOS/Herd (errno 60).
            CURLOPT_SSL_VERIFYPEER => $verifyPeer,
            CURLOPT_SSL_VERIFYHOST => $verifyPeer ? 2 : 0,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
        ];

        if (is_string($caFile) && $caFile !== '' && is_readable($caFile)) {
            $opts[CURLOPT_CAINFO] = $caFile;
        }

        curl_setopt_array($ch, $opts);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $response === false) {
            throw new SefazTransportException('Falha de transporte cURL/mTLS: '.$error.' (errno '.$errno.')');
        }

        if ($httpCode >= 500 || $httpCode === 0) {
            $fault = '';
            if (is_string($response) && preg_match('/<soapenv:Text[^>]*>([^<]+)/', $response, $m)) {
                $fault = ' — '.html_entity_decode($m[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
            } elseif (is_string($response) && preg_match('/<faultstring[^>]*>([^<]+)/', $response, $m)) {
                $fault = ' — '.html_entity_decode($m[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
            throw new SefazTransportException('SEFAZ HTTP '.$httpCode.' no webservice.'.$fault);
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
