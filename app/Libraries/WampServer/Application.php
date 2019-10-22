<?php

namespace App\Libraries\WampServer;

use App\Libraries\WampServer\Thruway\AccountSessions;
use App\Libraries\WampServer\Thruway\LimitedServerTransportProvider;
use App\Libraries\WampServer\Thruway\RatchetTransportProvider;
use App\Libraries\WampServer\Thruway\ServiceClientAuthentication;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as Monolog;
use stdClass;

use Thruway\Authentication\AuthenticationManager;
use Thruway\Authentication\AuthorizationManager;
use Thruway\Logging\Logger;
use Thruway\Peer\Router;
use App\Libraries\WampServer\Thruway\InternalClient;

class Application
{
        protected static $appConfig;
        protected static $router;
        protected static $logger;


    public static function setUp() {

        app()->configure('wamp-server');

        // setting logger
        Logger::set(self::getLogger());
    }

    public static function getConfig($section = null) {

        if(is_null(self::$appConfig))
        {
            self::$appConfig = config('wamp-server');
        }

        return is_null($section) ? self::$appConfig : self::$appConfig[$section];
    }

    /**
     * @return AuthorizationManager
     */
    public static function getAuthorizationManager() : AuthorizationManager
    {

        $authorizationManager = new AuthorizationManager("*");

        // don't allow anything by default
        $authorizationManager->flushAuthorizationRules(false);

        $config = self::getConfig('server');

        // allow sessions in the frontend role to subscribe to main topic
        $front = new stdClass();
        $front->role   = "frontend";
        $front->action = "subscribe";
        $front->uri    = $config['main_topic'];
        $front->allow  = true;

        $authorizationManager->addAuthorizationRule([$front]);

        // allow sessions in the frontend role to subscribe to main wildcard (domains) topic
        // order must prevail on the main namespace :)
        $front = new stdClass();
        $front->role   = "frontend";
        $front->action = "subscribe";
        $front->uri    = $config['main_topic'].'*';
        $front->allow  = true;

        $authorizationManager->addAuthorizationRule([$front]);

        return $authorizationManager;
    }

    public static function startServer($useLoop = true)
    {
        self::setUp();
        $config = self::getConfig();

        $router = new Router();
        $router->registerModule(self::getAuthorizationManager());


        if($config['server']['auth'])
        {
            $router->registerModule( new AuthenticationManager());
            $router->addInternalClient(new $config['auth']['class'](["*"], $config['auth']));
        }

        $router->addTransportProvider(new RatchetTransportProvider($config['server']['host'], $config['server']['port']));

        //    $router->addTransportProvider(new LimitedServerTransportProvider($config['server']));


        $router->addInternalClient(new AccountSessions($config['server']['front_end_realm']));

        $router->addInternalClient(new InternalClient($config['auth']['internal_realm']));
        $router->addInternalClient(new ServiceClientAuthentication($config['auth'])); //dont require auth for the service/ local  client process

        self::$router = $router;

        self::$router->start();
    }

    public static function getRouter() : Router
    {
        return self::$router;
    }

    public static function getLogger() : \Psr\Log\LoggerInterface
    {

        if(!empty( self::$logger))
        {
            return  self::$logger;
        }

        $config = self::getConfig('logging');

        if(isset($config['logger']) && !empty($config['logger']))
        {
            self::$logger = value($config['logger']);
        }
        else
        {
            $logger = new Monolog($config['component']);
            $logger->pushHandler(new StreamHandler('php://stdout', $config['loglevel']));
            self::$logger = $logger;
        }


        return self::$logger;
    }
}