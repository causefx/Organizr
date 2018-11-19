<?php

namespace LayerShifter\TLDSupport\Helpers;

/**
 * Helper class for work with strings.
 * Taken from Illuminate/Support package.
 *
 * @see https://github.com/illuminate/support
 */
class Str
{

    /**
     * @const string Encoding for strings.
     */
    const ENCODING = 'UTF-8';

    /**
     * Determine if a given string ends with a given substring.
     *
     * @param string       $haystack
     * @param string|array $needles
     *
     * @return bool
     */
    public static function endsWith($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ((string)$needle === self::substr($haystack, -self::length($needle))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the length of the given string.
     *
     * @param string $value
     *
     * @return int
     */
    public static function length($value)
    {
        return mb_strlen($value, self::ENCODING);
    }

    /**
     * Convert the given string to lower-case.
     *
     * @param string $value
     *
     * @return string
     */
    public static function lower($value)
    {
        return mb_strtolower($value, self::ENCODING);
    }

    /**
     * Returns the portion of string specified by the start and length parameters.
     *
     * @param string   $string
     * @param int      $start
     * @param int|null $length
     *
     * @return string
     */
    public static function substr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length, self::ENCODING);
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param string       $haystack
     * @param string|array $needles
     *
     * @return boolean
     */
    public static function startsWith($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find position of first occurrence of string in a string.
     *
     * @param string $haystack
     * @param string $needle
     * @param int    $offset
     *
     * @return bool|int
     */
    public static function strpos($haystack, $needle, $offset = 0)
    {
        return mb_strpos($haystack, $needle, $offset, self::ENCODING);
    }

    /**
     * Find position of last occurrence of a string in a string.
     *
     * @param string $haystack
     * @param string $needle
     * @param int    $offset
     *
     * @return bool|int
     */
    public static function strrpos($haystack, $needle, $offset = 0)
    {
        return mb_strrpos($haystack, $needle, $offset, self::ENCODING);
    }
}
