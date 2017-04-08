<?php

use Clue\StreamFilter as Filter;

class FunTest extends PHPUnit_Framework_TestCase
{
    public function testFunInRot13()
    {
        $rot = Filter\fun('string.rot13');

        $this->assertEquals('grfg', $rot('test'));
        $this->assertEquals('test', $rot($rot('test')));
        $this->assertEquals(null, $rot());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testFunWriteAfterCloseRot13()
    {
        $rot = Filter\fun('string.rot13');

        $this->assertEquals(null, $rot());
        $rot('test');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testFunInvalid()
    {
        Filter\fun('unknown');
    }
}
