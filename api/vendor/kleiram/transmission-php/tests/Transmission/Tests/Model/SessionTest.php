<?php
namespace Transmission\Tests\Model;

use Transmission\Model\Session;
use Transmission\Util\PropertyMapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SessionTest extends \PHPUnit_Framework_TestCase
{
    protected $session;

    /**
     * @test
     */
    public function shouldImplementModelInterface()
    {
        $this->assertInstanceOf('Transmission\Model\ModelInterface', $this->getSession());
    }

    /**
     * @test
     */
    public function shouldHaveNonEmptyMapping()
    {
        $this->assertNotEmpty($this->getSession()->getMapping());
    }

    /** 
     * @test
     */
    public function shouldBeCreatedFromMapping()
    {
        $source = (object) array(
            'alt-speed-down' => 1,
            'alt-speed-enabled' => true,
            'download-dir' => 'foo',
            'download-queue-enabled' => true,
            'download-queue-size' => 5,
            'incomplete-dir' => 'bar',
            'incomplete-dir-enabled' => true,
            'script-torrent-done-filename' => 'baz',
            'script-torrent-done-enabled' => true,
            'seedRatioLimit' => 3.14,
            'seedRatioLimited' => true,
            'seed-queue-size' => 5,
            'seed-queue-enabled' => true,
            'speed-limit-down' => 100,
            'speed-limit-down-enabled' => true,
        );

        PropertyMapper::map($this->getSession(), $source);

        $this->assertEquals(1, $this->getSession()->getAltSpeedDown());
        $this->assertTrue($this->getSession()->isAltSpeedEnabled());
        $this->assertEquals('foo', $this->getSession()->getDownloadDir());
        $this->assertEquals(5, $this->getSession()->getDownloadQueueSize());
        $this->assertTrue($this->getSession()->isDownloadQueueEnabled());
        $this->assertEquals('bar', $this->getSession()->getIncompleteDir());
        $this->assertTrue($this->getSession()->isIncompleteDirEnabled());
        $this->assertEquals('baz', $this->getSession()->getTorrentDoneScript());
        $this->assertTrue($this->getSession()->isTorrentDoneScriptEnabled());
        $this->assertEquals(3.14, $this->getSession()->getSeedRatioLimit());
        $this->assertTrue($this->getSession()->isSeedRatioLimited());
        $this->assertEquals(5, $this->getSession()->getSeedQueueSize());
        $this->assertTrue($this->getSession()->isSeedQueueEnabled());
        $this->assertEquals(100, $this->getSession()->getDownloadSpeedLimit());
        $this->assertTrue($this->getSession()->isDownloadSpeedLimitEnabled());
    }

    /**
     * @test
     */
    public function shouldSave()
    {
        $expected = array(
            'alt-speed-down' => 1,
            'alt-speed-enabled' => true,
            'download-dir' => 'foo',
            'download-queue-enabled' => true,
            'download-queue-size' => 5,
            'incomplete-dir' => 'bar',
            'incomplete-dir-enabled' => true,
            'script-torrent-done-filename' => 'baz',
            'script-torrent-done-enabled' => true,
            'seedRatioLimit' => 3.14,
            'seedRatioLimited' => true,
            'seed-queue-size' => 5,
            'seed-queue-enabled' => true,
            'speed-limit-down' => 100,
            'speed-limit-down-enabled' => true
        );

        PropertyMapper::map($this->getSession(), (object) $expected);

        $client = $this->getMock('Transmission\Client');
        $client->expects($this->once())
            ->method('call')
            ->with('session-set', $expected)
            ->will($this->returnCallback(function () {
                return (object) array(
                    'result' => 'success',
                );
            }));

        $this->getSession()->setClient($client);
        $this->getSession()->save();
    }

    /**
     * @test
     */
    public function shouldNotSaveWithNoClient()
    {
        $this->getSession()->save();
    }

    public function setup()
    {
        $this->session = new Session();
    }

    protected function getSession()
    {
        return $this->session;
    }
}
