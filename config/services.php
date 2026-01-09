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
    'nfse' => [
        'endpoint' => env('NFSE_ENDPOINT', 'https://sefin.producaorestrita.nfse.gov.br/SefinNacional/nfse'),
    ],
    'nfse_nacional' => [
        'url_sefin' => env('NFSE_NACIONAL_URL_SEFIN', 'https://sefin.producaorestrita.nfse.gov.br/SefinNacional/nfse'),
        'url_adn'   => env('NFSE_NACIONAL_URL_ADN', 'https://adn.producaorestrita.nfse.gov.br'),
        'url_consulta' => env('NFSE_NACIONAL_URL_CONSULTA_PUBLICA', 'https://www.nfse.gov.br/ConsultaPublica'),
    ],
];
