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

use LayerShifter\TLDSupport\Helpers\IP;

/**
 * Test cases for Helpers\IP class.
 */
class IPTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test for isValid() method.
     *
     * @return void
     */
    public function testIsValid()
    {
        // IPv4 test cases.

        self::assertTrue(IP::isValid('200.200.200.200'));
        self::assertTrue(IP::isValid(' 200.200.200.200'));
        self::assertTrue(IP::isValid('200.200.200.200 '));
        self::assertTrue(IP::isValid('0.0.0.0'));
        self::assertTrue(IP::isValid('255.255.255.255'));

        self::assertFalse(IP::isValid('00.00.00.00'));
        self::assertFalse(IP::isValid('100.100.020.100'));
        self::assertFalse(IP::isValid('-1.0.0.0'));
        self::assertFalse(IP::isValid('200.200.256.200'));
        self::assertFalse(IP::isValid('200.200.200.200.'));
        self::assertFalse(IP::isValid('200.200.200'));
        self::assertFalse(IP::isValid('200.200.200.2d0'));
        self::assertFalse(IP::isValid('200000000000000000000000000000000000000000000000000000.200.200.200'));

        // IPv6 test cases.

        self::assertTrue(IP::isValid('00AB:0002:3008:8CFD:00AB:0002:3008:8CFD'));
        self::assertTrue(IP::isValid('00ab:0002:3008:8cfd:00ab:0002:3008:8cfd'));
        self::assertTrue(IP::isValid('00aB:0002:3008:8cFd:00Ab:0002:3008:8cfD'));
        self::assertTrue(IP::isValid('AB:02:3008:8CFD:AB:02:3008:8CFD'));
        self::assertTrue(IP::isValid('AB:02:3008:8CFD::02:3008:8CFD'));
        self::assertTrue(IP::isValid('::'));
        self::assertTrue(IP::isValid('0::'));
        self::assertTrue(IP::isValid('0::0'));

        self::assertFalse(IP::isValid('00AB:00002:3008:8CFD:00AB:0002:3008:8CFD'));
        self::assertFalse(IP::isValid(':0002:3008:8CFD:00AB:0002:3008:8CFD'));
        self::assertFalse(IP::isValid('00AB:0002:3008:8CFD:00AB:0002:3008:'));
        self::assertFalse(IP::isValid('AB:02:3008:8CFD:AB:02:3008:8CFD:02'));
        self::assertFalse(IP::isValid('AB:02:3008:8CFD::02:3008:8CFD:02'));
        self::assertFalse(IP::isValid('AB:02:3008:8CFD::02::8CFD'));
        self::assertFalse(IP::isValid('GB:02:3008:8CFD:AB:02:3008:8CFD'));
        self::assertFalse(IP::isValid('00000000000005.10.10.10'));
        self::assertFalse(IP::isValid('2:::3'));

        self::assertTrue(IP::isValid('[AB:02:3008:8CFD::02:3008:8CFD]'));
        self::assertTrue(IP::isValid('[::]'));
        self::assertTrue(IP::isValid('[::1]'));

        self::assertFalse(IP::isValid('[AB:02:3008:8CFD::02:3008:8CFD'));
        self::assertFalse(IP::isValid('::]'));
        self::assertFalse(IP::isValid('/[::1]'));

        // Domain test cases.

        self::assertFalse(IP::isValid('google.com'));
        self::assertFalse(IP::isValid('.google.com'));
        self::assertFalse(IP::isValid('www.google.com'));
        self::assertFalse(IP::isValid('com'));
    }
}
