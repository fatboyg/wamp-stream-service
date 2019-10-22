<?php
namespace App\Libraries\InternalWampClient;
use Thruway\Message\ChallengeMessage;


class InternalClientAuth implements \Thruway\Authentication\ClientAuthenticationInterface
{

    protected $authMethod = 'internal';
    protected $conf;
    /**
     * InternalClientAuth constructor.
     */
    public function __construct($conf)
    {
        $this->conf  = $conf;
    }


    /**
     * Get authentication ID
     * 
     * @return mixed
     */
    public function getAuthId()
    {
        // TODO: Implement getAuthId() method.
    }

    /**
     * Set authentication
     * 
     * @param mixed $authid
     */
    public function setAuthId($authid)
    {
        // TODO: Implement setAuthId() method.
    }

    /**
     * Get list support authentication methods
     * 
     * @return array
     */
    public function getAuthMethods()
    {
        return [$this->authMethod];
    }

    /**
     * Make Authenticate message from challenge message
     * 
     * @param \Thruway\Message\ChallengeMessage $msg
     * @return \Thruway\Message\AuthenticateMessage
     */
    public function getAuthenticateFromChallenge(ChallengeMessage $msg)
    {
        return new \Thruway\Message\AuthenticateMessage($this->conf['internal_realm_password']);
    }

} 