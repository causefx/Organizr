<?php

use Monolog\Handler\SlackWebhookHandler;
use Nekonomokochan\PhpJsonLogger\Logger;
use Nekonomokochan\PhpJsonLogger\LoggerBuilder;

class OrganizrLogger extends LoggerBuilder
{
	public $isReady;
	/**
	 * @var SlackWEbhookHandler
	 */
	private $slackWebhookHandler;

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
			$this->setChannel('Organizr');
			$this->setLogLevel(self::DEBUG);
			$this->setMaxFiles(1);
		}
		return new Logger($this);
	}

	/**
	 * @return SlackWebhookHandler
	 */
	public function getSlackWebhookHandler(): ?SlackWebhookHandler
	{
		return $this->slackWebhookHandler;
	}

	/**
	 * @param SlackWebhookHandler $slackWebhookHandler
	 */
	public function setSlackWebhookHandler(SlackWebhookHandler $slackWebhookHandler)
	{
		$this->slackWebhookHandler = $slackWebhookHandler;
	}
}