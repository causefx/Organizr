<?php

namespace LayerShifter\TLDSupport\Helpers;

/**
 * Helper class with mixed functions.
 * Taken from Illuminate/Support package.
 *
 * @see https://github.com/illuminate/support
 */
class Mixed
{

    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function value($value)
    {
        return $value instanceof \Closure ? $value() : $value;
    }
}
