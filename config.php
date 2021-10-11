<?php

/**
 * This file is part of the PHP Telegram Bot example-bot package.
 * https://github.com/php-telegram-bot/example-bot/
 *
 * (c) PHP Telegram Bot Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This file contains all the configuration options for the PHP Telegram Bot.
 *
 * It is based on the configuration array of the PHP Telegram Bot Manager project.
 *
 * Simply adjust all the values that you need and extend where necessary.
 *
 * Options marked as [Manager Only] are only required if you use `manager.php`.
 *
 * For a full list of all options, check the Manager Readme:
 * https://github.com/php-telegram-bot/telegram-bot-manager#set-extra-bot-parameters
 */

return [
    // Add you bot's API key and name
    'api_key'      => '1756479939:AAE-fak2RNSViL3CxhNrEd9IJpvVi4uOo8k',
    'bot_username' => 'hookah_test_bot', // Without "@"

    // [Manager Only] Secret key required to access the webhook
    //'secret'       => 'super_secret',

    // When using the getUpdates method, this can be commented out
    'webhook'      => [
        'url' => 'https://159.224.124.220:8443/hook.php',
        //'url' => 'https://5e11-80-91-163-18.ngrok.io/hook.php',
        // Use self-signed certificate
        'certificate'     => __DIR__ . '\cert\public.pem',
        // Limit maximum number of connections
        // 'max_connections' => 5,
    ],

    // All command related configs go here
    'commands'     => [
        // Define all paths for your custom commands
        'paths'   => [
             __DIR__ . '/Commands',
        ],
        // Here you can set any command-specific parameters
        'configs' => [
            // - Google geocode/timezone API key for /date command (see DateCommand.php)
            // 'order'    => ['admin' => 792211523],
            // - OpenWeatherMap.org API key for /weather command (see WeatherCommand.php)
            // 'weather' => ['owm_api_key' => 'your_owm_api_key_here'],
            // - Payment Provider Token for /payment command (see Payments/PaymentCommand.php)
            // 'payment' => ['payment_provider_token' => 'your_payment_provider_token_here'],
        ],
    ],

    // Define all IDs of admin users
    'admins'       => [
        792211523,
    ],

    // Enter your MySQL database credentials
    'mysql'        => [
         'host'     => 'localhost',
         'user'     => 'root',
         'password' => '',
         'database' => 'hookah_test',
     ],

    // Logging (Debug, Error and Raw Updates)
     'logging'  => [
         'debug'  => __DIR__ . '/php-telegram-bot-debug.log',
         'error'  => __DIR__ . '/php-telegram-bot-error.log',
         'update' => __DIR__ . '/php-telegram-bot-update.log',
     ],

    // Set custom Upload and Download paths
    'paths'        => [
        'download' => __DIR__ . '/Download',
        'upload'   => __DIR__ . '/Upload',
    ],

    // Requests Limiter (tries to prevent reaching Telegram API limits)
    'limiter'      => [
        'enabled' => true,
    ],
];