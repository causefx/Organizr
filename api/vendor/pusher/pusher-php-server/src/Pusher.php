<?php

namespace Pusher;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Pusher implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string Version
     */
    public static $VERSION = '3.3.1';

    /**
     * @var null|PusherCrypto
     */
    private $crypto;

    /**
     * @var array Settings
     */
    private $settings = array(
        'scheme'                => 'http',
        'port'                  => 80,
        'timeout'               => 30,
        'debug'                 => false,
        'curl_options'          => array(),
        'encryption_master_key' => '',
    );

    /**
     * @var null|resource
     */
    private $ch = null; // Curl handler

    /**
     * Initializes a new Pusher instance with key, secret, app ID and channel.
     * You can optionally turn on debugging for all requests by setting debug to true.
     *
     * @param string $auth_key
     * @param string $secret
     * @param int    $app_id
     * @param array  $options  [optional]
     *                         Options to configure the Pusher instance.
     *                         Was previously a debug flag. Legacy support for this exists if a boolean is passed.
     *                         scheme - e.g. http or https
     *                         host - the host e.g. api.pusherapp.com. No trailing forward slash.
     *                         port - the http port
     *                         timeout - the http timeout
     *                         useTLS - quick option to use scheme of https and port 443.
     *                         encrypted - deprecated; renamed to `useTLS`.
     *                         cluster - cluster name to connect to.
     *                         encryption_master_key - a 32 char long key. This key, along with the channel name, are used to derive per-channel encryption keys. Per-channel keys are used encrypt event data on encrypted channels.
     *                         debug - (default `false`) if `true`, every `trigger()` and `triggerBatch()` call will return a `$response` object, useful for logging/inspection purposes.
     *                         curl_options - wrapper for curl_setopt, more here: http://php.net/manual/en/function.curl-setopt.php
     *                         notification_host - host to connect to for native notifications.
     *                         notification_scheme - scheme for the notification_host.
     * @param string $host     [optional] - deprecated
     * @param int    $port     [optional] - deprecated
     * @param int    $timeout  [optional] - deprecated
     *
     * @throws PusherException Throws exception if any required dependencies are missing
     */
    public function __construct($auth_key, $secret, $app_id, $options = array(), $host = null, $port = null, $timeout = null)
    {
        $this->check_compatibility();

        /* Start backward compatibility with old constructor **/
        if (is_bool($options) === true) {
            $options = array(
                'debug' => $options,
            );
        }

        if (!is_null($host)) {
            $match = null;
            preg_match("/(http[s]?)\:\/\/(.*)/", $host, $match);

            if (count($match) === 3) {
                $this->settings['scheme'] = $match[1];
                $host = $match[2];
            }

            $this->settings['host'] = $host;

            $this->log('Legacy $host parameter provided: {scheme} host: {host}', array(
                'scheme' => $this->settings['scheme'],
                'host'   => $this->settings['host'],
            ));
        }

        if (!is_null($port)) {
            $options['port'] = $port;
        }

        if (!is_null($timeout)) {
            $options['timeout'] = $timeout;
        }

        /* End backward compatibility with old constructor **/

        $useTLS = false;
        if (isset($options['useTLS'])) {
            $useTLS = $options['useTLS'] === true;
        } elseif (isset($options['encrypted'])) {
            // `encrypted` deprecated in favor of `forceTLS`
            $useTLS = $options['encrypted'] === true;
        }
        if (
            $useTLS &&
            !isset($options['scheme']) &&
            !isset($options['port'])
        ) {
            $options['scheme'] = 'https';
            $options['port'] = 443;
        }

        $this->settings['auth_key'] = $auth_key;
        $this->settings['secret'] = $secret;
        $this->settings['app_id'] = $app_id;
        $this->settings['base_path'] = '/apps/'.$this->settings['app_id'];

        foreach ($options as $key => $value) {
            // only set if valid setting/option
            if (isset($this->settings[$key])) {
                $this->settings[$key] = $value;
            }
        }

        // Set the native notification host
        if (isset($options['notification_host'])) {
            $this->settings['notification_host'] = $options['notification_host'];
        } else {
            $this->settings['notification_host'] = 'nativepush-cluster1.pusher.com';
        }

        // Set scheme for native notifications
        if (isset($options['notification_scheme'])) {
            $this->settings['notification_scheme'] = $options['notification_scheme'];
        } else {
            $this->settings['notification_scheme'] = 'https';
        }

        // handle the case when 'host' and 'cluster' are specified in the options.
        if (!array_key_exists('host', $this->settings)) {
            if (array_key_exists('host', $options)) {
                $this->settings['host'] = $options['host'];
            } elseif (array_key_exists('cluster', $options)) {
                $this->settings['host'] = 'api-'.$options['cluster'].'.pusher.com';
            } else {
                $this->settings['host'] = 'api.pusherapp.com';
            }
        }

        // ensure host doesn't have a scheme prefix
        $this->settings['host'] =
        preg_replace('/http[s]?\:\/\//', '', $this->settings['host'], 1);

        if ($this->settings['encryption_master_key'] != '') {
            $this->crypto = new PusherCrypto($this->settings['encryption_master_key']);
        }
    }

    /**
     * Fetch the settings.
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set a logger to be informed of internal log messages.
     *
     * @deprecated Use the PSR-3 compliant Pusher::setLogger() instead. This method will be removed in the next breaking release.
     *
     * @param object $logger A object with a public function log($message) method
     *
     * @return void
     */
    public function set_logger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Log a string.
     *
     * @param string           $msg     The message to log
     * @param array|\Exception $context [optional] Any extraneous information that does not fit well in a string.
     * @param string           $level   [optional] Importance of log message, highly recommended to use Psr\Log\LogLevel::{level}
     *
     * @return void
     */
    private function log($msg, array $context = array(), $level = LogLevel::INFO)
    {
        if (is_null($this->logger)) {
            return;
        }

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->log($level, $msg, $context);

            return;
        }

        // Support old style logger (deprecated)
        $msg = sprintf('Pusher: %s: %s', strtoupper($level), $msg);
        $replacement = array();

        foreach ($context as $k => $v) {
            $replacement['{'.$k.'}'] = $v;
        }

        $this->logger->log(strtr($msg, $replacement));
    }

    /**
     * Check if the current PHP setup is sufficient to run this class.
     *
     * @throws PusherException If any required dependencies are missing
     *
     * @return void
     */
    private function check_compatibility()
    {
        if (!extension_loaded('curl')) {
            throw new PusherException('The Pusher library requires the PHP cURL module. Please ensure it is installed');
        }

        if (!extension_loaded('json')) {
            throw new PusherException('The Pusher library requires the PHP JSON module. Please ensure it is installed');
        }

        if (!in_array('sha256', hash_algos())) {
            throw new PusherException('SHA256 appears to be unsupported - make sure you have support for it, or upgrade your version of PHP.');
        }
    }

    /**
     * Validate number of channels and channel name format.
     *
     * @param string[] $channels An array of channel names to validate
     *
     * @throws PusherException If $channels is too big or any channel is invalid
     *
     * @return void
     */
    private function validate_channels($channels)
    {
        if (count($channels) > 100) {
            throw new PusherException('An event can be triggered on a maximum of 100 channels in a single call.');
        }

        foreach ($channels as $channel) {
            $this->validate_channel($channel);
        }
    }

    /**
     * Ensure a channel name is valid based on our spec.
     *
     * @param string $channel The channel name to validate
     *
     * @throws PusherException If $channel is invalid
     *
     * @return void
     */
    private function validate_channel($channel)
    {
        if (!preg_match('/\A[-a-zA-Z0-9_=@,.;]+\z/', $channel)) {
            throw new PusherException('Invalid channel name '.$channel);
        }
    }

    /**
     * Ensure a socket_id is valid based on our spec.
     *
     * @param string $socket_id The socket ID to validate
     *
     * @throws PusherException If $socket_id is invalid
     */
    private function validate_socket_id($socket_id)
    {
        if ($socket_id !== null && !preg_match('/\A\d+\.\d+\z/', $socket_id)) {
            throw new PusherException('Invalid socket ID '.$socket_id);
        }
    }

    /**
     * Utility function used to create the curl object with common settings.
     *
     * @param string            $domain
     * @param string            $s_url
     * @param string [optional] $request_method
     * @param array [optional]  $query_params
     *
     * @throws PusherException Throws exception if curl wasn't initialized correctly
     *
     * @return resource
     */
    private function create_curl($domain, $s_url, $request_method = 'GET', $query_params = array())
    {
        // Create the signed signature...
        $signed_query = self::build_auth_query_string(
            $this->settings['auth_key'],
            $this->settings['secret'],
            $request_method,
            $s_url,
            $query_params
        );

        $full_url = $domain.$s_url.'?'.$signed_query;

        $this->log('create_curl( {full_url} )', array('full_url' => $full_url));

        // Create or reuse existing curl handle
        if (!is_resource($this->ch)) {
            $this->ch = curl_init();
        }

        if ($this->ch === false) {
            throw new PusherException('Could not initialise cURL!');
        }

        $ch = $this->ch;

        // curl handle is not reusable unless reset
        if (function_exists('curl_reset')) {
            curl_reset($ch);
        }

        // Set cURL opts and execute request
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Expect:',
            'X-Pusher-Library: pusher-http-php '.self::$VERSION,
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->settings['timeout']);
        if ($request_method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        } elseif ($request_method === 'GET') {
            curl_setopt($ch, CURLOPT_POST, 0);
        } // Otherwise let the user configure it

        // Set custom curl options
        if (!empty($this->settings['curl_options'])) {
            foreach ($this->settings['curl_options'] as $option => $value) {
                curl_setopt($ch, $option, $value);
            }
        }

        return $ch;
    }

    /**
     * Utility function to execute curl and create capture response information.
     *
     * @param $ch resource
     *
     * @return array
     */
    private function exec_curl($ch)
    {
        $response = array();

        $response['body'] = curl_exec($ch);
        $response['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response['body'] === false) {
            $this->log('exec_curl error: {error}', array('error' => curl_error($ch)), LogLevel::ERROR);
        } elseif ($response['status'] < 200 || 400 <= $response['status']) {
            $this->log('exec_curl {status} error from server: {body}', $response, LogLevel::ERROR);
        } else {
            $this->log('exec_curl {status} response: {body}', $response);
        }

        $this->log('exec_curl response: {response}', array('response' => print_r($response, true)));

        return $response;
    }

    /**
     * Build the notification domain.
     *
     * @return string
     */
    private function notification_domain()
    {
        return $this->settings['notification_scheme'].'://'.$this->settings['notification_host'];
    }

    /**
     * Build the Channels domain.
     *
     * @return string
     */
    private function channels_domain()
    {
        return $this->settings['scheme'].'://'.$this->settings['host'].':'.$this->settings['port'];
    }

    /**
     * Build the required HMAC'd auth string.
     *
     * @param string $auth_key
     * @param string $auth_secret
     * @param string $request_method
     * @param string $request_path
     * @param array  $query_params   [optional]
     * @param string $auth_version   [optional]
     * @param string $auth_timestamp [optional]
     *
     * @return string
     */
    public static function build_auth_query_string($auth_key, $auth_secret, $request_method, $request_path,
    $query_params = array(), $auth_version = '1.0', $auth_timestamp = null)
    {
        $params = array();
        $params['auth_key'] = $auth_key;
        $params['auth_timestamp'] = (is_null($auth_timestamp) ? time() : $auth_timestamp);
        $params['auth_version'] = $auth_version;

        $params = array_merge($params, $query_params);
        ksort($params);

        $string_to_sign = "$request_method\n".$request_path."\n".self::array_implode('=', '&', $params);

        $auth_signature = hash_hmac('sha256', $string_to_sign, $auth_secret, false);

        $params['auth_signature'] = $auth_signature;
        ksort($params);

        $auth_query_string = self::array_implode('=', '&', $params);

        return $auth_query_string;
    }

    /**
     * Implode an array with the key and value pair giving
     * a glue, a separator between pairs and the array
     * to implode.
     *
     * @param string       $glue      The glue between key and value
     * @param string       $separator Separator between pairs
     * @param array|string $array     The array to implode
     *
     * @return string The imploded array
     */
    public static function array_implode($glue, $separator, $array)
    {
        if (!is_array($array)) {
            return $array;
        }

        $string = array();
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $val = implode(',', $val);
            }
            $string[] = "{$key}{$glue}{$val}";
        }

        return implode($separator, $string);
    }

    /**
     * Trigger an event by providing event name and payload.
     * Optionally provide a socket ID to exclude a client (most likely the sender).
     *
     * @param array|string $channels        A channel name or an array of channel names to publish the event on.
     * @param string       $event
     * @param mixed        $data            Event data
     * @param string|null  $socket_id       [optional]
     * @param bool         $debug           [optional]
     * @param bool         $already_encoded [optional]
     *
     * @throws PusherException Throws exception if $channels is an array of size 101 or above or $socket_id is invalid
     *
     * @return bool|array
     */
    public function trigger($channels, $event, $data, $socket_id = null, $debug = false, $already_encoded = false)
    {
        if (is_string($channels) === true) {
            $channels = array($channels);
        }

        $this->validate_channels($channels);
        $this->validate_socket_id($socket_id);

        $has_encrypted_channel = false;
        foreach ($channels as $chan) {
            if (PusherCrypto::is_encrypted_channel($chan)) {
                $has_encrypted_channel = true;
            }
        }

        if ($has_encrypted_channel) {
            if (count($channels) > 1) {
                // For rationale, see limitations of end-to-end encryption in the README
                throw new PusherException('You cannot trigger to multiple channels when using encrypted channels');
            } else {
                $data_encoded = $this->crypto->encrypt_payload($channels[0], $already_encoded ? $data : json_encode($data));
            }
        } else {
            $data_encoded = $already_encoded ? $data : json_encode($data);
        }

        $query_params = array();

        $s_url = $this->settings['base_path'].'/events';

        // json_encode might return false on failure
        if (!$data_encoded) {
            $this->log('Failed to perform json_encode on the the provided data: {error}', array(
                'error' => print_r($data, true),
            ), LogLevel::ERROR);
        }

        $post_params = array();
        $post_params['name'] = $event;
        $post_params['data'] = $data_encoded;
        $post_params['channels'] = array_values($channels);

        if ($socket_id !== null) {
            $post_params['socket_id'] = $socket_id;
        }

        $post_value = json_encode($post_params);

        $query_params['body_md5'] = md5($post_value);

        $ch = $this->create_curl($this->channels_domain(), $s_url, 'POST', $query_params);

        $this->log('trigger POST: {post_value}', compact('post_value'));

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_value);

        $response = $this->exec_curl($ch);

        if ($debug === true || $this->settings['debug'] === true) {
            return $response;
        }

        if ($response['status'] === 200) {
            return true;
        }

        return false;
    }

    /**
     * Trigger multiple events at the same time.
     *
     * @param array $batch           [optional] An array of events to send
     * @param bool  $debug           [optional]
     * @param bool  $already_encoded [optional]
     *
     * @throws PusherException Throws exception if curl wasn't initialized correctly
     *
     * @return array|bool|string
     */
    public function triggerBatch($batch = array(), $debug = false, $already_encoded = false)
    {
        foreach ($batch as $key => $event) {
            $this->validate_channel($event['channel']);
            if (isset($event['socket_id'])) {
                $this->validate_socket_id($event['socket_id']);
            }

            $data = $event['data'];
            if (!is_string($data)) {
                $data = $already_encoded ? $data : json_encode($data);
            }

            if (PusherCrypto::is_encrypted_channel($event['channel'])) {
                $batch[$key]['data'] = $this->crypto->encrypt_payload($event['channel'], $data);
            } else {
                $batch[$key]['data'] = $data;
            }
        }

        $post_params = array();
        $post_params['batch'] = $batch;
        $post_value = json_encode($post_params);

        $query_params = array();
        $query_params['body_md5'] = md5($post_value);
        $s_url = $this->settings['base_path'].'/batch_events';

        $ch = $this->create_curl($this->channels_domain(), $s_url, 'POST', $query_params);

        $this->log('trigger POST: {post_value}', compact('post_value'));

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_value);

        $response = $this->exec_curl($ch);

        if ($debug === true || $this->settings['debug'] === true) {
            return $response;
        }

        if ($response['status'] === 200) {
            return true;
        }

        return false;
    }

    /**
     * Fetch channel information for a specific channel.
     *
     * @param string $channel The name of the channel
     * @param array  $params  Additional parameters for the query e.g. $params = array( 'info' => 'connection_count' )
     *
     * @throws PusherException If $channel is invalid or if curl wasn't initialized correctly
     *
     * @return bool|object
     */
    public function get_channel_info($channel, $params = array())
    {
        $this->validate_channel($channel);

        $response = $this->get('/channels/'.$channel, $params);

        if ($response['status'] === 200) {
            return json_decode($response['body']);
        }

        return false;
    }

    /**
     * Fetch a list containing all channels.
     *
     * @param array $params Additional parameters for the query e.g. $params = array( 'info' => 'connection_count' )
     *
     * @throws PusherException Throws exception if curl wasn't initialized correctly
     *
     * @return array|bool
     */
    public function get_channels($params = array())
    {
        $response = $this->get('/channels', $params);

        if ($response['status'] === 200) {
            $response = json_decode($response['body']);
            $response->channels = get_object_vars($response->channels);

            return $response;
        }

        return false;
    }

    /**
     * GET arbitrary REST API resource using a synchronous http client.
     * All request signing is handled automatically.
     *
     * @param string $path   Path excluding /apps/APP_ID
     * @param array  $params API params (see http://pusher.com/docs/rest_api)
     *
     * @throws PusherException Throws exception if curl wasn't initialized correctly
     *
     * @return array|bool See Pusher API docs
     */
    public function get($path, $params = array())
    {
        $s_url = $this->settings['base_path'].$path;

        $ch = $this->create_curl($this->channels_domain(), $s_url, 'GET', $params);

        $response = $this->exec_curl($ch);

        if ($response['status'] === 200) {
            $response['result'] = json_decode($response['body'], true);

            return $response;
        }

        return false;
    }

    /**
     * Creates a socket signature.
     *
     * @param string $channel
     * @param string $socket_id
     * @param string $custom_data
     *
     * @throws PusherException Throws exception if $channel is invalid or above or $socket_id is invalid
     *
     * @return string Json encoded authentication string.
     */
    public function socket_auth($channel, $socket_id, $custom_data = null)
    {
        $this->validate_channel($channel);
        $this->validate_socket_id($socket_id);

        if ($custom_data) {
            $signature = hash_hmac('sha256', $socket_id.':'.$channel.':'.$custom_data, $this->settings['secret'], false);
        } else {
            $signature = hash_hmac('sha256', $socket_id.':'.$channel, $this->settings['secret'], false);
        }

        $signature = array('auth' => $this->settings['auth_key'].':'.$signature);
        // add the custom data if it has been supplied
        if ($custom_data) {
            $signature['channel_data'] = $custom_data;
        }

        if (PusherCrypto::is_encrypted_channel($channel)) {
            if (!is_null($this->crypto)) {
                $signature['shared_secret'] = base64_encode($this->crypto->generate_shared_secret($channel));
            } else {
                throw new PusherException('You must specify an encryption master key to authorize an encrypted channel');
            }
        }

        return json_encode($signature, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Creates a presence signature (an extension of socket signing).
     *
     * @param string $channel
     * @param string $socket_id
     * @param string $user_id
     * @param mixed  $user_info
     *
     * @throws PusherException Throws exception if $channel is invalid or above or $socket_id is invalid
     *
     * @return string
     */
    public function presence_auth($channel, $socket_id, $user_id, $user_info = null)
    {
        $user_data = array('user_id' => $user_id);
        if ($user_info) {
            $user_data['user_info'] = $user_info;
        }

        return $this->socket_auth($channel, $socket_id, json_encode($user_data));
    }

    /**
     * Send a native notification via the Push Notifications Api.
     *
     * @param array $interests
     * @param array $data
     * @param bool  $debug
     *
     * @throws PusherException If validation fails
     *
     * @return array|bool|string
     */
    public function notify($interests, $data = array(), $debug = false)
    {
        $query_params = array();

        if (is_string($interests)) {
            $this->log('->notify received string interests "{interests}" Converting to array.', compact('interests'));
            $interests = array($interests);
        }

        if (count($interests) === 0) {
            throw new PusherException('$interests array must not be empty');
        }

        $data['interests'] = $interests;

        $post_value = json_encode($data);

        $query_params['body_md5'] = md5($post_value);

        $notification_path = '/server_api/v1'.$this->settings['base_path'].'/notifications';
        $ch = $this->create_curl($this->notification_domain(), $notification_path, 'POST', $query_params);

        $this->log('trigger POST (Native notifications): {post_value}', compact('post_value'));

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_value);

        $response = $this->exec_curl($ch);

        if ($response['status'] === 202 && $debug === false) {
            return true;
        }

        if ($debug === true || $this->settings['debug'] === true) {
            return $response;
        }

        return false;
    }

    /**
     * Verify that a webhook actually came from Pusher, decrypts any encrypted events, and marshals them into a PHP object.
     *
     * @param array  $headers a array of headers from the request (for example, from getallheaders())
     * @param string $body    the body of the request (for example, from file_get_contents('php://input'))
     *
     * @return array marshalled object with the properties time_ms (an int) and events (an array of event objects)
     */
    public function webhook($headers, $body)
    {
        $this->ensure_valid_signature($headers, $body);

        $decoded_events = array();
        $decoded_json = json_decode($body);
        foreach ($decoded_json->events as $key => $event) {
            if (PusherCrypto::is_encrypted_channel($event->channel)) {
                if (!is_null($this->crypto)) {
                    $decryptedEvent = $this->crypto->decrypt_event($event);

                    if ($decryptedEvent == false) {
                        $this->log('Unable to decrypt webhook event payload. Wrong key? Ignoring.', null, LogLevel::WARNING);
                        continue;
                    }
                    array_push($decoded_events, $decryptedEvent);
                } else {
                    $this->log('Got an encrypted webhook event payload, but no encryption_master_key specified. Ignoring.', null, LogLevel::WARNING);
                    continue;
                }
            } else {
                array_push($decoded_events, $event);
            }
        }
        $webhookobj = new Webhook($decoded_json->time_ms, $decoded_json->events);

        return $webhookobj;
    }

    /**
     * Verify that a given Pusher Signature is valid.
     *
     * @param array  $headers an array of headers from the request (for example, from getallheaders())
     * @param string $body    the body of the request (for example, from file_get_contents('php://input'))
     *
     * @throws PusherException if signature is inccorrect.
     */
    public function ensure_valid_signature($headers, $body)
    {
        $x_pusher_key = $headers['X-Pusher-Key'];
        $x_pusher_signature = $headers['X-Pusher-Signature'];
        if ($x_pusher_key == $this->settings['auth_key']) {
            $expected = hash_hmac('sha256', $body, $this->settings['secret']);
            if ($expected === $x_pusher_signature) {
                return;
            }
        }

        throw new PusherException(sprintf('Received WebHook with invalid signature: got %s.', $x_pusher_signature));
    }
}
