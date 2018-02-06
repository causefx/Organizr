<?php
namespace Transmission\Tests\Model;

use Transmission\Model\Tracker;
use Transmission\Util\PropertyMapper;

class TrackerTest extends \PHPUnit_Framework_TestCase
{
    protected $tracker;

    /**
     * @test
     */
    public function shouldImplementModelInterface()
    {
        $this->assertInstanceOf('Transmission\Model\ModelInterface', $this->getTracker());
    }

    /**
     * @test
     */
    public function shouldHaveNonEmptyMapping()
    {
        $this->assertNotEmpty($this->getTracker()->getMapping());
    }

    /**
     * @test
     */
    public function shouldBeCreatedFromMapping()
    {
        $source = (object) array(
            'id' => 1,
            'tier' => 1,
            'scrape' => 'foo',
            'announce' => 'bar'
        );

        PropertyMapper::map($this->getTracker(), $source);

        $this->assertEquals(1, $this->getTracker()->getId());
        $this->assertEquals(1, $this->getTracker()->getTier());
        $this->assertEquals('foo', $this->getTracker()->getScrape());
        $this->assertEquals('bar', $this->getTracker()->getAnnounce());
    }

    public function setup()
    {
        $this->tracker = new Tracker();
    }

    private function getTracker()
    {
        return $this->tracker;
    }
}
