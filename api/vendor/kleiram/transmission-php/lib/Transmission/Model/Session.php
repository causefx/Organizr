<?php
namespace Transmission\Model;

use Transmission\Util\ResponseValidator;

/**
 * @author Joysen Chellem
 * @author Ramon Kleiss <ramon@cubilon.nl>
 */
class Session extends AbstractModel
{
    /**
     * @var integer
     */
    protected $altSpeedDown;

    /**
     * @var boolean
     */
    protected $altSpeedEnabled;

    /**
     * @var string
     */
    protected $downloadDir;

    /**
     * @var boolean
     */
    protected $downloadQueueEnabled;

    /**
     * @var integer
     */
    protected $downloadQueueSize;

    /**
     * @var string
     */
    protected $incompleteDir;

    /**
     * @var boolean
     */
    protected $incompleteDirEnabled;

    /**
     * @var string
     */
    protected $torrentDoneScript;

    /**
     * @var boolean
     */
    protected $torrentDoneScriptEnabled;

    /**
     * @var double
     */
    protected $seedRatioLimit;

    /**
     * @var boolean
     */
    protected $seedRatioLimited;

    /**
     * @var integer
     */
    protected $seedQueueSize;

    /**
     * @var boolean
     */
    protected $seedQueueEnabled;

    /**
     * @var integer
     */
    protected $downloadSpeedLimit;

    /**
     * @var boolean
     */
    protected $downloadSpeedLimitEnabled;

    /**
     * @param integer $speed
     */
    public function setAltSpeedDown($speed)
    {
        $this->altSpeedDown = (integer) $speed;
    }

    /**
     * @return integer
     */
    public function getAltSpeedDown()
    {
        return $this->altSpeedDown;
    }

    /**
     * @param boolean $enabled
     */
    public function setAltSpeedEnabled($enabled)
    {
        $this->altSpeedEnabled = (boolean) $enabled;
    }

    /**
     * @return boolean
     */
    public function isAltSpeedEnabled()
    {
        return $this->altSpeedEnabled;
    }

    /**
     * @param string $downloadDir
     */
    public function setDownloadDir($downloadDir)
    {
        $this->downloadDir = (string) $downloadDir;
    }

    /**
     * @return string
     */
    public function getDownloadDir()
    {
        return $this->downloadDir;
    }

    /**
     * @param boolean $enabled
     */
    public function setDownloadQueueEnabled($enabled)
    {
        $this->downloadQueueEnabled = (boolean) $enabled;
    }

    /**
     * @return boolean
     */
    public function isDownloadQueueEnabled()
    {
        return $this->downloadQueueEnabled;
    }

    /**
     * @param integer $size
     */
    public function setDownloadQueueSize($size)
    {
        $this->downloadQueueSize = (integer) $size;
    }

    /**
     * @return integer
     */
    public function getDownloadQueueSize()
    {
        return $this->downloadQueueSize;
    }

    /**
     * @param string $directory
     */
    public function setIncompleteDir($directory)
    {
        $this->incompleteDir = (string) $directory;
    }

    /**
     * @return string
     */
    public function getIncompleteDir()
    {
        return $this->incompleteDir;
    }

    /**
     * @param boolean $enabled
     */
    public function setIncompleteDirEnabled($enabled)
    {
        $this->incompleteDirEnabled = (boolean) $enabled;
    }

    /**
     * @return boolean
     */
    public function isIncompleteDirEnabled()
    {
        return $this->incompleteDirEnabled;
    }

    /**
     * @param string $filename
     */
    public function setTorrentDoneScript($filename)
    {
        $this->torrentDoneScript = (string) $filename;
    }

    /**
     * @return string
     */
    public function getTorrentDoneScript()
    {
        return $this->torrentDoneScript;
    }

    /**
     * @param boolean $enabled
     */
    public function setTorrentDoneScriptEnabled($enabled)
    {
        $this->torrentDoneScriptEnabled = (boolean) $enabled;
    }

    /**
     * @return boolean
     */
    public function isTorrentDoneScriptEnabled()
    {
        return $this->torrentDoneScriptEnabled;
    }

    /**
     * @param double $limit
     */
    public function setSeedRatioLimit($limit)
    {
        $this->seedRatioLimit = (double) $limit;
    }

    /**
     * @return double
     */
    public function getSeedRatioLimit()
    {
        return $this->seedRatioLimit;
    }

    /**
     * @param boolean $limited
     */
    public function setSeedRatioLimited($limited)
    {
        $this->seedRatioLimited = (boolean) $limited;
    }

    /**
     * @return boolean
     */
    public function isSeedRatioLimited()
    {
        return $this->seedRatioLimited;
    }

    /**
     * @param integer $size
     */
    public function setSeedQueueSize($size)
    {
        $this->seedQueueSize = (integer) $size;
    }

    /**
     * @return integer
     */
    public function getSeedQueueSize()
    {
        return $this->seedQueueSize;
    }

    /**
     * @param boolean $enabled
     */
    public function setSeedQueueEnabled($enabled)
    {
        $this->seedQueueEnabled = (boolean) $enabled;
    }

    /**
     * @return boolean
     */
    public function isSeedQueueEnabled()
    {
        return $this->seedQueueEnabled;
    }

    /**
     * @param integer $limit
     */
    public function setDownloadSpeedLimit($limit)
    {
        $this->downloadSpeedLimit = (integer) $limit;
    }

    /**
     * @return integer
     */
    public function getDownloadSpeedLimit()
    {
        return $this->downloadSpeedLimit;
    }

    /**
     * @param boolean $enabled
     */
    public function setDownloadSpeedLimitEnabled($enabled)
    {
        $this->downloadSpeedLimitEnabled = (boolean) $enabled;
    }

    /**
     * @return boolean
     */
    public function isDownloadSpeedLimitEnabled()
    {
        return $this->downloadSpeedLimitEnabled;
    }

    /**
     * {@inheritDoc}
     */
    public static function getMapping()
    {
        return array(
            'alt-speed-down' => 'altSpeedDown',
            'alt-speed-enabled' => 'altSpeedEnabled',
            'download-dir' => 'downloadDir',
            'download-queue-enabled' => 'downloadQueueEnabled',
            'download-queue-size' => 'downloadQueueSize',
            'incomplete-dir' => 'incompleteDir',
            'incomplete-dir-enabled' => 'incompleteDirEnabled',
            'script-torrent-done-filename' => 'torrentDoneScript',
            'script-torrent-done-enabled' => 'torrentDoneScriptEnabled',
            'seedRatioLimit' => 'seedRatioLimit',
            'seedRatioLimited' => 'seedRatioLimited',
            'seed-queue-size' => 'seedQueueSize',
            'seed-queue-enabled' => 'seedQueueEnabled',
            'speed-limit-down' => 'downloadSpeedLimit',
            'speed-limit-down-enabled' => 'downloadSpeedLimitEnabled',
        );
    }

    public function save()
    {
        $arguments = array();
        $method    = 'session-set';

        foreach ($this->getMapping() as $key => $value) {
            $arguments[$key] = $this->$value;
        }

        if (!($client = $this->getClient())) {
            return;
        }

        ResponseValidator::validate(
            $method,
            $client->call($method, $arguments)
        );
    }
}
