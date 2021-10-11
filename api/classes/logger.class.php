<?php

use Nekonomokochan\PhpJsonLogger\Logger;
use Nekonomokochan\PhpJsonLogger\LoggerBuilder;

class OrganizrLogger extends LoggerBuilder
{
	public $isReady;
	
	/**
	 * @return string
	 */
	public function getReadyStatus(): bool
	{
		return $this->isReady;
	}
	
	/**
	 * @param string $traceId
	 */
	public function setReadyStatus(bool $readyStatus)
	{
		$this->isReady = $readyStatus;
	}
	
	public function build(): Logger
	{
		if (!$this->isReady) {
			$this->setChannel(self::DEFAULT_CHANNEL);
			$this->setLogLevel(self::INFO);
			$this->setFileName('/tmp/organizr-temp.log');
			$this->setMaxFiles(1);
		}
		return new Logger($this);
	}
}