<?php

namespace App\Libraries;

use App\Libraries\InternalWampClient\Client;
use Exception;
use Illuminate\Queue\WorkerOptions;
use Thruway\ClientSession;

class Worker extends \Illuminate\Queue\Worker
{
    const stackSize = 4;

    public $lastRestart;
    public $needSleep;

    public function daemon($connectionName, $queue, WorkerOptions $options)
    {
        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        $client = app(Client::class);


        $client->connection()->on('open', function(ClientSession $session) use ($connectionName, $queue, $options, &$client)
        {

            $loop = $client
                ->connection()
                ->getClient()
                ->getLoop();

            $perTimeUnit = 0.0001; // asap
            $loop
                ->addPeriodicTimer($perTimeUnit,
                    function ($timer) use ($connectionName, $queue, $options, $loop, $client) {

                        $jobs = 0;
                        $iterations = 0;


                        while (true)
                        {
                            if($iterations == 0 && $client->isInProgress()) {
                                //waiting for stack to get delivered
                                break;
                            }

                            // Before reserving any jobs, we will make sure this queue is not paused and
                            // if it is we will just pause this worker for a given amount of time and
                            // make sure we do not need to kill this worker process off completely.
                            if (! $this->daemonShouldRun($options, $connectionName, $queue)
                            )
                            {
                                $this->pauseWorker($options, $this->lastRestart);
                                continue;
                            }

                            // First, we will attempt to get the next job off of the queue. We will alsodaemonShouldRun
                            // register the timeout handler and reset the alarm for this job so it is
                            // not stuck in a frozen state forever. Then, we can fire off this job.
                            $job = $this->getNextJob(
                                $this->manager->connection($connectionName), $queue
                            );

                            if ($this->supportsAsyncSignals()) {
                                $this->registerTimeoutHandler($job, $options);
                            }

                            // If the daemon should run (not in maintenance mode, etc.), then we can run
                            // fire off this job for processing. Otherwise, we will need to sleep the
                            // worker so no more jobs are processed until they should be processed.
                            if ($job) {
                                // creating the message buffer/ will execute after our while loop
                                $this->runJob($job, $connectionName, $options);
                                $jobs++;
                            }

                            $iterations++;

                            if( $jobs >= self::stackSize
                                || ($iterations >= self::stackSize))
                            {
                                // flushing after attempting to fill a stack
                                // let the loop flush the socket stream
                                break;
                            }


                            $this->needSleep = $jobs < $iterations && ($jobs == 0);

                            if($this->needSleep)
                            { // flushing leftovers
                                break;
                            }

                            // Finally, we will check to see if we have exceeded our memory limits or if
                            // the queue should restart based on other indications. If so, we'll stop
                            // this worker and let whatever is "monitoring" it restart the process.
                            $this->stopIfNecessary($options, $this->lastRestart, $job);

                        }

                        if($this->needSleep)
                        {
                            $this->sleep($options->sleep);
                            $this->needSleep = false;
                        }
            });


            $this->lastRestart = $this->getTimestampOfLastQueueRestart();

        });

        // will hang till above promise is resolved
        $client->connection()->open();

    }


    /**
     * Stop listening and bail out of the script.
     *
     * @param  int  $status
     * @return void
     */
    public function stop($status = 0)
    {
        app(Client::class)->connection()->close();

        parent::stop($status);
    }

    /**
     * Kill the process.
     *
     * @param  int  $status
     * @return void
     */
    public function kill($status = 0)
    {
        app(Client::class)->connection()->close();

        parent::kill($status);
    }

}
