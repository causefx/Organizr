<?php
declare(strict_types=1);

if (class_exists('ParagonIE_Sodium_Core_Util', false)) {
    return;
}

/**
 * Class ParagonIE_Sodium_Core_Util
 */
abstract class ParagonIE_Sodium_Core_Util
{
    const U32_MAX = 0xFFFFFFFF;

    /**
     * @param int $integer
     * @param int $size (16, 32, 64)
     * @return int
     */
    public static function abs(
        #[SensitiveParameter]
        int $integer,
        int $size = 0
    ): int {
        $realSize = (PHP_INT_SIZE << 3) - 1;
        if ($size) {
            --$size;
        } else {
            $size = $realSize;
        }

        $negative = -(($integer >> $size) & 1);
        return (
            ($integer ^ $negative)
                +
            (($negative >> $realSize) & 1)
        );
    }

    /**
     * @param string $a
     * @param string $b
     * @return string
     * @throws SodiumException
     */
    public static function andStrings(
        #[SensitiveParameter]
        string $a,
        #[SensitiveParameter]
        string $b
    ): string {
        $len = self::strlen($a);
        if (self::strlen($b) !== $len) {
            throw new SodiumException('Both strings must be of equal length to combine with bitwise AND');
        }
        return $a & $b;
    }

    /**
     * Convert a binary string into a hexadecimal string without cache-timing
     * leaks
     *
     * @internal You should not use this directly from another application
     *
     * @param string $binaryString (raw binary)
     * @return string
     * @throws TypeError
     */
    public static function bin2hex(
        #[SensitiveParameter]
        string $binaryString
    ): string {
        $hex = '';
        $len = self::strlen($binaryString);
        for ($i = 0; $i < $len; ++$i) {
            /** @var array<int, int> $chunk */
            $chunk = unpack('C', $binaryString[$i]);
            /** @var int $c */
            $c = $chunk[1] & 0xf;
            /** @var int $b */
            $b = $chunk[1] >> 4;
            $hex .= pack(
                'CC',
                (87 + $b + ((($b - 10) >> 8) & ~38)),
                (87 + $c + ((($c - 10) >> 8) & ~38))
            );
        }
        return $hex;
    }

    /**
     * Cache-timing-safe variant of ord()
     *
     * @internal You should not use this directly from another application
     *
     * @param string $chr
     * @return int
     * @throws SodiumException
     * @throws TypeError
     */
    public static function chrToInt(
        #[SensitiveParameter]
        string $chr
    ): int {
        if (self::strlen($chr) !== 1) {
            throw new SodiumException('chrToInt() expects a string that is exactly 1 character long');
        }
        /** @var array<int, int> $chunk */
        $chunk = unpack('C', $chr);
        return $chunk[1];
    }

    /**
     * Compares two strings.
     *
     * @internal You should not use this directly from another application
     *
     * @param string $left
     * @param string $right
     * @param ?int $len
     * @return int
     * @throws SodiumException
     * @throws TypeError
     */
    public static function compare(
        #[SensitiveParameter]
        string $left,
        #[SensitiveParameter]
        string $right,
        ?int $len = null
    ): int {
        $leftLen = self::strlen($left);
        $rightLen = self::strlen($right);
        if ($len === null) {
            $len = max($leftLen, $rightLen);
            $left = str_pad($left, $len, "\x00");
            $right = str_pad($right, $len, "\x00");
        } elseif ($leftLen !== $rightLen) {
            throw new SodiumException("Argument #1 and argument #2 must have the same length");
        }

        $gt = 0;
        $eq = 1;
        $i = $len;
        while ($i !== 0) {
            --$i;
            $gt |= ((self::chrToInt($right[$i]) - self::chrToInt($left[$i])) >> 8) & $eq;
            $eq &= ((self::chrToInt($right[$i]) ^ self::chrToInt($left[$i])) - 1) >> 8;
        }
        return ($gt + $gt + $eq) - 1;
    }

    /**
     * Evaluate whether or not two strings are equal (in constant-time)
     *
     * @param string $left
     * @param string $right
     * @return bool
     *
     * @throws TypeError
     */
    public static function hashEquals(
        #[SensitiveParameter]
        string $left,
        #[SensitiveParameter]
        string $right
    ): bool {
        return hash_equals($left, $right);
    }

