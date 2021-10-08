<?php

trait LogFunctions
{
	public function info($msg, $username = null)
	{
		$this->writeLog('info', $msg, $username);
	}
	
	public function error($msg, $username = null)
	{
		$this->writeLog('error', $msg, $username);
	}
	
	public function warning($msg, $username = null)
	{
		$this->writeLog('warning', $msg, $username);
	}
	
	public function debug($msg, $username = null)
	{
		$this->writeLog('debug', $msg, $username);
	}
	
	public function setOrganizrLog()
	{
		if ($this->hasDB()) {
			$logPath = $this->config['dbLocation'] . 'logs' . DIRECTORY_SEPARATOR;
			return $logPath . 'organizr.log';
		}
		return false;
	}
	
	public function readLog($file, $pageSize = 10, $offset = 0, $filter = 'NONE')
	{
		if (file_exists($file)) {
			$filter = strtoupper($filter);
			switch ($filter) {
				case 'DEBUG':
				case 'INFO':
				case 'NOTICE':
				case 'WARNING':
				case 'ERROR':
				case 'CRITICAL':
				case 'ALERT':
				case 'EMERGENCY':
					break;
				case 'NONE':
					$filter = null;
					break;
				default:
					$filter = 'DEBUG';
					break;
			}
			$lineGenerator = Bcremer\LineReader\LineReader::readLinesBackwards($file);
			$lines = iterator_to_array($lineGenerator);
			if ($filter) {
				$results = [];
				foreach ($lines as $line) {
					if (stripos($line, '"' . $filter . '"') !== false) {
						$results[] = $line;
					}
				}
				$lines = $results;
			}
			return $this->formatLogResults($lines, $pageSize, $offset);
		}
		return false;
	}
	
	public function formatLogResults($lines, $pageSize, $offset)
	{
		$totalLines = count($lines);
		$totalPages = $totalLines / $pageSize;
		$results = array_slice($lines, $offset, $pageSize);
		$lines = [];
		foreach ($results as $line) {
			$lines[] = json_decode($line, true);
		}
		return [
			'pageInfo' => [
				'results' => $totalLines,
				'totalPages' => ceil($totalPages),
				'pageSize' => $pageSize,
				'page' => $offset >= $totalPages ? -1 : ceil($offset / $pageSize) + 1
			],
			'results' => $lines
		];
	}
	
	public function getLatestLogFile()
	{
		if ($this->log) {
			if (isset($this->log)) {
				$folder = $this->config['dbLocation'] . 'logs' . DIRECTORY_SEPARATOR;
				$directoryIterator = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
				$iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
				$files = [];
				foreach ($iteratorIterator as $info) {
					$files[] = $info->getPathname();
				}
				if (count($files) > 0) {
					usort($files, function ($x, $y) {
						preg_match('/[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/', $x, $xArray);
						preg_match('/[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/', $y, $yArray);
						return strtotime($xArray[0]) < strtotime($yArray[0]);
					});
					if (file_exists($files[0])) {
						return $files[0];
					}
				}
			}
		}
		return false;
	}
	
	public function getLogFiles()
	{
		if ($this->log) {
			if (isset($this->log)) {
				$folder = $this->config['dbLocation'] . 'logs' . DIRECTORY_SEPARATOR;
				$directoryIterator = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
				$iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
				$files = [];
				foreach ($iteratorIterator as $info) {
					$files[] = $info->getPathname();
				}
				if (count($files) > 0) {
					usort($files, function ($x, $y) {
						preg_match('/[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/', $x, $xArray);
						preg_match('/[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/', $y, $yArray);
						return strtotime($xArray[0]) < strtotime($yArray[0]);
					});
					return $files;
				}
			}
		}
		return false;
	}
	
	public function setLoggerChannel($channel = 'Organizr', $username = null)
	{
		$setLogger = false;
		if ($this->logger) {
			if (strtolower($this->logger->getChannel()) !== strtolower($channel)) {
				$setLogger = true;
			}
			if ($username) {
				if (strtolower($this->logger->getTraceId()) !== strtolower($channel)) {
					$setLogger = true;
				}
			}
		} else {
			$setLogger = true;
		}
		if ($setLogger) {
			$this->setupLogger($channel, $username);
		}
		return $this->logger;
	}
	
