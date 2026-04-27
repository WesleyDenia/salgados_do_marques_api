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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'vendus' => [
        'base_url' => env('VENDUS_BASE_URL', 'https://www.vendus.pt/ws/v1.1'),
        'token' => env('VENDUS_API_KEY'), 
    ],
    'wapify' => [
        'base_url' => env('WAPIFY_BASE_URL', 'https://app.wapify.net/api/text-message.php'),
        'instance' => env('WAPIFY_INSTANCE'),
        'api_key' => env('WAPIFY_API_KEY'),
        'verify_ssl' => env('WAPIFY_VERIFY_SSL', true),
    ],
    'whatsapp' => [
        'base_url' => env('WHATSAPP_BASE_URL', 'http://127.0.0.1:3000'),
        'internal_token' => env('WHATSAPP_INTERNAL_TOKEN'),
        'verify_ssl' => env('WHATSAPP_VERIFY_SSL', true),
        'timeout' => env('WHATSAPP_TIMEOUT', 10),
    ],
    'google_places' => [
        'base_url' => env('GOOGLE_PLACES_BASE_URL', 'https://places.googleapis.com/v1'),
        'api_key' => env('GOOGLE_PLACES_API_KEY'),
        'place_id' => env('GOOGLE_PLACES_PLACE_ID'),
        'language_code' => env('GOOGLE_PLACES_LANGUAGE_CODE', 'pt-PT'),
        'region_code' => env('GOOGLE_PLACES_REGION_CODE', 'PT'),
        'minimum_rating' => (int) env('GOOGLE_PLACES_MINIMUM_REVIEW_RATING', 4),
        'limit' => (int) env('GOOGLE_PLACES_REVIEW_LIMIT', 4),
        'cache_hours' => (int) env('GOOGLE_PLACES_CACHE_HOURS', 6),
    ],


];
