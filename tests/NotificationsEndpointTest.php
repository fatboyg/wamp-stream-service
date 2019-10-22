<?php


class NotificationsEndpointTest extends TestCase
{

    use \Laravel\Lumen\Testing\WithoutMiddleware;


    public function testCreateNotification() {

        $this
            ->json('POST', '/v1/notifications/create', $this->getPayload('NOTIFICATION_CREATE'));

        $this->assertResponseOk();

    }

    public function testBroadcastNotification() {
        $this
            ->json('POST', '/v1/notifications/createBroadcast', $this->getPayload('NOTIFICATION_BROADCAST'));

        if(!$this->response->isOk())
        {
            var_dump( 'notok', $this->response->getContent());
        }

        $this->assertResponseOk();
    }

    /***
     * @return void
     */
    public function testGetRouterStats()
    {
        $this
        ->json('GET', '/v1/notifications/stats');

        if(!$this->response->isOk())
        {
            var_dump( 'notok', $this->response->getContent());
        }

        $this->assertResponseOk();
    }

}