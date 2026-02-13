<?php

namespace Pusher;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;

interface PusherInterface
{
    /**
     * Fetch the settings.
     *
     * @return array
     */
    public function getSettings();

    /**
     * Trigger an event by providing event name and payload.
     * Optionally provide a socket ID to exclude a client (most likely the sender).
     *
     * @param array|string $channels        A channel name or an array of channel names to publish the event on.
     * @param string       $event
     * @param mixed        $data            Event data
     * @param array        $params          [optional]
     * @param bool         $already_encoded [optional]
     *
     * @throws PusherException   Throws exception if $channels is an array of size 101 or above or $socket_id is invalid
     * @throws ApiErrorException Throws ApiErrorException if the Channels HTTP API responds with an error
     * @throws GuzzleException
     *
     */
    public function trigger($channels, string $event, $data, array $params = [], bool $already_encoded = false): object;

    /**
     * Asynchronously trigger an event by providing event name and payload.
     * Optionally provide a socket ID to exclude a client (most likely the sender).
     *
     * @param array|string $channels        A channel name or an array of channel names to publish the event on.
     * @param mixed        $data            Event data
     * @param array        $params          [optional]
     * @param bool         $already_encoded [optional]
     *
     */
    public function triggerAsync($channels, string $event, $data, array $params = [], bool $already_encoded = false): PromiseInterface;

    /**
     * Trigger multiple events at the same time.
     *
     * @param array $batch           [optional] An array of events to send
     * @param bool  $already_encoded [optional]
     *
     * @throws PusherException   Throws exception if curl wasn't initialized correctly
     * @throws ApiErrorException Throws ApiErrorException if the Channels HTTP API responds with an error
     * @throws GuzzleException
     *
     */
    public function triggerBatch(array $batch = [], bool $already_encoded = false): object;

    /**
     * Asynchronously trigger multiple events at the same time.
     *
     * @param array $batch           [optional] An array of events to send
     * @param bool  $already_encoded [optional]
     *
     * @throws PusherException   Throws exception if curl wasn't initialized correctly
     * @throws ApiErrorException Throws ApiErrorException if the Channels HTTP API responds with an error
     *
     */
    public function triggerBatchAsync(array $batch = [], bool $already_encoded = false): PromiseInterface;

    /**
     * Get information, such as subscriber and user count, for a channel.
     *
     * @param string $channel The name of the channel
     * @param array  $params  Additional parameters for the query e.g. $params = array( 'info' => 'connection_count' )
     *
     * @throws PusherException   If $channel is invalid or if curl wasn't initialized correctly
     * @throws ApiErrorException Throws ApiErrorException if the Channels HTTP API responds with an error
     * @throws GuzzleException
     *
     */
    public function getChannelInfo(string $channel, array $params = []): object;

    /**
     * Fetch a list containing all channels.
     *
     * @param array $params Additional parameters for the query e.g. $params = array( 'info' => 'connection_count' )
     *
     * @throws PusherException   Throws exception if curl wasn't initialized correctly
     * @throws ApiErrorException Throws ApiErrorException if the Channels HTTP API responds with an error
     * @throws GuzzleException
     *
     */
    public function getChannels(array $params = []): object;

    /**
     * Fetch user ids currently subscribed to a presence channel.
     *
     * @param string $channel The name of the channel
     *
     * @throws PusherException   Throws exception if curl wasn't initialized correctly
     * @throws ApiErrorException Throws ApiErrorException if the Channels HTTP API responds with an error
     * @throws GuzzleException
     *
     */
    public function getPresenceUsers(string $channel): object;

    /**
     * GET arbitrary REST API resource using a synchronous http client.
     * All request signing is handled automatically.
     *
     * @param string $path   Path excluding /apps/APP_ID
     * @param array  $params API params (see http://pusher.com/docs/rest_api)
     * @param bool   $associative When true, return the response body as an associative array, else return as an object
     *
     * @throws PusherException   Throws exception if curl wasn't initialized correctly
     * @throws ApiErrorException Throws ApiErrorException if the Channels HTTP API responds with an error
     * @throws GuzzleException
     *
     * @return mixed See Pusher API docs
     */
    public function get(string $path, array $params = [], bool $associative = false);