	public function setupLogger($channel = 'Organizr', $username = null)
	{
		if ($this->log) {
			if (!$username) {
				$username = (isset($this->user['username'])) ? $this->user['username'] : 'System';
			}
			$loggerBuilder = new Nekonomokochan\PhpJsonLogger\LoggerBuilder();
			$loggerBuilder->setMaxFiles($this->config['maxLogFiles']);
			$loggerBuilder->setFileName($this->log);
			$loggerBuilder->setTraceId($username);
			$loggerBuilder->setChannel(ucwords(strtolower($channel)));
			switch ($this->config['logLevel']) {
				case 'DEBUG':
					$logLevel = Nekonomokochan\PhpJsonLogger\LoggerBuilder::DEBUG;
					break;
				case 'INFO':
					$logLevel = Nekonomokochan\PhpJsonLogger\LoggerBuilder::INFO;
					break;
				case 'NOTICE':
					$logLevel = Nekonomokochan\PhpJsonLogger\LoggerBuilder::NOTICE;
					break;
				case 'ERROR':
					$logLevel = Nekonomokochan\PhpJsonLogger\LoggerBuilder::ERROR;
					break;
				case 'CRITICAL':
					$logLevel = Nekonomokochan\PhpJsonLogger\LoggerBuilder::CRITICAL;
					break;
				case 'ALERT':
					$logLevel = Nekonomokochan\PhpJsonLogger\LoggerBuilder::ALERT;
					break;
				case 'EMERGENCY':
					$logLevel = Nekonomokochan\PhpJsonLogger\LoggerBuilder::EMERGENCY;
					break;
				default:
					$logLevel = Nekonomokochan\PhpJsonLogger\LoggerBuilder::WARNING;
					break;
			}
			$loggerBuilder->setLogLevel($logLevel);
			try {
				$this->logger = $loggerBuilder->build();
			} catch (Exception $e) {
				// nothing so far
			}
			/* setup:
			set the log channel before you send log (You can set an optional Username (2nd Variable) | If user is logged already logged in, it will use their username):
			$this->setLoggerChannel('Plex Homepage');
			normal log:
			$this->logger->info('test');
			normal log with context ($context must be an array):
			$this->logger->info('test', $context);
			exception:
			$this->logger->critical($exception, $context);
			*/
		}
	}
	
	public function getLog($pageSize = 10, $offset = 0, $filter = 'NONE', $number = 0)
	{
		if ($this->log) {
			if (isset($this->log)) {
				if ($number !== 0) {
					$logs = $this->getLogFiles();
					$log = $logs[$number] ?? $this->getLatestLogFile();
				} else {
					$log = $this->getLatestLogFile();
				}
				$readLog = $this->readLog($log, 1000, 0, $filter);
				$this->setResponse(200, 'Results for log: ' . $log, $readLog);
				return $readLog;
			} else {
				$this->setResponse(404, 'Log not found');
				return false;
			}
		} else {
			$this->setResponse(409, 'Logging not setup');
			return false;
		}
	}
	
	public function purgeLog($number)
	{
		$this->setLoggerChannel('Logger');
		$this->logger->debug('Starting log purge function');
		if ($this->log) {
			$this->logger->debug('Checking if log id exists');
			if ($number !== 0) {
				$logs = $this->getLogFiles();
				$file = $logs[$number] ?? false;
				if (!$file) {
					$this->setResponse(404, 'Log not found');
					return false;
				}
			} else {
				$file = $this->getLatestLogFile();
			}
			preg_match('/[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/', $file, $log);
			$log = $log[0];
			$this->logger->debug('Checking if log exists');
			if (file_exists($file)) {
				$this->logger->debug('Log: ' . $log . ' does exist');
				$this->logger->debug('Attempting to purge log: ' . $log);
				if (unlink($file)) {
					$this->logger->info('Log: ' . $log . ' has been purged/deleted');
					$this->setAPIResponse(null, 'Log purged');
					return true;
				} else {
					$this->logger->warning('Log: ' . $log . ' could not be purged/deleted');
					$this->setAPIResponse('error', 'Log could not be purged', 500);
					return false;
				}
			} else {
				$this->logger->debug('Log does not exist');
				$this->setAPIResponse('error', 'Log does not exist', 404);
				return false;
			}
		} else {
			$this->setResponse(409, 'Logging not setup');
			return false;
		}
	}
	
	public function logArray($context)
	{
		if (!is_array($context)) {
			if (is_string($context)) {
				return ['data' => $context];
			} else {
				$context = (string)$context;
				return ['data' => $context];
			}
		} else {
			return $context;
		}
	}
	
	function buildLogDropdown()
	{
		$logs = $this->getLogFiles();
		//<select class='form-control settings-dropdown-box system-settings-menu'><option value=''>About</option></select>
		if ($logs) {
			if (count($logs) > 0) {
				$options = '';
				$i = 0;
				foreach ($logs as $k => $log) {
					$selected = $i == 0 ? 'selected' : '';
					preg_match('/[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/', $log, $name);
					$options .= '<option data-id="' . $k . '" value="api/v2/log/' . $k . '" ' . $selected . '>' . $name[0] . '</option>';
					$i++;
				}
				return '<select class="form-control choose-organizr-log">' . $options . '</select>';
			}
		}
		return false;
	}
}