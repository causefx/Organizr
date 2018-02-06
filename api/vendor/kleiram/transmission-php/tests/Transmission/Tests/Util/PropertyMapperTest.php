<?php
namespace Transmission\Tests\Util;

use Transmission\Util\PropertyMapper;

class PropertyMapperTest extends \PHPUnit_Framework_TestCase
{
    protected $mapper;

    /**
     * @test
     */
    public function shouldMapSourcesToModelWithMethodCall()
    {
        $source = (object) array(
            'foo' => 'this',
            'bar' => 'that',
            'ba' => 'thus',
            'unused' => false
        );

        $model = new \Transmission\Mock\Model();

        $this->getMapper()->map($model, $source);

        $this->assertEquals('this', $model->getFo());
        $this->assertEquals('that', $model->getBar());
        $this->assertNull($model->getUnused());
    }

    public function setup()
    {
        $this->mapper = new PropertyMapper();
    }

    private function getMapper()
    {
        return $this->mapper;
    }
}
