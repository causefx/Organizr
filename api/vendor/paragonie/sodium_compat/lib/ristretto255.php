<?php
declare(strict_types=1);

if (!defined('SODIUM_CRYPTO_CORE_RISTRETTO255_BYTES')) {
    define(
        'SODIUM_CRYPTO_CORE_RISTRETTO255_BYTES',
        ParagonIE_Sodium_Compat::CRYPTO_CORE_RISTRETTO255_BYTES
    );
    define('SODIUM_COMPAT_POLYFILLED_RISTRETTO255', true);
}
if (!defined('SODIUM_CRYPTO_CORE_RISTRETTO255_HASHBYTES')) {
    define(
        'SODIUM_CRYPTO_CORE_RISTRETTO255_HASHBYTES',
        ParagonIE_Sodium_Compat::CRYPTO_CORE_RISTRETTO255_HASHBYTES
    );
}
if (!defined('SODIUM_CRYPTO_CORE_RISTRETTO255_SCALARBYTES')) {
    define(
        'SODIUM_CRYPTO_CORE_RISTRETTO255_SCALARBYTES',
        ParagonIE_Sodium_Compat::CRYPTO_CORE_RISTRETTO255_SCALARBYTES
    );
}
if (!defined('SODIUM_CRYPTO_CORE_RISTRETTO255_NONREDUCEDSCALARBYTES')) {
    define(
        'SODIUM_CRYPTO_CORE_RISTRETTO255_NONREDUCEDSCALARBYTES',
        ParagonIE_Sodium_Compat::CRYPTO_CORE_RISTRETTO255_NONREDUCEDSCALARBYTES
    );
}
if (!defined('SODIUM_CRYPTO_SCALARMULT_RISTRETTO255_SCALARBYTES')) {
    define(
        'SODIUM_CRYPTO_SCALARMULT_RISTRETTO255_SCALARBYTES',
        ParagonIE_Sodium_Compat::CRYPTO_SCALARMULT_RISTRETTO255_SCALARBYTES
    );
}
if (!defined('SODIUM_CRYPTO_SCALARMULT_RISTRETTO255_BYTES')) {
    define(
        'SODIUM_CRYPTO_SCALARMULT_RISTRETTO255_BYTES',
        ParagonIE_Sodium_Compat::CRYPTO_SCALARMULT_RISTRETTO255_BYTES
    );
}