    /**
     * Convert a hexadecimal string into a binary string without cache-timing
     * leaks
     *
     * @internal You should not use this directly from another application
     *
     * @param string $hexString
     * @param string $ignore
     * @param bool $strictPadding
     * @return string (raw binary)
     * @throws SodiumException
     * @throws TypeError
     */
    public static function hex2bin(
        #[SensitiveParameter]
        string $hexString,
        string $ignore = '',
        bool $strictPadding = false
    ): string {
        $hex_pos = 0;
        $bin = '';
        $c_acc = 0;
        $hex_len = self::strlen($hexString);
        $state = 0;
        $chunk = unpack('C*', $hexString);
        while ($hex_pos < $hex_len) {
            ++$hex_pos;
            /** @var int $c */
            $c = $chunk[$hex_pos];
            $c_num = $c ^ 48;
            $c_num0 = ($c_num - 10) >> 8;
            $c_alpha = ($c & ~32) - 55;
            $c_alpha0 = (($c_alpha - 10) ^ ($c_alpha - 16)) >> 8;
            if (($c_num0 | $c_alpha0) === 0) {
                if ($ignore && $state === 0 && str_contains($ignore, self::intToChr($c))) {
                    continue;
                }
                throw new SodiumException(
                    'hex2bin() only expects hexadecimal characters'
                );
            }
            $c_val = ($c_num0 & $c_num) | ($c_alpha & $c_alpha0);
            if ($state === 0) {
                $c_acc = $c_val * 16;
            } else {
                $bin .= pack('C', $c_acc | $c_val);
            }
            $state ^= 1;
        }
        if ($strictPadding && $state !== 0) {
            throw new SodiumException(
                'Expected an even number of hexadecimal characters'
            );
        }
        return $bin;
    }

    /**
     * Turn an array of integers into a string
     *
     * @internal You should not use this directly from another application
     *
     * @param array<int, int> $ints
     * @return string
     */
    public static function intArrayToString(array $ints): string
    {
        $args = $ints;
        foreach ($args as $i => $v) {
            $args[$i] = ($v & 0xff);
        }
        array_unshift($args, str_repeat('C', count($ints)));
        return (string) (call_user_func_array('pack', $args));
    }

    /**
     * Cache-timing-safe variant of ord()
     *
     * @internal You should not use this directly from another application
     *
     * @param int $int
     * @return string
     * @throws TypeError
     */
    public static function intToChr(
        #[SensitiveParameter]
        int $int
    ): string {
        return pack('C', $int);
    }

    /**
     * Load a 3 character substring into an integer
     *
     * @internal You should not use this directly from another application
     *
     * @param string $string
     * @return int
     * @throws RangeException
     * @throws TypeError
     */
    public static function load_3(
        #[SensitiveParameter]
        string $string
    ): int {
        /* Input validation: */
        if (self::strlen($string) < 3) {
            throw new RangeException(
                'String must be 3 bytes or more; ' . self::strlen($string) . ' given.'
            );
        }
        /** @var array<int, int> $unpacked */
        $unpacked = unpack('V', $string . "\0");
        return ($unpacked[1] & 0xffffff);
    }

    /**
     * Load a 4 character substring into an integer
     *
     * @internal You should not use this directly from another application
     *
     * @param string $string
     * @return int
     * @throws RangeException
     * @throws TypeError
     */
    public static function load_4(
        #[SensitiveParameter]
        string $string
    ): int {
        /* Input validation: */
        if (self::strlen($string) < 4) {
            throw new RangeException(
                'String must be 4 bytes or more; ' . self::strlen($string) . ' given.'
            );
        }
        /** @var array<int, int> $unpacked */
        $unpacked = unpack('V', $string);
        return $unpacked[1];
    }

    /**
     * Load a 8 character substring into an integer
     *
     * @internal You should not use this directly from another application
     *
     * @param string $string
     * @return int
     *
     * @throws RangeException
     * @throws SodiumException
     * @throws TypeError
     */
    public static function load64_le(
        #[SensitiveParameter]
        string $string
    ): int {
        /* Input validation: */
        if (self::strlen($string) < 4) {
            throw new RangeException(
                'String must be 4 bytes or more; ' . self::strlen($string) . ' given.'
            );
        }
        $unpacked = unpack('P', $string);
        return (int) $unpacked[1];
    }

    /**
     * @internal You should not use this directly from another application
     *
     * @param string $left
     * @param string $right
     * @return int
     * @throws TypeError
     */
    public static function memcmp(
        #[SensitiveParameter]
        string $left,
        #[SensitiveParameter]
        string $right
    ): int {
        $e = (int) !self::hashEquals($left, $right);
        return 0 - $e;
    }

