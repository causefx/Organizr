<?php

use Nekonomokochan\PhpJsonLogger\Logger;
use Nekonomokochan\PhpJsonLogger\LoggerBuilder;

class OrganizrLogger extends LoggerBuilder
{
	public $isReady;
	
	/**
	 * @return boolean
	 */
	public function getReadyStatus(): bool
	{
		return $this->isReady;
	}
	
	/**
	 * @param boolean $readyStatus
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
			$this->setMaxFiles(1);
		}
		return new Logger($this);
	}
}