<?php

namespace App\Console\Commands;

use App\Libraries\Worker;

class MessageWorkCommand extends \Illuminate\Queue\Console\WorkCommand
{

    protected $signature = 'wamp:queue:work
                            {connection? : The name of the queue connection to work}
                            {--queue=wamp : The names of the queues to work}
                            {--daemon : Run the worker in daemon mode (Deprecated)}
                            {--once : Only process the next job on the queue}
                            {--stop-when-empty : Stop when the queue is empty}
                            {--delay=0 : The number of seconds to delay failed jobs}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--sleep=2 : Number of seconds to sleep when no job is available}
                            {--timeout=10 : The number of seconds a child process can run}
                            {--tries=2 : Number of times to attempt a job before logging it failed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start processing wamp server messages on the queue as a daemon';

    /**
     * The queue worker instance.
     *
     * @var Worker
     */
    protected $worker;

    /**
     * Create a new queue listen command.
     *
     * @param  \App\Libraries\Worker  $worker
     * @return void
     */
    public function __construct(Worker $worker)
    {
        parent::__construct($worker);
    }
}
