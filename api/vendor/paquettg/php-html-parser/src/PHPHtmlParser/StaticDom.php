<?php

declare(strict_types=1);

namespace PHPHtmlParser;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\NotLoadedException;
use PHPHtmlParser\Exceptions\StrictException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Class StaticDom.
 */
final class StaticDom
{
    private static $dom = null;

    /**
     * Attempts to call the given method on the most recent created dom
     * from bellow.
     *
     * @throws NotLoadedException
     *
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments)
    {
        if (self::$dom instanceof Dom) {
            return \call_user_func_array([self::$dom, $method], $arguments);
        }
        throw new NotLoadedException('The dom is not loaded. Can not call a dom method.');
    }

    /**
     * Call this to mount the static facade. The facade allows you to use
     * this object as a $className.
     *
     * @param ?Dom $dom
     */
    public static function mount(string $className = 'Dom', ?Dom $dom = null): bool
    {
        if (\class_exists($className)) {
            return false;
        }
        \class_alias(__CLASS__, $className);
        if ($dom instanceof Dom) {
            self::$dom = $dom;
        }

        return true;
    }

    /**
     * Creates a new dom object and calls loadFromFile() on the
     * new object.
     *
     * @throws ChildNotFoundException
     * @throws CircularException
     * @throws StrictException
     * @throws Exceptions\LogicalException
     */
    public static function loadFromFile(string $file, ?Options $options = null): Dom
    {
        $dom = new Dom();
        self::$dom = $dom;

        return $dom->loadFromFile($file, $options);
    }

    /**
     * Creates a new dom object and calls loadFromUrl() on the
     * new object.
     *
     * @throws ChildNotFoundException
     * @throws CircularException
     * @throws StrictException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public static function loadFromUrl(string $url, ?Options $options = null, ClientInterface $client = null, RequestInterface $request = null): Dom
    {
        $dom = new Dom();
        self::$dom = $dom;

        if (\is_null($client)) {
            $client = new Client();
        }
        if (\is_null($request)) {
            $request = new Request('GET', $url);
        }

        return $dom->loadFromUrl($url, $options, $client, $request);
    }

    public static function loadStr(string $str, ?Options $options = null): Dom
    {
        $dom = new Dom();
        self::$dom = $dom;

        return $dom->loadStr($str, $options);
    }

    /**
     * Sets the $dom variable to null.
     */
    public static function unload(): void
    {
        self::$dom = null;
    }
}
