<?php

declare(strict_types=1);

namespace Bcremer\LineReader;

final class LineReader
{
    /**
     * Prevent instantiation
     */
    private function __construct()
    {
    }

    /**
     * @return \Generator<int, string, void, void>
     *
     * @throws \InvalidArgumentException if $filePath is not readable
     */
    public static function readLines(string $filePath): \Generator
    {
        if (!$fh = @fopen($filePath, 'r')) {
            throw new \InvalidArgumentException('Cannot open file for reading: ' . $filePath);
        }

        return self::read($fh);
    }

    /**
     * @return \Generator<int, string, void, void>
     *
     * @throws \InvalidArgumentException if $filePath is not readable
     */
    public static function readLinesBackwards(string $filePath): \Generator
    {
        if (!$fh = @fopen($filePath, 'r')) {
            throw new \InvalidArgumentException('Cannot open file for reading: ' . $filePath);
        }

        $size = filesize($filePath);
        if (!\is_int($size)) {
            throw new \RuntimeException('Could not get file size');
        }

        return self::readBackwards($fh, $size);
    }

    /**
     * @param resource $fh
     */
    private static function read($fh): \Generator
    {
        while (false !== $line = fgets($fh)) {
            yield rtrim($line, "\n");
        }

        fclose($fh);
    }

    /**
     * Read a file from the end using a buffer.
     *
     * This is way more efficient than using the naive method
     * of reading the file backwards byte by byte looking for
     * a newline character.
     *
     * @see http://stackoverflow.com/a/10494801/147634
     *
     * @param resource $fh
     *
     * @return \Generator<int, string, void, void>
     */
    private static function readBackwards($fh, int $pos): \Generator
    {
        $buffer = null;
        $bufferSize = 4096;

        if ($pos === 0) {
            return;
        }

        while (true) {
            if (isset($buffer[1])) { // faster than count($buffer) > 1
                yield array_pop($buffer);
                continue;
            }

            if ($pos === 0 && \is_array($buffer)) {
                yield array_pop($buffer);
                break;
            }

            if ($bufferSize > $pos) {
                $bufferSize = $pos;
                $pos = 0;
            } else {
                $pos -= $bufferSize;
            }
            fseek($fh, $pos);
            if ($bufferSize < 0) {
                throw new \RuntimeException('Buffer size cannot be negative');
            }
            $chunk = fread($fh, $bufferSize);
            if (!\is_string($chunk)) {
                throw new \RuntimeException('Could not read file');
            }
            if ($buffer === null) {
                // remove single trailing newline, rtrim cannot be used here
                if (substr($chunk, -1) === "\n") {
                    $chunk = substr($chunk, 0, -1);
                }
                $buffer = explode("\n", $chunk);
            } else {
                $buffer = explode("\n", $chunk . $buffer[0]);
            }
        }
    }
}