if (!is_callable('sodium_crypto_core_ristretto255_add')) {
    /**
     * @see ParagonIE_Sodium_Compat::ristretto255_add()
     *
     * @param string $p
     * @param string $q
     * @return string
     * @throws SodiumException
     */
    function sodium_crypto_core_ristretto255_add(
        #[\SensitiveParameter]
        string $p,
        #[\SensitiveParameter]
        string $q
    ): string {
        return ParagonIE_Sodium_Compat::ristretto255_add($p, $q, true);
    }
}
if (!is_callable('sodium_crypto_core_ristretto255_from_hash')) {
    /**
     * @see ParagonIE_Sodium_Compat::ristretto255_from_hash()
     *
     * @param string $s
     * @return string
     * @throws SodiumException
     */
    function sodium_crypto_core_ristretto255_from_hash(
        #[\SensitiveParameter]
        string $s
    ): string {
        return ParagonIE_Sodium_Compat::ristretto255_from_hash($s, true);
    }
}
if (!is_callable('sodium_crypto_core_ristretto255_is_valid_point')) {
    /**
     * @see ParagonIE_Sodium_Compat::ristretto255_is_valid_point()
     *
     * @param string $s
     * @return bool
     * @throws SodiumException
     */
    function sodium_crypto_core_ristretto255_is_valid_point(
        #[\SensitiveParameter]
        string $s
    ): bool {
        return ParagonIE_Sodium_Compat::ristretto255_is_valid_point($s, true);
    }
}
if (!is_callable('sodium_crypto_core_ristretto255_random')) {
    /**
     * @see ParagonIE_Sodium_Compat::ristretto255_random()
     *
     * @return string
     * @throws SodiumException
     */
    function sodium_crypto_core_ristretto255_random(): string
    {
        return ParagonIE_Sodium_Compat::ristretto255_random(true);
    }
}
if (!is_callable('sodium_crypto_core_ristretto255_scalar_add')) {
    /**
     * @see ParagonIE_Sodium_Compat::ristretto255_scalar_add()
     *
     * @param string $x
     * @param string $y
     * @return string
     * @throws SodiumException
     */
    function sodium_crypto_core_ristretto255_scalar_add(
        #[\SensitiveParameter]
        string $x,
        #[\SensitiveParameter]
        string $y
    ): string {
        return ParagonIE_Sodium_Compat::ristretto255_scalar_add($x, $y, true);
    }
}
if (!is_callable('sodium_crypto_core_ristretto255_scalar_complement')) {
    /**
     * @see ParagonIE_Sodium_Compat::ristretto255_scalar_complement()
     *
     * @param string $s
     * @return string
     * @throws SodiumException
     */
    function sodium_crypto_core_ristretto255_scalar_complement(
        #[\SensitiveParameter]
        string $s
    ): string {
        return ParagonIE_Sodium_Compat::ristretto255_scalar_complement($s, true);
    }
}
if (!is_callable('sodium_crypto_core_ristretto255_scalar_invert')) {
    /**
     * @see ParagonIE_Sodium_Compat::ristretto255_scalar_invert()
     *
     * @param string $p
     * @return string
     * @throws SodiumException
     */
    function sodium_crypto_core_ristretto255_scalar_invert(
        #[\SensitiveParameter]
        string $p
    ): string {
        return ParagonIE_Sodium_Compat::ristretto255_scalar_invert($p, true);
    }
}
if (!is_callable('sodium_crypto_core_ristretto255_scalar_mul')) {
    /**
     * @see ParagonIE_Sodium_Compat::ristretto255_scalar_mul()
     *
     * @param string $x
     * @param string $y
     * @return string
     * @throws SodiumException
     */
    function sodium_crypto_core_ristretto255_scalar_mul(
        #[\SensitiveParameter]
        string $x,
        #[\SensitiveParameter]
        string $y
    ): string {
        return ParagonIE_Sodium_Compat::ristretto255_scalar_mul($x, $y, true);
    }
}
if (!is_callable('sodium_crypto_core_ristretto255_scalar_negate')) {
    /**
     * @see ParagonIE_Sodium_Compat::ristretto255_scalar_negate()
     *
     * @param string $s
     * @return string
     * @throws SodiumException
     */
    function sodium_crypto_core_ristretto255_scalar_negate(
        #[\SensitiveParameter]
        string $s
    ): string {
        return ParagonIE_Sodium_Compat::ristretto255_scalar_negate($s, true);
    }
}
if (!is_callable('sodium_crypto_core_ristretto255_scalar_random')) {
    /**
     * @see ParagonIE_Sodium_Compat::ristretto255_scalar_random()
     *
     * @return string
     * @throws SodiumException
     */
    function sodium_crypto_core_ristretto255_scalar_random(): string
    {
        return ParagonIE_Sodium_Compat::ristretto255_scalar_random(true);
    }
}
if (!is_callable('sodium_crypto_core_ristretto255_scalar_reduce')) {
    /**
     * @see ParagonIE_Sodium_Compat::ristretto255_scalar_reduce()
     *
     * @param string $s
     * @return string
     * @throws SodiumException
     */
    function sodium_crypto_core_ristretto255_scalar_reduce(
        #[\SensitiveParameter]
        string $s
    ): string {
        return ParagonIE_Sodium_Compat::ristretto255_scalar_reduce($s, true);
    }
}
if (!is_callable('sodium_crypto_core_ristretto255_scalar_sub')) {
    /**
     * @see ParagonIE_Sodium_Compat::ristretto255_scalar_sub()
     *
     * @param string $x
     * @param string $y
     * @return string
     * @throws SodiumException
     */
    function sodium_crypto_core_ristretto255_scalar_sub(
        #[\SensitiveParameter]
        string $x,
        #[\SensitiveParameter]
        string $y
    ): string {
        return ParagonIE_Sodium_Compat::ristretto255_scalar_sub($x, $y, true);
    }
}
if (!is_callable('sodium_crypto_core_ristretto255_sub')) {
    /**
     * @see ParagonIE_Sodium_Compat::ristretto255_sub()
     *
     * @param string $p
     * @param string $q
     * @return string
     * @throws SodiumException
     */
    function sodium_crypto_core_ristretto255_sub(
        #[\SensitiveParameter]
        string $p,
        #[\SensitiveParameter]
        string $q
    ): string {
        return ParagonIE_Sodium_Compat::ristretto255_sub($p, $q, true);
    }
}
if (!is_callable('sodium_crypto_scalarmult_ristretto255')) {
    /**
     * @see ParagonIE_Sodium_Compat::crypto_scalarmult_ristretto255()
     * @param string $n
     * @param string $p
     * @return string
     * @throws SodiumException
     * @throws TypeError
     */
    function sodium_crypto_scalarmult_ristretto255(
        #[\SensitiveParameter]
        string $n,
        #[\SensitiveParameter]
        string $p
    ): string {
        return ParagonIE_Sodium_Compat::scalarmult_ristretto255($n, $p, true);
    }
}
if (!is_callable('sodium_crypto_scalarmult_ristretto255_base')) {
    /**
     * @see ParagonIE_Sodium_Compat::crypto_scalarmult_ristretto255_base()
     * @param string $n
     * @return string
     * @throws SodiumException
     * @throws TypeError
     */
    function sodium_crypto_scalarmult_ristretto255_base(
        #[\SensitiveParameter]
        string $n
    ): string {
        return ParagonIE_Sodium_Compat::scalarmult_ristretto255_base($n, true);
    }
}
