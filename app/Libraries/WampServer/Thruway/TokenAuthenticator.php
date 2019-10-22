<?php
namespace App\Libraries\WampServer\Thruway;


use Thruway\Logging\Logger as Log;


class TokenAuthenticator  extends \Thruway\Authentication\AbstractAuthProviderClient
{
    protected $config;
    protected $client;

    const AUTH_CACHE_TIMEOUT = 2*60;

    const INTERNAL_ERROR = 2;
    const INVALID_AUTH = 1;

    public function __construct(Array $authRealms, $config)
    {

        parent::__construct($authRealms);
        $this->config = $config;
    }

    /**
     * Get authentication method name
     *
     * @return string
     */
    public function getMethodName()
    {
        return 'token';
    }

    protected function checkToken($token)
    {

        if (empty($token)) {
            return null;
        }

        return true;
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
        $result = [
            "FAILURE",
            [
                "abort_uri" => __FUNCTION__,
                "details" => [
                    'code' => self::INVALID_AUTH,
                    "message" => "Invalid token"
                ]
            ]
        ];

        $authResponse = $this->checkToken($code);

        if($authResponse instanceof GetUserResponse)
        {
            $result =  [
                "SUCCESS",
                ["authid" => $authResponse->id,
                    "authroles" => 'frontend']
            ];

        }  elseif (is_null($authResponse))
        {
            $result[1]['details']['code'] = self::INTERNAL_ERROR;
            $result[1]['details']['message'] = "Authentication internal error";
        }


        return $result;
    }


}