    /**
     * Multiply two integers in constant-time
     *
     * Micro-architecture timing side-channels caused by how your CPU
     * implements multiplication are best prevented by never using the
     * multiplication operators and ensuring that our code always takes
     * the same number of operations to complete, regardless of the values
     * of $a and $b.
     *
     * @internal You should not use this directly from another application
     *
     * @param int $a
     * @param int $b
     * @param int $size Limits the number of operations (useful for small,
     *                  constant operands)
     * @return int
     */
    public static function mul(
        #[SensitiveParameter]
        int $a,
        #[SensitiveParameter]
        int $b,
        int $size = 0
    ): int {
        if (ParagonIE_Sodium_Compat::$fastMult) {
            return (int) ($a * $b);
        }

        static $defaultSize = null;
        if (!$defaultSize) {
            $defaultSize = (PHP_INT_SIZE << 3) - 1;
        }
        if ($size < 1) {
            $size = $defaultSize;
        }
        /** @var int $size */

        $c = 0;

        /**
         * Mask is either -1 or 0.
         *
         * -1 in binary looks like 0x1111 ... 1111
         *  0 in binary looks like 0x0000 ... 0000
         */
        $mask = -(($b >> ($defaultSize)) & 1);

        /**
         * Ensure $b is a positive integer, without creating
         * a branching side-channel
         *
         * @var int $b
         */
        $b = ($b & ~$mask) | ($mask & -$b);

        /**
         * Unless $size is provided:
         *
         * This loop always runs 32 times when PHP_INT_SIZE is 4.
         * This loop always runs 64 times when PHP_INT_SIZE is 8.
         */
        for ($i = $size; $i >= 0; --$i) {
            $c += ($a & -($b & 1));
            $a <<= 1;
            $b >>= 1;
        }
        $c = (int) @($c & -1);

        /**
         * If $b was negative, we then apply the same value to $c here.
         * It doesn't matter much if $a was negative; the $c += above would
         * have produced a negative integer to begin with. But a negative $b
         * makes $b >>= 1 never return 0, so we would end up with incorrect
         * results.
         *
         * The end result is what we'd expect from integer multiplication.
         */
        return (($c & ~$mask) | ($mask & -$c));
    }

    /**
     * Convert any arbitrary numbers into two 32-bit integers that represent
     * a 64-bit integer.
     *
     * @internal You should not use this directly from another application
     *
     * @param int|float $num
     * @return array<int, int>
     * @psalm-suppress RedundantCastGivenDocblockType
     */
    public static function numericTo64BitInteger(int|float $num): array
    {
        $high = 0;
        /** @var int $low */
        if (PHP_INT_SIZE === 4) {
            $low = (int) $num;
        } else {
            $low = $num & 0xffffffff;
        }

        if ((+(abs($num))) >= 1) {
            if ($num > 0) {
                /** @var int $high */
                $high = min((+(floor($num/4294967296))), 4294967295);
            } else {
                $high = ~~((+(ceil(($num - (+((~~($num)))))/4294967296))));
            }
        }
        /**
         * @var int $high
         * @var int $low
         */
        return array((int) $high, (int) $low);
    }

    /**
     * Store a 32-bit integer into a string, treating it as little-endian.
     *
     * @internal You should not use this directly from another application
     *
     * @param int $int
     * @return string
     * @throws TypeError
     */
    public static function store32_le(int $int): string
    {
       return pack('V', $int);
    }

    /**
     * Stores a 64-bit integer as an string, treating it as little-endian.
     *
     * @internal You should not use this directly from another application
     *
     * @param int $int
     * @return string
     * @throws TypeError
     */
    public static function store64_le(int $int): string
    {
        return pack('P', $int);
    }

    /**
     * Safe string length
     *
     * @internal You should not use this directly from another application
     *
     * @param string $str
     * @return int
     * @throws TypeError
     */
    public static function strlen(
        #[SensitiveParameter]
        string $str
    ): int {
        return strlen($str);
    }

    /**
     * Turn a string into an array of integers
     *
     * @internal You should not use this directly from another application
     *
     * @param string $string
     * @return array<int, int>
     * @throws TypeError
     */
    public static function stringToIntArray(string $string): array
    {
        return array_values(
            unpack('C*', $string)
        );
    }

    /**
     * Safe substring
     *
     * @internal You should not use this directly from another application
     *
     * @param string $str
     * @param int $start
     * @param ?int $length
     * @return string
     * @throws TypeError
     */
    public static function substr(
        #[SensitiveParameter]
        string $str,
        int $start = 0,
        ?int $length = null
    ): string {
        if ($length === 0) {
            return '';
        }

        return substr($str, $start, $length);
    }

    /**
     * Compare a 16-character byte string in constant time.
     *
     * @internal You should not use this directly from another application
     *
     * @param string $a
     * @param string $b
     * @return bool
     * @throws TypeError
     */
    public static function verify_16(
        #[SensitiveParameter]
        string $a,
        #[SensitiveParameter]
        string $b
    ): bool {
        return self::hashEquals(
            self::substr($a, 0, 16),
            self::substr($b, 0, 16)
        );
    }

    /**
     * Compare a 32-character byte string in constant time.
     *
     * @internal You should not use this directly from another application
     *
     * @param string $a
     * @param string $b
     * @return bool
     *
     * @throws SodiumException
     * @throws TypeError
     */
    public static function verify_32(
        #[SensitiveParameter]
        string $a,
        #[SensitiveParameter]
        string $b
    ): bool {
        return self::hashEquals(
            self::substr($a, 0, 32),
            self::substr($b, 0, 32)
        );
    }

    /**
     * Calculate $a ^ $b for two strings.
     *
     * @internal You should not use this directly from another application
     *
     * @param string $a
     * @param string $b
     * @return string
     * @throws TypeError
     */
    public static function xorStrings(
        #[SensitiveParameter]
        string $a,
        #[SensitiveParameter]
        string $b
    ): string {
        return $a ^ $b;
    }

}
