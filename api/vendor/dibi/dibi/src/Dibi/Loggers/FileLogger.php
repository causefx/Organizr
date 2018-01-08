<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Loggers;

use Dibi;


/**
 * dibi file logger.
 */
class FileLogger
{
	use Dibi\Strict;

	/** @var string  Name of the file where SQL errors should be logged */
	public $file;

	/** @var int */
	public $filter;


	public function __construct($file, $filter = null)
	{
		$this->file = $file;
		$this->filter = $filter ? (int) $filter : Dibi\Event::QUERY;
	}


	/**
	 * After event notification.
	 * @return void
	 */
	public function logEvent(Dibi\Event $event)
	{
		if (($event->type & $this->filter) === 0) {
			return;
		}

		$handle = fopen($this->file, 'a');
		if (!$handle) {
			return; // or throw exception?
		}
		flock($handle, LOCK_EX);

		if ($event->result instanceof \Exception) {
			$message = $event->result->getMessage();
			if ($code = $event->result->getCode()) {
				$message = "[$code] $message";
			}
			fwrite($handle,
				"ERROR: $message"
				. "\n-- SQL: " . $event->sql
				. "\n-- driver: " . $event->connection->getConfig('driver') . '/' . $event->connection->getConfig('name')
				. ";\n-- " . date('Y-m-d H:i:s')
				. "\n\n"
			);
		} else {
			fwrite($handle,
				'OK: ' . $event->sql
				. ($event->count ? ";\n-- rows: " . $event->count : '')
				. "\n-- takes: " . sprintf('%0.3f ms', $event->time * 1000)
				. "\n-- source: " . implode(':', $event->source)
				. "\n-- driver: " . $event->connection->getConfig('driver') . '/' . $event->connection->getConfig('name')
				. "\n-- " . date('Y-m-d H:i:s')
				. "\n\n"
			);
		}
		fclose($handle);
	}
}
