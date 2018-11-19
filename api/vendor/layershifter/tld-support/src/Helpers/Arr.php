<?php

namespace LayerShifter\TLDSupport\Helpers;

/**
 * Helper class for work with arrays.
 * Taken from Illuminate/Support package.
 *
 * @see https://github.com/illuminate/support
 */
class Arr
{

    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param array         $array    Haystack array
     * @param null|callable $callback Optional callback function
     * @param mixed         $default  Default value if array element not found
     *
     * @return mixed
     */
    public static function first($array, callable $callback = null, $default = null)
    {
        if (null === $callback) {
            return 0 === count($array) ? Mixed::value($default) : reset($array);
        }

        foreach ($array as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return $value;
            }
        }

        return Mixed::value($default);
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param array         $array    Haystack array
     * @param null|callable $callback Optional callback function
     * @param mixed         $default  Default value if array element not found
     *
     * @return mixed
     */
    public static function last($array, callable $callback = null, $default = null)
    {
        if (null === $callback) {
            return 0 === count($array) ? Mixed::value($default) : end($array);
        }

        return static::first(array_reverse($array, true), $callback, $default);
    }
}
