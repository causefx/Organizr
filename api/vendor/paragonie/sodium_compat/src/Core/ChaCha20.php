<?php
declare(strict_types=1);

if (class_exists('ParagonIE_Sodium_Core_ChaCha20', false)) {
    return;
}

/**
 * Class ParagonIE_Sodium_Core_ChaCha20
 */
class ParagonIE_Sodium_Core_ChaCha20 extends ParagonIE_Sodium_Core_Util
{
    /**
     * Bitwise left rotation
     *
     * @internal You should not use this directly from another application
     *
     * @param int $v
     * @param int $n
     * @return int
     */
    public static function rotate(int $v, int $n): int
    {
        $v &= 0xffffffff;
        $n &= 31;
        return (
            0xffffffff & (
                ($v << $n)
                    |
                ($v >> (32 - $n))
            )
        );
    }

    /**
     * The ChaCha20 quarter round function. Works on four 32-bit integers.
     *
     * @internal You should not use this directly from another application
     *
     * @param int $a
     * @param int $b
     * @param int $c
     * @param int $d
     * @return array<int, int>
     */
    protected static function quarterRound(int $a, int $b, int $c, int $d): array
    {
        # a = PLUS(a,b); d = ROTATE(XOR(d,a),16);
        /** @var int $a */
        $a = ($a + $b) & 0xffffffff;
        $d = self::rotate($d ^ $a, 16);

        # c = PLUS(c,d); b = ROTATE(XOR(b,c),12);
        /** @var int $c */
        $c = ($c + $d) & 0xffffffff;
        $b = self::rotate($b ^ $c, 12);

        # a = PLUS(a,b); d = ROTATE(XOR(d,a), 8);
        /** @var int $a */
        $a = ($a + $b) & 0xffffffff;
        $d = self::rotate($d ^ $a, 8);

        # c = PLUS(c,d); b = ROTATE(XOR(b,c), 7);
        /** @var int $c */
        $c = ($c + $d) & 0xffffffff;
        $b = self::rotate($b ^ $c, 7);
        return array($a, $b, $c, $d);
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param ParagonIE_Sodium_Core_ChaCha20_Ctx $ctx
     * @param string $message
     *
     * @return string
     * @throws TypeError
     * @throws SodiumException
     */
    public static function encryptBytes(
        ParagonIE_Sodium_Core_ChaCha20_Ctx $ctx,
        #[SensitiveParameter]
        string $message = ''
    ): string {
        $bytes = self::strlen($message);

        $j0  = (int) $ctx[0];
        $j1  = (int) $ctx[1];
        $j2  = (int) $ctx[2];
        $j3  = (int) $ctx[3];
        $j4  = (int) $ctx[4];
        $j5  = (int) $ctx[5];
        $j6  = (int) $ctx[6];
        $j7  = (int) $ctx[7];
        $j8  = (int) $ctx[8];
        $j9  = (int) $ctx[9];
        $j10 = (int) $ctx[10];
        $j11 = (int) $ctx[11];
        $j12 = (int) $ctx[12];
        $j13 = (int) $ctx[13];
        $j14 = (int) $ctx[14];
        $j15 = (int) $ctx[15];

        $c = '';
        for (;;) {
            if ($bytes < 64) {
                $message .= str_repeat("\x00", 64 - $bytes);
            }

            $x0 =  $j0;
            $x1 =  $j1;
            $x2 =  $j2;
            $x3 =  $j3;
            $x4 =  $j4;
            $x5 =  $j5;
            $x6 =  $j6;
            $x7 =  $j7;
            $x8 =  $j8;
            $x9 =  $j9;
            $x10 = $j10;
            $x11 = $j11;
            $x12 = $j12;
            $x13 = $j13;
            $x14 = $j14;
            $x15 = $j15;

            # for (i = 20; i > 0; i -= 2) {
            for ($i = 20; $i > 0; $i -= 2) {
                [$x0, $x4, $x8, $x12] = self::quarterRound($x0, $x4, $x8, $x12);
                [$x1, $x5, $x9, $x13] = self::quarterRound($x1, $x5, $x9, $x13);
                [$x2, $x6, $x10, $x14] = self::quarterRound($x2, $x6, $x10, $x14);
                [$x3, $x7, $x11, $x15] = self::quarterRound($x3, $x7, $x11, $x15);

                [$x0, $x5, $x10, $x15] = self::quarterRound($x0, $x5, $x10, $x15);
                [$x1, $x6, $x11, $x12] = self::quarterRound($x1, $x6, $x11, $x12);
                [$x2, $x7, $x8, $x13] = self::quarterRound($x2, $x7, $x8, $x13);
                [$x3, $x4, $x9, $x14] = self::quarterRound($x3, $x4, $x9, $x14);
            }
            $x0  = ($x0 & 0xffffffff) + $j0;
            $x1  = ($x1 & 0xffffffff) + $j1;
            $x2  = ($x2 & 0xffffffff) + $j2;
            $x3  = ($x3 & 0xffffffff) + $j3;
            $x4  = ($x4 & 0xffffffff) + $j4;
            $x5  = ($x5 & 0xffffffff) + $j5;
            $x6  = ($x6 & 0xffffffff) + $j6;
            $x7  = ($x7 & 0xffffffff) + $j7;
            $x8  = ($x8 & 0xffffffff) + $j8;
            $x9  = ($x9 & 0xffffffff) + $j9;
            $x10 = ($x10 & 0xffffffff) + $j10;
            $x11 = ($x11 & 0xffffffff) + $j11;
            $x12 = ($x12 & 0xffffffff) + $j12;
            $x13 = ($x13 & 0xffffffff) + $j13;
            $x14 = ($x14 & 0xffffffff) + $j14;
            $x15 = ($x15 & 0xffffffff) + $j15;

            $x0  ^= self::load_4(self::substr($message, 0, 4));
            $x1  ^= self::load_4(self::substr($message, 4, 4));
            $x2  ^= self::load_4(self::substr($message, 8, 4));
            $x3  ^= self::load_4(self::substr($message, 12, 4));
            $x4  ^= self::load_4(self::substr($message, 16, 4));
            $x5  ^= self::load_4(self::substr($message, 20, 4));
            $x6  ^= self::load_4(self::substr($message, 24, 4));
            $x7  ^= self::load_4(self::substr($message, 28, 4));
            $x8  ^= self::load_4(self::substr($message, 32, 4));
            $x9  ^= self::load_4(self::substr($message, 36, 4));
            $x10 ^= self::load_4(self::substr($message, 40, 4));
            $x11 ^= self::load_4(self::substr($message, 44, 4));
            $x12 ^= self::load_4(self::substr($message, 48, 4));
            $x13 ^= self::load_4(self::substr($message, 52, 4));
            $x14 ^= self::load_4(self::substr($message, 56, 4));
            $x15 ^= self::load_4(self::substr($message, 60, 4));

            ++$j12;
            if ($j12 & 0xf0000000) {
                throw new SodiumException('Overflow');
            }

            $block = self::store32_le(($x0  & 0xffffffff)) .
                 self::store32_le(($x1  & 0xffffffff)) .
                 self::store32_le(($x2  & 0xffffffff)) .
                 self::store32_le(($x3  & 0xffffffff)) .
                 self::store32_le(($x4  & 0xffffffff)) .
                 self::store32_le(($x5  & 0xffffffff)) .
                 self::store32_le(($x6  & 0xffffffff)) .
                 self::store32_le(($x7  & 0xffffffff)) .
                 self::store32_le(($x8  & 0xffffffff)) .
                 self::store32_le(($x9  & 0xffffffff)) .
                 self::store32_le(($x10 & 0xffffffff)) .
                 self::store32_le(($x11 & 0xffffffff)) .
                 self::store32_le(($x12 & 0xffffffff)) .
                 self::store32_le(($x13 & 0xffffffff)) .
                 self::store32_le(($x14 & 0xffffffff)) .
                 self::store32_le(($x15 & 0xffffffff));

            /* Partial block */
            if ($bytes < 64) {
                $c .= self::substr($block, 0, $bytes);
                break;
            }

            /* Full block */
            $c .= $block;
            $bytes -= 64;
            if ($bytes <= 0) {
                break;
            }
            $message = self::substr($message, 64);
        }
        /* end for(;;) loop */

        $ctx[12] = $j12;
        $ctx[13] = $j13;
        return $c;
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param int $len
     * @param string $nonce
     * @param string $key
     * @return string
     * @throws SodiumException
     * @throws TypeError
     */
    public static function stream(
        int $len,
        string $nonce,
        #[SensitiveParameter]
        string $key
    ): string {
        return self::encryptBytes(
            new ParagonIE_Sodium_Core_ChaCha20_Ctx($key, $nonce),
            str_repeat("\x00", $len)
        );
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param int $len
     * @param string $nonce
     * @param string $key
     * @return string
     * @throws SodiumException
     * @throws TypeError
     */
    public static function ietfStream(
        int $len,
        string $nonce,
        #[SensitiveParameter]
        string $key
    ): string {
        return self::encryptBytes(
            new ParagonIE_Sodium_Core_ChaCha20_IetfCtx($key, $nonce),
            str_repeat("\x00", $len)
        );
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param string $message
     * @param string $nonce
     * @param string $key
     * @param string $ic
     * @return string
     * @throws SodiumException
     * @throws TypeError
     */
    public static function ietfStreamXorIc(
        #[SensitiveParameter]
        string $message,
        string $nonce,
        #[SensitiveParameter]
        string $key,
        string $ic = ''
    ): string {
        return self::encryptBytes(
            new ParagonIE_Sodium_Core_ChaCha20_IetfCtx($key, $nonce, $ic),
            $message
        );
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param string $message
     * @param string $nonce
     * @param string $key
     * @param string $ic
     * @return string
     * @throws SodiumException
     * @throws TypeError
     */
    public static function streamXorIc(
        #[SensitiveParameter]
        string $message,
        string $nonce,
        #[SensitiveParameter]
        string $key,
        string $ic = ''
    ): string {
        return self::encryptBytes(
            new ParagonIE_Sodium_Core_ChaCha20_Ctx($key, $nonce, $ic),
            $message
        );
    }
}
