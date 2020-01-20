<?php

namespace Pusher;

class Webhook
{
    private $time_ms;
    private $events = array();

    public function __construct($time_ms, $events)
    {
        $this->time_ms = $time_ms;
        $this->events = $events;
    }

    public function get_events()
    {
        return $this->events;
    }

    public function get_time_ms()
    {
        return $this->time_ms;
    }
}
