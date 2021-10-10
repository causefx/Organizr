<?php

trait LogFunctions
{
	public function debug($msg, $context = [])
	{
		if ($this->logger) {
			$this->logger->debug($msg, $context);
		}
	}
	
	public function info($msg, $context = [])
	{
		if ($this->logger) {
			$this->logger->info($msg, $context);
		}
	}
	
	public function notice($msg, $context = [])
	{
		if ($this->logger) {
			$this->logger->notice($msg, $context);
		}
	}
	
	public function warning($msg, $context = [])
	{
		if ($this->logger) {
			$this->logger->warning($msg, $context);
		}
	}
	
	public function error($msg, $context = [])
	{
		if ($this->logger) {
			$this->logger->error($msg, $context);
		}
	}
	
	public function critical($msg, $context = [])
	{
		if ($this->logger) {
			$this->logger->critical($msg, $context);
		}
	}
	
	public function alert($msg, $context = [])
	{
		if ($this->logger) {
			$this->logger->alert($msg, $context);
		}
	}
	
	public function emergency($msg, $context = [])
	{
		if ($this->logger) {
			$this->logger->emergency($msg, $context);
		}
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
		$combinedLogs = false;
		if ($file == 'combined-logs') {
			$combinedLogs = true;
		}
		if (file_exists($file) || $combinedLogs) {
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
			if ($combinedLogs) {
				$logs = $this->getLogFiles();
				$lines = [];
				if ($logs) {
					foreach ($logs as $log) {
						if (file_exists($log)) {
							$lineGenerator = Bcremer\LineReader\LineReader::readLinesBackwards($log);
							$lines = array_merge(iterator_to_array($lineGenerator), $lines);
						}
					}
				}
			} else {
				$lineGenerator = Bcremer\LineReader\LineReader::readLinesBackwards($file);
				$lines = iterator_to_array($lineGenerator);
			}
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
		if ($this->hasDB()) {
			$setLogger = false;
			if ($this->logger) {
				if ($channel) {
					if (strtolower($this->logger->getChannel()) !== strtolower($channel)) {
						$setLogger = true;
					}
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
				$channel = $channel ?: 'Organizr';
				$this->setupLogger($channel, $username);
			}
		}
	}
	
	public function setupLogger($channel = 'Organizr', $username = null)
	{
		if ($this->hasDB()) {
			if ($this->log) {
				if (!$username) {
					$username = $this->user['username'] ?? 'System';
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
				$this->info('test');
				normal log with context ($context must be an array):
				$this->info('test', $context);
				exception:
				$this->critical($exception, $context);
				*/
			}
		}
	}
	
	public function getLog($pageSize = 10, $offset = 0, $filter = 'NONE', $number = 0)
	{
		if ($this->log) {
			if (isset($this->log)) {
				if ($number !== 0) {
					if ($number == 'all' || $number == 'combined-logs') {
						$log = 'combined-logs';
					} else {
						$logs = $this->getLogFiles();
						$log = $logs[$number] ?? $this->getLatestLogFile();
					}
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
		$this->debug('Starting log purge function');
		if ($this->log) {
			$this->debug('Checking if log id exists');
			if ($number !== 0) {
				if ($number == 'all' || $number == 'combined-logs') {
					$this->debug('Cannot delete log [all] as it is not a real log');
					$this->setResponse(409, 'Cannot delete log [all] as it is not a real log');
					return false;
				}
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
			$this->debug('Checking if log exists');
			if (file_exists($file)) {
				$this->debug('Log: ' . $log . ' does exist');
				$this->debug('Attempting to purge log: ' . $log);
				if (unlink($file)) {
					$this->info('Log: ' . $log . ' has been purged/deleted');
					$this->setResponse(200, 'Log purged');
					return true;
				} else {
					$this->warning('Log: ' . $log . ' could not be purged/deleted');
					$this->setResponse(500, 'Log could not be purged');
					return false;
				}
			} else {
				$this->debug('Log does not exist');
				$this->setResponse(404, 'Log does not exist');
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
				return '<select class="form-control choose-organizr-log"><option data-id="all" value="api/v2/log/all">All</option>' . $options . '</select>';
			}
		}
		return false;
	}
}