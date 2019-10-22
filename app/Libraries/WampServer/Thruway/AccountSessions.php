<?php


namespace App\Libraries\WampServer\Thruway;

use Thruway\Common\Utils;
use Thruway\Logging\Logger;


class AccountSessions extends \Thruway\Peer\Client
{

    const topic =  'com.robits.notifications';
    protected static $_sessions = [];
    public static $_sessionCount = 0;
    public static $_peakSessionsCount = 0;

    public static $prevMemUsage = 0;
    public static $lastSessionMemUsage = 0;



    public function onSessionStart($session, $transport)
    {
        $session->subscribe('wamp.metaevent.session.on_join',  [$this, 'onSessionJoin']);
        $session->subscribe('wamp.metaevent.session.on_leave', [$this, 'onSessionLeave']);

    }

    protected function updateMemStats() {

        $currentMem = memory_get_usage(false);

        if(self::$prevMemUsage >0)
        {
            self::$lastSessionMemUsage = $currentMem - self::$prevMemUsage;
        }

        if(self::$_sessionCount > self::$_peakSessionsCount)
        {
            self::$_peakSessionsCount = self::$_sessionCount;
        }

        self::$prevMemUsage = $currentMem;
    }


    /**
     * Handle on new session joinned
     *
     * @param array $args
     * @param array $kwArgs
     * @param array $options
     * @return void
     * @link https://github.com/crossbario/crossbar/wiki/Session-Metaevents
     */
    public function onSessionJoin($args, $kwArgs, $options)
    {
        $accountId = $args[0]->authid;
        $sid = $args[0]->session;
        Logger::debug($this, "AccountId {$accountId} Session {$args[0]->session} joinned");
        if(!isset(self::$_sessions[$accountId]))
        {
            self::$_sessions[$accountId] = [];
        }
        self::$_sessions[$accountId][$sid] = $args[0];
        self::$_sessionCount++;

        $this->updateMemStats();
    }

    /**
     * Handle on session leaved
     *
     * @param array $args
     * @param array $kwArgs
     * @param array $options
     * @return void
     * @link https://github.com/crossbario/crossbar/wiki/Session-Metaevents
     */
    public function onSessionLeave($args, $kwArgs, $options)
    {
        $accountId = $args[0]->authid;
        $sid = $args[0]->session;

        if (!empty($sid) || !empty($accountId)) {
            unset(self::$_sessions[$accountId][$sid]);
        }

        if(empty(self::$_sessions[$accountId]))
        {
            unset(self::$_sessions[$accountId]);
        }

        self::$_sessionCount--;
    }


    public static function getAccountIds() : array
    {
        return self::$_sessions;
    }


}