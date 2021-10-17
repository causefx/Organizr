<?php

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
	
	public function build(): OrganizrLoggerExt
	{
		if (!$this->isReady) {
			$this->setChannel('Organizr');
			$this->setLogLevel(self::DEBUG);
			$this->setMaxFiles(1);
		}
		return new OrganizrLoggerExt($this);
	}
}