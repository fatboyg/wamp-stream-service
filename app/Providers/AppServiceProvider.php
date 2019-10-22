<?php

namespace App\Providers;

use App\Libraries\InternalWampClient\Client;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Client::class, function ($app) {
            app()->configure('wamp-client');
            return new Client(config('wamp-client'));
        });
    }

    public function provides()
    {
        return [Client::class];
    }
}
