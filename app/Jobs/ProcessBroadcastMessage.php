<?php

namespace App\Jobs;

use App\Libraries\InternalWampClient\Client;
use App\Libraries\InternalWampClient\ClientNotificationMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessBroadcastMessage extends Job
{
    public $tries = 2; // lumen 5.7


    public $message;
    public $domains;


    public function __construct(ClientNotificationMessage $message, $domains = [])
    {
        $this->message = $message;
        $this->domains = $domains;
    }


    /**
     * Execute the job.02
     *
     * @return void
     */
    public function handle()
    {
        $hasFail = !$this->wamp()->notifyAll($this->message, $this->domains);

        $this->jobResult = $this->wamp()->getResult();

        if($hasFail)
        {
            $this->fail();
        }

    }


}
