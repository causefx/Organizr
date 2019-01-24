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

use LayerShifter\TLDSupport\Helpers\Mixed;

/**
 * Test cases for Helpers\Mixed class.
 */
class MixedTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test for value() method.
     *
     * @return void
     */
    public function testValue()
    {
        self::assertEquals(1, Mixed::value(1));
        self::assertInternalType('int', Mixed::value(1));

        self::assertEquals(2, Mixed::value(function () {
            return 2;
        }));
        self::assertInternalType('int', Mixed::value(function () {
            return 2;
        }));
    }
}
