<?php

trait LogFunctions
{
	public function logLocation()
	{
		return isset($this->config['logLocation']) && $this->config['logLocation'] !== '' ? $this->config['logLocation'] : $this->config['dbLocation'] . 'logs' . DIRECTORY_SEPARATOR;
	}

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
			$this->makeDir($this->logLocation());
			$logPath = $this->logLocation();
			return $logPath . 'organizr.log';
		}
		return false;
	}

	public function readLog($file, $pageSize = 10, $offset = 0, $filter = 'NONE', $trace_id = null)
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
							$lines = array_merge($lines, iterator_to_array($lineGenerator));
						}
					}
				}
			} else {
				$lineGenerator = Bcremer\LineReader\LineReader::readLinesBackwards($file);
				$lines = iterator_to_array($lineGenerator);
			}
			if ($filter || $trace_id) {
				$results = [];
				foreach ($lines as $line) {
					if ($filter) {
						if (stripos($line, '"' . $filter . '"') !== false) {
							$results[] = $line;
						}
					} elseif ($trace_id) {
						if (stripos($line, '"' . $trace_id . '"') !== false) {
							$results = $line;
						}
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
		if (is_array($lines)) {
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
		} else {
			return json_decode($lines, true);
		}
	}

	public function getLatestLogFile()
	{
		if ($this->log) {
			if (isset($this->log)) {
				$folder = $this->logLocation();
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
				$folder = $this->logLocation();
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
			if ($username) {
				$username = $this->sanitizeUserString($username);
			}
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
				return $this->setupLogger($channel, $username);
			} else {
				return $this->logger;
			}
		}
	}

	public function setupLogger($channel = 'Organizr', $username = null)
	{
		if (!$username) {
			$username = $this->user['username'] ?? 'System';
		}
		$loggerBuilder = new OrganizrLogger();
		$loggerBuilder->setReadyStatus($this->hasDB() && $this->log);
		$loggerBuilder->setMaxFiles($this->config['maxLogFiles']);
		$loggerBuilder->setFileName($this->tempLogIfNeeded());
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
			return $this->logger;
		} catch (Exception $e) {
			// nothing so far
			$this->logger = null;
			return $this->logger;
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

	public function tempLogIfNeeded()
	{
		if (!$this->log) {
			return $this->root . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'organizr-' . $this->randString() . '.log';
		} else {
			return $this->log;
		}
	}

	public function getLog($pageSize = 10, $offset = 0, $filter = 'NONE', $number = 0, $trace_id = null)
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
				$readLog = $this->readLog($log, $pageSize, $offset, $filter, $trace_id);
				$msg = ($trace_id) ? 'Results for trace_id: ' . $trace_id : 'Results for log: ' . $log;
				$this->setResponse(200, $msg, $readLog);
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
				if ($number == 'all' || $number == 'combined-logs') {
					$this->logger->debug('Cannot delete log [all] as it is not a real log');
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
			$this->logger->debug('Checking if log exists');
			if (file_exists($file)) {
				$this->logger->debug('Log: ' . $log . ' does exist');
				$this->logger->debug('Attempting to purge log: ' . $log);
				if (unlink($file)) {
					$this->logger->info('Log: ' . $log . ' has been purged/deleted');
					$this->setResponse(200, 'Log purged');
					return true;
				} else {
					$this->logger->warning('Log: ' . $log . ' could not be purged/deleted');
					$this->setResponse(500, 'Log could not be purged');
					return false;
				}
			} else {
				$this->logger->debug('Log does not exist');
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
					$options .= '<option data-id="' . $k . '" value="api/v2/log/' . $k . '?filter=NONE&pageSize=1000&offset=0" ' . $selected . '>' . $name[0] . '</option>';
					$i++;
				}
				return '<select class="form-control choose-organizr-log"><option data-id="all" value="api/v2/log/all?filter=NONE&pageSize=1000&offset=0">All</option>' . $options . '</select>';
			}
		}
		return false;
	}

	function buildFilterDropdown()
	{
		$dropdownItems = '<li><a href="javascript:toggleLogFilter(\'DEBUG\')"><span lang="en">Debug</span></a></li>';
		$dropdownItems .= '<li><a href="javascript:toggleLogFilter(\'INFO\')"><span lang="en">Info</span></a></li>';
		$dropdownItems .= '<li><a href="javascript:toggleLogFilter(\'NOTICE\')"><span lang="en">Notice</span></a></li>';
		$dropdownItems .= '<li><a href="javascript:toggleLogFilter(\'WARNING\')"><span lang="en">Warning</span></a></li>';
		$dropdownItems .= '<li><a href="javascript:toggleLogFilter(\'ERROR\')"><span lang="en">Error</span></a></li>';
		$dropdownItems .= '<li><a href="javascript:toggleLogFilter(\'CRITICAL\')"><span lang="en">Critical</span></a></li>';
		$dropdownItems .= '<li><a href="javascript:toggleLogFilter(\'ALERT\')"><span lang="en">Alert</span></a></li>';
		$dropdownItems .= '<li><a href="javascript:toggleLogFilter(\'EMERGENCY\')"><span lang="en">Emergency</span></a></li>';
		$dropdownItems .= '<li class="divider"></li><li><a href="javascript:toggleLogFilter(\'NONE\')"><span lang="en">None</span></a></li>';
		return '<button aria-expanded="false" data-toggle="dropdown" class="btn btn-inverse dropdown-toggle waves-effect waves-light pull-right m-r-5 hidden-xs" type="button"> <span class="log-filter-text m-r-5" lang="en">NONE</span><i class="fa fa-filter m-r-5"></i></button><ul role="menu" class="dropdown-menu log-filter-dropdown pull-right">' . $dropdownItems . '</ul>';
	}
}