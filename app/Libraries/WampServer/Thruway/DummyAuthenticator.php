<?php
namespace App\Libraries\WampServer\Thruway;


class DummyAuthenticator  extends TokenAuthenticator
{

    protected  $apiUrl;

    public function __construct(Array $authRealms, $apiUrl)
    {
        parent::__construct($authRealms, null);
    }

    /**
     * Process authenticate
     *
     * @param mixed $code
     * @param mixed $extra
     * @return array
     */
    public function processAuthenticate($code, $extra = null)
    {

       return [
            "SUCCESS",
            ["authid" => $code == 'test' ? '00000000' : rand(1000000,10000000),
            "authroles" => 'frontend'
            ]
        ];


    }


}