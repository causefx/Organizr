<?php

namespace Pusher;

class PusherInstance
{
    private static $instance = null;
    private static $app_id = '';
    private static $secret = '';
    private static $api_key = '';

    /**
     * Get the pusher singleton instance.
     *
     * @return Pusher
     * @throws PusherException
     */
    public static function get_pusher()
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::$instance = new Pusher(
            self::$api_key,
            self::$secret,
            self::$app_id
        );

        return self::$instance;
    }
}
