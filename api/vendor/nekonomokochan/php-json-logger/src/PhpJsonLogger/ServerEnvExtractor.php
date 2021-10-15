<?php

namespace Nekonomokochan\PhpJsonLogger;

/**
 * Trait ServerEnvExtractor
 *
 * @package Nekonomokochan\PhpJsonLogger
 */
trait ServerEnvExtractor
{
    /**
     * @return string
     */
    public function extractRemoteIpAddress()
    {
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

            $ipList = explode(',', $ip);

            return $ipList[0];
        }

        if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '127.0.0.1';
    }

    /**
     * @return string
     */
    public function extractServerIpAddress()
    {
        if (array_key_exists('SERVER_ADDR', $_SERVER)) {
            return $_SERVER['SERVER_ADDR'];
        }

        return '127.0.0.1';
    }

    /**
     * @return string
     */
    public function extractUserAgent()
    {
        if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
            return $_SERVER['HTTP_USER_AGENT'];
        }

        return 'unknown';
    }
}
