<?php

use Illuminate\Support\Facades\Log;
use Monolog\Logger;


$config['logging'] = [
    'loglevel' => (env('APP_ENV') !== 'production') ? Logger::DEBUG : Logger::WARNING,
    'component' => env('LOG_FACILITY', 'wamp-server'),
   // 'logger' => function() { return Log::getFacadeRoot(); }
];
// internal router client auth
$config['auth'] = [
    'internal_realm' => env('INTERNAL_DOMAIN', 'randomPass'),
    'internal_realm_password' => env('INTERNAL_DOMAIN_PASSWORD', 'letMeIn'),
    'class' => \App\Libraries\WampServer\Thruway\DummyAuthenticator::class // use dummy for dev if needed
];

// wamp server
$config['server'] = [
    'host' => env('WAMP_HOST', '0.0.0.0'), // listen host
    'port' => env('WAMP_PORT', 9901), // listen port
    'auth' => env('WAMP_USE_AUTH', true), //enable api authentication mechanism
    'front_end_realm' =>  env('PUBLIC_DOMAIN', 'web'), //frontend domain where to broadcast messages
    'main_topic' => env('PUBLIC_DOMAIN_TOPIC', 'com.robits.notifications'), // main topic on the frontend domain where to push messages
  //  'max_connections' => 2,   // how many sessions to handle
];

return $config;