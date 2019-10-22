<?php

return [
    'dsn' => env('WS_ENDPOINT','ws://wamp-server:9901'),
    'timeout' => 10,
    'internal_realm' =>  env('INTERNAL_DOMAIN', 'randomPass'), //admin/secret domain
    "internal_realm_password" => env('INTERNAL_DOMAIN_PASSWORD', 'letMeIn'),
    'frontend_realm' => env('PUBLIC_DOMAIN', 'web'),
    'notifications_topic' => env('PUBLIC_DOMAIN_TOPIC', 'com.robits.notifications')
];