<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi;

use InvalidArgumentException;
use Symfony\Component\Finder\Finder;

/**
 * Convenient utility functions that don't neatly fit anywhere else.
 */
class Util
{
    /**
     * Turns the given $fullPath into a relative path based on $basePaths, which can either
     * be a single string path, or a list of possible paths. If a list is given, the first
     * matching basePath in the list will be used to compute the relative path. If no
     * relative path could be computed, the original string will be returned because there
     * is always a chance it was a valid relative path to begin with.
     *
     * It should be noted that these are "relative paths" primarily in Finder's sense of them,
     * and conform specifically to what is expected by functions like `exclude()` and `notPath()`.
     * In particular, leading and trailing slashes are removed.
     *
     * @param array|string $basePaths
     */
    public static function getRelativePath(string $fullPath, $basePaths): string
    {
        $relativePath = null;
        if (is_string($basePaths)) { // just a single path, not an array of possible paths
            $relativePath = self::removePrefix($fullPath, $basePaths);
        } else { // an array of paths
            foreach ($basePaths as $basePath) {
                $relativePath = self::removePrefix($fullPath, $basePath);
                if (!empty($relativePath)) {
                    break;
                }
            }
        }

        return !empty($relativePath) ? trim($relativePath, '/') : $fullPath;
    }

    /**
     * Removes a prefix from the start of a string if it exists, or null otherwise.
     */
    private static function removePrefix(string $str, string $prefix): ?string
    {
        if (substr($str, 0, strlen($prefix)) == $prefix) {
            return substr($str, strlen($prefix));
        }

        return null;
    }

    /**
     * Build a Symfony Finder object that scans the given $directory.
     *
     * @param array|Finder|string $directory The directory(s) or filename(s)
     * @param null|array|string   $exclude   The directory(s) or filename(s) to exclude (as absolute or relative paths)
     * @param null|string         $pattern   The pattern of the files to scan
     *
     * @throws InvalidArgumentException
     */
    public static function finder($directory, $exclude = null, $pattern = null): Finder
    {
        if ($directory instanceof Finder) {
            // Make sure that the provided Finder only finds files and follows symbolic links.
            return $directory->files()->followLinks();
        } else {
            $finder = new Finder();
            $finder->sortByName();
        }
        if ($pattern === null) {
            $pattern = '*.php';
        }

        $finder->files()->followLinks()->name($pattern);
        if (is_string($directory)) {
            if (is_file($directory)) { // Scan a single file?
                $finder->append([$directory]);
            } else { // Scan a directory
                $finder->in($directory);
            }
        } elseif (is_array($directory)) {
            foreach ($directory as $path) {
                if (is_file($path)) { // Scan a file?
                    $finder->append([$path]);
                } else {
                    $finder->in($path);
                }
            }
        } else {
            throw new InvalidArgumentException('Unexpected $directory value:' . gettype($directory));
        }
        if ($exclude !== null) {
            if (is_string($exclude)) {
                $finder->notPath(Util::getRelativePath($exclude, $directory));
            } elseif (is_array($exclude)) {
                foreach ($exclude as $path) {
                    $finder->notPath(Util::getRelativePath($path, $directory));
                }
            } else {
                throw new InvalidArgumentException('Unexpected $exclude value:' . gettype($exclude));
            }
        }

        return $finder;
    }

    /**
     * Escapes the special characters "/" and "~".
     *
     * https://swagger.io/docs/specification/using-ref/
     * https://tools.ietf.org/html/rfc6901#page-3
     */
    public static function refEncode(string $raw): string
    {
        return str_replace('/', '~1', str_replace('~', '~0', $raw));
    }

    /**
     * Converted the escaped characters "~1" and "~" back to "/" and "~".
     *
     * https://swagger.io/docs/specification/using-ref/
     * https://tools.ietf.org/html/rfc6901#page-3
     */
    public static function refDecode(string $encoded): string
    {
        return str_replace('~1', '/', str_replace('~0', '~', $encoded));
    }

    /**
     * Shorten class name(s).
     *
     * @param array|object|string $classes Class(es) to shorten
     *
     * @return string|string[] One or more shortened class names
     */
    public static function shorten($classes)
    {
        $short = [];
        foreach ((array) $classes as $class) {
            $short[] = '@' . str_replace('OpenApi\\Annotations\\', 'OA\\', $class);
        }

        return is_array($classes) ? $short : array_pop($short);
    }
}
