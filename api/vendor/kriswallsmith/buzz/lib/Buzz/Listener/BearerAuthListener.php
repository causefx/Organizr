<?php

namespace Buzz\Listener;

use Buzz\Exception\InvalidArgumentException;
use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;

class BearerAuthListener implements ListenerInterface
{
    private $accessToken;

    public function __construct($accessToken)
    {
        if ($accessToken === null || $accessToken === '') {
            throw new InvalidArgumentException('You must supply a non empty accessToken');
        }

        $this->accessToken = $accessToken;
    }

    public function preSend(RequestInterface $request)
    {
        $request->addHeader(sprintf('Authorization: Bearer %s', $this->accessToken));
    }

    public function postSend(RequestInterface $request, MessageInterface $response)
    {
    }
}
