<?php

namespace Buzz\Listener;

use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;

interface ListenerInterface
{
    public function preSend(RequestInterface $request);
    public function postSend(RequestInterface $request, MessageInterface $response);
}
