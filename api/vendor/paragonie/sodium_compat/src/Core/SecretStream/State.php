<?php
declare(strict_types=1);

/**
 * Class ParagonIE_Sodium_Core_SecretStream_State
 */
class ParagonIE_Sodium_Core_SecretStream_State
{
    protected string $key;
    protected int $counter;
    protected string $nonce;
    protected string $_pad;

    /**
     * ParagonIE_Sodium_Core_SecretStream_State constructor.
     * @param string $key
     * @param string|null $nonce
     */
    public function __construct(
        #[SensitiveParameter]
        string $key,
        ?string $nonce = null
    ) {
        $this->key = $key;
        $this->counter = 1;
        if (is_null($nonce)) {
            $nonce = str_repeat("\0", 12);
        }
        $this->nonce = str_pad($nonce, 12, "\0");
        $this->_pad = str_repeat("\0", 4);
    }

    /**
     * @return self
     */
    public function counterReset(): self
    {
        $this->counter = 1;
        $this->_pad = str_repeat("\0", 4);
        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getCounter(): string
    {
        return ParagonIE_Sodium_Core_Util::store32_le($this->counter);
    }

    /**
     * @return string
     */
    public function getNonce(): string
    {
        if (ParagonIE_Sodium_Core_Util::strlen($this->nonce) !== 12) {
            $this->nonce = str_pad($this->nonce, 12, "\0");
        }
        return $this->nonce;
    }

    /**
     * @return string
     */
    public function getCombinedNonce(): string
    {
        return $this->getCounter() .
            ParagonIE_Sodium_Core_Util::substr($this->getNonce(), 0, 8);
    }

    /**
     * @return self
     */
    public function incrementCounter(): self
    {
        ++$this->counter;
        return $this;
    }

    /**
     * @return bool
     */
    public function needsRekey(): bool
    {
        return ($this->counter & 0xffff) === 0;
    }

    /**
     * @param string $newKeyAndNonce
     * @return self
     */
    public function rekey(
        #[SensitiveParameter]
        string $newKeyAndNonce
    ): self {
        $this->key = ParagonIE_Sodium_Core_Util::substr($newKeyAndNonce, 0, 32);
        $this->nonce = str_pad(
            ParagonIE_Sodium_Core_Util::substr($newKeyAndNonce, 32),
            12,
            "\0"
        );
        return $this;
    }

    /**
     * @param string $str
     * @return self
     */
    public function xorNonce(
        #[SensitiveParameter]
        string $str
    ): self {
        $this->nonce = ParagonIE_Sodium_Core_Util::xorStrings(
            $this->getNonce(),
            str_pad(
                ParagonIE_Sodium_Core_Util::substr($str, 0, 8),
                12,
                "\0"
            )
        );
        return $this;
    }

    /**
     * @param string $string
     * @return self
     */
    public static function fromString(
        #[SensitiveParameter]
        string $string
    ): self {
        $state = new ParagonIE_Sodium_Core_SecretStream_State(
            ParagonIE_Sodium_Core_Util::substr($string, 0, 32)
        );
        $state->counter = ParagonIE_Sodium_Core_Util::load_4(
            ParagonIE_Sodium_Core_Util::substr($string, 32, 4)
        );
        $state->nonce = ParagonIE_Sodium_Core_Util::substr($string, 36, 12);
        $state->_pad = ParagonIE_Sodium_Core_Util::substr($string, 48, 8);
        return $state;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->key .
            $this->getCounter() .
            $this->nonce .
            $this->_pad;
    }
}