    /**
     * Creates a socket signature.
     *
     * @param string $channel
     * @param string $socket_id
     * @param string|null $custom_data
     * @return string Json encoded authentication string.
     * @throws PusherException Throws exception if $channel is invalid or above or $socket_id is invalid
     */
    public function socketAuth(string $channel, string $socket_id, ?string $custom_data = null): string;

    /**
     * Creates a presence signature (an extension of socket signing).
     *
     * @param mixed  $user_info
     *
     * @throws PusherException Throws exception if $channel is invalid or above or $socket_id is invalid
     *
     */
    public function presenceAuth(string $channel, string $socket_id, string $user_id, $user_info = null): string;

    /**
     * Verify that a webhook actually came from Pusher, decrypts any
     * encrypted events, and marshals them into a PHP object.
     *
     * @param array  $headers a array of headers from the request (for example, from getallheaders())
     * @param string $body    the body of the request (for example, from file_get_contents('php://input'))
     *
     * @throws PusherException
     *
     * @return Webhook marshalled object with the properties time_ms (an int) and events (an array of event objects)
     */
    public function webhook(array $headers, string $body): object;

    /**
     * Verify that a given Pusher Signature is valid.
     *
     * @param array  $headers an array of headers from the request (for example, from getallheaders())
     * @param string $body    the body of the request (for example, from file_get_contents('php://input'))
     *
     * @throws PusherException if signature is incorrect.
     */
    public function verifySignature(array $headers, string $body);


    /*******************************************************************
     *
     * DEPRECATION WARNING:
     *
     * all the functions below have been deprecated in favour of their
     * camelCased variants. They will be removed in the next major
     * update.
     */

    /**
     * Get information, such as subscriber and user count, for a channel.
     *
     * @deprecated in favour of getChannelInfo
     *
     * @param string $channel The name of the channel
     * @param array  $params  Additional parameters for the query e.g. $params = array( 'info' => 'connection_count' )
     *
     * @throws PusherException   If $channel is invalid or if curl wasn't initialized correctly
     * @throws ApiErrorException Throws ApiErrorException if the Channels HTTP API responds with an error
     * @throws GuzzleException
     *
     */
    public function get_channel_info(string $channel, array $params = []): object;

    /**
     * Fetch a list containing all channels.
     *
     * @deprecated in favour of getChannels
     *
     * @param array $params Additional parameters for the query e.g. $params = array( 'info' => 'connection_count' )
     *
     * @throws PusherException   Throws exception if curl wasn't initialized correctly
     * @throws ApiErrorException Throws ApiErrorException if the Channels HTTP API responds with an error
     * @throws GuzzleException
     *
     */
    public function get_channels(array $params = []): object;

    /**
     * Fetch user ids currently subscribed to a presence channel.
     *
     * @deprecated in favour of getPresenceUsers
     *
     * @param string $channel The name of the channel
     *
     * @throws PusherException   Throws exception if curl wasn't initialized correctly
     * @throws ApiErrorException Throws ApiErrorException if the Channels HTTP API responds with an error
     * @throws GuzzleException
     *
     */
    public function get_users_info(string $channel): object;

    /**
     * Creates a socket signature.
     *
     * @deprecated in favour of socketAuth
     *
     * @param string $channel
     * @param string $socket_id
     * @param string|null $custom_data
     * @return string Json encoded authentication string.
     * @throws PusherException Throws exception if $channel is invalid or above or $socket_id is invalid
     */
    public function socket_auth(string $channel, string $socket_id, ?string $custom_data = null): string;

    /**
     * Creates a presence signature (an extension of socket signing).
     *
     * @deprecated in favour of presenceAuth
     *
     * @param mixed  $user_info
     *
     * @throws PusherException Throws exception if $channel is invalid or above or $socket_id is invalid
     *
     */
    public function presence_auth(string $channel, string $socket_id, string $user_id, $user_info = null): string;

    /**
     * Verify that a given Pusher Signature is valid.
     *
     * @deprecated in favour of verifySignature
     *
     * @param array  $headers an array of headers from the request (for example, from getallheaders())
     * @param string $body    the body of the request (for example, from file_get_contents('php://input'))
     *
     * @throws PusherException if signature is incorrect.
     */
    public function ensure_valid_signature(array $headers, string $body);
}
