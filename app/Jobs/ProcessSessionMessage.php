<?php

namespace App\Jobs;

use App\Libraries\InternalWampClient\Client;
use App\Libraries\InternalWampClient\ClientNotificationMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessSessionMessage extends Job
{
    public $tries = 2; // lumen 5.7


    public $message;
    public $sessionIds;


    public function __construct(ClientNotificationMessage $message, array $sessionIds)
    {
        $this->message = $message;
        $this->sessionIds = $sessionIds;
    }


    /**
     * Execute the job.02
     *
     * @return void
     */
    public function handle()
    {
        $hasFail = !$this->wamp()->notifyAccountId($this->sessionIds, $this->message);

        $this->jobResult = $this->wamp()->getResult();
        if($hasFail)
        {
            $this->fail();
        }

    }


}
