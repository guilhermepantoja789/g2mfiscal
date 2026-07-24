<?php

return [

    'driver' => env('FISCAL_NFCE_DRIVER', 'raw_native'),

    'uf' => 'AM',
    'cUF' => '13',
    'mod' => '65',
    'versao' => '4.00',
    'qrcode_version' => env('NFCE_QRCODE_VERSION', '2'),

    /*
    |--------------------------------------------------------------------------
    | Perfil de endpoint SEFAZ-AM
    | homolog | homolog_nac | producao
    |--------------------------------------------------------------------------
    */
    'endpoint_profile' => env('NFCE_ENDPOINT_PROFILE', 'homolog_nac'),

    'urls' => [
        'homolog' => [
            'autorizacao' => 'https://homnfce.sefaz.am.gov.br/nfce-services/services/NfeAutorizacao',
            'retorno_autorizacao' => 'https://homnfce.sefaz.am.gov.br/nfce-services/services/NfeRetAutorizacao',
            'consulta' => 'https://homnfce.sefaz.am.gov.br/nfce-services/services/NfeConsulta2',
            'evento' => 'https://homnfce.sefaz.am.gov.br/nfce-services/services/RecepcaoEvento',
            'status' => 'https://homnfce.sefaz.am.gov.br/nfce-services/services/NfeStatusServico2',
            'inutilizacao' => 'https://homnfce.sefaz.am.gov.br/nfce-services/services/NfeInutilizacao2',
            'qrcode' => 'https://sistemas.sefaz.am.gov.br/nfceweb-hom/consultarNFCe.jsp',
            'consulta_chave' => 'https://sistemas.sefaz.am.gov.br/nfceweb-hom/formConsulta.do',
        ],
        'homolog_nac' => [
            'autorizacao' => 'https://homnfce.sefaz.am.gov.br/nfce-services-nac/services/NfeAutorizacao',
            'retorno_autorizacao' => 'https://homnfce.sefaz.am.gov.br/nfce-services-nac/services/NfeRetAutorizacao',
            'consulta' => 'https://homnfce.sefaz.am.gov.br/nfce-services-nac/services/NfeConsulta2',
            'evento' => 'https://homnfce.sefaz.am.gov.br/nfce-services-nac/services/RecepcaoEvento',
            'status' => 'https://homnfce.sefaz.am.gov.br/nfce-services-nac/services/NfeStatusServico2',
            'inutilizacao' => 'https://homnfce.sefaz.am.gov.br/nfce-services-nac/services/NfeInutilizacao2',
            'qrcode' => 'https://sistemas.sefaz.am.gov.br/nfceweb-hom/consultarNFCe.jsp',
            'consulta_chave' => 'https://sistemas.sefaz.am.gov.br/nfceweb-hom/formConsulta.do',
        ],
        'producao' => [
            'autorizacao' => 'https://nfce.sefaz.am.gov.br/nfce-services/services/NfeAutorizacao',
            'retorno_autorizacao' => 'https://nfce.sefaz.am.gov.br/nfce-services/services/NfeRetAutorizacao',
            'consulta' => 'https://nfce.sefaz.am.gov.br/nfce-services/services/NfeConsulta2',
            'evento' => 'https://nfce.sefaz.am.gov.br/nfce-services/services/RecepcaoEvento',
            'status' => 'https://nfce.sefaz.am.gov.br/nfce-services/services/NfeStatusServico2',
            'inutilizacao' => 'https://nfce.sefaz.am.gov.br/nfce-services/services/NfeInutilizacao2',
            'qrcode' => 'https://sistemas.sefaz.am.gov.br/nfceweb/consultarNFCe.jsp',
            'consulta_chave' => 'https://sistemas.sefaz.am.gov.br/nfceweb/formConsulta.do',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cancelamento NFC-e
    |--------------------------------------------------------------------------
    | Prazo típico AM/MOC: 30 minutos após autorização.
    */
    'cancelamento_prazo_minutos' => (int) env('NFCE_CANCELAMENTO_PRAZO_MINUTOS', 30),

    'soap' => [
        'timeout' => (int) env('NFCE_SOAP_TIMEOUT', 60),
        'connect_timeout' => (int) env('NFCE_SOAP_CONNECT_TIMEOUT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | TLS / mTLS
    |--------------------------------------------------------------------------
    | SEFAZ-AM usa cadeia ICP-Brasil; o CA store do macOS/Herd costuma falhar
    | com "self-signed certificate in certificate chain" (cURL errno 60).
    | Em local/homolog o padrão é false. Em produção, prefira true + CA ICP-Brasil.
    */
    'ssl_verify' => filter_var(env('NFCE_SSL_VERIFY', false), FILTER_VALIDATE_BOOLEAN),
    'ssl_cafile' => env('NFCE_SSL_CAFILE'),
];
