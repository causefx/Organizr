<?php

namespace Pusher;

class PusherCrypto
{
    private $encryption_master_key = '';

    // The prefix any e2e channel must have
    const ENCRYPTED_PREFIX = 'private-encrypted-';

    /**
     * Checks if a given channel is an encrypted channel.
     *
     * @param string $channel the name of the channel
     *
     * @return bool true if channel is an encrypted channel
     */
    public static function is_encrypted_channel($channel)
    {
        return substr($channel, 0, strlen(self::ENCRYPTED_PREFIX)) === self::ENCRYPTED_PREFIX;
    }

    /**
     * Initialises a PusherCrypto instance.
     *
     * @param string $encryption_master_key the SECRET_KEY_LENGTH key that will be used for key derivation.
     */
    public function __construct($encryption_master_key)
    {
        if (function_exists('sodium_crypto_secretbox')) {
            if (strlen($encryption_master_key) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
                $this->encryption_master_key = $encryption_master_key;

                return;
            } else {
                throw new PusherException('Your end to end encryption key must be 32 chars long');
            }
        }

        throw new PusherException('To use end to end encryption, you must either be using PHP 7.2 or greater or have installed the libsodium-php extension for php < 7.2.');
    }

    /**
     * Decrypts a given event.
     *
     * @param object $event an object that has an encrypted data property and a channel property.
     *
     * @return object the event with a decrypted payload, or false if decryption was unsuccessful.
     */
    public function decrypt_event($event)
    {
        $parsed_payload = $this->parse_encrypted_message($event->data);
        $shared_secret = $this->generate_shared_secret($event->channel, $this->encryption_master_key);
        $decrypted_payload = $this->decrypt_payload($parsed_payload->ciphertext, $parsed_payload->nonce, $shared_secret);
        if (!$decrypted_payload) {
            throw new PusherException('Decryption of the payload failed. Wrong key?');
        }
        $event->data = $decrypted_payload;

        return $event;
    }

    /**
     * Derives a shared secret from the secret key and the channel to broadcast to.
     *
     * @param string $channel the name of the channel
     *
     * @return string a SHA256 hash (encoded as base64) of the channel name appended to the encryption key
     */
    public function generate_shared_secret($channel)
    {
        if (!self::is_encrypted_channel($channel)) {
            throw new PusherException('You must specify a channel of the form private-encrypted-* for E2E encryption. Got '.$channel);
        }

        return hash('sha256', $channel.$this->encryption_master_key, true);
    }

    /**
     * Encrypts a given plaintext for broadcast on a particular channel.
     *
     * @param string $channel   the name of the channel the payloads event will be broadcast on
     * @param string $plaintext the data to encrypt
     *
     * @return string a string ready to be sent as the data of an event.
     */
    public function encrypt_payload($channel, $plaintext)
    {
        if (!self::is_encrypted_channel($channel)) {
            throw new PusherException('Cannot encrypt plaintext for a channel that is not of the form private-encrypted-*. Got '.$channel);
        }
        $nonce = $this->generate_nonce();
        $shared_secret = $this->generate_shared_secret($channel);
        $cipher_text = sodium_crypto_secretbox($plaintext, $nonce, $shared_secret);

        return $this->format_encrypted_message($nonce, $cipher_text);
    }

    /**
     * Decrypts a given payload using the nonce and shared secret.
     *
     * @param string $payload       the ciphertext
     * @param string $nonce         the nonce used in the encryption
     * @param string $shared_secret the shared_secret used in the encryption
     *
     * @return string plaintext
     */
    public function decrypt_payload($payload, $nonce, $shared_secret)
    {
        $plaintext = sodium_crypto_secretbox_open($payload, $nonce, $shared_secret);
        if (empty($plaintext)) {
            return false;
        }

        return $plaintext;
    }

    /**
     * Formats an encrypted message ready for broadcast.
     *
     * @param string $nonce      the nonce used in the encryption process (bytes)
     * @param string $ciphertext the ciphertext (bytes)
     *
     * @return string JSON with base64 encoded nonce and ciphertext`
     */
    private function format_encrypted_message($nonce, $ciphertext)
    {
        $encrypted_message = new \stdClass();
        $encrypted_message->nonce = base64_encode($nonce);
        $encrypted_message->ciphertext = base64_encode($ciphertext);

        return json_encode($encrypted_message);
    }

    /**
     * Parses an encrypted message into its nonce and ciphertext components.
     *
     *
     * @param string $payload the encrypted message payload
     *
     * @return string php object with decoded nonce and ciphertext
     */
    private function parse_encrypted_message($payload)
    {
        $decoded_payload = json_decode($payload);
        $decoded_payload->nonce = base64_decode($decoded_payload->nonce);
        $decoded_payload->ciphertext = base64_decode($decoded_payload->ciphertext);
        if (strlen($decoded_payload->nonce) != SODIUM_CRYPTO_SECRETBOX_NONCEBYTES || $decoded_payload->ciphertext == '') {
            throw new PusherException('Received a payload that cannot be parsed.');
        }

        return $decoded_payload;
    }

    /**
     * Generates a nonce that is SODIUM_CRYPTO_SECRETBOX_NONCEBYTES long.
     */
    private function generate_nonce()
    {
        return random_bytes(
            SODIUM_CRYPTO_SECRETBOX_NONCEBYTES
        );
    }
}
