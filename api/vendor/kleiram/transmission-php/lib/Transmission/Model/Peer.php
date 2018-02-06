<?php
namespace Transmission\Model;

/**
 * @author Ramon Kleiss <ramon@cubilon.nl>
 */
class Peer extends AbstractModel
{
    /**
     * @var string
     */
    protected $address;

    /**
     * @var integer
     */
    protected $port;

    /**
     * @var string
     */
    protected $clientName;

    /**
     * @var boolean
     */
    protected $clientChoked;

    /**
     * @var boolean
     */
    protected $clientInterested;

    /**
     * @var boolean
     */
    protected $downloading;

    /**
     * @var boolean
     */
    protected $encrypted;

    /**
     * @var boolean
     */
    protected $incoming;

    /**
     * @var boolean
     */
    protected $uploading;

    /**
     * @var boolean
     */
    protected $utp;

    /**
     * @var boolean
     */
    protected $peerChoked;

    /**
     * @var boolean
     */
    protected $peerInterested;

    /**
     * @var double
     */
    protected $progress;

    /**
     * @var integer
     */
    protected $uploadRate;

    /**
     * @var integer
     */
    protected $downloadRate;

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = (string) $address;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param integer $port
     */
    public function setPort($port)
    {
        $this->port = (integer) $port;
    }

    /**
     * @return integer
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param string $clientName
     */
    public function setClientName($clientName)
    {
        $this->clientName = (string) $clientName;
    }

    /**
     * @return string
     */
    public function getClientName()
    {
        return $this->clientName;
    }

    /**
     * @param boolean $choked
     */
    public function setClientChoked($choked)
    {
        $this->clientChoked = (boolean) $choked;
    }

    /**
     * @return boolean
     */
    public function isClientChoked()
    {
        return $this->clientChoked;
    }

    /**
     * @param boolean $interested
     */
    public function setClientInterested($interested)
    {
        $this->clientInterested = (boolean) $interested;
    }

    /**
     * @return boolean
     */
    public function isClientInterested()
    {
        return $this->clientInterested;
    }

    /**
     * @param boolean $downloading
     */
    public function setDownloading($downloading)
    {
        $this->downloading = (boolean) $downloading;
    }

    /**
     * @return boolean
     */
    public function isDownloading()
    {
        return $this->downloading;
    }

    /**
     * @param boolean $encrypted
     */
    public function setEncrypted($encrypted)
    {
        $this->encrypted = (boolean) $encrypted;
    }

    /**
     * @return boolean
     */
    public function isEncrypted()
    {
        return $this->encrypted;
    }

    /**
     * @param boolean $incoming
     */
    public function setIncoming($incoming)
    {
        $this->incoming = (boolean) $incoming;
    }

    /**
     * @return boolean
     */
    public function isIncoming()
    {
        return $this->incoming;
    }

    /**
     * @param boolean $uploading
     */
    public function setUploading($uploading)
    {
        $this->uploading = (boolean) $uploading;
    }

    /**
     * @return boolean
     */
    public function isUploading()
    {
        return $this->uploading;
    }

    /**
     * @param boolean $utp
     */
    public function setUtp($utp)
    {
        $this->utp = (boolean) $utp;
    }

    /**
     * @return boolean
     */
    public function isUtp()
    {
        return $this->utp;
    }

    /**
     * @param boolean $choked
     */
    public function setPeerChoked($choked)
    {
        $this->peerChoked = (boolean) $choked;
    }

    /**
     * @return boolean
     */
    public function isPeerChoked()
    {
        return $this->peerChoked;
    }

    /**
     * @param boolean $interested
     */
    public function setPeerInterested($interested)
    {
        $this->peerInterested = (boolean) $interested;
    }

    /**
     * @return boolean
     */
    public function isPeerInterested()
    {
        return $this->peerInterested;
    }

    /**
     * @param double $progress
     */
    public function setProgress($progress)
    {
        $this->progress = (double) $progress;
    }

    /**
     * @return double
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * @param integer $rate
     */
    public function setUploadRate($rate)
    {
        $this->uploadRate = (integer) $rate;
    }

    /**
     * @return integer
     */
    public function getUploadRate()
    {
        return $this->uploadRate;
    }

    /**
     * @param integer $rate
     */
    public function setDownloadRate($rate)
    {
        $this->downloadRate = (integer) $rate;
    }

    /**
     * @return integer
     */
    public function getDownloadRate()
    {
        return $this->downloadRate;
    }

    /**
     * {@inheritDoc}
     */
    public static function getMapping()
    {
        return array(
            'address' => 'address',
            'port' => 'port',
            'clientName' => 'clientName',
            'clientIsChoked' => 'clientChoked',
            'clientIsInterested' => 'clientInterested',
            'isDownloadingFrom' => 'downloading',
            'isEncrypted' => 'encrypted',
            'isIncoming' => 'incoming',
            'isUploadingTo' => 'uploading',
            'isUTP' => 'utp',
            'peerIsChoked' => 'peerChoked',
            'peerIsInterested' => 'peerInterested',
            'progress' => 'progress',
            'rateToClient' => 'uploadRate',
            'rateFromClient' => 'downloadRate'
        );
    }
}
