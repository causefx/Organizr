<?php

namespace Clue\StreamFilter;

use RuntimeException;

/**
 * append a callback filter to the given stream
 *
 * @param resource $stream
 * @param callable $callback
 * @param int $read_write
 * @return resource filter resource which can be used for `remove()`
 * @throws Exception on error
 * @uses stream_filter_append()
 */
function append($stream, $callback, $read_write = STREAM_FILTER_ALL)
{
    $ret = @stream_filter_append($stream, register(), $read_write, $callback);

    if ($ret === false) {
        $error = error_get_last() + array('message' => '');
        throw new RuntimeException('Unable to append filter: ' . $error['message']);
    }

    return $ret;
}

/**
 * prepend a callback filter to the given stream
 *
 * @param resource $stream
 * @param callable $callback
 * @param int $read_write
 * @return resource filter resource which can be used for `remove()`
 * @throws Exception on error
 * @uses stream_filter_prepend()
 */
function prepend($stream, $callback, $read_write = STREAM_FILTER_ALL)
{
    $ret = @stream_filter_prepend($stream, register(), $read_write, $callback);

    if ($ret === false) {
        $error = error_get_last() + array('message' => '');
        throw new RuntimeException('Unable to prepend filter: ' . $error['message']);
    }

    return $ret;
}

/**
 * Creates filter fun (function) which uses the given built-in $filter
 *
 * @param string $filter built-in filter name, see stream_get_filters()
 * @param mixed  $params additional parameters to pass to the built-in filter
 * @return callable a filter callback which can be append()'ed or prepend()'ed
 * @throws RuntimeException on error
 * @see stream_get_filters()
 * @see append()
 */
function fun($filter, $params = null)
{
    $fp = fopen('php://memory', 'w');
    $filter = @stream_filter_append($fp, $filter, STREAM_FILTER_WRITE, $params);

    if ($filter === false) {
        fclose($fp);
        $error = error_get_last() + array('message' => '');
        throw new RuntimeException('Unable to access built-in filter: ' . $error['message']);
    }

    // append filter function which buffers internally
    $buffer = '';
    append($fp, function ($chunk) use (&$buffer) {
        $buffer .= $chunk;

        // always return empty string in order to skip actually writing to stream resource
        return '';
    }, STREAM_FILTER_WRITE);

    $closed = false;

    return function ($chunk = null) use ($fp, $filter, &$buffer, &$closed) {
        if ($closed) {
            throw new \RuntimeException('Unable to perform operation on closed stream');
        }
        if ($chunk === null) {
            $closed = true;
            $buffer = '';
            fclose($fp);
            return $buffer;
        }
        // initialize buffer and invoke filters by attempting to write to stream
        $buffer = '';
        fwrite($fp, $chunk);

        // buffer now contains everything the filter function returned
        return $buffer;
    };
}

/**
 * remove a callback filter from the given stream
 *
 * @param resource $filter
 * @return boolean true on success or false on error
 * @throws Exception on error
 * @uses stream_filter_remove()
 */
function remove($filter)
{
    if (@stream_filter_remove($filter) === false) {
        throw new RuntimeException('Unable to remove given filter');
    }
}

/**
 * registers the callback filter and returns the resulting filter name
 *
 * There should be little reason to call this function manually.
 *
 * @return string filter name
 * @uses CallbackFilter
 */
function register()
{
    static $registered = null;
    if ($registered === null) {
        $registered = 'stream-callback';
        stream_filter_register($registered, __NAMESPACE__ . '\CallbackFilter');
    }
    return $registered;
}
