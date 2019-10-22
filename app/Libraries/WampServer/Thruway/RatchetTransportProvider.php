<?php

namespace App\Libraries\WampServer\Thruway;

use Ratchet\ConnectionInterface;
use Thruway\Logging\Logger;

class RatchetTransportProvider extends \Thruway\Transport\RatchetTransportProvider
{

    /** log some client disconnects **/
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Logger::info($this, "Client error  " . get_class($e) . 'exception: ' . $e->getMessage());
    }
}