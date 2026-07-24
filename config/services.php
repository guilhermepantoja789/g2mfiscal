<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'financeiro' => [
        // Gateway Asaas / cobranças (convive com P/R gerencial)
        'enabled' => filter_var(env('FEATURE_FINANCEIRO', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'nfse' => [
        'endpoint' => env('NFSE_ENDPOINT'), // legado; preferir nfse_nacional
    ],
    'nfse_nacional' => [
        // 1 = produção | 2 = homologação (produção restrita). Null = deriva de APP_ENV.
        'tp_amb' => env('NFSE_NACIONAL_TP_AMB'),

        // Overrides opcionais (se vazios, usa urls conforme tp_amb).
        'url_sefin' => env('NFSE_NACIONAL_URL_SEFIN'),
        'url_adn' => env('NFSE_NACIONAL_URL_ADN'),
        'url_consulta_api' => env('NFSE_NACIONAL_URL_CONSULTA_API'),
        'url_consulta' => env('NFSE_NACIONAL_URL_CONSULTA_PUBLICA', 'https://www.nfse.gov.br/ConsultaPublica'),

        'urls' => [
            'producao' => [
                'sefin' => 'https://sefin.nfse.gov.br/SefinNacional/nfse',
                'adn' => 'https://adn.nfse.gov.br',
                'consulta' => 'https://api.nfse.gov.br/nfse/v1/nfse',
            ],
            'homologacao' => [
                'sefin' => 'https://sefin.producaorestrita.nfse.gov.br/SefinNacional/nfse',
                'adn' => 'https://adn.producaorestrita.nfse.gov.br',
                'consulta' => 'https://sefin.producaorestrita.nfse.gov.br/SefinNacional/nfse',
            ],
        ],
    ],
];
