<?php

/* handles the authentification of the service realm/domain */

namespace App\Libraries\WampServer\Thruway;


use App\Libraries\WampServer\Application;

class ServiceClientAuthentication extends \Thruway\Authentication\AbstractAuthProviderClient
{
    protected $authMethod = 'internal';
    protected $config;

    public function __construct($config)
    {

        parent::__construct([$config['internal_realm']]);
        $this->config = $config;
    }

    /**
     * @return mixed
     */
    public function getMethodName()
    {
        return $this->authMethod;
    }

    /**
     * Process Authenticate message
     *
     * @param mixed $signature
     * @param mixed $extra
     * @return array
     */
    public function processAuthenticate($signature, $extra = null)
    {
        if ($signature == $this->config['internal_realm_password']) {
            $authDetails = [
                'authmethod'   => $this->authMethod,
                'authrole'     => 'service_client',
                'authid'       => 'internal01'
            ];
            return ["SUCCESS", $authDetails];
        } else {
            return ["FAILURE"];
        }

    }

}