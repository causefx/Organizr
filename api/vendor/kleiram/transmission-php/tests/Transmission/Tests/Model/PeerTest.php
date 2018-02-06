<?php
namespace Transmission\Tests\Model;

use Transmission\Model\Peer;
use Transmission\Util\PropertyMapper;

class PeerTest extends \PHPUnit_Framework_TestCase
{
    protected $peer;

    /**
     * @test
     */
    public function shouldImplementModelInterface()
    {
        $this->assertInstanceOf('Transmission\Model\ModelInterface', $this->getPeer());
    }

    /**
     * @test
     */
    public function shouldHaveNonEmptyMapping()
    {
        $this->assertNotEmpty($this->getPeer()->getMapping());
    }

    /**
     * @test
     */
    public function shouldBeCreatedFromMapping()
    {
        $source = (object) array(
            'address' => 'foo',
            'clientName' => 'foo',
            'clientIsChoked' => false,
            'clientIsInterested' => true,
            'flagStr' => 'foo',
            'isDownloadingFrom' => false,
            'isEncrypted' => true,
            'isIncoming' => false,
            'isUploadingTo' => true,
            'isUTP' => false,
            'peerIsChoked' => true,
            'peerIsInterested' => false,
            'port' => 3000,
            'progress' => 10.5,
            'rateToClient' => 1000,
            'rateFromClient' => 10000
        );

        PropertyMapper::map($this->getPeer(), $source);

        $this->assertEquals('foo', $this->getPeer()->getAddress());
        $this->assertEquals('foo', $this->getPeer()->getClientName());
        $this->assertFalse($this->getPeer()->isClientChoked());
        $this->assertTrue($this->getPeer()->isClientInterested());
        $this->assertFalse($this->getPeer()->isDownloading());
        $this->assertTrue($this->getPeer()->isEncrypted());
        $this->assertFalse($this->getPeer()->isIncoming());
        $this->assertTrue($this->getPeer()->isUploading());
        $this->assertFalse($this->getPeer()->isUtp());
        $this->assertTrue($this->getPeer()->isPeerChoked());
        $this->assertFalse($this->getPeer()->isPeerInterested());
        $this->assertEquals(3000, $this->getPeer()->getPort());
        $this->assertEquals(10.5, $this->getPeer()->getProgress());
        $this->assertEquals(1000, $this->getPeer()->getUploadRate());
        $this->assertEquals(10000, $this->getPeer()->getDownloadRate());
    }

    public function setup()
    {
        $this->peer = new Peer();
    }

    public function getPeer()
    {
        return $this->peer;
    }
}
