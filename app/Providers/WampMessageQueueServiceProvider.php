<?php

namespace App\Providers;

use App\Libraries\Worker;

use Illuminate\Contracts\Debug\ExceptionHandler;

class WampMessageQueueServiceProvider extends \Illuminate\Queue\QueueServiceProvider
{

    /**
     * Register the queue worker.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Worker::class, function () {
            return new Worker(
                $this->app['queue'], $this->app['events'], $this->app[ExceptionHandler::class]
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [Worker::class];
    }
}
