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

use LayerShifter\TLDSupport\Helpers\Arr;

/**
 * Test cases for Helpers\Arr class.
 */
class ArrTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test for first() method.
     *
     * @return void
     */
    public function testFirst()
    {
        self::assertEquals(1, Arr::first([1, 2, 3]));
        self::assertEquals('a', Arr::first(['a', 'b', 'c']));
        self::assertNotEquals('b', Arr::first(['a', 'b', 'c']));

        self::assertEquals(2, Arr::first([1, 2, 3], function ($value) {
            return $value === 2;
        }));
        self::assertEquals(null, Arr::first([1, 2, 3], function ($value) {
            return $value === 20;
        }));

        self::assertInternalType('int', Arr::first([1, 2, 3]));
        self::assertInternalType('string', Arr::first(['a', 'b', 'c']));
    }

    /**
     * Test for last() method.
     *
     * @return void
     */
    public function testLast()
    {
        self::assertEquals(3, Arr::last([1, 2, 3]));
        self::assertEquals('c', Arr::last(['a', 'b', 'c']));
        self::assertNotEquals('b', Arr::last(['a', 'b', 'c']));

        self::assertEquals(2, Arr::last([1, 2, 3], function ($value) {
            return $value === 2;
        }));
        self::assertEquals(null, Arr::last([1, 2, 3], function ($value) {
            return $value === 20;
        }));

        self::assertInternalType('int', Arr::last([1, 2, 3]));
        self::assertInternalType('string', Arr::last(['a', 'b', 'c']));
    }
}
