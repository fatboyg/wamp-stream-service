<?php
namespace App\Libraries\WampServer\Thruway;


use App\Helpers\Format;
use App\Libraries\WampServer\Application;
use Thruway\Session;
use Thruway\Logging\Logger;

class InternalClient extends \Thruway\Peer\Client
{


    protected $mainTopic;
    protected  $frontendDomain;

    const SUCCESS_CODE = 'SUCCESS';
    /**
     * Constructor
     */
    public function __construct($authRealm)
    {
        parent::__construct($authRealm);
        $config = Application::getConfig('server');

        $this->mainTopic = $config['main_topic'];
        $this->frontendDomain = $config['front_end_realm'];

    }

    /**
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        // TODO: implement conte
        Logger::info( $this, "--------------- Binding to service methods ------------");
        $session->register($this->mainTopic.'.getPhpVersion', [$this, 'getPhpVersion']);
        $session->register($this->mainTopic.'.notifyAccountId',     [$this, 'notifyAccountId']);
        $session->register($this->mainTopic.'.notifyAll',     [$this, 'notifyAll']);
        $session->register($this->mainTopic.'.getStats',     [$this, 'getStats']);

    }

    /**
     * Handle get PHP version
     * 
     * @return array
     */
    public function getPhpVersion()
    {
        return [phpversion()];
    }
    
    /**
     * Sends message to specific accountId sessions online
     * 
     * @return array
     */
    public function notifyAccountId($args)
    {

        if(count($args) < 2)
        {
            return  [
                "FAILURE",
                [
                    "abort_uri" => $this->mainTopic.'.'.__FUNCTION__,
                    "details" => [
                        "message" => "Invalid arguments: accountId, message"
                    ]
                ]
            ];
        }

        list($accountId, $message) = $args;

        Logger::info($this, "Broadcasting to realm: ".  $this->frontendDomain .' accountId : ' . $accountId);
        Logger::debug($this, json_encode($message));
        Application::getRouter()
            ->getRealmManager()
            ->getRealm($this->frontendDomain)
            ->publishMeta($this->mainTopic, [$message], null, ['_thruway_eligible_authids' => [$accountId]]);

          return  [
                "SUCCESS",
                [
                    "abort_uri" => __FUNCTION__,
                    "details" => [
                        "message" => self::SUCCESS_CODE
                    ]
                ]
            ];

    }

    /**
     * Sends message to specific accountId sessions online
     *
     * @return array
     */
    public function notifyAll($args)
    {
        $argsc = count($args);
        $topic = null;

        if($argsc < 1)
        {
            return  [
                "FAILURE",
                [
                    "abort_uri" => $this->mainTopic.'.'.__FUNCTION__,
                    "details" => [
                        "message" => "Invalid arguments: message"
                    ]
                ]
            ];
        }
        else if ($argsc > 1)
        {
            // topic was sent
            list($message, $topic) = $args;
        }
         else {
            // no topic specified
             list($message) = $args;
         }

        $topic = empty($topic) ? $this->mainTopic : $topic;

        Logger::info($this, "Broadcasting to realm: ".  $this->frontendDomain  . ' topic: ' . $topic );
        Logger::debug($this, 'Message: ', $args);

        $message = is_array($message) ? $message : [$message];

        Application::getRouter()
            ->getRealmManager()
            ->getRealm( $this->frontendDomain )
            ->publishMeta($topic, $message);

        return  [
            "SUCCESS",
            [
                "abort_uri" => __FUNCTION__,
                "details" => [
                    "message" => self::SUCCESS_CODE
                ]
            ]
        ];

    }

    public function getStats($args) {

        return
            [
                "SUCCESS",
                [
                    "abort_uri" => __FUNCTION__,
                    "details" => [
                        "message" =>
                            [   'accounts_count' => count(AccountSessions::getAccountIds()),
                                'sessions_count' => AccountSessions::$_sessionCount,
                                'peak_sessions_count' => AccountSessions::$_peakSessionsCount,
                                'memory_usage' => Format::formatSize(memory_get_usage(false)),
                                'peak_memory_usage' => Format::formatSize(memory_get_peak_usage (false)),
                                'last_client_memory_usage' => AccountSessions::$lastSessionMemUsage .' bytes',
                            ]
                    ]
                ]
            ];
    }
}