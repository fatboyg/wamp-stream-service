<?php


abstract class TestCase extends Laravel\Lumen\Testing\TestCase
{
    public static $authToken = null;

    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    public function getAuthToken() : string
    {
        return $_ENV['AUTH_API_USER_TOKEN1'];
    }

    protected function getPayload($name) :array {

        return json_decode(file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . $_ENV[$name]), true);

    }

}
