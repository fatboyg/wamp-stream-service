<?php


namespace App\Libraries\InternalWampClient;


class ClientNotificationMessage
{
    public $type;
    public $date;
    public $message;
    public $wasRead;

    public function __construct($type, $message = null)
    {
        $this->type = $type;
        $this->message = $message;
        $this->date = new \DateTime();
    }
}