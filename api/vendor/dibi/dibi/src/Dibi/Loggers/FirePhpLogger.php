<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Loggers;

use Dibi;


/**
 * dibi FirePHP logger.
 */
class FirePhpLogger
{
	use Dibi\Strict;

	/** maximum number of rows */
	public static $maxQueries = 30;

	/** maximum SQL length */
	public static $maxLength = 1000;

	/** size of json stream chunk */
	public static $streamChunkSize = 4990;

	/** @var int */
	public $filter;

	/** @var int  Elapsed time for all queries */
	public $totalTime = 0;

	/** @var int  Number of all queries */
	public $numOfQueries = 0;

	/** @var array */
	private static $fireTable = [['Time', 'SQL Statement', 'Rows', 'Connection']];


	/**
	 * @return bool
	 */
	public static function isAvailable()
	{
		return isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'FirePHP/');
	}


	public function __construct($filter = null)
	{
		$this->filter = $filter ? (int) $filter : Dibi\Event::QUERY;
	}


	/**
	 * After event notification.
	 * @return void
	 */
	public function logEvent(Dibi\Event $event)
	{
		if (headers_sent() || ($event->type & $this->filter) === 0 || count(self::$fireTable) > self::$maxQueries) {
			return;
		}

		if (!$this->numOfQueries) {
			header('X-Wf-Protocol-dibi: http://meta.wildfirehq.org/Protocol/JsonStream/0.2');
			header('X-Wf-dibi-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.2.0');
			header('X-Wf-dibi-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');
		}
		$this->totalTime += $event->time;
		$this->numOfQueries++;
		self::$fireTable[] = [
			sprintf('%0.3f', $event->time * 1000),
			strlen($event->sql) > self::$maxLength ? substr($event->sql, 0, self::$maxLength) . '...' : $event->sql,
			$event->result instanceof \Exception ? 'ERROR' : (string) $event->count,
			$event->connection->getConfig('driver') . '/' . $event->connection->getConfig('name'),
		];

		$payload = json_encode([
			[
				'Type' => 'TABLE',
				'Label' => 'dibi profiler (' . $this->numOfQueries . ' SQL queries took ' . sprintf('%0.3f', $this->totalTime * 1000) . ' ms)',
			],
			self::$fireTable,
		]);
		foreach (str_split($payload, self::$streamChunkSize) as $num => $s) {
			$num++;
			header("X-Wf-dibi-1-1-d$num: |$s|\\"); // protocol-, structure-, plugin-, message-index
		}
		header("X-Wf-dibi-1-1-d$num: |$s|");
	}
}
