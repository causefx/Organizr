<?php
namespace Transmission\Tests;

use Transmission\Transmission;

class TransmissionTest extends \PHPUnit_Framework_TestCase
{
    protected $transmission;

    /**
     * @test
     */
    public function shouldHaveDefaultHost()
    {
        $this->assertEquals('localhost', $this->getTransmission()->getClient()->getHost());
    }

    /**
     * @test
     */
    public function shouldGetAllTorrentsInDownloadQueue()
    {
        $client = $this->getMock('Transmission\Client');
        $client->expects($this->once())
            ->method('call')
            ->with('torrent-get')
            ->will($this->returnCallback(function ($method, $arguments) {
                return (object) array(
                    'result' => 'success',
                    'arguments' => (object) array(
                        'torrents' => array(
                            (object) array(),
                            (object) array(),
                            (object) array(),
                        )
                    )
                );
            }));

        $this->getTransmission()->setClient($client);

        $torrents = $this->getTransmission()->all();

        $this->assertCount(3, $torrents);
    }

    /**
     * @test
     */
    public function shouldGetTorrentById()
    {
        $that   = $this;
        $client = $this->getMock('Transmission\Client');
        $client->expects($this->once())
            ->method('call')
            ->with('torrent-get')
            ->will($this->returnCallback(function ($method, $arguments) use ($that) {
                $that->assertEquals(1, $arguments['ids'][0]);

                return (object) array(
                    'result' => 'success',
                    'arguments' => (object) array(
                        'torrents' => array(
                            (object) array()
                        )
                    )
                );
            }));

        $this->getTransmission()->setClient($client);

        $torrent = $this->getTransmission()->get(1);

        $this->assertInstanceOf('Transmission\Model\Torrent', $torrent);
    }

    /**
     * @test
     * @expectedException RuntimeException
     */
    public function shouldThrowExceptionWhenTorrentIsNotFound()
    {
        $client = $this->getMock('Transmission\Client');
        $client->expects($this->once())
            ->method('call')
            ->with('torrent-get')
            ->will($this->returnCallback(function ($method, $arguments) {
                return (object) array(
                    'result' => 'success',
                    'arguments' => (object) array(
                        'torrents' => array()
                    )
                );
            }));

        $this->getTransmission()->setClient($client);
        $this->getTransmission()->get(1);
    }

    /**
     * @test
     */
    public function shouldAddTorrentByFilename()
    {
        $that   = $this;
        $client = $this->getMock('Transmission\Client');
        $client->expects($this->once())
            ->method('call')
            ->with('torrent-add')
            ->will($this->returnCallback(function ($method, $arguments) use ($that) {
                $that->assertArrayHasKey('filename', $arguments);

                return (object) array(
                    'result' => 'success',
                    'arguments' => (object) array(
                        'torrent-added' => (object) array()
                    )
                );
            }));

        $this->getTransmission()->setClient($client);

        $torrent = $this->getTransmission()->add('foo');
        $this->assertInstanceOf('Transmission\Model\Torrent', $torrent);
    }

    /**
     * @test
     */
    public function shouldAddTorrentByMetainfo()
    {
        $that   = $this;
        $client = $this->getMock('Transmission\Client');
        $client->expects($this->once())
            ->method('call')
            ->with('torrent-add')
            ->will($this->returnCallback(function ($method, $arguments) use ($that) {
                $that->assertArrayHasKey('metainfo', $arguments);

                return (object) array(
                    'result' => 'success',
                    'arguments' => (object) array(
                        'torrent-added' => (object) array()
                    )
                );
            }));

        $this->getTransmission()->setClient($client);

        $torrent = $this->getTransmission()->add('foo', true);
        $this->assertInstanceOf('Transmission\Model\Torrent', $torrent);
    }

    /**
     * @test
     */
    public function shouldHandleDuplicateTorrent()
    {
        $that   = $this;
        $client = $this->getMock('Transmission\Client');
        $client->expects($this->once())
            ->method('call')
            ->with('torrent-add')
            ->will($this->returnCallback(function ($method, $arguments) use ($that) {
                $that->assertArrayHasKey('metainfo', $arguments);

                return (object) array(
                    'result' => 'duplicate torrent',
                    'arguments' => (object) array(
                        'torrent-duplicate' => (object) array()
                    )
                );
            }));

        $this->getTransmission()->setClient($client);

        $torrent = $this->getTransmission()->add('foo', true);
        $this->assertInstanceOf('Transmission\Model\Torrent', $torrent);
    }

    /**
     * @test
     */
    public function shouldGetSession()
    {
        $that   = $this;
        $client = $this->getMock('Transmission\Client');
        $client->expects($this->once())
            ->method('call')
            ->with('session-get')
            ->will($this->returnCallback(function ($method, $arguments) use ($that) {
                $that->assertEmpty($arguments);

                return (object) array(
                    'result' => 'success',
                    'arguments' => (object) array()
                );
            }));

        $this->getTransmission()->setClient($client);
        $session = $this->getTransmission()->getSession();

        $this->assertInstanceOf('Transmission\Model\Session', $session);
    }

    /**
     * @test
     */
    public function shouldHaveDefaultPort()
    {
        $this->assertEquals(9091, $this->getTransmission()->getClient()->getPort());
    }

    /**
     * @test
     */
    public function shouldProvideFacadeForClient()
    {
        $client = $this->getMock('Transmission\Client');
        $client->expects($this->once())
            ->method('setHost')
            ->with('example.org');

        $client->expects($this->once())
            ->method('getHost')
            ->will($this->returnValue('example.org'));

        $client->expects($this->once())
            ->method('setPort')
            ->with(80);

        $client->expects($this->once())
            ->method('getPort')
            ->will($this->returnValue(80));

        $this->getTransmission()->setClient($client);
        $this->getTransmission()->setHost('example.org');
        $this->getTransmission()->setPort(80);

        $this->assertEquals('example.org', $this->getTransmission()->getHost());
        $this->assertEquals(80, $this->getTransmission()->getPort());
    }

    public function setup()
    {
        $this->transmission = new Transmission();
    }

    private function getTransmission()
    {
        return $this->transmission;
    }
}
