<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Loggers;

use Dibi;


/**
 * Dibi file logger.
 */
class FileLogger
{
	use Dibi\Strict;

	/** @var string  Name of the file where SQL errors should be logged */
	public $file;

	/** @var int */
	public $filter;

	/** @var bool */
	private $errorsOnly;


	public function __construct(string $file, ?int $filter = null, bool $errorsOnly = false)
	{
		$this->file = $file;
		$this->filter = $filter ?: Dibi\Event::QUERY;
		$this->errorsOnly = $errorsOnly;
	}


	/**
	 * After event notification.
	 */
	public function logEvent(Dibi\Event $event): void
	{
		if (
			(($event->type & $this->filter) === 0)
			|| ($this->errorsOnly === true && !$event->result instanceof \Exception)
		) {
			return;
		}

		if ($event->result instanceof \Exception) {
			$message = $event->result->getMessage();
			if ($code = $event->result->getCode()) {
				$message = "[$code] $message";
			}

			$this->writeToFile(
				$event,
				"ERROR: $message"
					. "\n-- SQL: " . $event->sql
			);
		} else {
			$this->writeToFile(
				$event,
				'OK: ' . $event->sql
					. ($event->count ? ";\n-- rows: " . $event->count : '')
					. "\n-- takes: " . sprintf('%0.3f ms', $event->time * 1000)
					. "\n-- source: " . implode(':', $event->source)
			);
		}
	}


	private function writeToFile(Dibi\Event $event, string $message): void
	{
		$driver = $event->connection->getConfig('driver');
		$message .=
			"\n-- driver: " . (is_object($driver) ? get_class($driver) : $driver) . '/' . $event->connection->getConfig('name')
			. "\n-- " . date('Y-m-d H:i:s')
			. "\n\n";
		file_put_contents($this->file, $message, FILE_APPEND | LOCK_EX);
	}
}
