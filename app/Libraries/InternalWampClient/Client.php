<?php

namespace App\Libraries\InternalWampClient;


use Thruway\CallResult;
use Thruway\ClientSession;
use Thruway\Connection;
use React\Promise\Timer\TimeoutException;
use Thruway\Logging\Logger;
use Thruway\Message\ErrorMessage;

//TODO: refactor duplicate code
class Client
{

    const SUCCESS_RESPONSE = 'SUCCESS';

    protected $connected = false;
    protected $config = [];

    protected $connection;

    protected $result;

    protected $callMethod;

    protected static $session;
    protected $inProgress  = false;

    public function __construct(Array $config)
    {
        $this->config = $config;
    }

    public function connection() : Connection
    {
        if(is_null($this->connection))
        {
            $this->connection = $this->getConnection($this->config['internal_realm']);
        }

        return $this->connection;
    }


    public static function getSession() : ?ClientSession
    {
        return self::$session;
    }

    protected function getConnection($realm) : Connection
    {

        $conn = new Connection(['realm' => $realm, 'url' => $this->config['dsn']]);

        $client = $conn->getClient();
        $client->setAttemptRetry(false);
        $client->addClientAuthenticator(new InternalClientAuth($this->config));

        $loop = $client->getLoop();


        $loop ->addTimer($this->config['timeout'], function () use ( $conn, $loop) {
           if(!$this->connected) {
               $loop->stop();
               throw new TimeoutException($this->config['timeout'], 'Timeout or auth failure while connecting to ' . $this->config['dsn']);
           }

        });


        $conn->on('open', function (ClientSession $session)  {

            self::$session = $session;
            $this->connected = true;
        });
        $conn->on('close', function () use (  $loop) {
            $loop->stop();
            $this->connected = false;

        });

        $conn->on('error', function () use (  $loop) {
            $loop->stop();
            $this->connected = false;

        });



        return $conn;
    }

    public function hasConnection() : bool
    {
        return is_null($this->connection);
    }

    public function isInProgress() : bool
    {
        return $this->inProgress;
    }

    public function notifyAll($messages, $subtopics = [null]) : bool
    {

        $subtopics = !is_array($subtopics) ? [$subtopics] : array_unique($subtopics);

        !is_array($messages) && $messages = [$messages];

        $this->callMethod = $this->config['notifications_topic'] . '.notifyAll';

        $results = [];

        $this->inProgress = true;

        foreach ($subtopics as $subtopic) {

                $topic = $this->config['notifications_topic'] . (is_null($subtopic) ? '' : '.' . $subtopic);

                self::getSession()->call($this->callMethod, [$messages, $topic])
                    ->then(
                        function (CallResult $res) use ($subtopic, $subtopics, &$results)
                        {
                            $results[$subtopic] = ($res->__toString() === self::SUCCESS_RESPONSE);

                            if(!$results[$subtopic])
                            {
                                Logger::error($this, $this->callMethod.' method call error: '. json_encode($res->getResultMessage()->getAdditionalMsgFields()) );
                            }

                            if(count($results) >= count($subtopics) )
                            {
                                $this->inProgress = false;
                             //   $this->connection()->close();
                            }

                        }
                        , function (ErrorMessage $error) use ($subtopic, $subtopics, &$results)
                    {

                        $results[$subtopic] = false;
                        Logger::error($this, $this->callMethod.' method call invalid response: '. json_encode($error->getAdditionalMsgFields()) );

                        if(count($results) >= count($subtopics) )
                        {
                            $this->inProgress = false;

                            // $this->connection()->close();
                        }
                    });

            }
        $this->result = $results;

        //check for failure
        return !self::resultsHasFailure($results);
    }

    public function notifyAccountId($accountIds, $messages, $realm = null) : bool {

        $accountIds = !is_array($accountIds) ? [$accountIds] :  array_unique($accountIds);

        !is_array($messages) && $messages = [$messages];

        $this->callMethod = $this->config['notifications_topic'] . '.notifyAccountId';

        $results = [];

        foreach ($accountIds as $accountId) {
                self::getSession()->call($this->callMethod, [$accountId, $messages])
                    ->then(
                        function (CallResult $res) use ($accountIds, $accountId, &$results)
                        {
                            $results[$accountId] = ($res->__toString() === self::SUCCESS_RESPONSE);

                            if(!$results[$accountId])
                            {
                                Logger::error($this, $this->callMethod.' method call error: '. json_encode($res->getResultMessage()->getAdditionalMsgFields()) );
                            }

                            if(count($results) >= count($accountIds) )
                            {
                                $this->inProgress = false;
                                //$this->connection()->close();
                            }

                        }
                        , function (ErrorMessage $error) use ($accountId, $accountIds, &$results)
                        {

                            $results[$accountId] = false;
                            Logger::error($this, $this->callMethod.' method call invalid response: '. json_encode($error->getAdditionalMsgFields()) );

                            if(count($results) >= count($accountIds) )
                            {
                                $this->inProgress = false;
                                //$this->connection()->close();
                            }
                   });

            }

        $this->result = $results;

        //check for failure
        return !self::resultsHasFailure($results);
    }

    public function getStats() : \stdClass
    {
        $this->callMethod('getStats', []);

        // will hang till above promise is resolved
        $this->connection()->open();

        return ($this->result instanceof CallResult) ?  $this->result[1]->details->message : (object)[];
    }

    public function handleResponse(CallResult $res)
    {
        $this->result = $res;

        if($res->__toString() != self::SUCCESS_RESPONSE)
        {
            Logger::error($this, $this->callMethod.' method call error: '. json_encode($res->getResultMessage()->getAdditionalMsgFields()) );
        }

        $this->connection()->close();
    }

    public function handleErrorResponse(ErrorMessage $error) {

        $this->result = $error;

        Logger::error($this, $this->callMethod.' method call invalid response: '. json_encode($error->getAdditionalMsgFields()) );
        $this->connection()->close();
    }

    protected function callMethod($methodName, $args) {

        $this->callMethod = $this->config['notifications_topic'] . '.' . $methodName;

        return $this->connection()->on('open', function(ClientSession $session) use ( $args) {
            $session->call($this->callMethod, $args)
                ->then([$this, 'handleResponse'], [$this, 'handleErrorResponse']);
        });
    }

    public static function resultsHasFailure($results) {
        //check for failure
        foreach ($results as $v) {
            if(!$v) {
                return true;
            }
        }

        // nothing detected
        return false;
    }

    public function getResult()
    {
        return $this->result;
    }

}