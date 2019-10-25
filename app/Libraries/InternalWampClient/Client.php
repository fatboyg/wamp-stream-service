<?php

namespace App\Libraries\InternalWampClient;


use Exception;
use React\Dns\Config\Config;
use React\Dns\Model\Message;
use React\EventLoop\Factory;
use Thruway\CallResult;
use Thruway\ClientSession;
use App\Libraries\InternalWampClient\Connection;
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

    protected $connections = [];
    protected $connectionPromises = [];
    protected $connectedSessions = [];


    protected $result;

    protected $callMethod;

    protected static $session;
    protected $inProgress  = false;

    protected static $eventLoop;
    /**
     * @var array
     */
    private $results;
    /**
     * @var array
     */
    private $subtopics;
    /**
     * @var \React\Promise\Promise
     */
    private $connectionsPromise;

    public function __construct(Array $config)
    {
        $this->config = $config;
        if(is_null(self::$eventLoop))
        {
            self::$eventLoop = Factory::create();
        }
    }

    public function getEventLoop() {
        return self::$eventLoop;
    }

    public function connections() : \React\Promise\Promise
    {

        if($this->connectionsPromise)
        {
            return $this->connectionsPromise;
        }

       if(empty($this->connections))
       {
           $routerUrl = parse_url($this->config['dsn']);

           $this->getHostIps($routerUrl['host'])
           ->then(function ($hostIps) use ($routerUrl) {

               var_dump('got host ips', $hostIps);

               foreach ($hostIps as $id => $hostIp)
               {
                   $hostUrl =$routerUrl['scheme'].'://' . $hostIp . ":" . $routerUrl['port'].'/'.$routerUrl['path'];
                   $conn = $this->getConnection($hostUrl, $this->config['internal_realm']);


                   $conn->on('open', function (ClientSession $session) use($id) {

                       $this->connectedSessions[$id] = $session;
                   });
                   $conn->on('close', function () use($id, $hostUrl) {

                       Logger::error($this, 'Got connection error', ['host' => $hostUrl]);

                       //    self::$eventLoop->stop();

                   });

                   $conn->on('error', function ()  use($id, $hostUrl) {
                       Logger::error($this, 'Got connection error', ['host' => $hostUrl]);
                       //    self::$eventLoop->stop();
                   });

                   $conn->open(false);

                   array_push($this->connectionPromises, $conn->getRegisteredPromise());


                   $this->connections[$id] = $conn;

               }
           });


       }

        $this->connectionsPromise = \React\Promise\all($this->connectionPromises);
        return $this->connectionsPromise;
    }

    public function connection($id) : ?Connection
    {

         if(isset($this->connections[$id]))
        {
            return $this->connection[$id];
        }

         return null;
    }


    public static function getSession() : ?ClientSession
    {
        return self::$session;
    }

    protected function getConnection($dsn, $realm) : Connection
    {

        $conn = new Connection(['realm' => $realm, 'url' => $dsn], self::$eventLoop);

        $client = $conn->getClient();
        $client->setAttemptRetry(false);
        $client->addClientAuthenticator(new InternalClientAuth($this->config));

        $loop = $client->getLoop();


        $loop ->addTimer($this->config['timeout'], function () use ( $conn, $loop, $dsn) {
           if(!$this->connected) {
               $loop->stop();
               throw new TimeoutException($this->config['timeout'], 'Timeout or auth failure while connecting to ' . $dsn);
           }

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

    public function getHostIps($hostname) : \React\Promise\Promise
    {
        if(filter_var($hostname, FILTER_VALIDATE_IP))
        {
            $deferred = new \React\Promise\Deferred();
            // since the host is an ip already resolve the promise
            $deferred->resolve([$hostname]);

            return $deferred->promise();
        }

        $config = Config::loadSystemConfigBlocking();
        $server = reset($config->nameservers);

        $factory = new \React\Dns\Resolver\Factory();
        $resolver = $factory->create($server, self::getEventLoop());

        return $resolver->resolveAll($hostname, Message::TYPE_A);

    }

    public function notifyAll($messages, $subtopics = [null]) : bool
    {

        $this->subtopics = !is_array($subtopics) ? [$subtopics] : array_unique($subtopics);

        !is_array($messages) && $messages = [$messages];

        $this->callMethod = $this->config['notifications_topic'] . '.notifyAll';

        $this->results = [];
        $promises = [];
        $this->inProgress = true;



        // once we have all connections open
        $this->connections()->then(function () use ($messages, &$promises)
        {
            $subtopics = $this->subtopics;

            foreach ($subtopics as $subtopic) {

                $topic = $this->config['notifications_topic'] . (is_null($subtopic) ? '' : '.' . $subtopic);

                foreach ($this->connections as $id => $connection) {
                    $promises[] = $connection->getClient()->getSession()->call($this->callMethod, [$messages, $topic])
                        ->then(
                            function (CallResult $res) use ($subtopic, $subtopics) {
                                $this->results[$subtopic] = ($res->__toString() === self::SUCCESS_RESPONSE);

                                if (!$this->results[$subtopic]) {
                                    Logger::error($this, $this->callMethod . ' method call error: ' . json_encode($res->getResultMessage()->getAdditionalMsgFields()));
                                }

                                $this->handleResponse($res);
                                return $res;

                            }
                            , function (ErrorMessage $error) use ($subtopic, $subtopics, &$results) {

                            $this->results[$subtopic] = false;
                            $this->handleErrorResponse($error);
                            return $error;
                        });
                }
            }


        });


        $thePromise = \React\Promise\all($promises);

        return $this->handleResultPromise($thePromise);
    }

    public function notifyAccountId($accountIds, $messages, $realm = null) : bool {

        $accountIds = !is_array($accountIds) ? [$accountIds] :  array_unique($accountIds);

        !is_array($messages) && $messages = [$messages];

        $this->callMethod = $this->config['notifications_topic'] . '.notifyAccountId';
        $this->inProgress = true;

        $this->results = [];

        foreach ($accountIds as $accountId) {
            foreach ($this->connections as $id => $connection) {

                $connection->getClient()->getSession()->call($this->callMethod, [$accountId, $messages])
                    ->then(
                        function (CallResult $res) use ($accountIds, $accountId, &$results) {
                            $this->results[$accountId] = ($res->__toString() === self::SUCCESS_RESPONSE);

                            if (!$this->results[$accountId]) {
                                Logger::error($this, $this->callMethod . ' method call error: ' . json_encode($res->getResultMessage()->getAdditionalMsgFields()));
                            }

                            $this->handleResponse($res);


                        }
                        , function (ErrorMessage $error) use ($accountId, $accountIds, &$results) {

                            $this->results[$accountId] = false;

                            $this->handleErrorResponse($error);
                    });

            }
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
        $connCount = count($this->connections);

        if (count($this->results)*$connCount >= count($this->subtopics)*$connCount) {
        //    $this->inProgress = false;
        }

      //  $this->connection()->close();
    }

    public function handleErrorResponse(ErrorMessage $error) {

        Logger::error($this, $this->callMethod . ' method call invalid response: ' . json_encode($error->getAdditionalMsgFields()));

        $connCount = count($this->connections);

        if (count($this->results)*$connCount >= count($this->subtopics)*$connCount) {
           // $this->inProgress = false;
        }
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

    private function handleResultPromise(\React\Promise\Promise $thePromise)
    {
        $thePromise = $thePromise.then(function (){
                var_dump(__METHOD__, 'success on all  connections');

                $this->inProgress = false;
            }, function (){
                $this->inProgress = false;
                var_dump(__METHOD__, 'fail on one of  connections');

            },
                function (){
                    var_dump(__METHOD__, 'in progress on connections');
                    $this->inProgress = true;
                });

        return $thePromise;
    }

}