<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
        ],

        'single' => [
            'driver' => 'single',
            'path' => env('LOGS_DIR', storage_path('logs/')).'/'.'stream-meta-service' . ((strpos(php_sapi_name(), 'cli') !== false) ? '-cli' : '').'.log',
            'level' => 'debug',
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => env('LOGS_DIR', storage_path('logs/')) .DIRECTORY_SEPARATOR .'stream-meta-' . env('LOG_FACILITY', 'service') . '-' .  gethostname ().  ((strpos(php_sapi_name(), 'cli') !== false) ? '-cli' : '').'.log',
            'level' => (env('APP_ENV') !== 'production') ? 'debug' : \Monolog\Logger::WARNING,
            'facility' => env('LOG_FACILITY', 'stream-meta'),
            'days' => 2,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Lumen Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
        ],
    ],

];
