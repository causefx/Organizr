<?php
/**
 * TLDSupport: Support package for TLDDatabase and TLDExtract.
 *
 * @link      https://github.com/layershifter/TLDSupport
 *
 * @copyright Copyright (c) 2016, Alexander Fedyashov
 * @license   https://raw.githubusercontent.com/layershifter/TLDSupport/master/LICENSE Apache 2.0 License
 */

namespace LayerShifter\TLDSupport\Tests\Helpers;

use LayerShifter\TLDSupport\Helpers\Str;

/**
 * Test cases for Helpers\Str class.
 */
class StrTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test for endsWith() method.
     *
     * @return void
     */
    public function testEndsWith()
    {
        self::assertTrue(Str::endsWith('jason', 'on'));
        self::assertTrue(Str::endsWith('jason', 'jason'));
        self::assertTrue(Str::endsWith('jason', ['on']));
        self::assertTrue(Str::endsWith('jason', ['no', 'on']));
        self::assertFalse(Str::endsWith('jason', 'no'));
        self::assertFalse(Str::endsWith('jason', ['no']));
        self::assertFalse(Str::endsWith('jason', ''));
        self::assertFalse(Str::endsWith('7', ' 7'));
    }

    /**
     * Test for length() method.
     *
     * @return void
     */
    public function testLength()
    {
        self::assertEquals(11, Str::length('foo bar baz'));
    }

    /**
     * Test for lower() method.
     *
     * @return void
     */
    public function testLower()
    {
        self::assertEquals('foo bar baz', Str::lower('FOO BAR BAZ'));
        self::assertEquals('foo bar baz', Str::lower('fOo Bar bAz'));
    }

    /**
     * Test for substr() method.
     *
     * @return void
     */
    public function testSubstr()
    {
        self::assertEquals('Ё', Str::substr('БГДЖИЛЁ', -1));
        self::assertEquals('ЛЁ', Str::substr('БГДЖИЛЁ', -2));
        self::assertEquals('И', Str::substr('БГДЖИЛЁ', -3, 1));
        self::assertEquals('ДЖИЛ', Str::substr('БГДЖИЛЁ', 2, -1));
        self::assertEmpty(Str::substr('БГДЖИЛЁ', 4, -4));
        self::assertEquals('ИЛ', Str::substr('БГДЖИЛЁ', -3, -1));
        self::assertEquals('ГДЖИЛЁ', Str::substr('БГДЖИЛЁ', 1));
        self::assertEquals('ГДЖ', Str::substr('БГДЖИЛЁ', 1, 3));
        self::assertEquals('БГДЖ', Str::substr('БГДЖИЛЁ', 0, 4));
        self::assertEquals('Ё', Str::substr('БГДЖИЛЁ', -1, 1));
        self::assertEmpty(Str::substr('Б', 2));
    }

    /**
     * Test for startsWith() method.
     *
     * @return void
     */
    public function testStartsWith()
    {
        self::assertTrue(Str::startsWith('jason', 'jas'));
        self::assertTrue(Str::startsWith('jason', 'jason'));
        self::assertTrue(Str::startsWith('jason', ['jas']));
        self::assertTrue(Str::startsWith('jason', ['day', 'jas']));
        self::assertFalse(Str::startsWith('jason', 'day'));
        self::assertFalse(Str::startsWith('jason', ['day']));
        self::assertFalse(Str::startsWith('jason', ''));
    }

    /**
     * Test for strpos() method.
     *
     * @return void
     */
    public function testStrPos()
    {
        self::assertEquals(6, Str::strpos('БГДЖИЛЁ', 'Ё'));
        self::assertEquals(0, Str::strpos('БГДЖИЛЁ', 'Б'));
        self::assertEquals(0, Str::strpos('ЁБГДЖИЛЁ', 'Ё'));
        self::assertEquals(2, Str::strpos('БГДЖИЛЁД', 'Д'));
        self::assertFalse(Str::strpos('БГДЖИЛЁ', 'П'));
    }

    /**
     * Test for strrpos() method.
     *
     * @return void
     */
    public function testStrRPos()
    {
        self::assertEquals(6, Str::strrpos('БГДЖИЛЁ', 'Ё'));
        self::assertEquals(0, Str::strrpos('БГДЖИЛЁ', 'Б'));
        self::assertEquals(7, Str::strrpos('ЁБГДЖИЛЁ', 'Ё'));
        self::assertEquals(7, Str::strrpos('БГДЖИЛЁД', 'Д'));
        self::assertFalse(Str::strrpos('БГДЖИЛЁ', 'П'));
    }
}
