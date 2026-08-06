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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    // DocuMind: motor RAG externo. IUDocs le sincroniza los materiales (ingesta)
    // y consulta el chat, autenticándose como servicio con la X-Service-Key.
    'documind' => [
        'enabled' => env('DOCUMIND_ENABLED', false),
        'url' => env('DOCUMIND_URL'),
        'service_key' => env('DOCUMIND_SERVICE_KEY'),
        'timeout' => (int) env('DOCUMIND_TIMEOUT', 30),
        // Límite de subida de DocuMind (MB): archivos más grandes se marcan 'skipped'.
        'max_mb' => (int) env('DOCUMIND_MAX_MB', 20),
        // Extensiones que DocuMind sabe procesar (PDF/DOCX/TXT/MD, sin OCR).
        'extensions' => ['pdf', 'txt', 'md', 'docx'],
    ],

];
