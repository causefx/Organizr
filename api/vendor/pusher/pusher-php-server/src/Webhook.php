<?php

namespace Pusher;

class Webhook
{
    /** @var int $time_ms */
    private $time_ms;
    /** @var array $events */
    private $events;

    public function __construct($time_ms, $events)
    {
        $this->time_ms = $time_ms;
        $this->events = $events;
    }

    public function get_events(): array
    {
        return $this->events;
    }

    public function get_time_ms(): int
    {
        return $this->time_ms;
    }
}
