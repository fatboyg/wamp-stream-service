<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


Route::get('/', function () {
    return view('home');
});


$router->get('/robots.txt', function (){
    echo "User-agent: * \n\r";
    echo "Disallow: /";
});

$router->group(
    [
        'namespace'  => 'V1',
        'prefix'     => 'v1',
        'middleware' => [\App\Http\Middleware\AuthenticateOnceWithBasicAuth::class]
    ],
    function () use ($router) {
        $router->get('notifications/stats', 'NotificationController@stats');
        $router->post('notifications/createBroadcast', 'NotificationController@createBroadcast');
        $router->post('notifications/create', 'NotificationController@create');


    });
