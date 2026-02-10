<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['general'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'info' => [
            'driver' => 'daily',
            'path' => storage_path('logs/info.log'),
            'level' => 'info', // Только info и выше (info, notice, warning, error, etc.)
            'days' => 14, // Хранить 14 дней
        ],

        // Каналы для API-логирования
        'general' => [
            'driver' => 'single',
            'path' => storage_path('logs/api/general.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        'creation' => [
            'driver' => 'single',
            'path' => storage_path('logs/api/creation.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        'update' => [
            'driver' => 'single',
            'path' => storage_path('logs/api/update.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        'deletion' => [
            'driver' => 'single',
            'path' => storage_path('logs/api/deletion.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        'errors' => [
            'driver' => 'single',
            'path' => storage_path('logs/api/errors.log'),
            'level' => 'error',
            'days' => 30,
        ],

        // Для масс удаления
        'mass-delete' => [
            'driver' => 'single',
            'path' => storage_path('logs/mass-delete.log'),
            'level' => 'info', // или 'debug' если нужно больше деталей
            'replace_placeholders' => true,
        ],

        // Для масс удаления
        'change-price' => [
            'driver' => 'single',
            'path' => storage_path('logs/change-price.log'),
            'level' => 'info', // или 'debug' если нужно больше деталей
            'replace_placeholders' => true,
        ],

        // Остальные стандартные каналы Laravel...
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
    ],

];
