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

    /*
|--------------------------------------------------------------------------
| M-PESA Payment Gateway
|--------------------------------------------------------------------------
*/
    'mpesa' => [
        'env' => env('MPESA_ENV', 'sandbox'),
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'passkey' => env('MPESA_PASSKEY'),
        'shortcode' => env('MPESA_SHORTCODE'),
        'shortcode_type' => env('MPESA_SHORTCODE_TYPE', 'Paybill'),
        'initiator_name' => env('MPESA_INITIATOR_NAME'),
        'initiator_password' => env('MPESA_INITIATOR_PASSWORD', '@Matiku!=43560'),
        'b2c_shortcode' => env('MPESA_B2C_SHORTCODE'),
        'b2c_command_id' => env('MPESA_B2C_COMMAND_ID', 'BusinessPayment'),
        'b2c_security_credential' => env('MPESA_B2C_SECURITY_CREDENTIAL'),
        'callback_url' => env('MPESA_CALLBACK_URL'),
        'confirmation_url' => env('MPESA_CONFIRMATION_URL'),
        'validation_url' => env('MPESA_VALIDATION_URL'),
        'result_url' => env('MPESA_RESULT_URL'),
        'queue_timeout_url' => env('MPESA_QUEUE_TIMEOUT_URL'),
        'version' => env('MPESA_C2B_VERSION', 'v1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    'socialite' => [
        'redirect_frontend' => env('SOCIALITE_REDIRECT_FRONTEND', 'http://localhost:3000/auth/social/callback'),
    ],

    // App variables
    'system_variables' => [
        'phone_unlock_cost' => env('PHONE_UNLOCK_COST', 10),
        'message_cost' => env('MESSAGE_COST', 1),
    ],

];
